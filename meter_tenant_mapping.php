<?php
require_once 'config/master.php';

// --- Controller Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['mapping_id'])) {
        $result = updateMeterTenantMapping($_POST, (int)$_POST['mapping_id']);
    } else {
        $result = addMeterTenantMapping($_POST);
    }
    $_SESSION['flash_message'] = $result;
    header("Location: meter_tenant_mapping.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $result = deleteMeterTenantMapping((int)$_GET['id']);
    $_SESSION['flash_message'] = $result;
    header("Location: meter_tenant_mapping.php");
    exit;
}

// --- Data Fetching ---
 $mappings = getMeterTenantMappings();
 $meters = $db->query("SELECT m.id, m.meter_name, b.name as building_name FROM meters m JOIN buildings b ON m.building_id = b.id ORDER BY b.name, m.meter_name")->fetchAll();
  $tenants = $db->query("SELECT id, name FROM tenants ORDER BY name")->fetchAll();

// Get flash message
 $message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h3 class="mb-4">Meter - Tenant Mapping</h3>

        <!-- Flash Message -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message['status'] === 'success' ? 'success' : 'danger' ?>">
                <?= $message['status'] === 'success' ? ($message['message'] ?? 'Operation successful!') : $message['message'] ?>
            </div>
        <?php endif; ?>

        <!-- Add / Update Mapping Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Map Meter to Tenant</h5>
                <form method="POST" id="mappingForm" class="row g-3">
                    <input type="hidden" name="mapping_id" id="mapping_id" value="">
                    
                    <!-- Meter -->
                    <div class="col-md-6">
                        <label class="form-label">Meter *</label>
                        <select name="meter_id" id="meter_id" class="form-control" required>
                            <option value="">-- Select Meter --</option>
                            <?php foreach ($meters as $meter): ?>
                                <option value="<?= (int)$meter['id'] ?>"><?= htmlspecialchars($meter['building_name'] . ' → ' . $meter['meter_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tenant -->
                    <div class="col-md-6">
                        <label class="form-label">Tenant *</label>
                        <select name="tenant_id" id="tenant_id" class="form-control" required>
                            <option value="">-- Select Tenant --</option>
                            <?php foreach ($tenants as $tenant): ?>
                                <option value="<?= (int)$tenant['id'] ?>"><?= htmlspecialchars($tenant['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <small class="text-muted">Leave blank if this mapping is currently active.</small>
                    </div>

                    <div class="col-12">
                        <small id="current_tenants_info" class="text-muted"></small>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save Mapping</button>
                        <button type="button" class="btn btn-secondary" id="cancelBtn" style="display:none;" onclick="resetForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Mappings Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">All Mappings</h5>
                <div class="table-responsive">
                    <table id="mappingsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Meter</th>
                                <th>Tenant</th>
                                <th>Effective Period</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mappings as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['building_name'] . ' → ' . $m['meter_name']) ?></td>
                                    <td><?= htmlspecialchars($m['tenant_name']) ?></td>
                                    <td>
                                        <?= date('d M Y', strtotime($m['effective_from'])) ?>
                                        -
                                        <?= $m['effective_to'] ? date('d M Y', strtotime($m['effective_to'])) : 'Present' ?>
                                    </td>
                                    <td>
                                        <?php if (!$m['effective_to']): ?>
                                            <span class="badge bg-success">Current</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Past</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editMapping(<?= htmlspecialchars(json_encode($m)) ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <?php if ($m['effective_to']): ?>
                                            <a href="?action=delete&id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-danger" disabled title="Cannot delete a currently active mapping.">
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