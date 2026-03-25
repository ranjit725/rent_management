<?php
require_once 'config/master.php';

// Handle POST request for both Add and Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // print_r($_POST);
    $tenant_id = (int)($_POST['tenant_id'] ?? 0);

    if ($tenant_id > 0) {
        $result = updateTenant($_POST, $_FILES, $tenant_id);
    } else {
        $result = createTenant($_POST, $_FILES);;
    }

    $_SESSION['flash_message'] = $result;
    header("Location: tenants.php");
    exit;
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $tenant_id = (int)$_GET['id'];
    $result = deleteTenant($tenant_id);
    $_SESSION['flash_message'] = $result;
    header("Location: tenants.php");
    exit;
}

// Fetch data
 $tenants = getTenantsWithUnit();
 $units = getUnits(); // for dropdown

// Get flash message
 $message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Get old input
 $old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
<?php include 'components/navbar.php'; ?>

<div class="container-fluid mt-4">

    <h3 class="mb-4">Tenants</h3>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
            <?= $message['status'] === 'success' ? ($message['message'] ?? 'Operation successful!') : $message['message'] ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 id="formTitle">Add Tenant</h5>

                    <form method="POST" id="tenantForm" enctype="multipart/form-data">
                        <input type="hidden" name="tenant_id" id="tenant_id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" name="mobile" id="mobile" class="form-control" value="<?= htmlspecialchars($old['mobile'] ?? '') ?>">
                            </div>
                        </div>

                        <h6>ID Proof</h6>
                        <div class="mb-3">
                            <label class="form-label">ID Proof (optional)</label>
                            <input type="file" name="id_proof" id="id_proof" accept="image/*,application/pdf" capture="camera" class="form-control">
                            <small class="form-text text-muted">Accepted: Images or PDF</small>
                            <div id="currentProofContainer"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="active" <?= (isset($old['status']) && $old['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (isset($old['status']) && $old['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <hr>

                        <h6>Unit Assignment</h6>

                        <div class="mb-3">
                            <label class="form-label">Assign Unit</label>
                            <select name="unit_id" id="unit_id" class="form-control">
                                <option value="">-- Select Unit --</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>" <?= (isset($old['unit_id']) && (int)$old['unit_id'] === (int)$u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['building_name'] . ' - ' . $u['unit_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" name="effective_from" id="effective_from" class="form-control" value="<?= $old['effective_from'] ?? date('Y-m-d') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Effective To</label>
                            <input type="date" name="effective_to" id="effective_to" class="form-control" value="<?= $old['effective_to'] ?? '' ?>" <?= (empty($old['unit_id'])) ? 'disabled' : '' ?>>
                            <small class="form-text text-muted">Leave blank if this is an ongoing assignment.</small>
                        </div>         

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            Add Tenant
                        </button>

                    </form>
                </div>
            </div>

        </div>

        <div class="col-md-7">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Tenant List</h5>

                    <table class="table table-bordered mt-3" id="tenantsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Current Unit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php if (!empty($tenants)): ?>
        <?php foreach ($tenants as $t): ?>
            <tr>
                <!-- Column 1: ID -->
                <td><?= htmlspecialchars($t['id']) ?></td>
                <!-- Column 2: Name -->
                <td><?= htmlspecialchars($t['name']) ?></td>
                <!-- Column 3: Mobile -->
                <td><?= htmlspecialchars($t['mobile']) ?></td>
                <!-- Column 4: Current Unit -->
                <td>
                    <?php 
                    if ($t['unit_id']) {
                        echo htmlspecialchars($t['building_name'] . ' - ' . $t['unit_name']);
                    } else {
                        echo 'Not Assigned';
                    }
                    ?>
                </td>
                <!-- Column 5: Actions -->
                <td>
                    <button class="btn btn-sm btn-warning edit-tenant-btn" data-tenant="<?= htmlspecialchars(json_encode($t)) ?>">
                        <i class="fa fa-edit"></i>
                    </button>
                    <a href="?action=delete&id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this tenant?')">
                        <i class="fa fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
<!-- Leave tbody empty -->
<?php endif; ?>
</tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>

</div>
</div>

<?php include 'components/footer.php'; ?>