<?php
require_once 'config/master.php';

// --- Controller Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['history_id'])) {
        $result = updateRentHistory($_POST, (int)$_POST['history_id']);
    } else {
        $result = addRentHistory($_POST);
    }
    $_SESSION['flash_message'] = $result;
    header("Location: rent_history.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $result = deleteRentHistory((int)$_GET['id']);
    $_SESSION['flash_message'] = $result;
    header("Location: rent_history.php");
    exit;
}

// --- Data Fetching ---
 $rentHistory = getRentHistory();
// Corrected query to use 'b.name'
 $units = $db->query("SELECT u.id, u.unit_name, b.name as building_name FROM units u JOIN buildings b ON u.building_id = b.id ORDER BY b.name, u.unit_name")->fetchAll();

// Get flash message
 $message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h3 class="mb-4">Rent History Management</h3>

        <!-- Flash Message -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
                <?= $message['status'] === 'success' ? ($message['message'] ?? 'Operation successful!') : $message['message'] ?>
            </div>
        <?php endif; ?>

        <!-- Add New Rent History Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Add / Update Rent History</h5>
                <form method="POST" id="historyForm" class="row g-3">
                    <input type="hidden" name="history_id" id="history_id" value="">
                    
                    <!-- Unit -->
                    <div class="col-md-6">
                        <label class="form-label">Unit *</label>
                        <select name="unit_id" id="unit_id" class="form-control" required>
                            <option value="">-- Select Unit --</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?= (int)$unit['id'] ?>"><?= htmlspecialchars($unit['building_name'] . ' → ' . $unit['unit_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rent Amount -->
                    <div class="col-md-6">
                        <label class="form-label">Rent Amount *</label>
                        <input type="number" step="0.01" name="rent_amount" id="rent_amount" class="form-control" placeholder="e.g., 15000" required>
                    </div>

                    <!-- Effective From -->
                    <div class="col-md-6">
                        <label class="form-label">Effective From *</label>
                        <input type="date" name="effective_from" id="effective_from" class="form-control" required>
                    </div>

                    <!-- Effective To -->
                    <div class="col-md-6">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" id="effective_to" class="form-control">
                        <small class="text-muted">Leave blank if this is the current rent.</small>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save Rent History</button>
                        <button type="button" class="btn btn-secondary" id="cancelBtn" style="display:none;" onclick="resetForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rent History Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">All Rent History</h5>
                <div class="table-responsive">
                    <table id="historyTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Rent Amount</th>
                                <th>Effective Period</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentHistory as $rh): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rh['building_name'] . ' → ' . $rh['unit_name']) ?></td>
                                    <td>₹<?= number_format($rh['rent_amount'], 2) ?></td>
                                    <td>
                                        <?= date('d M Y', strtotime($rh['effective_from'])) ?>
                                        -
                                        <?= $rh['effective_to'] ? date('d M Y', strtotime($rh['effective_to'])) : 'Present' ?>
                                    </td>
                                    <td>
                                        <?php if (!$rh['effective_to']): ?>
                                            <span class="badge bg-success">Current</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Past</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editRentHistory(<?= htmlspecialchars(json_encode($rh)) ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <?php if ($rh['effective_to']): ?>
                                            <!-- This is a PAST record, so we can delete it -->
                                            <a href="?action=delete&id=<?= (int)$rh['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this history record?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <!-- This is the CURRENT record, so we disable the delete button -->
                                            <button class="btn btn-sm btn-danger" disabled title="Cannot delete the currently active rent. To change it, add a new rent history record.">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

include 'components/footer.php'; 
?>