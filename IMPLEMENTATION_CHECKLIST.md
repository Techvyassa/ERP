# Production Order & MIR Implementation Checklist

## Overview
This checklist guides the implementation of the complete production order and MIR workflow with batch runs, two-level status tracking, and production floor receiving gates.

---

## Phase 1: Database Migrations

### 1.1 Update `production_batch_runs` Table
- [ ] Add `receiving_status` column (enum: PENDING_RECEIPT, RECEIVED)
- [ ] Add `received_at` column (nullable timestamp)
- [ ] Add `received_by` column (nullable FK to users)
- [ ] Add `receiving_notes` column (nullable text)
- [ ] Add `actual_start_at` column (nullable timestamp) — if not exists
- [ ] Add `actual_end_at` column (nullable timestamp) — if not exists

**Migration Example**:
```php
Schema::table('production_batch_runs', function (Blueprint $table) {
    $table->enum('receiving_status', ['PENDING_RECEIPT', 'RECEIVED'])->default('PENDING_RECEIPT');
    $table->timestamp('received_at')->nullable();
    $table->unsignedBigInteger('received_by')->nullable();
    $table->text('receiving_notes')->nullable();
    $table->timestamp('actual_start_at')->nullable();
    $table->timestamp('actual_end_at')->nullable();
    $table->foreign('received_by')->references('id')->on('users');
});
```

### 1.2 Update `material_issue_requests` Table
- [ ] Update `status` enum to include: PENDING, APPROVED, PARTIALLY_ISSUED, FULLY_ISSUED, REJECTED, CLOSED
- [ ] Add `fully_issued_at` column (nullable timestamp)
- [ ] Add `closed_at` column (nullable timestamp)
- [ ] Add `batch_run_id` column (FK to production_batch_runs) — if not exists

**Migration Example**:
```php
Schema::table('material_issue_requests', function (Blueprint $table) {
    $table->enum('status', ['PENDING', 'APPROVED', 'PARTIALLY_ISSUED', 'FULLY_ISSUED', 'REJECTED', 'CLOSED'])->change();
    $table->timestamp('fully_issued_at')->nullable();
    $table->timestamp('closed_at')->nullable();
    $table->unsignedBigInteger('batch_run_id')->nullable();
    $table->foreign('batch_run_id')->references('id')->on('production_batch_runs');
});
```

### 1.3 Update `mir_line_items` Table
- [ ] Update `status` enum to include: PENDING, APPROVED, PARTIALLY_PICKED, FULLY_PICKED, REJECTED
- [ ] Add `last_issued_at` column (nullable timestamp)
- [ ] Add `rejected_reason` column (nullable text)

**Migration Example**:
```php
Schema::table('mir_line_items', function (Blueprint $table) {
    $table->enum('status', ['PENDING', 'APPROVED', 'PARTIALLY_PICKED', 'FULLY_PICKED', 'REJECTED'])->change();
    $table->timestamp('last_issued_at')->nullable();
    $table->text('rejected_reason')->nullable();
});
```

### 1.4 Create `mir_issue_transactions` Table (NEW)
- [ ] Create table with columns: id, mir_line_id, issued_qty, issued_by, issued_at, notes
- [ ] Add foreign keys and indexes

**Migration Example**:
```php
Schema::create('mir_issue_transactions', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('mir_line_id');
    $table->decimal('issued_qty', 12, 4);
    $table->unsignedBigInteger('issued_by');
    $table->timestamp('issued_at');
    $table->text('notes')->nullable();
    $table->timestamps();
    
    $table->foreign('mir_line_id')->references('id')->on('mir_line_items');
    $table->foreign('issued_by')->references('id')->on('users');
    $table->index('mir_line_id');
    $table->index('issued_at');
});
```

### 1.5 Update `production_orders` Table (Optional)
- [ ] Verify `status` enum includes: DRAFT, RELEASED, IN_PROGRESS, CLOSED
- [ ] Add `production_qty` column (decimal) — if not exists
- [ ] Add `bom_id` column (FK) — if not exists

---

## Phase 2: Model Updates

### 2.1 Update `ProductionBatchRun` Model
- [ ] Add fillable fields: receiving_status, received_at, received_by, receiving_notes, actual_start_at, actual_end_at
- [ ] Add casts for timestamps and decimals
- [ ] Add relationship: `mir()` → hasOne MaterialIssueRequest
- [ ] Add relationship: `receiver()` → belongsTo User
- [ ] Add method: `canStart()` — checks if MIR FULLY_ISSUED and receiving RECEIVED
- [ ] Add method: `canComplete()` — checks if status IN_PROGRESS
- [ ] Add method: `calculateMaterialRequirements()` — calculates required qty per material

**Example**:
```php
public function mir()
{
    return $this->hasOne(MaterialIssueRequest::class, 'batch_run_id');
}

public function receiver()
{
    return $this->belongsTo(User::class, 'received_by');
}

public function canStart(): bool
{
    return $this->status === 'PENDING' 
        && $this->mir?->status === 'FULLY_ISSUED'
        && $this->receiving_status === 'RECEIVED';
}
```

### 2.2 Update `MaterialIssueRequest` Model
- [ ] Add fillable fields: batch_run_id, fully_issued_at, closed_at
- [ ] Add casts for timestamps
- [ ] Add relationship: `batchRun()` → belongsTo ProductionBatchRun
- [ ] Add method: `deriveHeaderStatus()` — calculates header status from line statuses
- [ ] Add method: `updateHeaderStatus()` — updates header status and timestamps
- [ ] Add method: `canApprove()` — checks if all lines APPROVED

**Example**:
```php
public function batchRun()
{
    return $this->belongsTo(ProductionBatchRun::class, 'batch_run_id');
}

public function deriveHeaderStatus(): string
{
    $lineStatuses = $this->lines()->pluck('status')->toArray();
    
    if (in_array('REJECTED', $lineStatuses)) {
        return 'REJECTED';
    }
    if (count(array_unique($lineStatuses)) === 1) {
        return match($lineStatuses[0]) {
            'PENDING' => 'PENDING',
            'APPROVED' => 'APPROVED',
            'FULLY_PICKED' => 'FULLY_ISSUED',
            default => 'PARTIALLY_ISSUED',
        };
    }
    return 'PARTIALLY_ISSUED';
}
```

### 2.3 Update `MIRLineItem` Model
- [ ] Add fillable fields: last_issued_at, rejected_reason
- [ ] Add casts for timestamps and decimals
- [ ] Add relationship: `mir()` → belongsTo MaterialIssueRequest
- [ ] Add relationship: `transactions()` → hasMany MIRIssueTransaction
- [ ] Add method: `updateStatus()` — updates line status based on issued_qty
- [ ] Add method: `canIssue()` — checks if line APPROVED or PARTIALLY_PICKED

**Example**:
```php
public function transactions()
{
    return $this->hasMany(MIRIssueTransaction::class, 'mir_line_id');
}

public function updateStatus(): void
{
    if ($this->issued_qty >= $this->required_qty) {
        $this->status = 'FULLY_PICKED';
    } elseif ($this->issued_qty > 0) {
        $this->status = 'PARTIALLY_PICKED';
    }
    $this->last_issued_at = now();
    $this->save();
}
```

### 2.4 Create `MIRIssueTransaction` Model (NEW)
- [ ] Create model with table: mir_issue_transactions
- [ ] Add fillable fields: mir_line_id, issued_qty, issued_by, issued_at, notes
- [ ] Add relationships: mir_line, issuer

**Example**:
```php
class MIRIssueTransaction extends Model
{
    protected $connection = 'tenant';
    protected $table = 'mir_issue_transactions';
    public $timestamps = false;

    protected $fillable = ['mir_line_id', 'issued_qty', 'issued_by', 'issued_at', 'notes'];
    protected $casts = ['issued_qty' => 'decimal:4', 'issued_at' => 'datetime'];

    public function mirLine()
    {
        return $this->belongsTo(MIRLineItem::class, 'mir_line_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
```

### 2.5 Update `BatchRunMaterial` Model
- [ ] Add relationship: `mir_line()` → hasOne MIRLineItem
- [ ] Add method: `calculateRequiredQty()` — calculates with scrap percentage

---

## Phase 3: Controller Implementation

### 3.1 Create `BatchRunController` (NEW)
- [ ] `index()` — List batch runs with filtering
- [ ] `store()` — Create batch run, auto-generate MIR
- [ ] `show()` — Get batch run details with materials and MIR
- [ ] `start()` — Start batch run (PENDING → MIR_RAISED → IN_PROGRESS)
  - Validate: MIR FULLY_ISSUED, receiving RECEIVED
  - Set actual_start_at
- [ ] `complete()` — Complete batch run (IN_PROGRESS → COMPLETED)
  - Set actual_end_at
- [ ] `materials()` — Get required materials with calculated quantities
- [ ] `mir()` — Get associated MIR

**Key Logic**:
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'production_order_id' => 'required|exists:production_orders,id',
        'run_qty' => 'required|numeric|min:0.01',
        'planned_date' => 'required|date',
    ]);

    $batchRun = ProductionBatchRun::create($validated);
    
    // Auto-generate MIR
    $this->generateMIR($batchRun);
    
    return response()->json(['success' => true, 'data' => $batchRun], 201);
}

public function start(Request $request, $id)
{
    $batchRun = ProductionBatchRun::findOrFail($id);
    
    // Validate preconditions
    if ($batchRun->status !== 'PENDING') {
        return response()->json(['success' => false, 'message' => 'Invalid status'], 422);
    }
    
    if ($batchRun->mir?->status !== 'FULLY_ISSUED') {
        return response()->json(['success' => false, 'message' => 'MIR not fully issued'], 422);
    }
    
    if ($batchRun->receiving_status !== 'RECEIVED') {
        return response()->json(['success' => false, 'message' => 'Production floor receiving not confirmed'], 422);
    }
    
    $batchRun->update([
        'status' => 'IN_PROGRESS',
        'actual_start_at' => now(),
    ]);
    
    return response()->json(['success' => true, 'data' => $batchRun]);
}
```

### 3.2 Create `MaterialIssueRequestController` Updates
- [ ] `index()` — List MIRs with filtering by status, batch_run_id
- [ ] `show()` — Get MIR with all lines and transactions
- [ ] `lines()` — Get all MIR lines with transaction history
- [ ] `approve()` — Approve MIR (PENDING → APPROVED)
  - Validate: all lines APPROVED
  - Set approved_at
- [ ] `reject()` — Reject MIR (PENDING → REJECTED)
  - Set rejection_reason

**Key Logic**:
```php
public function approve(Request $request, $id)
{
    $mir = MaterialIssueRequest::findOrFail($id);
    
    if ($mir->status !== 'PENDING') {
        return response()->json(['success' => false, 'message' => 'Invalid status'], 422);
    }
    
    // Check all lines are APPROVED
    $unapprovedLines = $mir->lines()->whereNotIn('status', ['APPROVED'])->count();
    if ($unapprovedLines > 0) {
        return response()->json(['success' => false, 'message' => 'Not all lines approved'], 422);
    }
    
    $mir->update([
        'status' => 'APPROVED',
        'approved_at' => now(),
        'approved_by' => $request->input('auth_user_id'),
    ]);
    
    return response()->json(['success' => true, 'data' => $mir]);
}
```

### 3.3 Create `MIRLineController` (NEW)
- [ ] `show()` — Get line details with transaction history
- [ ] `approve()` — Approve line (PENDING → APPROVED)
- [ ] `reject()` — Reject line (PENDING → REJECTED)
- [ ] `issue()` — Issue material (partial or full)
  - Validate: issued_qty > 0 and <= remaining
  - Create transaction record
  - Update line status
  - Recalculate MIR header status

**Key Logic**:
```php
public function issue(Request $request, $id)
{
    $line = MIRLineItem::findOrFail($id);
    
    $validated = $request->validate([
        'issued_qty' => 'required|numeric|min:0.01',
        'notes' => 'nullable|string',
    ]);
    
    // Validate preconditions
    if (!in_array($line->status, ['APPROVED', 'PARTIALLY_PICKED'])) {
        return response()->json(['success' => false, 'message' => 'Line not ready for issue'], 422);
    }
    
    $remaining = $line->required_qty - $line->issued_qty;
    if ($validated['issued_qty'] > $remaining) {
        return response()->json(['success' => false, 'message' => 'Issued qty exceeds remaining'], 422);
    }
    
    // Create transaction
    MIRIssueTransaction::create([
        'mir_line_id' => $line->id,
        'issued_qty' => $validated['issued_qty'],
        'issued_by' => $request->input('auth_user_id'),
        'issued_at' => now(),
        'notes' => $validated['notes'],
    ]);
    
    // Update line
    $line->issued_qty += $validated['issued_qty'];
    $line->updateStatus();
    
    // Recalculate MIR header status
    $mir = $line->mir;
    $mir->status = $mir->deriveHeaderStatus();
    if ($mir->status === 'FULLY_ISSUED') {
        $mir->fully_issued_at = now();
    }
    $mir->save();
    
    return response()->json(['success' => true, 'data' => $line]);
}
```

### 3.4 Create `BatchRunReceivingController` (NEW)
- [ ] `show()` — Get receiving status with MIR details
- [ ] `confirm()` — Confirm receipt (PENDING_RECEIPT → RECEIVED)
  - Validate: MIR FULLY_ISSUED
  - Set received_at, received_by, receiving_notes
  - Update MIR header to CLOSED

**Key Logic**:
```php
public function confirm(Request $request, $batchRunId)
{
    $batchRun = ProductionBatchRun::findOrFail($batchRunId);
    
    $validated = $request->validate([
        'receiving_notes' => 'nullable|string',
    ]);
    
    // Validate preconditions
    if ($batchRun->mir?->status !== 'FULLY_ISSUED') {
        return response()->json(['success' => false, 'message' => 'MIR not fully issued'], 422);
    }
    
    if ($batchRun->receiving_status !== 'PENDING_RECEIPT') {
        return response()->json(['success' => false, 'message' => 'Invalid receiving status'], 422);
    }
    
    $batchRun->update([
        'receiving_status' => 'RECEIVED',
        'received_at' => now(),
        'received_by' => $request->input('auth_user_id'),
        'receiving_notes' => $validated['receiving_notes'],
    ]);
    
    // Close MIR
    $batchRun->mir->update([
        'status' => 'CLOSED',
        'closed_at' => now(),
    ]);
    
    return response()->json(['success' => true, 'data' => $batchRun]);
}
```

### 3.5 Create `FGReceiptController` (NEW)
- [ ] `store()` — Create FG receipt
  - Validate: batch_run COMPLETED
  - Calculate accepted_qty and yield_percent
  - Create lot_number
- [ ] `show()` — Get FG receipt details

**Key Logic**:
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'batch_run_id' => 'required|exists:production_batch_runs,id',
        'received_qty' => 'required|numeric|min:0.01',
        'rejected_qty' => 'required|numeric|min:0',
        'lot_number' => 'required|string|unique:fg_receipts,lot_number',
    ]);
    
    $batchRun = ProductionBatchRun::findOrFail($validated['batch_run_id']);
    
    if ($batchRun->status !== 'COMPLETED') {
        return response()->json(['success' => false, 'message' => 'Batch run not completed'], 422);
    }
    
    $acceptedQty = $validated['received_qty'] - $validated['rejected_qty'];
    $yieldPercent = ($acceptedQty / $batchRun->run_qty) * 100;
    
    $receipt = FGReceipt::create([
        'batch_run_id' => $validated['batch_run_id'],
        'planned_qty' => $batchRun->run_qty,
        'received_qty' => $validated['received_qty'],
        'rejected_qty' => $validated['rejected_qty'],
        'accepted_qty' => $acceptedQty,
        'yield_actual_pct' => $yieldPercent,
        'lot_number' => $validated['lot_number'],
        'created_by' => $request->input('auth_user_id'),
    ]);
    
    return response()->json(['success' => true, 'data' => $receipt], 201);
}
```

### 3.6 Update `ProductionOrderController`
- [ ] Update `release()` — DRAFT → RELEASED
- [ ] Update `close()` — IN_PROGRESS → CLOSED
- [ ] Add validation for batch run completion

---

## Phase 4: Testing

### 4.1 Unit Tests
- [ ] Test batch run creation and MIR auto-generation
- [ ] Test MIR line status transitions
- [ ] Test MIR header status derivation
- [ ] Test partial issuance logic
- [ ] Test production floor receiving gate
- [ ] Test FG receipt creation and yield calculation

### 4.2 Integration Tests
- [ ] Test complete happy path workflow
- [ ] Test partial issuance scenario
- [ ] Test rejection scenario
- [ ] Test receiving discrepancy handling
- [ ] Test error scenarios (invalid transitions, missing preconditions)

### 4.3 Manual Testing
- [ ] Create production order and batch runs
- [ ] Verify MIR auto-generation
- [ ] Test Store approval and partial issuance
- [ ] Test production floor receiving
- [ ] Test batch run start/complete
- [ ] Test FG receipt creation

---

## Phase 5: Documentation & Deployment

### 5.1 API Documentation
- [ ] Document all endpoints with examples
- [ ] Document status flows and transitions
- [ ] Document error codes and messages
- [ ] Document business rules and constraints

### 5.2 User Documentation
- [ ] Create user guide for production team
- [ ] Create user guide for store team
- [ ] Create troubleshooting guide
- [ ] Create FAQ

### 5.3 Deployment
- [ ] Run migrations in staging
- [ ] Run migrations in production
- [ ] Deploy code changes
- [ ] Verify all endpoints working
- [ ] Monitor for errors

---

## Phase 6: Post-Deployment

### 6.1 Monitoring
- [ ] Monitor API response times
- [ ] Monitor error rates
- [ ] Monitor database performance
- [ ] Monitor user adoption

### 6.2 Optimization
- [ ] Add database indexes if needed
- [ ] Optimize queries if needed
- [ ] Cache frequently accessed data
- [ ] Profile and optimize slow endpoints

### 6.3 Feedback & Iteration
- [ ] Collect user feedback
- [ ] Identify pain points
- [ ] Plan improvements
- [ ] Schedule follow-up enhancements

---

## Rollback Plan

If issues arise:
1. Revert code changes
2. Revert database migrations (create down migrations)
3. Notify users of rollback
4. Investigate root cause
5. Plan fix and re-deployment

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
