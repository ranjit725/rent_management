<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<?php
require_once 'config/master.php';
$stats = getDashboardStats();
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-4">
                <div class="card border-left-warning shadow h-100">
    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
        <h6 class="m-0 font-weight-bold text-warning">Pending Meter Readings</h6>
        <span class="badge bg-warning text-dark"><?= count($stats['pending_readings']) ?> Missing</span>
    </div>
    <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
        <?php if (empty($stats['pending_readings'])): ?>
            <div class="p-5 text-center text-success">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <p class="mb-0 fw-bold">All caught up!</p>
                <small class="text-muted">Every active meter has a reading for <?= date('F') ?>.</small>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($stats['pending_readings'] as $meter): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom px-4 py-3">
                        <div>
                            <div class="small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($meter['building_name']) ?>
                            </div>
                            <div class="text-gray-800 fw-bold"><?= htmlspecialchars($meter['meter_name']) ?></div>
                        </div>
                        <a href="meter_readings.php?meter_id=<?= $meter['id'] ?? '' ?>" class="btn btn-sm btn-warning shadow-sm">
                            <i class="fas fa-plus fa-sm me-1"></i> Add
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
            </div>

            <div class="col-xl-8">
                <div class="card border-left-danger shadow h-100">
    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
        <h6 class="m-0 font-weight-bold text-danger">Unpaid Invoices & Defaulters</h6>
        <span class="badge bg-danger rounded-pill"><?= count($stats['unpaid_highlights']) ?> Active Debtors</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="border-0 px-4">Tenant</th>
                        <th class="border-0">Debt Breakdown</th>
                        <th class="border-0">Total Due</th>
                        <th class="border-0 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats['unpaid_highlights'])): ?>
                        <tr><td colspan="4" class="text-center p-4 text-success">No pending payments!</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['unpaid_highlights'] as $u): ?>
                            <tr>
                                <td class="px-4">
                                    <div class="fw-bold text-gray-800"><?= htmlspecialchars($u['name']) ?></div>
                                    <small class="text-muted">ID: #<?= $u['tenant_id'] ?></small>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <span class="badge bg-light text-dark border">Rent: <?= $u['rent_status']['total'] ?></span>
                                        <?php if(!empty($u['rent_status']['formula_with_sums'])): ?>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;"><?= $u['rent_status']['formula_with_sums'] ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="badge bg-light text-dark border">Elec: <?= $u['elec_status']['total'] ?></span>
                                        <?php if(!empty($u['elec_status']['formula_with_sums'])): ?>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;"><?= strip_tags($u['elec_status']['formula_with_sums']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-danger fw-bold" style="font-size: 1.1rem;">
                                        ₹<?= number_format($u['total_due'], 2) ?>
                                    </div>
                                    <?php if($u['total_due'] > 10000): ?>
                                        <span class="badge bg-danger p-1" style="font-size: 0.6rem;">CRITICAL</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="billing.php?id=<?= $u['tenant_id'] ?>" class="btn btn-sm btn-outline-danger">Ledger</a>
                                        <a href="generate_bill.php?tenant_id=<?= $u['tenant_id'] ?>" class="btn btn-sm btn-danger"><i class="fas fa-plus"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-left-primary shadow py-2 h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Buildings</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_buildings'] ?></div>
                            </div>
                            <div class="col-auto"><i class="fas fa-building fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-left-info shadow py-2 h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Units</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_units'] ?></div>
                            </div>
                            <div class="col-auto"><i class="fas fa-layer-group fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-left-success shadow py-2 h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Tenants</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_tenants'] ?></div>
                            </div>
                            <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-left-warning shadow py-2 h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Occupancy Rate</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['occupancy_rate'] ?>%</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-chart-pie fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-primary">Financial Summary (Current Month)</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 border-right">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Collected</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">₹<?= number_format($stats['monthly_revenue'], 2) ?></div>
                            </div>
                            <div class="col-6">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Pending</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">₹<?= number_format($stats['pending_payments'], 2) ?></div>
                            </div>
                        </div>
                        <hr>
                        <div class="chart-pie pt-4 pb-2">
                            <canvas id="occupancyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($stats['recent_activity'])): ?>
                            <p class="p-4 text-center text-muted">No recent activity to show.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <tbody>
                                        <?php foreach ($stats['recent_activity'] as $activity): ?>
                                            <tr>
                                                <td class="px-4">
                                                    <div class="fw-bold"><?= htmlspecialchars($activity['tenant_name']) ?></div>
                                                    <small class="text-muted"><?= date('M Y', strtotime($activity['billing_month'])) ?></small>
                                                </td>
                                                <td>₹<?= number_format($activity['total_amount'], 2) ?></td>
                                                <td>
                                                    <?php if($activity['balance'] <= 0): ?>
                                                        <span class="badge rounded-pill bg-success">Paid</span>
                                                    <?php else: ?>
                                                        <span class="badge rounded-pill bg-danger-subtle text-danger">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Animation for chronic defaulters */
@keyframes pulse-red {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}
.animate-pulse {
    animation: pulse-red 2s infinite;
}
.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
.border-left-danger { border-left: 0.25rem solid #e74a3b !important; }
.bg-danger-subtle { background-color: #f8d7da; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById("occupancyChart");
        
        // Ensure the library loaded and the element exists
        if (ctx && typeof Chart !== 'undefined') {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ["Occupied", "Vacant"],
                    datasets: [{
                        // Casting to integers to prevent JS errors
                        data: [<?= (int)$stats['occupied_units'] ?>, <?= (int)($stats['total_units'] - $stats['occupied_units']) ?>],
                        backgroundColor: ['#1cc88a', '#e3e6f0'],
                        hoverBackgroundColor: ['#17a673', '#d2d6e0'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutoutPercentage: 70,
                    legend: { display: true, position: 'bottom' }
                },
            });
        } else {
            console.error("Chart.js failed to load from CDN.");
        }
    });
</script>

<?php include 'components/footer.php'; ?>