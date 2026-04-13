<?php
$file = 'c:/xampp/htdocs/ERP/ERP/routes/web.php';
$content = file_get_contents($file);

$startMarker = "        // ====================================================================\n        // MAINTENANCE PORTAL\n        // ====================================================================\n        Route::prefix('maintenance')->middleware(['check.module.permission:MAINTENANCE'])->name('maintenance.')->group(function () use (\$getOrg) {\n";
$endMarker = "        // OTHER PAGES\n        // ====================================================================";

$startPos = strpos($content, $startMarker);
if ($startPos === false) {
    die("Start marker not found.");
}

$endPos = strpos($content, $endMarker, $startPos);
if ($endPos === false) {
    die("End marker not found.");
}

$replacement = <<<EOT
        // ====================================================================
        // MAINTENANCE PORTAL
        // ====================================================================
        Route::prefix('maintenance')->middleware(['check.module.permission:MAINTENANCE'])->name('maintenance.')->group(function () {
            
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Maintenance\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/work-orders-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'workOrdersJson'])->name('dashboard.work-orders-json');
            Route::get('/dashboard/assets-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'assetsJson'])->name('dashboard.assets-json');
            Route::get('/dashboard/pm-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'pmJson'])->name('dashboard.pm-json');
            Route::get('/dashboard/low-stock-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'lowStockJson'])->name('dashboard.low-stock-json');
            Route::get('/dashboard/material-requests-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'materialRequestsJson'])->name('dashboard.material-requests-json');
            Route::get('/dashboard/requests-json', [\App\Http\Controllers\Maintenance\DashboardController::class, 'requestsJson'])->name('dashboard.requests-json');

            // Requests
            Route::get('/requests', [\App\Http\Controllers\Maintenance\RequestController::class, 'index'])->name('requests');
            Route::post('/requests', [\App\Http\Controllers\Maintenance\RequestController::class, 'store'])->name('requests.store');

            // Approvals
            Route::get('/approvals', [\App\Http\Controllers\Maintenance\ApprovalController::class, 'index'])->name('approvals');
            Route::post('/approvals/{id}/approve', [\App\Http\Controllers\Maintenance\ApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('/approvals/{id}/reject', [\App\Http\Controllers\Maintenance\ApprovalController::class, 'reject'])->name('approvals.reject');

            // Assignments
            Route::get('/assignments', [\App\Http\Controllers\Maintenance\AssignmentController::class, 'index'])->name('assignments');
            Route::post('/assignments', [\App\Http\Controllers\Maintenance\AssignmentController::class, 'store'])->name('assignments.store');
            Route::post('/assignments/{wo}/update-status', [\App\Http\Controllers\Maintenance\AssignmentController::class, 'updateStatus'])->name('assignments.update-status');

            // Work Orders
            Route::get('/work-orders', [\App\Http\Controllers\Maintenance\WorkOrderController::class, 'index'])->name('work-orders');

            // Material Requests
            Route::get('/material-requests', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'index'])->name('material-requests');
            Route::post('/material-requests', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'store'])->name('material-requests.store');
            Route::post('/material-requests/{id}/issue', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'issue'])->name('material-requests.issue');
            Route::post('/material-requests/{id}/raise-po', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'raisePo'])->name('material-requests.raise-po');
            Route::post('/material-requests/raise-po-direct', [\App\Http\Controllers\Maintenance\MaterialRequestController::class, 'raisePoDirect'])->name('material-requests.raise-po-direct');

            // Closure
            Route::get('/closure', [\App\Http\Controllers\Maintenance\ClosureController::class, 'index'])->name('closure');
            Route::post('/closure/{wo}/close', [\App\Http\Controllers\Maintenance\ClosureController::class, 'close'])->name('closure.close');

            // Assets
            Route::get('/assets', [\App\Http\Controllers\Maintenance\AssetController::class, 'index'])->name('assets');
            Route::post('/assets', [\App\Http\Controllers\Maintenance\AssetController::class, 'store'])->name('assets.store');

            // Schedule
            Route::get('/schedule', [\App\Http\Controllers\Maintenance\ScheduleController::class, 'index'])->name('schedule');
            Route::post('/schedule', [\App\Http\Controllers\Maintenance\ScheduleController::class, 'store'])->name('schedule.store');
            Route::post('/schedule/{id}/done', [\App\Http\Controllers\Maintenance\ScheduleController::class, 'done'])->name('schedule.done');

            // Spare Parts
            Route::get('/spare-parts', [\App\Http\Controllers\Maintenance\SparePartController::class, 'index'])->name('spare-parts');
            Route::post('/spare-parts', [\App\Http\Controllers\Maintenance\SparePartController::class, 'store'])->name('spare-parts.store');
            Route::post('/spare-parts/{code}/issue', [\App\Http\Controllers\Maintenance\SparePartController::class, 'issue'])->name('spare-parts.issue');
            Route::post('/spare-parts/{code}/receive', [\App\Http\Controllers\Maintenance\SparePartController::class, 'receive'])->name('spare-parts.receive');

            // Procurement
            Route::get('/procurement', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'index'])->name('procurement');
            Route::get('/procurement/orders-json', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'ordersJson'])->name('procurement.orders-json');
            Route::post('/procurement', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'store'])->name('procurement.store');
            Route::post('/procurement/{id}/mark-ordered', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'markOrdered'])->name('procurement.mark-ordered');
            Route::post('/procurement/{id}/receive', [\App\Http\Controllers\Maintenance\ProcurementController::class, 'receive'])->name('procurement.receive');

            // Stock Management
            Route::get('/stock-management', [\App\Http\Controllers\Maintenance\StockManagementController::class, 'index'])->name('stock-management');
            Route::post('/stock-management/{id}/adjust', [\App\Http\Controllers\Maintenance\StockManagementController::class, 'adjust'])->name('stock-management.adjust');
        });

        // ====================================================================
EOT;

$newContent = substr_replace($content, $replacement, $startPos, $endPos - $startPos);
file_put_contents($file, $newContent);
echo "web.php updated successfully.";
