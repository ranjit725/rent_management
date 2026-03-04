<?php
require_once 'config/master.php';

// Handle POST request for both Add and Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_id = (int)($_POST['unit_id'] ?? 0);

    if ($unit_id > 0) {
        $result = updateUnit($_POST, $unit_id);
    } else {
        $result = createUnit($_POST);
    }

    $_SESSION['flash_message'] = $result;
    header("Location: units.php");
    exit;
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $unit_id = (int)$_GET['id'];
    $result = deleteUnit($unit_id);
    $_SESSION['flash_message'] = $result;
    header("Location: units.php");
    exit;
}

// Fetch data
 $units = getUnits();
 $buildings = getBuildings(); // for dropdown

// Get flash message if exists
 $message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Get old input if exists
 $old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
<?php include 'components/navbar.php'; ?>

<div class="container-fluid mt-4">

    <h3 class="mb-4">Units</h3>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
            <?= $message['status'] === 'success' ? ($message['message'] ?? 'Operation successful!') : $message['message'] ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 id="formTitle">Add Unit</h5>

                    <form method="POST" id="unitForm">
                        <input type="hidden" name="unit_id" id="unit_id">

                        <div class="mb-3">
                            <label class="form-label">Building</label>
                            <select name="building_id" id="building_id" class="form-control" required>
                                <option value="">Select Building</option>
                                <?php foreach ($buildings as $b): ?>
                                    <option value="<?= (int)$b['id'] ?>" <?= (isset($old['building_id']) && (int)$old['building_id'] === (int)$b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Unit Name</label>
                            <input type="text"
                                   name="unit_name"
                                   id="unit_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($old['unit_name'] ?? '') ?>"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            Add Unit
                        </button>

                    </form>
                </div>
            </div>

        </div>

        <div class="col-md-8">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Unit List</h5>

                    <table class="table table-bordered mt-3" id="unitsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Building</th>
                                <th>Unit Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($units)): ?>
                                <?php foreach ($units as $u): ?>
                                    <tr>
                                        <td><?= (int)$u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['building_name']) ?></td>
                                        <td><?= htmlspecialchars($u['unit_name']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editUnit(<?= htmlspecialchars(json_encode($u)) ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No units found.</td>
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

<?php include 'components/footer.php'; ?>