<?php
require_once 'config/master.php';

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_id = (int)($_POST['tenant_id'] ?? 0);
    $result = createTenant($_POST, $_FILES, $tenant_id ?: null);
    $_SESSION['message'] = $result;
    header("Location: tenants.php");
    exit;
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

$tenants = getTenants();
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
<?php include 'components/navbar.php'; ?>

<div class="container-fluid mt-4">

    <h3 class="mb-4">Tenants</h3>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
            <?= $message['status'] === 'success' ? 'Tenant saved successfully!' : $message['message'] ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <button class="btn btn-primary mb-3" onclick="openTenantModal()">Add Tenant</button>

            <div class="table-responsive">
                <table class="table table-bordered" id="tenantsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>ID Proof</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tenants as $t): ?>
                            <tr class="<?= $t['status'] === 'inactive' ? 'table-danger' : '' ?>">
                                <td><?= (int)$t['id'] ?></td>
                                <td><?= htmlspecialchars($t['name']) ?></td>
                                <td><?= htmlspecialchars($t['mobile']) ?></td>
                                <td>
                                    <?php if ($t['id_proof']): ?>
                                        <a href="<?= htmlspecialchars($t['id_proof']) ?>" target="_blank">
                                            <?= pathinfo($t['id_proof'], PATHINFO_EXTENSION) === 'pdf' ? 'PDF' : '<img src="'.htmlspecialchars($t['id_proof']).'" height="30">' ?>
                                        </a>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </td>
                                <td><?= ucfirst($t['status']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='editTenant(<?= json_encode($t) ?>)'>Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Tenant Modal -->
    <div class="modal" id="tenantModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content p-3">
                <h5 id="modalTitle">Add Tenant</h5>
                <form method="POST" enctype="multipart/form-data" id="tenantForm">
                    <input type="hidden" name="tenant_id" id="tenant_id">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="tenant_name" id="tenant_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Mobile</label>
                        <input type="text" name="mobile" id="mobile" class="form-control" placeholder="10 digits">
                    </div>
                    <div class="mb-3">
                        <label>ID Proof (optional)</label>
                        <input type="file" name="id_proof" accept="image/*,application/pdf" capture="camera" class="form-control">
                        <small class="text-muted">Accepted: Images or PDF</small>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="closeTenantModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Tenant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>



<?php include 'components/footer.php'; ?>



