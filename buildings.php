<?php
require_once 'config/master.php';

// Handle POST request for both Add and Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $building_id = (int)($_POST['building_id'] ?? 0);

    if ($building_id > 0) {
        $result = updateBuilding($_POST, $building_id);
    } else {
        $result = createBuilding($_POST);
    }

    $_SESSION['flash_message'] = $result; // Use consistent key
    header("Location: buildings.php");
    exit;
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $building_id = (int)$_GET['id'];
    $result = deleteBuilding($building_id);
    $_SESSION['flash_message'] = $result;
    header("Location: buildings.php");
    exit;
}

// Fetch data
 $buildings = getBuildings();

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

    <h3 class="mb-4">Buildings</h3>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
            <?= $message['status'] === 'success' ? ($message['message'] ?? 'Operation successful!') : $message['message'] ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 id="formTitle">Add Building</h5>

                    <form method="POST" id="buildingForm">
                        <input type="hidden" name="building_id" id="building_id">

                        <div class="mb-3">
                            <label class="form-label">Building Name</label>
                            <input type="text"
                                   name="building_name"
                                   id="building_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($old['building_name'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address"
                                      id="address"
                                      class="form-control"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            Add Building
                        </button>

                    </form>
                </div>
            </div>

        </div>

        <div class="col-md-8">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Building List</h5>

                    <table class="table table-bordered mt-3" id="buildingsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($buildings)): ?>
                                <?php foreach ($buildings as $b): ?>
                                    <tr>
                                        <td><?= (int)$b['id'] ?></td>
                                        <td><?= htmlspecialchars($b['name']) ?></td>
                                        <td><?= htmlspecialchars($b['address']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editBuilding(<?= htmlspecialchars(json_encode($b)) ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No buildings found.</td>
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