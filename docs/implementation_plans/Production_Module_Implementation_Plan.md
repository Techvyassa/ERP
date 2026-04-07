# Production Module — Implementation Plan

**Date:** 2026-03-30
**Module:** Production (Manufacturing Execution)
**Status:** Phase 1 (BOM + MIR + Production Order) — Partially Built. Phases 2–5 — Planned.

---

## 1. Current State Review

### 1.1 What Is Built

| Component | Tables | Status |
|---|---|---|
| BOM Header | `bom_header` | Complete — versioned, effective dates, DRAFT/ACTIVE/OBSOLETE |
| BOM Detail | `bom_detail` | Complete — scrap_percent, effective_qty (generated column), substitute material, critical flag |
| Production Order | `production_orders` | Partial — DRAFT/IN_PROGRESS/COMPLETED/CANCELLED, no execution fields |
| Material Issue Request | `material_issue_requests` | Complete — PENDING/APPROVED/REJECTED, approval + rejection flow |
| MIR Line Items | `mir_line_items` | Complete — HHT barcode scan (bin + material), stock deduction via StockService |
| Stock Ledger | `inventory_transactions` | Complete — immutable, all transaction types defined |
| Stock Balance | `stock_balances` | Complete — materialized cache, bucket-based, bin-level |
| Inbound QC | `inspection_lots`, `inspection_results`, `usage_decisions` | Complete — tied to GRN lines only |

### 1.2 Known Bugs / Gaps in Current Code

| # | Gap | Location | Impact |
|---|---|---|---|
| G1 | `uom_id` is hardcoded `null` on MIR line creation | `ProductionOrderController::store()` | MIR lines have no UOM — display and stock deduction broken |
| G2 | No soft-allocation (reservation) when MIR is created | `ProductionOrderController::store()` | Another order can consume the same RM before scanning |
| G3 | `batch_number` is `null` in MIR scan stock post | `MaterialIssueRequestController::scan()` | No RM-to-FG batch traceability |
| G4 | No FG Confirmation endpoint | `ProductionOrderController` | Production order can never reach COMPLETED; FG never enters stock |
| G5 | No `PRODUCTION_RECEIPT` stock posting | `StockService` (enum exists, never called) | Finished goods never hit AVAILABLE bucket |
| G6 | No yield / variance fields on production order | `production_orders` table | No actual vs target tracking, no scrap/rework split |
| G7 | No floor stock / deficit check on MIR generation | `ProductionOrderController::store()` | MIR always requests full BOM qty even if floor already has stock |
| G8 | No backorder tracking on partial MIR approval | `MaterialIssueRequest` | Production team has no visibility into what is still missing |
| G9 | No FG QC path | `inspection_lots` | Existing QC is inbound-only (GRN-tied); no QC for finished goods |
| G10 | No packing module | — | No carton/label/hierarchy tables or endpoints |

---

## 2. System Flow (End-to-End)

```
[BOM Master] ──────────────────────────────────────────────────────────────┐
                                                                            ▼
[Production Planner]                                              [Production Order Created]
  POST /production-orders                                          status = DRAFT
  (product_id, bom_id, target_qty, planned_date)                  order_no = PRD-XXXXX
        │                                                                   │
        │  Auto-generates MIR                                               │
        ▼                                                                   ▼
[Material Issue Request]                                    [Soft-Allocate RM in stock_balances]
  status = PENDING                                           StockService::reserve() per BOM line
  mir_no = MIR-XXXXX                                        (deficit check: required - floor_stock)
        │
        ▼
[Store Team — Approve / Reject MIR]
  POST /material-issue-requests/{id}/approve
  POST /material-issue-requests/{id}/reject
        │
        ▼ (APPROVED)
[HHT Operator — Scan RM Lines]
  POST /material-issue-requests/{id}/lines/{lineId}/scan
  (bin_barcode + material_barcode)
  → Validates bin, material, stock availability
  → StockService::post(MATERIAL_ISSUE, AVAILABLE, -qty)
  → Captures batch_number from stock_balances
        │
        ▼ (all lines ISSUED)
[Production Order → IN_PROGRESS]
  POST /production-orders/{id}/start
  records actual_start_at
        │
        ▼
[Production Execution on Floor]
        │
        ▼
[FG Confirmation]
  POST /production-orders/{id}/confirm-fg
  (actual_qty, rejected_qty, rework_qty, fg_bin_id, fg_batch_number)
  → StockService::post(PRODUCTION_RECEIPT, QC_HOLD or AVAILABLE, +actual_qty, product_id)
  → Backflush: compare actual RM consumed vs BOM expected
  → Calculate yield_percent = (actual_qty / target_qty) * 100
  → If product has QC params → auto-create InspectionLot (source_type = PRODUCTION)
  → status = COMPLETED
        │
        ├──[FG QC Required]──────────────────────────────────────────────────┐
        │                                                                     ▼
        │                                                         [FG Inspection Lot]
        │                                                          QC technician records results
        │                                                          QC manager makes decision
        │                                                          ACCEPTED → FG moves QC_HOLD → AVAILABLE
        │                                                          REJECTED → FG moves to BLOCKED
        │
        ▼ (FG in AVAILABLE)
[Packing]
  POST /packing-orders
  POST /packing-orders/{id}/cartons          ← open carton
  POST /packing-orders/{id}/cartons/{id}/scan ← scan FG unit into carton
  POST /packing-orders/{id}/cartons/{id}/seal ← seal + label
  POST /packing-orders/{id}/complete
```

---

## 3. Table Structures

### 3.1 Existing Tables (Reference)

#### `production_orders`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_no | varchar(30) unique | PRD-XXXXX |
| product_id | FK → product_master | |
| bom_id | FK → bom_header | |
| target_qty | decimal(12,3) | |
| planned_date | date | |
| status | enum | DRAFT, IN_PROGRESS, COMPLETED, CANCELLED |
| created_by | FK → users | |

#### `material_issue_requests`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| mir_no | varchar(30) unique | MIR-XXXXX |
| production_order_id | FK → production_orders | |
| status | enum | PENDING, APPROVED, REJECTED |
| remarks | text nullable | |
| rejection_reason | text nullable | |
| approved_by | FK → users nullable | |
| approved_at | timestamp nullable | |

#### `mir_line_items`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| mir_id | FK → material_issue_requests | |
| material_id | FK → material_master | |
| required_qty | decimal(12,3) | |
| uom_id | FK → uom_master nullable | BUG: currently always null — fix in Phase 1 |
| bin_barcode | varchar(100) nullable | Scanned bin code |
| material_barcode | varchar(100) nullable | Scanned material code |
| scan_status | enum | PENDING, SCANNED, ISSUED |
| bin_id | FK → bin_locations nullable | Resolved on scan |
| warehouse_id | FK → warehouse_master nullable | Resolved on scan |
| scanned_at | timestamp nullable | |

#### `bom_header`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| bom_code | varchar(30) unique | BOM-FG001-V2 |
| product_id | FK → product_master | |
| version | smallint | 1, 2, 3... |
| effective_from | date | |
| effective_to | date nullable | NULL = currently active |
| bom_status | varchar(15) | DRAFT, ACTIVE, OBSOLETE |
| batch_size | decimal(12,3) | Output qty per batch |
| output_uom_id | FK → uom_master | |
| remarks | text nullable | |
| created_by | FK → users nullable | |
| approved_by | FK → users nullable | |

#### `bom_detail`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| bom_id | FK → bom_header | |
| material_id | FK → material_master | |
| qty_required | decimal(12,4) | Per batch_size |
| uom_id | FK → uom_master | |
| scrap_percent | decimal(5,2) default 0 | Process loss % |
| effective_qty | decimal(12,4) GENERATED | qty_required × (1 + scrap%/100) |
| substitute_material_id | FK → material_master nullable | |
| is_critical | boolean default false | No substitute if true |
| line_no | smallint | Sort order |
| remarks | varchar(200) nullable | |

---

### 3.2 New Tables — Phase 2 (Execution Fields)

**Migration:** `2026_03_28_000001_add_execution_fields_to_production_orders.php`

Alter `production_orders` — add columns:

| Column | Type | Notes |
|---|---|---|
| actual_start_at | timestamp nullable | Set on /start |
| actual_end_at | timestamp nullable | Set on /confirm-fg |
| actual_qty | decimal(12,3) nullable | FG confirmed quantity |
| rejected_qty | decimal(12,3) default 0 | Scrap — written off |
| rework_qty | decimal(12,3) default 0 | Reworkable rejects |
| yield_percent | decimal(5,2) nullable | (actual_qty / target_qty) × 100 |
| fg_bin_id | FK → bin_locations nullable | Where FG was placed |
| fg_warehouse_id | FK → warehouse_master nullable | |
| fg_batch_number | varchar(50) nullable | FG batch/lot number |
| confirmed_by | FK → users nullable | |
| confirmed_at | timestamp nullable | |

---

### 3.3 New Tables — Phase 4 (FG QC)

**Migration:** `2026_03_28_000002_add_fg_qc_support_to_inspection_lots.php`

Alter `inspection_lots` — add columns:

| Column | Type | Notes |
|---|---|---|
| production_order_id | FK → production_orders nullable | Set for FG QC lots |
| source_type | enum('GRN','PRODUCTION') default 'GRN' | Distinguishes inbound vs FG QC |

Make `grn_id` nullable (currently NOT NULL — needs constraint change).

---

### 3.4 New Tables — Phase 5 (Packing)

**Migration:** `2026_03_28_000003_create_packing_tables.php`

#### `packing_orders`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| packing_order_no | varchar(30) unique | PKG-XXXXX |
| production_order_id | FK → production_orders | |
| status | enum | PENDING, IN_PROGRESS, COMPLETED |
| created_by | FK → users nullable | |
| timestamps | | |

#### `cartons`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| carton_barcode | varchar(100) unique | System-generated or scanned |
| packing_order_id | FK → packing_orders | |
| carton_type | enum | INNER, OUTER, PALLET |
| parent_carton_id | FK → cartons nullable | Self-referential for nesting |
| status | enum | OPEN, SEALED, LABELLED, DISPATCHED |
| calculated_weight | decimal(8,3) nullable | From product master × qty |
| actual_weight | decimal(8,3) nullable | From scale integration |
| sealed_at | timestamp nullable | |
| labelled_at | timestamp nullable | |
| timestamps | | |

#### `carton_items`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| carton_id | FK → cartons | |
| product_id | FK → product_master | |
| product_barcode | varchar(100) | Scanned unit barcode |
| qty | decimal(12,3) | |
| uom_id | FK → uom_master | |
| batch_number | varchar(50) nullable | Traceability |
| scanned_at | timestamp | |
| scanned_by | FK → users nullable | |

---

## 4. API Endpoints

### 4.1 Existing Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/production-orders` | List all orders (filter by status, search) |
| POST | `/api/v1/production-orders` | Create order + auto-generate MIR |
| GET | `/api/v1/production-orders/{id}` | Get order with MIR and lines |
| GET | `/api/v1/material-issue-requests` | List MIRs |
| GET | `/api/v1/material-issue-requests/{id}` | Get MIR with lines |
| POST | `/api/v1/material-issue-requests/{id}/approve` | Store approves MIR |
| POST | `/api/v1/material-issue-requests/{id}/reject` | Store rejects MIR with reason |
| POST | `/api/v1/material-issue-requests/{id}/lines/{lineId}/scan` | HHT scan bin + material |
| GET/POST/PUT/DELETE | `/api/v1/bom-headers` | BOM header CRUD |
| GET/POST/PUT/DELETE | `/api/v1/bom-details` | BOM detail CRUD |

### 4.2 New Endpoints — Phase 2

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/production-orders/{id}/start` | Start production, record actual_start_at |
| POST | `/api/v1/production-orders/{id}/confirm-fg` | Confirm FG qty, post PRODUCTION_RECEIPT stock |
| GET | `/api/v1/production-orders/{id}/variance` | Yield report: target vs actual, RM consumed vs BOM |

**`POST /confirm-fg` Request Body:**
```json
{
  "actual_qty": 95,
  "rejected_qty": 3,
  "rework_qty": 2,
  "fg_bin_id": 12,
  "fg_batch_number": "FG-2526-001",
  "tenant_db_name": "tenant_xyz"
}
```

**`GET /variance` Response:**
```json
{
  "order_no": "PRD-00001",
  "target_qty": 100,
  "actual_qty": 95,
  "rejected_qty": 3,
  "rework_qty": 2,
  "yield_percent": 95.00,
  "rm_lines": [
    {
      "material_name": "Steel Rod",
      "bom_required": 200,
      "bom_effective": 204,
      "actually_consumed": 201,
      "variance": -3
    }
  ]
}
```

### 4.3 New Endpoints — Phase 5 (Packing)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/packing-orders` | List packing orders |
| POST | `/api/v1/packing-orders` | Create packing order from production order |
| GET | `/api/v1/packing-orders/{id}` | Get packing order with cartons |
| POST | `/api/v1/packing-orders/{id}/cartons` | Open a new carton |
| POST | `/api/v1/packing-orders/{id}/cartons/{cartonId}/scan` | Scan product unit into carton |
| POST | `/api/v1/packing-orders/{id}/cartons/{cartonId}/seal` | Seal carton + generate label |
| POST | `/api/v1/packing-orders/{id}/complete` | Complete packing order |

---

## 5. Implementation Phases

### Phase 1 — Bug Fixes (No new migrations)

**Priority: High — unblocks current functionality**

- [ ] Fix `uom_id = null` in `ProductionOrderController::store()` — resolve from BOM detail line
- [ ] Add `StockService::reserve()` call per MIR line on production order creation (soft-allocation)
- [ ] Reverse reservation on MIR REJECTED or when scan issues the stock
- [ ] Capture `batch_number` from `stock_balances` during MIR scan and pass to `StockService::post()`

---

### Phase 2 — FG Confirmation & Execution Tracking

**Priority: High — core production completion flow**

- [ ] Migration: add execution fields to `production_orders`
- [ ] `POST /production-orders/{id}/start` endpoint
- [ ] `POST /production-orders/{id}/confirm-fg` endpoint
  - Validate actual_qty + rejected_qty + rework_qty <= target_qty
  - Post `PRODUCTION_RECEIPT` via `StockService::post()` with `product_id`
  - Post scrap to `BLOCKED` bucket if rejected_qty > 0
  - Calculate and store yield_percent
  - Transition status to COMPLETED
- [ ] `GET /production-orders/{id}/variance` endpoint
- [ ] Add `PRODUCTION_RECEIPT` and `PRODUCTION_ISSUE` to web portal production dashboard

---

### Phase 3 — Backflushing & Floor Stock

**Priority: Medium**

- [ ] Check `bin_type = FLOOR` bins in `bin_locations` (migration `2026_03_25_000003` adds bin_type — verify FLOOR type exists)
- [ ] On MIR generation: query floor stock per RM, calculate deficit, only request deficit qty
- [ ] On `confirm-fg`: compare actual RM consumed (from `inventory_transactions` for this order) vs BOM effective_qty × actual_qty/batch_size
- [ ] Post `STOCK_ADJUSTMENT` for any backflush delta

---

### Phase 4 — FG Quality Control

**Priority: Medium**

- [ ] Migration: add `production_order_id` + `source_type` to `inspection_lots`, make `grn_id` nullable
- [ ] On `confirm-fg`: if product has active QC parameters, auto-create `InspectionLot` with `source_type = PRODUCTION`
- [ ] Post FG to `QC_HOLD` bucket initially (not AVAILABLE) when FG QC is required
- [ ] On QC ACCEPTED: `StockService::transfer()` QC_HOLD → AVAILABLE
- [ ] On QC REJECTED: `StockService::transfer()` QC_HOLD → BLOCKED
- [ ] Reuse existing `QCController`, `QCService`, `InspectionLot`, `QCResult`, `QCDecision` — minimal new code

---

### Phase 5 — Packing Module

**Priority: Low (post-production outbound)**

- [ ] Migration: create `packing_orders`, `cartons`, `carton_items`
- [ ] `PackingController` with full CRUD + scan + seal + complete endpoints
- [ ] Carton nesting via `parent_carton_id` self-join (INNER → OUTER → PALLET)
- [ ] Weight validation: if `actual_weight` differs from `calculated_weight` by > threshold, block label print
- [ ] On packing complete: update stock bucket to SHIPPED-ready state

---

## 6. SOP Enhancement Notes

Based on the SOP review, the following enhancements are incorporated into this plan:

| SOP Point | Implementation |
|---|---|
| Floor stock / deficit check on MIR | Phase 3 — query FLOOR bin stock before MIR line qty calculation |
| Soft-allocation (hard vs soft reserve) | Phase 1 — `StockService::reserve()` on MIR creation |
| Batch/Lot capture on HHT scan | Phase 1 — resolve batch_number from stock_balances during scan |
| Backorder tracking on partial MIR | `scan_status` on mir_line_items already supports this; surface in API response |
| Scrap vs Rework split | Phase 2 — separate `rejected_qty` (scrap) and `rework_qty` fields |
| Automated backflushing | Phase 3 — post-FG confirmation RM delta calculation |
| FG QC with skip-lot logic | Phase 4 — `sampling_method = SKIP` already in inspection_lots; apply to FG lots |
| Digital sign-off on QC | `decided_by` + `decided_at` already in `usage_decisions` |
| Nested carton hierarchy | Phase 5 — `parent_carton_id` self-join on cartons table |
| Weight integration at packing | Phase 5 — `actual_weight` vs `calculated_weight` with block-on-mismatch logic |
| FIFO enforcement on HHT issue | Future — requires `received_at` ordering in bin stock query |
| Labor tracking (start/end time) | Phase 2 — `actual_start_at` / `actual_end_at` on production_orders |

---

## 7. Stock Transaction Map (Production Module)

| Event | Transaction Type | Bucket | qty_change | item |
|---|---|---|---|---|
| MIR line scanned + issued | `MATERIAL_ISSUE` | AVAILABLE | negative | material_id |
| FG confirmed (no QC required) | `PRODUCTION_RECEIPT` | AVAILABLE | positive | product_id |
| FG confirmed (QC required) | `PRODUCTION_RECEIPT` | QC_HOLD | positive | product_id |
| FG QC accepted | `QC_PASS` | QC_HOLD → AVAILABLE | transfer | product_id |
| FG QC rejected | `QC_REJECT` | QC_HOLD → BLOCKED | transfer | product_id |
| Scrap on FG confirmation | `STOCK_ADJUSTMENT` | BLOCKED | positive | product_id |
| Backflush delta | `STOCK_ADJUSTMENT` | AVAILABLE | negative | material_id |

---

## 8. File Index

| File | Role |
|---|---|
| `app/Http/Controllers/ProductionOrderController.php` | Production order CRUD + start + confirm-fg + variance |
| `app/Http/Controllers/MaterialIssueRequestController.php` | MIR approval, rejection, HHT scan |
| `app/Http/Controllers/BOMHeaderController.php` | BOM header CRUD |
| `app/Http/Controllers/BOMDetailController.php` | BOM detail CRUD |
| `app/Http/Controllers/PackingController.php` | Packing order + carton management (Phase 5) |
| `app/Models/Tenant/ProductionOrder.php` | Production order model |
| `app/Models/Tenant/MaterialIssueRequest.php` | MIR model |
| `app/Models/Tenant/MIRLineItem.php` | MIR line item model |
| `app/Models/Tenant/BOMHeader.php` | BOM header model |
| `app/Models/Tenant/BOMDetail.php` | BOM detail model |
| `app/Models/Tenant/PackingOrder.php` | Packing order model (Phase 5) |
| `app/Models/Tenant/Carton.php` | Carton model (Phase 5) |
| `app/Models/Tenant/CartonItem.php` | Carton item model (Phase 5) |
| `app/Services/StockService.php` | All stock postings — post(), transfer(), reserve() |
| `app/Services/QCService.php` | QC lot creation, test recording, usage decision |
| `database/migrations/tenant/2026_03_27_120001_create_production_orders_table.php` | Base production tables |
| `database/migrations/tenant/2026_03_27_120002_add_scan_fields_to_mir_tables.php` | HHT scan fields |
| `database/migrations/tenant/2026_03_28_000001_add_execution_fields_to_production_orders.php` | Phase 2 |
| `database/migrations/tenant/2026_03_28_000002_add_fg_qc_support_to_inspection_lots.php` | Phase 4 |
| `database/migrations/tenant/2026_03_28_000003_create_packing_tables.php` | Phase 5 |
| `routes/api.php` | All API route definitions |
