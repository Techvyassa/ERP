# Production Order & MIR Documentation Index

## Overview

This index provides a guide to all documentation files created for the Production Order and Material Issue Request (MIR) system implementation.

## Documentation Files

### 1. **README_PRODUCTION_MIR.md** ⭐ START HERE
**Purpose**: High-level overview of the entire system
**Contents**:
- System overview and key features
- API endpoints summary
- Status flows
- Example workflow
- Implementation steps
- Business rules
- Database tables overview

**When to use**: First-time readers, project overview, stakeholder briefing

---

### 2. **PRODUCTION_MIR_API_GUIDE.md** 📚 COMPLETE REFERENCE
**Purpose**: Comprehensive API documentation with examples
**Contents**:
- Detailed endpoint documentation
- Request/response examples
- Status flow diagrams
- Complete workflow examples
- Error scenarios
- Database field reference
- Key business rules

**When to use**: API development, integration testing, troubleshooting

---

### 3. **QUICK_REFERENCE.md** ⚡ QUICK LOOKUP
**Purpose**: Quick reference for common tasks
**Contents**:
- API endpoints table
- Status flows
- Common workflows
- Error scenarios and solutions
- Web routes
- Controllers to implement
- Testing checklist

**When to use**: Quick lookups, common questions, during development

---

### 4. **IMPLEMENTATION_CHECKLIST.md** ✅ STEP-BY-STEP GUIDE
**Purpose**: Detailed implementation guide with code examples
**Contents**:
- Phase 1: Database migrations with SQL examples
- Phase 2: Model updates with code examples
- Phase 3: Controller implementation with logic
- Phase 4: Testing scenarios
- Phase 5: Documentation & deployment
- Phase 6: Post-deployment monitoring
- Rollback plan
- Success criteria

**When to use**: Implementation phase, code review, deployment planning

---

### 5. **PRODUCTION_MIR_UPDATES_SUMMARY.md** 📋 CHANGE SUMMARY
**Purpose**: Summary of all changes made to routes
**Contents**:
- API routes changes
- Web routes changes
- Key features implemented
- Status flow diagrams
- Database fields added/modified
- Business rules enforced
- Controllers required
- Migration requirements
- Testing scenarios
- Backward compatibility notes

**When to use**: Code review, change management, deployment notes

---

### 6. **WORKFLOW_DIAGRAMS.md** 📊 VISUAL REFERENCE
**Purpose**: Visual diagrams of workflows and processes
**Contents**:
- Complete production workflow
- MIR status derivation logic
- Partial issuance flow
- Production floor receiving gate
- Rejection scenario
- Batch run lifecycle
- API call sequence
- Status transition matrix
- Data flow diagram
- Error handling flow

**When to use**: Understanding workflows, training, documentation

---

## Quick Navigation

### By Role

#### **Product Manager / Stakeholder**
1. Start with: **README_PRODUCTION_MIR.md**
2. Review: **WORKFLOW_DIAGRAMS.md** (visual understanding)
3. Reference: **QUICK_REFERENCE.md** (common workflows)

#### **Backend Developer**
1. Start with: **README_PRODUCTION_MIR.md**
2. Deep dive: **PRODUCTION_MIR_API_GUIDE.md**
3. Implement: **IMPLEMENTATION_CHECKLIST.md**
4. Reference: **QUICK_REFERENCE.md**

#### **Frontend Developer**
1. Start with: **README_PRODUCTION_MIR.md**
2. Review: **PRODUCTION_MIR_API_GUIDE.md** (API contracts)
3. Reference: **QUICK_REFERENCE.md** (endpoints)
4. Study: **WORKFLOW_DIAGRAMS.md** (user flows)

#### **QA / Tester**
1. Start with: **README_PRODUCTION_MIR.md**
2. Review: **IMPLEMENTATION_CHECKLIST.md** (testing section)
3. Reference: **QUICK_REFERENCE.md** (error scenarios)
4. Study: **WORKFLOW_DIAGRAMS.md** (test scenarios)

#### **DevOps / Deployment**
1. Review: **IMPLEMENTATION_CHECKLIST.md** (deployment section)
2. Reference: **PRODUCTION_MIR_UPDATES_SUMMARY.md** (changes)
3. Plan: Rollback plan in **IMPLEMENTATION_CHECKLIST.md**

---

### By Task

#### **Understanding the System**
1. **README_PRODUCTION_MIR.md** — Overview
2. **WORKFLOW_DIAGRAMS.md** — Visual flows
3. **QUICK_REFERENCE.md** — Key concepts

#### **API Development**
1. **PRODUCTION_MIR_API_GUIDE.md** — Endpoint details
2. **IMPLEMENTATION_CHECKLIST.md** — Code examples
3. **QUICK_REFERENCE.md** — Quick lookup

#### **Database Design**
1. **IMPLEMENTATION_CHECKLIST.md** — Phase 1 (Migrations)
2. **PRODUCTION_MIR_UPDATES_SUMMARY.md** — Database fields
3. **PRODUCTION_MIR_API_GUIDE.md** — Database tables reference

#### **Testing**
1. **IMPLEMENTATION_CHECKLIST.md** — Phase 4 (Testing)
2. **QUICK_REFERENCE.md** — Error scenarios
3. **WORKFLOW_DIAGRAMS.md** — Test scenarios

#### **Deployment**
1. **IMPLEMENTATION_CHECKLIST.md** — Phase 5 & 6
2. **PRODUCTION_MIR_UPDATES_SUMMARY.md** — Changes summary
3. **QUICK_REFERENCE.md** — Monitoring checklist

---

## Key Concepts

### Production Order
- Raised against a specific product and BOM version
- User enters `production_qty` — total FG units to produce
- Status: DRAFT → RELEASED → IN_PROGRESS → CLOSED

### Batch Runs
- Independent execution units per production order
- User-defined `run_qty` (flexible)
- MIR auto-generated when batch run moves to IN_PROGRESS
- Status: PENDING → MIR_RAISED → IN_PROGRESS → COMPLETED

### Material Issue Request (MIR)
- Auto-generated per batch run
- Two-level tracking: Line level + Header level
- Header status auto-derived from line statuses
- Status: PENDING → APPROVED → PARTIALLY_ISSUED → FULLY_ISSUED → CLOSED

### Production Floor Receiving
- Hard gate: Batch run cannot start until receiving_status = RECEIVED
- Prevents false starts and mid-run material shortages
- Supports quantity discrepancy notes (non-blocking)

### Finished Goods Receipt
- Records actual production output per batch run
- Calculates yield: `yield_actual_pct = accepted_qty / planned_qty × 100`
- Unique lot number per receipt for traceability

---

## API Endpoints Summary

### Production Orders
```
GET    /api/v1/production-orders
POST   /api/v1/production-orders
GET    /api/v1/production-orders/{id}
PATCH  /api/v1/production-orders/{id}/release
PATCH  /api/v1/production-orders/{id}/close
```

### Batch Runs
```
GET    /api/v1/batch-runs
POST   /api/v1/batch-runs
GET    /api/v1/batch-runs/{id}
GET    /api/v1/batch-runs/{id}/materials
GET    /api/v1/batch-runs/{id}/mir
PATCH  /api/v1/batch-runs/{id}/start
PATCH  /api/v1/batch-runs/{id}/complete
```

### Material Issue Requests
```
GET    /api/v1/material-issue-requests
GET    /api/v1/material-issue-requests/{id}
GET    /api/v1/material-issue-requests/{id}/lines
PATCH  /api/v1/material-issue-requests/{id}/approve
PATCH  /api/v1/material-issue-requests/{id}/reject
```

### MIR Lines
```
GET    /api/v1/mir-lines/{id}
PATCH  /api/v1/mir-lines/{id}/approve
PATCH  /api/v1/mir-lines/{id}/reject
POST   /api/v1/mir-lines/{id}/issue
```

### Production Floor Receiving
```
GET    /api/v1/batch-runs/{batchRunId}/receiving
PATCH  /api/v1/batch-runs/{batchRunId}/receiving/confirm
```

### Finished Goods
```
POST   /api/v1/fg-receipts
GET    /api/v1/fg-receipts/{id}
```

---

## Files Modified

### Route Files
- **routes/api.php** — Updated with new API endpoints
- **routes/web.php** — Updated with new web routes

### Documentation Files Created
1. README_PRODUCTION_MIR.md
2. PRODUCTION_MIR_API_GUIDE.md
3. QUICK_REFERENCE.md
4. IMPLEMENTATION_CHECKLIST.md
5. PRODUCTION_MIR_UPDATES_SUMMARY.md
6. WORKFLOW_DIAGRAMS.md
7. DOCUMENTATION_INDEX.md (this file)

---

## Implementation Timeline

### Phase 1: Database Migrations (1-2 days)
- Update production_batch_runs table
- Update material_issue_requests table
- Update mir_line_items table
- Create mir_issue_transactions table

### Phase 2: Model Updates (1 day)
- Update ProductionBatchRun model
- Update MaterialIssueRequest model
- Update MIRLineItem model
- Create MIRIssueTransaction model

### Phase 3: Controller Implementation (3-4 days)
- Create BatchRunController
- Create MIRLineController
- Create BatchRunReceivingController
- Create FGReceiptController
- Update ProductionOrderController
- Update MaterialIssueRequestController

### Phase 4: Testing (2-3 days)
- Unit tests
- Integration tests
- Manual testing

### Phase 5: Deployment (1 day)
- Staging deployment
- Production deployment
- Monitoring

**Total: 8-11 days**

---

## Success Criteria

- [ ] All batch runs can be created and tracked independently
- [ ] MIR auto-generates when batch run moves to IN_PROGRESS
- [ ] Store can approve and issue materials with partial issuance support
- [ ] MIR header status derives correctly from line statuses
- [ ] Production floor receiving gate prevents batch run start until RECEIVED
- [ ] FG receipts can be created with yield calculations
- [ ] All status transitions work as specified
- [ ] Audit trail captures all transactions
- [ ] Error handling is comprehensive
- [ ] Performance is acceptable (< 500ms for most endpoints)

---

## Support & Questions

### Common Questions

**Q: How do I create a batch run?**
A: See **QUICK_REFERENCE.md** → "Create Batch Run" section

**Q: What happens when I issue materials?**
A: See **WORKFLOW_DIAGRAMS.md** → "Partial Issuance Flow"

**Q: Why can't I start the batch run?**
A: See **QUICK_REFERENCE.md** → "Common Error Scenarios"

**Q: How do I implement the controllers?**
A: See **IMPLEMENTATION_CHECKLIST.md** → "Phase 3: Controller Implementation"

**Q: What are the database changes?**
A: See **IMPLEMENTATION_CHECKLIST.md** → "Phase 1: Database Migrations"

---

## Document Versions

| Document | Version | Date | Status |
|----------|---------|------|--------|
| README_PRODUCTION_MIR.md | 1.0 | 2026-04-13 | Final |
| PRODUCTION_MIR_API_GUIDE.md | 1.0 | 2026-04-13 | Final |
| QUICK_REFERENCE.md | 1.0 | 2026-04-13 | Final |
| IMPLEMENTATION_CHECKLIST.md | 1.0 | 2026-04-13 | Final |
| PRODUCTION_MIR_UPDATES_SUMMARY.md | 1.0 | 2026-04-13 | Final |
| WORKFLOW_DIAGRAMS.md | 1.0 | 2026-04-13 | Final |
| DOCUMENTATION_INDEX.md | 1.0 | 2026-04-13 | Final |

---

## Next Steps

1. **Review** — Read README_PRODUCTION_MIR.md for overview
2. **Plan** — Review IMPLEMENTATION_CHECKLIST.md for timeline
3. **Design** — Study WORKFLOW_DIAGRAMS.md for understanding
4. **Implement** — Follow IMPLEMENTATION_CHECKLIST.md step-by-step
5. **Test** — Use QUICK_REFERENCE.md for test scenarios
6. **Deploy** — Follow deployment section in IMPLEMENTATION_CHECKLIST.md
7. **Monitor** — Use monitoring checklist in QUICK_REFERENCE.md

---

## Contact & Support

For questions or clarifications:
1. Check the relevant documentation file
2. Review QUICK_REFERENCE.md for common issues
3. Consult WORKFLOW_DIAGRAMS.md for visual understanding
4. Review IMPLEMENTATION_CHECKLIST.md for code examples
