<?php
require_once 'config/master.php';

// echo "<pre>";
// print_r(getTenantMonthlyStructure(1)); // Example call to generate monthly structure for tenant with ID 1
// exit; // Remove this exit after testing the function

// --- Controller Logic ---

// Handle form submission to process a payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['btn_save_fifo'])) {
    $result = processFIFOLedgerEntry($_POST);
    $_SESSION['msg'] = $result;
    header("Location: billing.php");
    exit;
}
}

// Handle request to delete a bill record
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $result = deleteBill((int)$_GET['id']);
    $_SESSION['flash_message'] = $result;
    header("Location: billing.php");
    exit;
}

// --- Data Fetching ---

// --- Data Fetching ---
$tenants = getActiveTenantsWithContext(); // Updated function call
$billing_records = getBills();
$message = $_SESSION['msg'] ?? null;
print_r($message);
unset($_SESSION['msg']);
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<!-- ... (header and sidebar includes) ... -->
<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h3 class="mb-4">Billing & Payments</h3>

        <!-- Flash Message -->
        <?php if ($message): ?>
    <?php 
        // If it's just a string, we assume it's a success message
        $is_array = is_array($message);
        $status = $is_array ? ($message['status'] ?? 'success') : 'success';
        $text = $is_array ? ($message['message'] ?? 'Operation successful!') : $message;
    ?>
    <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($text) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

        <div class="card shadow-sm mb-4 border-0">
    <div class="card-body">
        <h5 class="card-title mb-3 fw-bold text-primary">Quick Entry (FIFO)</h5>
        <form action="billing.php" method="POST" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Select Tenant</label>
                <select name="tenant_id" id="fifo_tenant_select" class="form-select border-primary-subtle" required>
                    <option value="">-- Choose Location & Tenant --</option>
                    <?php 
                    $contextTenants = getActiveTenantsWithContext();
                    foreach($contextTenants as $t): ?>
                        <option value="<?= $t['id'] ?>">
                            <?= htmlspecialchars($t['building_name']) ?> | <?= htmlspecialchars($t['unit_name']) ?> - <?= htmlspecialchars($t['tenant_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">Rent (₹)</label>
                <input type="number" step="0.01" name="rent_amount" class="form-control" placeholder="0.00">
                <div id="hint_rent" class="text-muted small mt-1 fw-bold"></div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Electricity (₹)</label>
                <input type="number" step="0.01" name="electricity_amount" class="form-control" placeholder="0.00">
                <div id="hint_elec" class="text-muted small mt-1 fw-bold"></div>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Adjustment (₹)</label>
                <input type="number" step="0.01" name="adjustment_amount" class="form-control" placeholder="e.g. -50">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="btn_save_fifo" class="btn btn-primary w-100 shadow-sm fw-bold">
                    Save Record
                </button>
            </div>
        </form>
    </div>
</div>

        <!-- Billing Ledger Table -->
        <?php 
// Calculate Previous Month Label
$prevMonthLabel = date('M Y', strtotime('-1 month'));
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center g-3">
            <div class="col-md-4 col-12">
                <span class="fw-bold text-primary">
                    <i class="fa fa-file-invoice me-2"></i>Tenant Billing Ledger
                </span>
                <span class="badge bg-warning-subtle text-primary ms-2"><?= date('M Y') ?></span>
            </div>
            
            <div class="col-md-8 col-12">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-primary-subtle text-primary">
                        <i class="fa fa-search"></i>
                    </span>
                    <input type="text" id="customTableSearch" class="form-control border-primary-subtle" placeholder="Search by name, unit, or building...">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0"> <table id="billingDataTable" class="table table-hover align-middle dtr-inline" style="width:100%">
            <thead class="table-light">
                <tr>
                    <th class="all">Tenant & Context</th>
                    <th class="desktop tablet">Rent Pending</th>
                    <th class="desktop">Elec. Pending</th>
                    <th class="all text-end">Net Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($contextTenants as $tenant): 
                    $rent = getTenantDebtString($tenant['id'], 'rent');
                    $elec = getTenantDebtString($tenant['id'], 'electricity');
                    
                    $netVal = (float)str_replace(['₹', ','], '', $rent['total'] === 'Settled' ? '0' : $rent['total']) + 
                              (float)str_replace(['₹', ','], '', $elec['total'] === 'Settled' ? '0' : $elec['total']);
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <a href="tenant_history.php?id=<?= $tenant['id'] ?>" class="fw-bold text-primary text-decoration-none" style="font-size: 1rem;">
                                <?= htmlspecialchars($tenant['tenant_name']) ?>
                            </a>
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-building-fill-check small"></i> <?= $tenant['building_name'] ?> | <?= $tenant['unit_name'] ?>
                        </div>
                    </td>
                    <td>
    <?php if (isset($rent['is_warning']) && $rent['is_warning']): ?>
        <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
            <i class="fa fa-exclamation-triangle"></i> Rent Not Fixed
        </span>
        <div class="small text-muted mt-1" style="font-size:0.65rem;">Check Rent History</div>
    <?php else: ?>
        <div class="<?= $rent['is_advance'] ? 'text-success' : 'text-danger' ?> fw-bold">
            <?= $rent['total'] ?>
        </div>
        <small class="text-muted" style="font-size:0.7rem;">
            <?= $rent['formula_with_sums'] ?>
        </small>
    <?php endif; ?>
</td>
                    <td>
                        <div class="fw-bold <?= $elec['is_advance'] ? 'text-success' : 'text-warning' ?>"><?= $elec['total'] ?></div>
                        <small class="text-muted" style="font-size:0.7rem;" title="<?= htmlspecialchars($elec['audit_hint'] ?? '') ?>">
                            <?= $elec['formula_with_sums'] ?>
                        </small>
                    </td>
                    <td class="text-end">
                        <?php if (isset($rent['is_warning']) && $rent['is_warning']): ?>
                            <span class="badge rounded-pill bg-light text-secondary border px-3 py-2">
                                Incomplete
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill <?= $netVal > 0 ? 'bg-danger-subtle text-danger border border-danger' : 'bg-success-subtle text-success border border-success' ?> px-3 py-2">
                                <?= $netVal > 0 ? '₹'.number_format($netVal,2) : ($netVal < 0 ? 'Adv: ₹'.abs($netVal) : 'No Dues') ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Charge Modal -->
<div class="modal fade" id="addChargeModal" tabindex="-1" aria-labelledby="addChargeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addChargeForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addChargeModalLabel">Add Charge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="bill_id" id="charge_bill_id">
                    <div class="mb-3">
                        <label for="charge_amount" class="form-label">Amount (₹)</label>
                        <input type="number" step="0.01" class="form-control" id="charge_amount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="charge_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="charge_description" name="description" placeholder="e.g., Late Fee, Maintenance" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Charge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View History Modal -->
<div class="modal fade" id="viewHistoryModal" tabindex="-1" aria-labelledby="viewHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewHistoryModalLabel">Payment History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent">
                    <!-- History will be loaded here via AJAX -->
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
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

<!-- ... (footer include) ... -->

<?php include 'components/footer.php'; ?>