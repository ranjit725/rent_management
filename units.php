<?php
require_once 'config/master.php';

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = createUnit($_POST);
    $_SESSION['unit_message'] = $result;
    header("Location: units.php");
    exit;
}

// Display session message once
if (isset($_SESSION['unit_message'])) {
    $message = $_SESSION['unit_message'];
    unset($_SESSION['unit_message']);
}

// Fetch data
$units = getUnits();
$buildings = getBuildings(); // for dropdown
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
<?php include 'components/navbar.php'; ?>

<div class="container-fluid mt-4">

    <h3 class="mb-4">Units</h3>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
            <?= $message['status'] === 'success' ? 'Unit added successfully!' : $message['message'] ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Add Unit</h5>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Building</label>
                            <select name="building_id" class="form-control" required>
                                <option value="">Select Building</option>
                                <?php foreach ($buildings as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Unit Name</label>
                            <input type="text" name="unit_name" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
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

                    <table class="table table-bordered mt-3">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Building</th>
                                <th>Unit Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($units as $u): ?>
                                <tr>
                                    <td><?= (int)$u['id'] ?></td>
                                    <td><?= htmlspecialchars($u['building_name']) ?></td>
                                    <td><?= htmlspecialchars($u['unit_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>

</div>
</div>

<?php include 'components/footer.php'; ?>