@extends('tenant.layouts.app')

@section('title', 'Stock Management Dashboard')

@section('content')
<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-boxes me-2"></i>Stock Management Dashboard
                    </h2>
                    <p class="text-muted mb-0">View and manage raw materials stock across all warehouses</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="refreshAllData()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Warehouse Selection & Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Select Warehouse</label>
                    <select class="form-select" id="warehouseFilter" onchange="loadWarehouseData()">
                        <option value="">All Warehouses</option>
                        @if(isset($warehouses) && $warehouses->count() > 0)
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }} ({{ $warehouse->warehouse_code }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search Material</label>
                    <input type="text" class="form-control" id="materialSearch" placeholder="Material code or name..." onkeyup="filterMaterials()">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bucket Filter</label>
                    <select class="form-select" id="bucketFilter" onchange="filterByBucket()">
                        <option value="">All Buckets</option>
                        <option value="AVAILABLE">Available</option>
                        <option value="QC_HOLD">QC Hold</option>
                        <option value="PUTAWAY_PENDING">Putaway Pending</option>
                        <option value="RESERVED">Reserved</option>
                        <option value="BLOCKED">Blocked</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Status</label>
                    <select class="form-select" id="stockStatusFilter" onchange="filterByStatus()">
                        <option value="all">All Stock</option>
                        <option value="in_stock">In Stock (>0)</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-success w-100" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4" id="summaryCards">
        <div class="col-md-3">
            <div class="card bg-gradient-primary h-100">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2">Total Materials</h6>
                            <h2 class="mb-0" id="totalMaterials">0</h2>
                        </div>
                        <i class="fas fa-boxes fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-success h-100">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2">Available Stock</h6>
                            <h2 class="mb-0" id="totalAvailable">0</h2>
                            <small class="opacity-75">Ready to use</small>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-warning h-100">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2">QC Hold</h6>
                            <h2 class="mb-0" id="totalQcHold">0</h2>
                            <small class="opacity-75">Awaiting inspection</small>
                        </div>
                        <i class="fas fa-flask fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gradient-info h-100">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2">Putaway Pending</h6>
                            <h2 class="mb-0" id="totalPutawayPending">0</h2>
                            <small class="opacity-75">On forklift/staging</small>
                        </div>
                        <i class="fas fa-dolly fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Stock Table -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Raw Materials Stock Details</h5>
                <span class="badge bg-primary" id="materialCount">0 materials</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="stockTable">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Material Code</th>
                            <th>Material Name</th>
                            <th>Type</th>
                            <th>UOM</th>
                            <th class="text-center">On Hand</th>
                            <th class="text-center">Available</th>
                            <th class="text-center">QC Hold</th>
                            <th class="text-center">Putaway Pending</th>
                            <th class="text-center">Reserved</th>
                            <th class="text-center">Blocked</th>
                            <th>Primary Location</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                        <tr>
                            <td colspan="13" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3 text-muted">Loading stock data...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stock by Bucket Breakdown -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Stock Distribution by Bucket</h5>
                </div>
                <div class="card-body">
                    <canvas id="bucketChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Top 10 Materials by Stock Value</h5>
                </div>
                <div class="card-body">
                    <canvas id="topMaterialsChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Stock Modal -->
    <div class="modal fade" id="stockDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-search-plus me-2"></i>Stock Details - <span id="modalMaterialName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Material Info -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Material Code:</strong>
                                            <p class="mb-0" id="modalMaterialCode">-</p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Material Type:</strong>
                                            <p class="mb-0" id="modalMaterialType">-</p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>UOM:</strong>
                                            <p class="mb-0" id="modalMaterialUom">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="printStockDetails()">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#warehouse-tab">Warehouse Breakdown</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#bin-tab">Bin Locations</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#history-tab">Transaction History</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Warehouse Tab -->
                        <div class="tab-pane fade show active" id="warehouse-tab">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Warehouse</th>
                                            <th class="text-end">On Hand</th>
                                            <th class="text-end">Available</th>
                                            <th class="text-end">QC Hold</th>
                                            <th class="text-end">Putaway Pending</th>
                                            <th class="text-end">Reserved</th>
                                            <th class="text-end">Blocked</th>
                                        </tr>
                                    </thead>
                                    <tbody id="warehouseBreakdownBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bin Locations Tab -->
                        <div class="tab-pane fade" id="bin-tab">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Bin Code</th>
                                            <th>Warehouse</th>
                                            <th>Bucket</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Reserved</th>
                                            <th class="text-end">Available</th>
                                        </tr>
                                    </thead>
                                    <tbody id="binBreakdownBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- History Tab -->
                        <div class="tab-pane fade" id="history-tab">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Transaction Type</th>
                                            <th>Reference</th>
                                            <th>Warehouse</th>
                                            <th class="text-end">Change</th>
                                            <th class="text-end">Balance</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactionHistoryBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    .badge {
        padding: 0.35em 0.65em;
        font-weight: 600;
    }
    
    .stock-badge {
        min-width: 80px;
    }
    
    .action-btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .opacity-50 {
        opacity: 0.5 !important;
    }
    
    /* Toast Notification Styles */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
    
    .toast {
        min-width: 300px;
        margin-bottom: 10px;
    }
</style>
@endsection

@section('scripts')
<script>
// Global variables
let allStockData = [];
let warehouseData = {};
let bucketChartInstance = null;
let topMaterialsChartInstance = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
    }
    loadAllStockData();
});

// Load all stock data
async function loadAllStockData() {
    try {
        const warehouseId = document.getElementById('warehouseFilter').value;
        const url = warehouseId 
            ? `/api/v1/stock/warehouse/${warehouseId}`
            : '/api/v1/stock/warehouse/all';
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('access_token')}`
            }
        });
        
        if (!response.ok) throw new Error('Failed to load stock data');
        
        const result = await response.json();
        
        if (result.success) {
            allStockData = result.data || [];
            updateSummaryCards();
            renderStockTable();
            updateCharts();
        }
    } catch (error) {
        console.error('Error loading stock data:', error);
        showToast('Error loading stock data', 'error');
    }
}

// Update summary cards
function updateSummaryCards() {
    const totalMaterials = allStockData.length;
    const totalAvailable = allStockData.reduce((sum, item) => sum + (parseFloat(item.available) || 0), 0);
    const totalQcHold = allStockData.reduce((sum, item) => sum + (parseFloat(item.qc_hold) || 0), 0);
    const totalPutawayPending = allStockData.reduce((sum, item) => sum + (parseFloat(item.putaway_pending) || 0), 0);
    
    document.getElementById('totalMaterials').textContent = totalMaterials.toLocaleString();
    document.getElementById('totalAvailable').textContent = totalAvailable.toLocaleString(2);
    document.getElementById('totalQcHold').textContent = totalQcHold.toLocaleString(2);
    document.getElementById('totalPutawayPending').textContent = totalPutawayPending.toLocaleString(2);
}

// Render stock table
function renderStockTable(data = allStockData) {
    const tbody = document.getElementById('stockTableBody');
    
    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="13" class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No stock data found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = data.map((item, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${escapeHtml(item.material_code)}</strong></td>
            <td>${escapeHtml(item.material_name)}</td>
            <td>${escapeHtml(item.type || 'Raw Material')}</td>
            <td>${escapeHtml(item.uom || '-')}</td>
            <td class="text-center"><span class="badge bg-secondary stock-badge">${formatNumber(item.on_hand)}</span></td>
            <td class="text-center"><span class="badge bg-success stock-badge">${formatNumber(item.available)}</span></td>
            <td class="text-center"><span class="badge bg-warning stock-badge">${formatNumber(item.qc_hold)}</span></td>
            <td class="text-center"><span class="badge bg-info stock-badge">${formatNumber(item.putaway_pending)}</span></td>
            <td class="text-center"><span class="badge bg-primary stock-badge">${formatNumber(item.reserved)}</span></td>
            <td class="text-center"><span class="badge bg-danger stock-badge">${formatNumber(item.blocked)}</span></td>
            <td>${getPrimaryLocation(item)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary action-btn" onclick="viewStockDetails(${item.material_id})" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-info action-btn ms-1" onclick="viewHistory(${item.material_id})" title="View History">
                    <i class="fas fa-history"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    document.getElementById('materialCount').textContent = `${data.length} materials`;
}

// View stock details in modal
async function viewStockDetails(materialId) {
    try {
        const response = await fetch(`/api/v1/stock/snapshot/${materialId}`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('access_token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Update modal header
            document.getElementById('modalMaterialName').textContent = data.material_name || `Material #${materialId}`;
            document.getElementById('modalMaterialCode').textContent = data.material_code || '-';
            document.getElementById('modalMaterialType').textContent = data.material_type || '-';
            document.getElementById('modalMaterialUom').textContent = data.uom || '-';
            
            // Update warehouse breakdown
            updateWarehouseBreakdown(data.by_warehouse || []);
            
            // Update bin breakdown
            updateBinBreakdown(data.by_bin || []);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('stockDetailModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error loading stock details:', error);
        showToast('Error loading stock details', 'error');
    }
}

// Update warehouse breakdown table
function updateWarehouseBreakdown(warehouses) {
    const tbody = document.getElementById('warehouseBreakdownBody');
    
    if (!warehouses || warehouses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No warehouse data</td></tr>';
        return;
    }
    
    tbody.innerHTML = warehouses.map(wh => `
        <tr>
            <td><strong>${escapeHtml(wh.warehouse_name)}</strong></td>
            <td class="text-end">${formatNumber(wh.on_hand)}</td>
            <td class="text-end">${formatNumber(wh.available)}</td>
            <td class="text-end">${formatNumber(wh.qc_hold)}</td>
            <td class="text-end">${formatNumber(wh.putaway_pending)}</td>
            <td class="text-end">${formatNumber(wh.reserved)}</td>
            <td class="text-end">${formatNumber(wh.blocked)}</td>
        </tr>
    `).join('');
}

// Update bin breakdown table
function updateBinBreakdown(bins) {
    const tbody = document.getElementById('binBreakdownBody');
    
    if (!bins || Object.keys(bins).length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No bin location data</td></tr>';
        return;
    }
    
    tbody.innerHTML = Object.values(bins).map(bin => `
        <tr>
            <td><strong>${escapeHtml(bin.bin_code)}</strong></td>
            <td>${escapeHtml(bin.warehouse)}</td>
            <td><span class="badge bg-${getBucketColor(bin.bucket)}">${bin.bucket}</span></td>
            <td class="text-end">${formatNumber(bin.qty)}</td>
            <td class="text-end">${formatNumber(bin.reserved)}</td>
            <td class="text-end">${formatNumber(bin.available)}</td>
        </tr>
    `).join('');
}

// Load transaction history
async function viewHistory(materialId) {
    try {
        const response = await fetch(`/api/v1/stock/history/${materialId}?limit=50`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('access_token')}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('transactionHistoryBody');
            const transactions = result.data || [];
            
            if (transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No transaction history</td></tr>';
                return;
            }
            
            tbody.innerHTML = transactions.map(txn => `
                <tr>
                    <td>${formatDate(txn.transaction_date)}</td>
                    <td><span class="badge bg-${getTransactionTypeColor(txn.transaction_type)}">${txn.transaction_type}</span></td>
                    <td>${escapeHtml(txn.reference_number || '-')}</td>
                    <td>${escapeHtml(txn.warehouse_name || '-')}</td>
                    <td class="text-end ${txn.change_qty > 0 ? 'text-success' : 'text-danger'}">
                        ${txn.change_qty > 0 ? '+' : ''}${formatNumber(txn.change_qty)}
                    </td>
                    <td class="text-end"><strong>${formatNumber(txn.balance_qty)}</strong></td>
                    <td>${escapeHtml(txn.remarks || '')}</td>
                </tr>
            `).join('');
            
            // Switch to history tab
            const tabTrigger = new bootstrap.Tab(document.querySelector('[data-bs-target="#history-tab"]'));
            tabTrigger.show();
        }
    } catch (error) {
        console.error('Error loading history:', error);
        showToast('Error loading transaction history', 'error');
    }
}

// Update charts
function updateCharts() {
    updateBucketChart();
    updateTopMaterialsChart();
}

// Update bucket distribution chart
function updateBucketChart() {
    const ctx = document.getElementById('bucketChart').getContext('2d');
    
    const buckets = {
        'AVAILABLE': 0,
        'QC_HOLD': 0,
        'PUTAWAY_PENDING': 0,
        'RESERVED': 0,
        'BLOCKED': 0
    };
    
    allStockData.forEach(item => {
        buckets['AVAILABLE'] += parseFloat(item.available) || 0;
        buckets['QC_HOLD'] += parseFloat(item.qc_hold) || 0;
        buckets['PUTAWAY_PENDING'] += parseFloat(item.putaway_pending) || 0;
        buckets['RESERVED'] += parseFloat(item.reserved) || 0;
        buckets['BLOCKED'] += parseFloat(item.blocked) || 0;
    });
    
    if (bucketChartInstance) {
        bucketChartInstance.destroy();
    }
    
    bucketChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(buckets),
            datasets: [{
                data: Object.values(buckets),
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${label}: ${value.toLocaleString(2)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Update top materials chart
function updateTopMaterialsChart() {
    const ctx = document.getElementById('topMaterialsChart').getContext('2d');
    
    // Get top 10 materials by available stock
    const topMaterials = [...allStockData]
        .sort((a, b) => (parseFloat(b.available) || 0) - (parseFloat(a.available) || 0))
        .slice(0, 10);
    
    if (topMaterialsChartInstance) {
        topMaterialsChartInstance.destroy();
    }
    
    topMaterialsChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: topMaterials.map(m => m.material_code),
            datasets: [{
                label: 'Available Stock',
                data: topMaterials.map(m => parseFloat(m.available) || 0),
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Available: ${context.parsed.y.toLocaleString(2)}`;
                        }
                    }
                }
            }
        }
    });
}

// Filter functions
function filterMaterials() {
    const searchTerm = document.getElementById('materialSearch').value.toLowerCase();
    const filtered = allStockData.filter(item => 
        item.material_code.toLowerCase().includes(searchTerm) ||
        item.material_name.toLowerCase().includes(searchTerm)
    );
    renderStockTable(filtered);
}

function filterByBucket() {
    const bucket = document.getElementById('bucketFilter').value;
    if (!bucket) {
        renderStockTable(allStockData);
        return;
    }
    
    const filtered = allStockData.filter(item => {
        const bucketValue = parseFloat(item[bucket.toLowerCase().replace('_hold', '_hold')]) || 0;
        return bucketValue > 0;
    });
    renderStockTable(filtered);
}

function filterByStatus() {
    const status = document.getElementById('stockStatusFilter').value;
    let filtered;
    
    switch(status) {
        case 'in_stock':
            filtered = allStockData.filter(item => (parseFloat(item.on_hand) || 0) > 0);
            break;
        case 'low_stock':
            filtered = allStockData.filter(item => {
                const available = parseFloat(item.available) || 0;
                return available > 0 && available < 10;
            });
            break;
        case 'out_of_stock':
            filtered = allStockData.filter(item => (parseFloat(item.on_hand) || 0) === 0);
            break;
        default:
            filtered = allStockData;
    }
    
    renderStockTable(filtered);
}

// Utility functions
function formatNumber(num) {
    return (parseFloat(num) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getPrimaryLocation(item) {
    // This would need to be populated from additional API data
    return 'Multiple locations';
}

function getBucketColor(bucket) {
    const colors = {
        'AVAILABLE': 'success',
        'QC_HOLD': 'warning',
        'PUTAWAY_PENDING': 'info',
        'RESERVED': 'primary',
        'BLOCKED': 'danger'
    };
    return colors[bucket] || 'secondary';
}

function getTransactionTypeColor(type) {
    const colors = {
        'GRN': 'success',
        'PUTAWAY': 'info',
        'MATERIAL_ISSUE': 'warning',
        'ADJUSTMENT': 'secondary',
        'RETURN': 'primary',
        'TRANSFER': 'dark'
    };
    return colors[type] || 'secondary';
}

function refreshAllData() {
    loadAllStockData();
    showToast('Data refreshed successfully', 'success');
}

function exportToExcel() {
    // Simple CSV export
    const headers = ['Material Code', 'Material Name', 'Type', 'UOM', 'On Hand', 'Available', 'QC Hold', 'Putaway Pending', 'Reserved', 'Blocked'];
    const rows = allStockData.map(item => [
        item.material_code,
        item.material_name,
        item.type || 'Raw Material',
        item.uom || '-',
        item.on_hand,
        item.available,
        item.qc_hold,
        item.putaway_pending,
        item.reserved,
        item.blocked
    ]);
    
    const csv = [headers, ...rows].map(row => row.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `stock_report_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    showToast('Export completed successfully', 'success');
}

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const bgClass = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    }[type] || 'bg-info';
    
    const icon = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-triangle',
        'warning': 'fa-exclamation-circle',
        'info': 'fa-info-circle'
    }[type] || 'fa-info-circle';
    
    const toastHTML = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
            <div class="toast-body d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas ${icon} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // Auto-remove after hide
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function printStockDetails() {
    window.print();
}
</script>
@endsection
