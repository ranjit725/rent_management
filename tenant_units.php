<?php
require_once 'config/master.php';

// ================== HANDLE FORM SUBMIT ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mapping_id = (int)($_POST['mapping_id'] ?? 0);
    $result = createTenantUnitMapping($_POST, $mapping_id ?: null);

    $_SESSION['message'] = $result;
    header("Location: tenant_units.php");
    exit;
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// Fetch tenants, units, and mappings
$tenants = getTenants();
$units   = getUnits();
$mappings = getTenantUnitMappings();
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
<?php include 'components/navbar.php'; ?>

<div class="container-fluid mt-4">

    <h3 class="mb-4">Tenant-Unit Mappings</h3>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['status'] === 'success' ? 'success':'danger' ?>">
            <?= $message['status'] === 'success' ? 'Saved successfully!' : $message['message'] ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <button class="btn btn-primary mb-3" onclick="openMappingModal()">Add Mapping</button>

            <div class="table-responsive">
                <table class="table table-bordered" id="mappingTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tenant</th>
                            <th>Building</th>
                            <th>Unit</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings as $m): ?>
                        <tr>
                            <td><?= (int)$m['id'] ?></td>
                            <td><?= htmlspecialchars($m['tenant_name']) ?></td>
                            <td><?= htmlspecialchars($m['building_name']) ?></td>
                            <td><?= htmlspecialchars($m['unit_name']) ?></td>
                            <td><?= htmlspecialchars($m['effective_from']) ?></td>
                            <td><?= htmlspecialchars($m['effective_to'] ?? '--') ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick='editMapping(<?= json_encode($m) ?>)'>Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="mappingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content p-3">
                <h5 id="modalTitle">Add Mapping</h5>
                <form method="POST" id="mappingForm">
                    <input type="hidden" name="mapping_id" id="mapping_id">

                    <div class="mb-3">
                        <label>Tenant</label>
                        <select name="tenant_id" id="tenant_id" class="form-control" required>
                            <option value="">-- Select Tenant --</option>
                            <?php foreach($tenants as $t): ?>
                                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Unit</label>
                        <select name="unit_id" id="unit_id" class="form-control" required>
                            <option value="">-- Select Unit --</option>
                            <?php foreach($units as $u): ?>
                                <option value="<?= (int)$u['id'] ?>">
                                    <?= htmlspecialchars($u['building_name'] . ' - ' . $u['unit_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Effective From</label>
                        <input type="date" name="effective_from" id="effective_from" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Effective To (optional)</label>
                        <input type="date" name="effective_to" id="effective_to" class="form-control">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="closeMappingModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

<?php include 'components/footer.php'; ?>