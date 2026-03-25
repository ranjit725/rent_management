<?php
ob_start();
require_once 'config/master.php';

$tenant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($tenant_id <= 0) {
    header("Location: billing.php");
    exit;
}

// --- Action Handlers ---

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['bill_id'])) {
    $_SESSION['flash_message'] = deleteBill((int)$_GET['bill_id']);
    header("Location: tenant_history.php?id=" . $tenant_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_payment'])) {
    $_SESSION['flash_message'] = updateBillPayment($_POST);
    header("Location: tenant_history.php?id=" . $tenant_id);
    exit;
}

// --- Data Fetching ---
$tenant_details = getTenantById($tenant_id); 
$payments = getTenantPaymentHistory($tenant_id);

$message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold">History: <span class="text-primary"><?= htmlspecialchars($tenant_details['name'] ?? 'Tenant') ?></span></h3>
            <a href="billing.php" class="btn btn-sm btn-outline-secondary">Back to Ledger</a>
        </div>

        <?php if ($message): ?>
            <?php 
                $status = is_array($message) ? ($message['status'] ?? 'success') : 'success';
                $text = is_array($message) ? ($message['message'] ?? '') : $message;
            ?>
            <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= htmlspecialchars($text) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table id="historyTable" class="table table-hover align-middle mb-0">
    <thead class="bg-light">
        <tr>
            <th>Billing Month</th>
            <th>Rent Paid</th>
            <th>Elec. Paid</th>
            <th>Adjustment</th>
            <th>Recorded On</th> <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td class="fw-bold"><?= date('F Y', strtotime($p['billing_month'])) ?></td>
            <td>₹<?= number_format($p['rent_amount'], 2) ?></td>
            <td>₹<?= number_format($p['electricity_amount'], 2) ?></td>
            <td class="<?= $p['adjustment_amount'] < 0 ? 'text-success' : ($p['adjustment_amount'] > 0 ? 'text-danger' : 'text-muted') ?>">
                ₹<?= number_format($p['adjustment_amount'], 2) ?>
            </td>
            <td class="text-muted small">
                <?= date('d M Y, h:i A', strtotime($p['created_at'])) ?>
            </td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary edit-btn" 
                        data-item='<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>'>
                    <i class="fa fa-edit"></i>
                </button>
                <a href="?id=<?= $tenant_id ?>&action=delete&bill_id=<?= $p['id'] ?>" 
                   class="btn btn-sm btn-outline-danger" 
                   onclick="return confirm('Are you sure you want to delete this billing record?')">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr> <?php endforeach; ?>
    </tbody>
</table>
            </div>
        </div>
    </div>
</div>

<<div class="modal fade" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Billing Record (<span id="modal_month_label"></span>)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="bill_id" id="edit_id">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Rent Amount Paid (₹)</label>
                    <input type="number" step="0.01" name="rent_amount" id="edit_rent" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Electricity Amount Paid (₹)</label>
                    <input type="number" step="0.01" name="electricity_amount" id="edit_elec" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Adjustment (₹)</label>
                    <input type="number" step="0.01" name="adjustment_amount" id="edit_adj" class="form-control" required>
                    <small class="text-muted">Use negative values for discount (e.g., -50)</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="btn_update_payment" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include 'components/footer.php'; ?>

