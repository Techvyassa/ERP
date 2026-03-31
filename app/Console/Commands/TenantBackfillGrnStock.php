<?php

namespace App\Console\Commands;

use App\Contracts\DatabaseConnectionRouter;
use App\Models\Control\Organization;
use App\Models\Tenant\GRN;
use App\Models\Tenant\InspectionLot;
use App\Models\Tenant\InventoryTransaction;
use App\Models\Tenant\PutawayTask;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantBackfillGrnStock extends Command
{
    protected $signature = 'tenant:backfill-grn-stock {org_slug : The organization slug}
                            {--grn_id= : Backfill only one GRN id}
                            {--dry-run : Show what would be written without writing anything}';

    protected $description = 'Backfill missing inventory_transactions and stock_balances for legacy tenant GRNs';

    public function handle(DatabaseConnectionRouter $connectionRouter, StockService $stockService): int
    {
        $orgSlug = $this->argument('org_slug');
        $grnId = $this->option('grn_id');
        $dryRun = (bool) $this->option('dry-run');

        $organization = Organization::where('org_slug', $orgSlug)->first();
        if (!$organization) {
            $this->error("Organization not found: {$orgSlug}");
            return Command::FAILURE;
        }

        if (!$organization->tenant_db_name) {
            $this->error('Organization does not have a tenant database configured.');
            return Command::FAILURE;
        }

        $this->info("Backfilling tenant stock for: {$organization->org_name}");
        $this->line("Database: {$organization->tenant_db_name}");
        $this->line('Mode: ' . ($dryRun ? 'DRY RUN' : 'WRITE'));
        $this->newLine();

        try {
            $connectionRouter->switchToTenant($organization->tenant_db_name);

            $query = GRN::with([
                'purchaseOrder',
                'lineItems.warehouseBin',
            ])->orderBy('id');

            if ($grnId) {
                $query->whereKey($grnId);
            }

            $grns = $query->get();
            if ($grns->isEmpty()) {
                $this->warn('No GRNs found for backfill.');
                return Command::SUCCESS;
            }

            $summary = [
                'grn_receipt' => 0,
                'qc_pass' => 0,
                'qc_reject' => 0,
                'putaway_complete' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];

            foreach ($grns as $grn) {
                $this->line("GRN {$grn->id} ({$grn->grn_number})");

                foreach ($grn->lineItems as $line) {
                    if (!$line->material_id) {
                        $summary['skipped']++;
                        $this->line("  - line {$line->id}: skipped (no material_id)");
                        continue;
                    }

                    try {
                        $this->backfillGrnLine($grn, $line, $stockService, $dryRun, $summary);
                    } catch (\Throwable $e) {
                        $summary['failed']++;
                        $this->warn("  - line {$line->id}: {$e->getMessage()}");
                    }
                }
            }

            $this->newLine();
            $this->info('Backfill summary');
            $this->line("  GRN_RECEIPT: {$summary['grn_receipt']}");
            $this->line("  QC_PASS: {$summary['qc_pass']}");
            $this->line("  QC_REJECT: {$summary['qc_reject']}");
            $this->line("  PUTAWAY_COMPLETE: {$summary['putaway_complete']}");
            $this->line("  skipped: {$summary['skipped']}");
            $this->line("  failed: {$summary['failed']}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backfill failed: {$e->getMessage()}");
            return Command::FAILURE;
        } finally {
            $connectionRouter->switchToControl();
        }
    }

    private function backfillGrnLine(GRN $grn, $line, StockService $stockService, bool $dryRun, array &$summary): void
    {
        $warehouseId = $this->resolveWarehouseId($grn, $line);
        if (!$warehouseId) {
            throw new \Exception('warehouse could not be resolved');
        }

        $receivedQty = $this->resolveReceivedQty($line);
        if ($receivedQty <= 0) {
            $summary['skipped']++;
            $this->line("  - line {$line->id}: skipped (received quantity is 0)");
            return;
        }

        $actorId = $this->resolveActorId([
            $grn->created_by,
            $grn->approved_by,
        ]);

        $item = [
            'material_id' => $line->material_id,
            'uom_id' => $line->uom_id,
            'warehouse_id' => $warehouseId,
            'batch_number' => $line->batch_number,
        ];

        $existingReceipt = $this->sumTransactions(
            'GRN_RECEIPT',
            'GRN',
            $grn->id,
            'QC_HOLD',
            $line->material_id,
            $line->batch_number
        );
        $missingReceipt = round($receivedQty - $existingReceipt, 3);
        if ($missingReceipt > 0) {
            $this->line("  - line {$line->id}: backfill GRN_RECEIPT {$missingReceipt}");
            $summary['grn_receipt']++;

            if (!$dryRun) {
                $stockService->post(
                    $item,
                    'QC_HOLD',
                    $missingReceipt,
                    'GRN_RECEIPT',
                    'GRN',
                    $grn->id,
                    $grn->grn_number,
                    $actorId,
                    null,
                    $line->unit_price,
                    'Backfilled GRN receipt for legacy stock ledger'
                );
            }
        }

        $inspectionLot = InspectionLot::with('usageDecision')
            ->where('grn_line_id', $line->id)
            ->first();

        $acceptedQty = 0.0;
        $rejectedQty = 0.0;
        if ($inspectionLot?->usageDecision) {
            $acceptedQty = (float) $inspectionLot->usageDecision->accepted_qty;
            $rejectedQty = (float) $inspectionLot->usageDecision->rejected_qty;
            $actorId = $this->resolveActorId([
                $inspectionLot->usageDecision->decided_by,
                $actorId,
            ]);
        }

        $existingQcPass = $this->sumTransactions(
            'QC_PASS',
            'GRN',
            $grn->id,
            'PUTAWAY_PENDING',
            $line->material_id,
            $line->batch_number
        );
        $missingQcPass = round($acceptedQty - $existingQcPass, 3);
        if ($missingQcPass > 0) {
            $this->line("  - line {$line->id}: backfill QC_PASS {$missingQcPass}");
            $summary['qc_pass']++;

            if (!$dryRun) {
                $stockService->transfer(
                    $item,
                    'QC_HOLD',
                    'PUTAWAY_PENDING',
                    $missingQcPass,
                    'QC_PASS',
                    'GRN',
                    $grn->id,
                    $grn->grn_number,
                    $actorId,
                    null,
                    null,
                    $line->unit_price,
                    'Backfilled QC pass for legacy stock ledger'
                );
            }
        }

        $existingQcReject = $this->sumTransactions(
            'QC_REJECT',
            'GRN',
            $grn->id,
            'BLOCKED',
            $line->material_id,
            $line->batch_number
        );
        $missingQcReject = round($rejectedQty - $existingQcReject, 3);
        if ($missingQcReject > 0) {
            $this->line("  - line {$line->id}: backfill QC_REJECT {$missingQcReject}");
            $summary['qc_reject']++;

            if (!$dryRun) {
                $stockService->transfer(
                    $item,
                    'QC_HOLD',
                    'BLOCKED',
                    $missingQcReject,
                    'QC_REJECT',
                    'GRN',
                    $grn->id,
                    $grn->grn_number,
                    $actorId,
                    null,
                    null,
                    $line->unit_price,
                    'Backfilled QC reject for legacy stock ledger'
                );
            }
        }

        $completedTasks = PutawayTask::with(['putawayLines'])
            ->where('grn_line_id', $line->id)
            ->where('status', 'COMPLETED')
            ->get();

        foreach ($completedTasks as $task) {
            $taskQty = $task->putawayLines->sum(fn($putawayLine) => (float) $putawayLine->quantity);
            if ($taskQty <= 0) {
                $taskQty = (float) $task->quantity;
            }

            if ($taskQty <= 0) {
                continue;
            }

            if (!$task->destination_bin_id) {
                throw new \Exception("completed putaway task {$task->id} has no destination bin");
            }

            $existingPutaway = $this->sumTransactions(
                'PUTAWAY_COMPLETE',
                'PutawayTask',
                $task->id,
                'AVAILABLE',
                $line->material_id,
                $line->batch_number
            );
            $missingPutaway = round($taskQty - $existingPutaway, 3);
            if ($missingPutaway <= 0) {
                continue;
            }

            $this->line("  - line {$line->id}: backfill PUTAWAY_COMPLETE {$missingPutaway} via task {$task->id}");
            $summary['putaway_complete']++;

            if (!$dryRun) {
                $taskActorId = $this->resolveActorId([
                    $task->completed_by,
                    $task->assigned_to,
                    $actorId,
                ]);

                $stockService->transfer(
                    $item,
                    'PUTAWAY_PENDING',
                    'AVAILABLE',
                    $missingPutaway,
                    'PUTAWAY_COMPLETE',
                    'PutawayTask',
                    $task->id,
                    $task->task_number,
                    $taskActorId,
                    null,
                    $task->destination_bin_id,
                    $line->unit_price,
                    'Backfilled putaway completion for legacy stock ledger'
                );
            }
        }
    }

    private function resolveWarehouseId(GRN $grn, $line): ?int
    {
        return $grn->purchaseOrder?->warehouse_id
            ?? $line->warehouseBin?->warehouse_id
            ?? InspectionLot::query()
            ->where('grn_line_id', $line->id)
            ->value('warehouse_id')
            ?? InventoryTransaction::query()
            ->where('material_id', $line->material_id)
            ->where('reference_type', 'GRN')
            ->where('reference_id', $grn->id)
            ->where('batch_number', $line->batch_number)
            ->value('warehouse_id')
            ?? null;
    }

    private function resolveReceivedQty($line): float
    {
        $acceptedQty = (float) $line->accepted_qty;
        $rejectedQty = (float) $line->rejected_qty;
        $receivedQty = round($acceptedQty + $rejectedQty, 3);

        return $receivedQty > 0 ? $receivedQty : $acceptedQty;
    }

    private function sumTransactions(
        string $transactionType,
        string $referenceType,
        int $referenceId,
        string $bucket,
        int $materialId,
        ?string $batchNumber
    ): float {
        return (float) InventoryTransaction::query()
            ->where('transaction_type', $transactionType)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('bucket', $bucket)
            ->where('material_id', $materialId)
            ->where('batch_number', $batchNumber)
            ->where('qty_change', '>', 0)
            ->sum('qty_change');
    }

    private function resolveActorId(array $candidates): int
    {
        foreach ($candidates as $candidate) {
            if ($candidate) {
                return (int) $candidate;
            }
        }

        $firstUserId = DB::connection('tenant')->table('users')->min('id');
        if ($firstUserId) {
            return (int) $firstUserId;
        }

        throw new \Exception('no tenant user found to attribute backfill transactions');
    }
}
