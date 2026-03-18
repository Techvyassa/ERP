# QC & Putaway Implementation Guide

## Overview
This document outlines the implementation of Quality Control (QC) and Putaway features for the ERP inward material process.

## Architecture

### 1. QC Module (Quality Control Department)

**Status Flow:** PENDING → IN_PROGRESS → COMPLETED → DECISION_MADE

**Roles:**
- `QC_TECH` — Record test results
- `QC_MGR` — Make usage decisions
- `ADMIN` — All permissions

**Key Entities:**
- Inspection Lot (linked to GRN)
- QC Parameters (per material)
- QC Results (test recordings)
- Usage Decision (Accepted/Rejected/Conditional)

### 2. Putaway Module (Warehouse Department)

**Status Flow:** PENDING → IN_PROGRESS → COMPLETED

**Roles:**
- `STOREKEEPER` — Create/execute putaway tasks
- `STORE_MGR` — Approve putaway
- `ADMIN` — All permissions

**Key Entities:**
- Putaway Task (transfer order)
- Source Bin (Receiving Zone)
- Destination Bin (Permanent location)
- Putaway Strategy (Manual/Fixed/Empty/FIFO)

---

## Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── QCController.php (existing)
│   │   └── PutawayController.php (existing)
│   └── Requests/
│       ├── Tenant/
│       │   ├── StoreQCResultRequest.php (NEW)
│       │   ├── MakeQCDecisionRequest.php (NEW)
│       │   ├── StorePutawayRequest.php (NEW)
│       │   └── CompletePutawayRequest.php (NEW)
├── Models/
│   ├── Tenant/
│   │   ├── InspectionLot.php (NEW)
│   │   ├── QCParameter.php (NEW)
│   │   ├── QCResult.php (NEW)
│   │   ├── QCDecision.php (NEW)
│   │   ├── PutawayTask.php (NEW)
│   │   └── PutawayLine.php (NEW)
├── Services/
│   ├── QCService.php (NEW)
│   └── PutawayService.php (NEW)
└── Contracts/
    ├── QCService.php (interface)
    └── PutawayService.php (interface)

resources/views/
├── tenant/
│   ├── quality/
│   │   ├── dashboard.blade.php (existing)
│   │   ├── inspections/
│   │   │   ├── index.blade.php (NEW)
│   │   │   └── show.blade.php (NEW)
│   │   ├── decisions/
│   │   │   └── index.blade.php (NEW)
│   │   └── reports/
│   │       └── index.blade.php (NEW)
│   └── warehouse/
│       ├── putaway/
│       │   ├── index.blade.php (NEW)
│       │   └── show.blade.php (NEW)

database/migrations/tenant/
├── 2024_01_01_000040_create_inspection_lots_table.php (existing)
├── 2024_01_01_000041_create_qc_parameters_table.php (NEW)
├── 2024_01_01_000042_create_qc_results_table.php (NEW)
├── 2024_01_01_000043_create_qc_decisions_table.php (NEW)
├── 2024_01_01_000044_create_putaway_tasks_table.php (existing)
└── 2024_01_01_000045_create_putaway_lines_table.php (NEW)
```

---

## API Endpoints

### QC Endpoints (Already in routes/api.php)

```
GET    /api/v1/qc                          — List all QC lots
GET    /api/v1/qc/{id}                     — Get single QC lot
POST   /api/v1/qc                          — Create inspection lot
PUT    /api/v1/qc/{id}                     — Update QC lot
GET    /api/v1/qc/pending                  — Get pending QC lots
GET    /api/v1/qc/in-progress              — Get in-progress QC lots
GET    /api/v1/qc/completed                — Get completed QC lots
GET    /api/v1/qc/by-grn/{grnId}           — Get QC by GRN
GET    /api/v1/qc/parameters/{materialId}  — Get QC parameters for material
POST   /api/v1/qc/{lotId}/test-results     — Record test result
PATCH  /api/v1/qc/{id}/start               — Start inspection (PENDING → IN_PROGRESS)
PATCH  /api/v1/qc/{id}/complete            — Complete inspection (IN_PROGRESS → COMPLETED)
POST   /api/v1/qc/{id}/decision            — Make usage decision (COMPLETED → DECISION_MADE)
```

### Putaway Endpoints (Already in routes/api.php)

```
GET    /api/v1/putaway                     — List all putaway tasks
GET    /api/v1/putaway/{id}                — Get single putaway task
POST   /api/v1/putaway                     — Create putaway task
PUT    /api/v1/putaway/{id}                — Update putaway task
GET    /api/v1/putaway/pending             — Get pending putaway tasks
GET    /api/v1/putaway/in-progress         — Get in-progress putaway tasks
GET    /api/v1/putaway/completed           — Get completed putaway tasks
PATCH  /api/v1/putaway/{id}/start          — Start putaway (PENDING → IN_PROGRESS)
PATCH  /api/v1/putaway/{id}/complete       — Complete putaway (IN_PROGRESS → COMPLETED)
PATCH  /api/v1/putaway/{id}/cancel         — Cancel putaway (Any → CANCELLED)
```

---

## Web Routes

### QC Routes (Already in routes/web.php)

```
GET  /org/{org_slug}/quality/dashboard     — Quality dashboard
GET  /org/{org_slug}/quality/inspections   — QC inspections list
GET  /org/{org_slug}/quality/decisions     — Usage decisions list
GET  /org/{org_slug}/quality/reports       — Quality reports
```

### Putaway Routes (Already in routes/web.php)

```
GET  /org/{org_slug}/warehouse/putaway     — Putaway tasks list
```

---

## Database Schema

### inspection_lots table
```sql
id, grn_id, material_id, sample_size, status, created_by, approved_by, approved_at, created_at, updated_at
```

### qc_parameters table
```sql
id, material_id, parameter_name, standard_value_min, standard_value_max, unit, test_method, created_at, updated_at
```

### qc_results table
```sql
id, inspection_lot_id, qc_parameter_id, observed_value, status, recorded_by, created_at, updated_at
```

### qc_decisions table
```sql
id, inspection_lot_id, decision (ACCEPTED/REJECTED/CONDITIONAL), remarks, decided_by, decided_at, created_at, updated_at
```

### putaway_tasks table
```sql
id, grn_id, material_id, source_bin_id, destination_bin_id, quantity, status, strategy, created_by, completed_by, completed_at, created_at, updated_at
```

### putaway_lines table
```sql
id, putaway_task_id, line_number, batch_number, quantity, status, created_at, updated_at
```

---

## Implementation Checklist

- [ ] Create QC Models (InspectionLot, QCParameter, QCResult, QCDecision)
- [ ] Create Putaway Models (PutawayTask, PutawayLine)
- [ ] Create QC Service (QCService.php)
- [ ] Create Putaway Service (PutawayService.php)
- [ ] Create QC Request Validators
- [ ] Create Putaway Request Validators
- [ ] Create QC Views (inspections, decisions, reports)
- [ ] Create Putaway Views (tasks, details)
- [ ] Update GRNService to trigger Inspection Lot creation
- [ ] Update QCService to trigger Putaway Task creation on decision
- [ ] Add role-based permissions to RBAC
- [ ] Create database migrations
- [ ] Add API documentation

---

## Data Flow

```
GRN Created (PROVISIONAL)
    ↓
GRN Approved (QC_PENDING)
    ↓
Inspection Lot Created (PENDING)
    ↓
QC Technician Records Results (IN_PROGRESS)
    ↓
QC Manager Makes Decision (COMPLETED → DECISION_MADE)
    ├─ ACCEPTED → Putaway Task Created (PENDING)
    ├─ REJECTED → RTV Workflow Triggered
    └─ CONDITIONAL → Approval Override Required
    ↓
Storekeeper Executes Putaway (IN_PROGRESS)
    ↓
Putaway Completed (COMPLETED)
    ↓
Stock Available for Production
```

---

## Role Permissions

### QC_TECH
- View inspection lots
- Record test results
- View QC parameters

### QC_MGR
- View inspection lots
- Record test results
- Make usage decisions
- View QC reports

### STOREKEEPER
- View putaway tasks
- Execute putaway (scan bins)
- Complete putaway

### STORE_MGR
- View putaway tasks
- Approve putaway
- View putaway reports

### ADMIN
- All QC and Putaway permissions

