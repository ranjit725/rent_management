<?php
require_once 'config/master.php';

// --- Controller Logic ---

// Handle POST request for both Add and Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meter_id = (int)($_POST['meter_id'] ?? 0);
    
    if ($meter_id > 0) {
        $result = updateMeter($_POST, $meter_id);
    } else {
        $result = addMeter($_POST);
    }

    $_SESSION['flash_message'] = $result;
    header("Location: meters.php");
    exit;
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $meter_id = (int)$_GET['id'];
    $result = deleteMeter($meter_id);
    $_SESSION['flash_message'] = $result;
    header("Location: meters.php");
    exit;
}

// Fetch data for the page
 $meters = getMeters();
 $buildings = $db->query("SELECT id, name FROM buildings ORDER BY name ASC")->fetchAll();

// Get flash message if exists
 $message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Get old input if exists (for form repopulation on error)
 $old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid mt-4">

        <h3 class="mb-4">Meters</h3>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
                <?= $message['status'] === 'success' ? ($message['message'] ?? 'Operation successful!') : $message['message'] ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 id="formTitle">Add Meter</h5>

                        <form method="POST" id="meterForm">
                            <input type="hidden" name="meter_id" id="meter_id">

                            <div class="mb-3">
                                <label class="form-label">Building</label>
                                <select name="building_id" id="building_id" class="form-control" required>
                                    <option value="">-- Select Building --</option>
                                    <?php foreach ($buildings as $b): ?>
                                        <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Meter Name</label>
                                <input type="text"
                                       name="meter_name"
                                       id="meter_name"
                                       class="form-control"
                                       value="<?= htmlspecialchars($old['meter_name'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Meter Type</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="meter_type" id="type_personal" value="personal" required>
                                        <label class="form-check-label" for="type_personal">Personal</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="meter_type" id="type_common" value="common" required>
                                        <label class="form-check-label" for="type_common">Common</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="status" id="status" value="1" checked>
                                    <label class="form-check-label" for="status">Active</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                Add Meter
                            </button>

                        </form>
                    </div>
                </div>

            </div>

            <div class="col-md-8">

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Meter List</h5>

                        <table class="table table-bordered mt-3" id="metersTable">
                            <thead>
                                <tr>
                                    <th>Meter Name</th>
                                    <th>Building</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($meters)): ?>
                                    <?php foreach ($meters as $m): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($m['meter_name']) ?></td>
                                            <td><?= htmlspecialchars($m['building_name']) ?></td>
                                            <td><span class="badge bg-<?= $m['meter_type'] === 'common' ? 'info' : 'secondary' ?>"><?= htmlspecialchars(ucfirst($m['meter_type'])) ?></span></td>
                                            <td>
                                                <span class="badge bg-<?= $m['status'] ? 'success' : 'danger' ?>">
                                                    <?= $m['status'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editMeter(<?= htmlspecialchars(json_encode($m)) ?>)">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <a href="?action=delete&id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this meter?')">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No meters found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<?php

include 'components/footer.php'; 
?>