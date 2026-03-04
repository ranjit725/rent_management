<?php
require_once 'config/master.php';

// ================== HANDLE FORM SUBMIT ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = createBuilding($_POST);

    if ($result['status'] === 'success') {

        // Set flash message
        $_SESSION['flash_message'] = [
            'type'    => 'success',
            'message' => 'Building added successfully!'
        ];

        header("Location: buildings.php");
        exit;
    } else {

        // Store error + old input
        $_SESSION['flash_message'] = [
            'type'    => 'danger',
            'message' => $result['message'] ?? 'Something went wrong!'
        ];

        $_SESSION['old_input'] = $_POST;

        header("Location: buildings.php");
        exit;
    }
}

// ================== FETCH DATA ==================
$buildings = getBuildings();

// Get flash message if exists
$flash = $_SESSION['flash_message'] ?? null;
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

    <h3 class="mb-4">Buildings</h3>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Add Building</h5>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Building Name</label>
                            <input type="text"
                                   name="building_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($old['building_name'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address"
                                      class="form-control"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
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

                    <table class="table table-bordered mt-3">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($buildings)): ?>
                                <?php foreach ($buildings as $b): ?>
                                    <tr>
                                        <td><?= (int)$b['id'] ?></td>
                                        <td><?= htmlspecialchars($b['name']) ?></td>
                                        <td><?= htmlspecialchars($b['address']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No buildings found.</td>
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