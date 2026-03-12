# Quality Control (QC) API Examples

## Overview
Quality Control manages inspection lots, test results, and usage decisions for incoming materials.

## Base URL
```
http://127.0.0.1:8000/api/v1/qc
```

## Authentication
All requests require JWT token:
```
Authorization: Bearer <your_jwt_token>
```

## Test Data
- org_slug: `amit-tech-solutions-pvt-ltd`
- user_id: 1 (ADMIN role)
- GRN ID: Use an existing GRN in QC_PENDING status

---

## 1. List All Inspection Lots

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### Filter by Status
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc?status=PENDING" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

### Filter by Technician
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc?assigned_to=1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 2. Get Single Inspection Lot

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 3. Create Inspection Lot

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/qc" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "grn_id": 1,
    "grn_line_id": 1,
    "material_id": 1,
    "lot_qty": 100,
    "sample_size": 10,
    "sampling_method": "AQL",
    "assigned_to": 1,
    "due_by": "2026-03-15 17:00:00",
    "remarks": "QC inspection for GRN-2603-0001"
  }'
```

---

## 4. Update Inspection Lot

```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/qc/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "sample_size": 15,
    "assigned_to": 1,
    "due_by": "2026-03-16 17:00:00",
    "remarks": "Updated due date"
  }'
```

---

## 5. Start Inspection

Moves lot from PENDING to IN_PROGRESS status.

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/qc/1/start" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{}'
```

---

## 6. Complete Inspection

Moves lot from IN_PROGRESS to COMPLETED status.

```bash
curl -X PATCH "http://127.0.0.1:8000/api/v1/qc/1/complete" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{}'
```

---

## 7. Record Test Result

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/qc/1/test-results" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "parameter_name": "Purity %",
    "standard_min": "99.0",
    "standard_max": "100.0",
    "standard_value": "99.5",
    "observed_value": "99.8",
    "unit_of_measurement": "%",
    "remarks": "Test passed - purity within range"
  }'
```

### Record Multiple Test Results
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/qc/1/test-results" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "parameter_name": "Moisture Content",
    "standard_max": "0.5",
    "observed_value": "0.3",
    "unit_of_measurement": "%"
  }'
```

---

## 8. Make Usage Decision

### Accept Material
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/qc/1/decision" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "decision": "ACCEPTED",
    "accepted_qty": 100,
    "remarks": "All tests passed. Material approved for production.",
    "coa_file_path": "/uploads/coa_grn1_batch1.pdf"
  }'
```

### Reject Material
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/qc/1/decision" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "decision": "REJECTED",
    "rejected_qty": 100,
    "remarks": "Critical parameter failed - Purity 95% (required 99%)",
    "coa_file_path": "/uploads/coa_grn1_batch1.pdf"
  }'
```

### Conditional Acceptance (Requires Override)
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/qc/1/decision" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "decision": "CONDITIONALLY_ACCEPTED",
    "accepted_qty": 100,
    "override_approved_by": 2,
    "override_reason": "Production emergency - approved by Technical Head",
    "remarks": "Material slightly off-spec but usable for non-critical application"
  }'
```

---

## 9. Get Pending Inspection Lots

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc/pending" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 10. Get In-Progress Inspection Lots

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc/in-progress" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 11. Get Completed Inspection Lots

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc/completed" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 12. Get Inspection Lots by GRN

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc/by-grn/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## 13. Get QC Parameters for Material

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/qc/parameters/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

---

## Status Flow

```
PENDING (lot created, not yet sampled)
    ↓
IN_PROGRESS (sampling started, tests being recorded)
    ↓
COMPLETED (all tests recorded)
    ↓
DECISION_MADE (usage decision posted)
```

---

## Response Examples

### Success Response (Create Lot)
```json
{
  "success": true,
  "message": "Inspection lot created successfully",
  "data": {
    "id": 1,
    "lot_number": "IL-2603/0001",
    "grn_id": 1,
    "grn_line_id": 1,
    "material_id": 1,
    "lot_qty": 100.000,
    "sample_size": 10.000,
    "sampling_method": "AQL",
    "status": "PENDING",
    "test_results": [
      {
        "id": 1,
        "parameter_name": "Purity %",
        "standard_min": "99.0",
        "standard_max": "100.0",
        "standard_value": "99.5",
        "observed_value": null,
        "is_pass": null
      }
    ]
  }
}
```

### Success Response (Record Test Result)
```json
{
  "success": true,
  "message": "Test result recorded successfully",
  "data": {
    "id": 1,
    "lot_id": 1,
    "parameter_name": "Purity %",
    "standard_min": "99.0",
    "standard_max": "100.0",
    "standard_value": "99.5",
    "observed_value": "99.8",
    "unit_of_measurement": "%",
    "is_pass": true,
    "remarks": "Test passed - purity within range"
  }
}
```

### Success Response (Usage Decision)
```json
{
  "success": true,
  "message": "Usage decision recorded successfully",
  "data": {
    "id": 1,
    "lot_id": 1,
    "decision": "ACCEPTED",
    "accepted_qty": 100.000,
    "rejected_qty": 0.000,
    "coa_file_path": "/uploads/coa_grn1_batch1.pdf",
    "remarks": "All tests passed. Material approved for production.",
    "decided_by": 1,
    "decided_at": "2026-03-12T10:30:00.000000Z"
  }
}
```

---

## Key Features

1. **Automatic Lot Generation**: Triggered when GRN is saved
2. **Auto-populated Test Parameters**: From QC parameters master
3. **Sampling Plans**: AQL, 100PCT, SKIP methods
4. **Test Result Recording**: Parameter-wise with pass/fail evaluation
5. **Usage Decision**: ACCEPTED, REJECTED, CONDITIONALLY_ACCEPTED, REWORK_REQUIRED
6. **Stock Status Update**: Based on usage decision
7. **Certificate of Analysis**: File attachment support
8. **Override Approval**: For conditional acceptance

---

## Business Rules

1. Inspection lot auto-created when GRN is saved
2. Lot must be in PENDING status to start inspection
3. All test parameters must have results before completing inspection
4. Usage decision can only be made after inspection is completed
5. ACCEPTED → Stock UNRESTRICTED (available for production)
6. REJECTED → Stock BLOCKED (RTV workflow triggered)
7. CONDITIONALLY_ACCEPTED → Stock RESTRICTED (requires override approval)
8. REWORK_REQUIRED → Stock RETURNED (material to be reworked)

---

## Testing Checklist

- [ ] Create inspection lot from GRN
- [ ] List all inspection lots with filters
- [ ] Start inspection (PENDING → IN_PROGRESS)
- [ ] Record test results for multiple parameters
- [ ] Complete inspection (IN_PROGRESS → COMPLETED)
- [ ] Make ACCEPTED decision
- [ ] Make REJECTED decision
- [ ] Make CONDITIONALLY_ACCEPTED decision (with override)
- [ ] Get pending lots
- [ ] Get in-progress lots
- [ ] Get completed lots
- [ ] Get QC parameters for material
- [ ] Verify stock status updates after decision
- [ ] Verify COA file path storage
- [ ] Verify override approval workflow
