<?php
require_once 'config/master.php';

// --- Controller Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = addMeterReading($_POST, $_FILES);
    $_SESSION['flash_message'] = $result;
    header("Location: meter_readings.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $reading_id = (int)$_GET['id'];
    $result = deleteMeterReading($reading_id);
    $_SESSION['flash_message'] = $result;
    header("Location: meter_readings.php");
    exit;
}

// Fetch data for the page
 $readings = getMeterReadings();
 $meters = $db->query("SELECT m.id, m.meter_name, b.name as building_name FROM meters m LEFT JOIN buildings b ON m.building_id = b.id ORDER BY b.name, m.meter_name")->fetchAll();

// Get flash message
 $message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h3 class="mb-4">Meter Readings</h3>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
                <?= $message['status'] === 'success' ? ($message['message'] ?? 'Operation successful!') : $message['message'] ?>
            </div>
        <?php endif; ?>

        <!-- Add Reading Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Add New Reading</h5>
                
                <form method="POST" id="readingForm" enctype="multipart/form-data" class="row g-3">

                    <!-- Meter -->
                    <div class="col-md-6">
                        <label class="form-label">Meter *</label>
                        <select name="meter_id" id="meter_id" class="form-control" required>
                            <option value="">-- Select Meter --</option>
                            <?php foreach ($meters as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">
                                    <?= htmlspecialchars($m['building_name']) ?> → <?= htmlspecialchars($m['meter_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Reading Date -->
                    <div class="col-md-6">
                        <label class="form-label">Reading Date *</label>
                        <input type="date"
                            name="reading_date"
                            id="reading_date"
                            class="form-control"
                            value="<?php echo date('Y-m-d'); ?>" 
                            required>
                    </div>

                    <!-- Last Reading Info -->
                    <div class="col-12">
                        <small id="last_reading_info" class="text-muted"></small>
                    </div>

                    <!-- Previous Reading -->
                    <div class="col-md-6">
                        <label class="form-label">Previous Reading *</label>

                        <!-- Visible field -->
                        <input type="number"
                            step="1"
                            id="previous_reading"
                            class="form-control"
                            value="0"
                            min="0"
                            readonly>

                        <!-- Hidden field for form submit -->
                        <input type="hidden"
                            name="previous_reading"
                            id="previous_reading_hidden">
                    </div>

                    <!-- Current Reading -->
                    <div class="col-md-6">
                        <label class="form-label">Current Reading *</label>
                        <input type="number"
                            step="1"
                            name="current_reading"
                            id="current_reading"
                            class="form-control"
                            min="0"
                            placeholder="Enter meter reading"
                            required>
                    </div>

                    <!-- Units -->
                    <div class="col-md-4">
                        <label class="form-label">Units Consumed</label>
                        <input type="number"
                            step="1"
                            id="units_consumed"
                            class="form-control"
                            readonly>
                    </div>

                    <!-- Rate -->
                    <div class="col-md-4">
                        <label class="form-label">Per Unit Rate</label>
                        <input type="number"
                            step="0.01"
                            name="per_unit_rate"
                            id="per_unit_rate"
                            class="form-control"
                            value="8.50"
                            required>
                    </div>

                    <!-- Total -->
                    <div class="col-md-4">
                        <label class="form-label">Total Amount</label>
                        <input type="number"
                            step="0.01"
                            id="total_amount"
                            class="form-control"
                            readonly>
                    </div>

                    <!-- Photo -->
                    <div class="col-12">
                        <label class="form-label">Meter Photo</label>
                        <input type="file"
                            name="image_path"
                            class="form-control"
                            accept="image/*"
                            capture="environment">
                        <small class="text-muted">Optional (Max 2MB) — You can take a photo using your camera.</small>
                    </div>

                    <!-- Submit -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            Save Reading
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- Reading History Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Reading History</h5>
                <div class="table-responsive">
                    <table class="table table-bordered mt-3" id="readingsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Meter</th>
                                <th>Building</th>
                                <th>Previous</th>
                                <th>Current</th>
                                <th>Units</th>
                                <th>Rate</th>
                                <th>Amount</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php if (!empty($readings)): ?>
                                <?php foreach ($readings as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('M j, Y', strtotime($r['reading_date']))) ?></td>
                                        <td><?= htmlspecialchars($r['meter_name']) ?></td>
                                        <td><?= htmlspecialchars($r['building_name']) ?></td>
                                        <td><?= (int)$r['previous_reading'] ?></td>
                                        <td><?= (int)$r['current_reading'] ?></td>
                                        <td><?= (int)$r['units_consumed'] ?></td>
                                        <td><?= number_format($r['per_unit_rate'], 2) ?></td>
                                        <td><?= number_format($r['total_amount'], 2) ?></td>

                                        <td>
                                            <?php if (!empty($r['image_path'])): ?>
                                                <a href="<?= htmlspecialchars($r['image_path']) ?>" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fa fa-image"></i>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <a href="?action=delete&id=<?= (int)$r['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td>Date</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>No data</td>
                                </tr>

                            <?php endif; ?>

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