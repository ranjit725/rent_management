<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======== Helper ========
function getInput(array $data, string $key, bool $htmlEscape = false): string {
    $value = trim($data[$key] ?? '');
    return $htmlEscape ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
}

// ======== Dashboard Stats ========
/**
 * Gathers comprehensive statistics for the professional dashboard.
 */
function getDashboardStats() {
    $db = DB::getInstance(); 
    $stats = [];
    $current_month = date('Y-m-01');

    // --- 1. Basic Counts ---
    $stats['total_buildings'] = $db->query("SELECT COUNT(*) as count FROM buildings WHERE status = 1")->fetch()['count'];
    $stats['total_units'] = $db->query("SELECT COUNT(*) as count FROM units WHERE status = 1")->fetch()['count'];
    $stats['total_tenants'] = $db->query("SELECT COUNT(*) as count FROM tenants WHERE status = 'active'")->fetch()['count'];

    // --- 2. Occupancy Calculation ---
    $occupied = $db->query("SELECT COUNT(DISTINCT unit_id) as count FROM tenant_unit_mapping WHERE (effective_to IS NULL OR effective_to >= CURDATE())")->fetch()['count'];
    $stats['occupied_units'] = $occupied;
    $stats['occupancy_rate'] = ($stats['total_units'] > 0) ? round(($occupied / $stats['total_units']) * 100) : 0;

    // --- 3. Financials (Revenue) ---
    $revenueQuery = "SELECT COALESCE(SUM(rent_amount + electricity_amount + adjustment_amount), 0) as total 
                     FROM billing WHERE billing_month = :month";
    $revenue = $db->query($revenueQuery, [':month' => $current_month])->fetch();
    $stats['monthly_revenue'] = $revenue['total'];

    // --- 4. Meter Readings (No changes here) ---
    $stats['pending_readings'] = $db->query("
        SELECT m.id, m.meter_name, b.name as building_name 
        FROM meters m
        JOIN buildings b ON m.building_id = b.id
        WHERE m.status = 1 
        AND m.id IN (
            SELECT DISTINCT meter_id 
            FROM meter_tenant_mapping 
            WHERE tenant_id IN (SELECT id FROM tenants WHERE status = 'active')
        ) 
        AND m.id NOT IN (
            SELECT meter_id 
            FROM meter_readings 
            WHERE DATE_FORMAT(reading_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        )
    ")->fetchAll();

    // --- 5. UPDATED: Approach 2 (Liability vs Paid) ---
    $active_tenants = $db->query("SELECT id, name FROM tenants WHERE status = 'active'")->fetchAll();
    $unpaid_list = [];
    $total_pending_amount = 0;

    foreach ($active_tenants as $t) {
        // A. Get Expectation (What he should pay)
        $structure = getTenantMonthlyStructure($t['id']);
        $total_expected = 0;
        
        foreach ($structure as $month_key => $data) {
            // Only count up to current month
            if ($month_key <= date('Y-m')) {
                $total_expected += (float)($data['rent'] ?? 0);
                if (!empty($data['electricity'])) {
                    foreach ($data['electricity'] as $elec) {
                        $total_expected += (float)$elec['my_share'];
                    }
                }
            }
        }

        // B. Get Reality (What he actually paid - Summing all rows)
        $paid = $db->query("
            SELECT COALESCE(SUM(rent_amount + electricity_amount + adjustment_amount), 0) as total 
            FROM billing WHERE tenant_id = :tid", 
            [':tid' => $t['id']]
        )->fetch()['total'];

        $debt = $total_expected - $paid;

        // C. If debt exists (> 1 to avoid decimal noise), add to list
        if ($debt > 1) {
            $unpaid_list[] = [
                'tenant_id' => $t['id'],
                'name'      => $t['name'],
                'total_due' => $debt,
                // We use your existing function for the UI string if needed
                'rent_status' => getTenantDebtString($t['id'], 'rent'),
                'elec_status' => getTenantDebtString($t['id'], 'elec')
            ];
            $total_pending_amount += $debt;
        }
    }
    
    $stats['unpaid_highlights'] = $unpaid_list;
    $stats['pending_payments'] = $total_pending_amount;

    // --- 6. Recent Activity (No changes here) ---
    $stats['recent_activity'] = $db->query("
        SELECT b.id, b.billing_month,
        (b.rent_amount + b.electricity_amount + b.adjustment_amount) as total_amount, 
        t.name AS tenant_name
        FROM billing b
        JOIN tenants t ON b.tenant_id = t.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ")->fetchAll();

    return $stats;
}

// ======== Buildings ========
function createBuilding(array $data): array
{
    $db = DB::getInstance();

    $sql = "INSERT INTO buildings (name, address) VALUES (:name, :address)";
    $params = [
        ':name'    => getInput($data, 'building_name'),
        ':address' => getInput($data, 'address')
    ];

    try {
        $db->query($sql, $params);
        return ['status' => 'success', 'id' => $db->lastInsertId()];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function getBuildings(): array
{
    $db = DB::getInstance();
    return $db->fetchAll("SELECT * FROM buildings ORDER BY id DESC");
}

// ======== Units ========
function createUnit(array $data): array
{
    $db = DB::getInstance();

    $sql = "INSERT INTO units (building_id, unit_name) VALUES (:building_id, :unit_name)";
    $params = [
        ':building_id' => (int)($data['building_id'] ?? 0),
        ':unit_name'   => getInput($data, 'unit_name')
    ];

    try {
        $db->query($sql, $params);
        return ['status' => 'success', 'id' => $db->lastInsertId()];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Add to config/master.php

// Add to config/master.php

function updateBuilding(array $data, int $id): array
{
    $db = DB::getInstance();
    $sql = "UPDATE buildings SET name=:name, address=:address WHERE id=:id";
    $params = [
        ':name'    => getInput($data, 'building_name'),
        ':address' => getInput($data, 'address'),
        ':id'      => $id
    ];
    try {
        $db->query($sql, $params);
        return ['status' => 'success', 'message' => 'Building updated successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function deleteBuilding(int $id): array
{
    $db = DB::getInstance();
    $sql = "DELETE FROM buildings WHERE id=:id";
    try {
        $db->query($sql, [':id' => $id]);
        return ['status' => 'success', 'message' => 'Building deleted successfully!'];
    } catch (Exception $e) {
        // Prevent deletion if building has units
        if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
            return ['status' => 'error', 'message' => 'Cannot delete building with existing units.'];
        }
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function getUnits(): array
{
    $db = DB::getInstance();
    return $db->fetchAll("
        SELECT u.*, b.name as building_name 
        FROM units u 
        INNER JOIN buildings b ON b.id = u.building_id
        ORDER BY u.id DESC
    ");
}


function updateUnit(array $data, int $id): array
{
    $db = DB::getInstance();
    $sql = "UPDATE units SET building_id=:building_id, unit_name=:unit_name WHERE id=:id";
    $params = [
        ':building_id' => (int)getInput($data, 'building_id'),
        ':unit_name'   => getInput($data, 'unit_name'),
        ':id'          => $id
    ];
    try {
        $db->query($sql, $params);
        return ['status' => 'success', 'message' => 'Unit updated successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function deleteUnit(int $id): array
{
    $db = DB::getInstance();
    $sql = "DELETE FROM units WHERE id=:id";
    try {
        $db->query($sql, [':id' => $id]);
        return ['status' => 'success', 'message' => 'Unit deleted successfully!'];
    } catch (Exception $e) {
        // Prevent deletion if unit has tenants
        if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
            return ['status' => 'error', 'message' => 'Cannot delete unit with existing tenant mappings.'];
        }
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}


// ======== Tenants ========
function getTenantsWithUnit(): array
{
    $db = DB::getInstance();
    $sql = "
        SELECT 
            t.id, t.name, t.mobile, t.status, t.id_proof,
            m.id as mapping_id, m.unit_id, m.effective_from, m.effective_to,
            u.unit_name, 
            b.name as building_name -- CORRECT: Select name from the buildings table
        FROM tenants t
        LEFT JOIN tenant_unit_mapping m ON t.id = m.tenant_id
        LEFT JOIN units u ON m.unit_id = u.id
        LEFT JOIN buildings b ON u.building_id = b.id -- CORRECT: Join the buildings table
        WHERE m.id = (
            SELECT sub_m.id FROM tenant_unit_mapping sub_m 
            WHERE sub_m.tenant_id = t.id 
            ORDER BY sub_m.effective_from DESC, sub_m.id DESC 
            LIMIT 1
        )
        ORDER BY t.name ASC
    ";
    return $db->query($sql)->fetchAll();
}

function handleIdProofUpload($file, $current_file = null): array
{
    // Check if a new file was actually uploaded
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        // No new file uploaded, which is okay. Return success with the old filename.
        return ['status' => 'no_change', 'filename' => $current_file];
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return ['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF, and PDF are allowed.'];
    }

    // Generate a unique filename
    $upload_dir = __DIR__ . '/../uploads/id_proofs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = uniqid('proof_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $destination = $upload_dir . $filename;

    // Move the file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // If successful, delete the old file
        if ($current_file && file_exists($upload_dir . $current_file)) {
            unlink($upload_dir . $current_file);
        }
        return ['status' => 'success', 'filename' => $filename];
    } else {
        return ['status' => 'error', 'message' => 'Failed to move uploaded file.'];
    }
}

function createTenant(array $data, array $file_data): array
{
    $db = DB::getInstance();

    // 1. Validate Mobile Number
    $mobile = getInput($data, 'mobile');
    if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        return ['status' => 'error', 'message' => 'Mobile number must be a valid 10-digit number starting with 6-9.'];
    }

    // 2. Handle File Upload (CORRECTED LOGIC)
    $upload_result = handleIdProofUpload($file_data['id_proof'] ?? null);
    if ($upload_result['status'] === 'error') {
        return $upload_result;
    }
    
    // FIX: If a file was uploaded successfully, use its name. Otherwise, use an empty string.
    $id_proof_filename = ($upload_result['status'] === 'success') ? $upload_result['filename'] : '';

    // 3. Validate Assignment Dates
    $unit_id = (int)getInput($data, 'unit_id');
    $effective_from = getInput($data, 'effective_from');
    $effective_to = getInput($data, 'effective_to');

    if ($unit_id > 0) {
        if (empty($effective_from)) {
            return ['status' => 'error', 'message' => 'Effective From date is required when assigning a unit.'];
        }
        if (!empty($effective_to) && $effective_to < $effective_from) {
            return ['status' => 'error', 'message' => 'Effective To date cannot be before Effective From date.'];
        }
    }

    try {
        $db->beginTransaction();

        // Insert tenant core details
        $db->query("INSERT INTO tenants (name, mobile, id_proof, status) VALUES (:name, :mobile, :id_proof, :status)", [
            ':name'     => getInput($data, 'name'),
            ':mobile'   => $mobile,
            ':id_proof' => $id_proof_filename, // This will now be an empty string, not null
            ':status'   => getInput($data, 'status', 'active')
        ]);
        
        $tenant_id = $db->lastInsertId();

        // Handle unit assignment if provided
        if ($unit_id > 0 && !empty($effective_from)) {
            $db->query("INSERT INTO tenant_unit_mapping (tenant_id, unit_id, effective_from, effective_to) VALUES (:tenant_id, :unit_id, :effective_from, :effective_to)", [
                ':tenant_id'      => $tenant_id,
                ':unit_id'        => $unit_id,
                ':effective_from' => $effective_from,
                ':effective_to'   => !empty($effective_to) ? $effective_to : null
            ]);
        }

        $db->commit();
        return ['status' => 'success', 'message' => 'Tenant created successfully!'];

    } catch (Exception $e) {
        $db->rollBack();
        // If we uploaded a file and the transaction failed, delete it
        if ($id_proof_filename && file_exists(__DIR__ . '/../uploads/id_proofs/' . $id_proof_filename)) {
            unlink(__DIR__ . '/../uploads/id_proofs/' . $id_proof_filename);
        }
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function updateTenant(array $data, array $file_data, int $id): array
{
    $db = DB::getInstance();
    
    // 1. Validate Mobile Number
    $mobile = getInput($data, 'mobile');
    if ($mobile && !preg_match('/^[6-9]\d{9}$/', $mobile)) {
        return ['status' => 'error', 'message' => 'Mobile number must be a valid 10-digit number starting with 6-9.'];
    }

    // 2. Handle File Upload
    $current_tenant = $db->query("SELECT id_proof FROM tenants WHERE id = :id", [':id' => $id])->fetch();
    if (!$current_tenant) return ['status' => 'error', 'message' => 'Tenant not found.'];
    
    $upload_result = handleIdProofUpload($file_data['id_proof'] ?? null, $current_tenant['id_proof']);
    if ($upload_result['status'] === 'error') {
        return $upload_result;
    }
    $id_proof_filename = $upload_result['status'] === 'success' ? $upload_result['filename'] : $current_tenant['id_proof'];

    // 3. Validate Assignment Dates
    $new_unit_id = (int)getInput($data, 'unit_id');
    $effective_from = getInput($data, 'effective_from');
    $effective_to = getInput($data, 'effective_to');

    if ($new_unit_id > 0) {
        if (empty($effective_from)) {
            return ['status' => 'error', 'message' => 'Effective From date is required when assigning a unit.'];
        }
        if (!empty($effective_to) && $effective_to < $effective_from) {
            return ['status' => 'error', 'message' => 'Effective To date cannot be before Effective From date.'];
        }
    }

    try {
        $db->beginTransaction();

        // Update tenant core details
        $db->query("UPDATE tenants SET name=:name, mobile=:mobile, id_proof=:id_proof, status=:status WHERE id=:id", [
            ':name'     => getInput($data, 'name'),
            ':mobile'   => $mobile,
            ':id_proof' => $id_proof_filename,
            ':status'   => getInput($data, 'status', 'active'),
            ':id'       => $id
        ]);

        // Handle unit assignment changes
        if ($new_unit_id > 0 && !empty($effective_from)) {
            // Get the current OPEN assignment for this tenant
            $current_mapping = $db->query("SELECT id, unit_id FROM tenant_unit_mapping WHERE tenant_id = :tenant_id AND effective_to IS NULL ORDER BY effective_from DESC LIMIT 1", [':tenant_id' => $id])->fetch();

            if ($current_mapping) {
                // CASE 1: The unit is being CHANGED. This is a "move".
                if ((int)$current_mapping['unit_id'] !== $new_unit_id) {
                    // Close the old assignment
                    $db->query("UPDATE tenant_unit_mapping SET effective_to = :old_effective_to WHERE id = :id", [
                        ':old_effective_to' => date('Y-m-d', strtotime($effective_from . ' -1 day')),
                        ':id' => $current_mapping['id']
                    ]);
                    // Create the NEW assignment
                    $db->query("INSERT INTO tenant_unit_mapping (tenant_id, unit_id, effective_from, effective_to) VALUES (:tenant_id, :unit_id, :effective_from, :new_effective_to)", [
                        ':tenant_id'        => $id,
                        ':unit_id'          => $new_unit_id,
                        ':effective_from'   => $effective_from,
                        ':new_effective_to' => !empty($effective_to) ? $effective_to : null
                    ]);
                } 
                // CASE 2: The unit is the SAME. This is an "edit" of the current assignment's dates.
                else {
                    $db->query("UPDATE tenant_unit_mapping SET effective_from = :effective_from, effective_to = :effective_to WHERE id = :id", [
                        ':effective_from' => $effective_from,
                        ':effective_to'   => !empty($effective_to) ? $effective_to : null,
                        ':id' => $current_mapping['id']
                    ]);
                }
            } else {
                // No current assignment exists, so create a new one.
                $db->query("INSERT INTO tenant_unit_mapping (tenant_id, unit_id, effective_from, effective_to) VALUES (:tenant_id, :unit_id, :effective_from, :effective_to)", [
                    ':tenant_id'      => $id,
                    ':unit_id'        => $new_unit_id,
                    ':effective_from' => $effective_from,
                    ':effective_to'   => !empty($effective_to) ? $effective_to : null
                ]);
            }
        }

        $db->commit();
        return ['status' => 'success', 'message' => 'Tenant updated successfully!'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function deleteTenant(int $id): array
{
    $db = DB::getInstance();
    $sql = "DELETE FROM tenants WHERE id=:id";
    try {
        $db->query($sql, [':id' => $id]);
        // Due to ON DELETE CASCADE, related mappings will be deleted automatically.
        return ['status' => 'success', 'message' => 'Tenant deleted successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Creates a new meter.
 */
function addMeter(array $data): array
{
    $db = DB::getInstance();
    try {
        $db->query("INSERT INTO meters (building_id, meter_name, meter_type, status) VALUES (:building_id, :meter_name, :meter_type, :status)", [
            ':building_id' => (int)getInput($data, 'building_id'),
            ':meter_name'  => getInput($data, 'meter_name'),
            ':meter_type'  => getInput($data, 'meter_type'),
            ':status'      => (int)(getInput($data, 'status') === '1'),
        ]);
        return ['status' => 'success', 'message' => 'Meter added successfully!'];
    } catch (Exception $e) {
        // Check for duplicate entry error
        if ($e->getCode() == 23000) {
            return ['status' => 'error', 'message' => 'A meter with this name already exists for the selected building.'];
        }
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Fetches all meters with their building names.
 */
function getMeters(): array
{
    $db = DB::getInstance();
    $sql = "
        SELECT 
            m.id, m.building_id, m.meter_name, m.meter_type, m.status, m.created_at,
            b.name as building_name
        FROM meters m
        LEFT JOIN buildings b ON m.building_id = b.id
        ORDER BY b.name ASC, m.meter_name ASC
    ";
    return $db->query($sql)->fetchAll();
}

/**
 * Fetches a single meter by its ID.
 */
function getMeterById(int $id): ?array
{
    $db = DB::getInstance();
    $meter = $db->query("SELECT * FROM meters WHERE id = :id", [':id' => $id])->fetch();
    return $meter ?: null;
}

/**
 * Updates an existing meter.
 */
function updateMeter(array $data, int $id): array
{
    $db = DB::getInstance();
    try {
        $db->query("UPDATE meters SET building_id=:building_id, meter_name=:meter_name, meter_type=:meter_type, status=:status WHERE id=:id", [
            ':building_id' => (int)getInput($data, 'building_id'),
            ':meter_name'  => getInput($data, 'meter_name'),
            ':meter_type'  => getInput($data, 'meter_type'),
            ':status'      => (int)(getInput($data, 'status') === '1'),
            ':id'          => $id
        ]);
        return ['status' => 'success', 'message' => 'Meter updated successfully!'];
    } catch (Exception $e) {
        if ($e->getCode() == 23000) {
            return ['status' => 'error', 'message' => 'A meter with this name already exists for the selected building.'];
        }
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Deletes a meter.
 */
function deleteMeter(int $id): array
{
    $db = DB::getInstance();
    try {
        $db->query("DELETE FROM meters WHERE id = :id", [':id' => $id]);
        return ['status' => 'success', 'message' => 'Meter deleted successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}


/**
 * Fetches the last reading for a given meter to determine the 'previous' value.
 */
function getLastReadingForMeter(int $meter_id): ?array
{
    $db = DB::getInstance();
    $sql = "SELECT reading_date,current_reading FROM meter_readings WHERE meter_id = :meter_id ORDER BY reading_date DESC, id DESC LIMIT 1";
    $reading = $db->query($sql, [':meter_id' => $meter_id])->fetch();
    return $reading ?: null;
}

/**
 * Creates a new meter reading.
 * Handles file upload with robust error checking.
 */
function addMeterReading(array $data, array $file = null): array
{
    $db = DB::getInstance();
    $meter_id = (int)getInput($data, 'meter_id');
    $current_reading = (int)getInput($data, 'current_reading');
    $reading_date = getInput($data, 'reading_date');
    $per_unit_rate = (float)getInput($data, 'per_unit_rate');

    // Get the previous reading
    $last_reading = getLastReadingForMeter($meter_id);
    $previous_reading = $last_reading ? (int)$last_reading['current_reading'] : 0;

    // Handle file upload
    $image_path = null;
    if ($file && isset($file['image_path']) && $file['image_path']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/meter_readings/';
        // Ensure the directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = time() . '_' . basename($file['image_path']['name']);
        $target_file = $upload_dir . $file_name;

        // Validate the file (optional but recommended)
        $check = getimagesize($file['image_path']['tmp_name']);
        if ($check === false) {
            return ['status' => 'error', 'message' => 'File is not a valid image.'];
        }

        if (move_uploaded_file($file['image_path']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            // This is a common failure point, provide a clear message
            return ['status' => 'error', 'message' => 'Failed to move uploaded file. Please check directory permissions for ' . $upload_dir];
        }
    } elseif ($file && isset($file['image_path']) && $file['image_path']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        return ['status' => 'error', 'message' => 'File upload error code: ' . $file['image_path']['error']];
    }

    try {
        $sql = "INSERT INTO meter_readings (meter_id, reading_date, previous_reading, current_reading, per_unit_rate, image_path) 
                VALUES (:meter_id, :reading_date, :previous_reading, :current_reading, :per_unit_rate, :image_path)";
        $db->query($sql, [
            ':meter_id' => $meter_id,
            ':reading_date' => $reading_date,
            ':previous_reading' => $previous_reading,
            ':current_reading' => $current_reading,
            ':per_unit_rate' => $per_unit_rate,
            ':image_path' => $image_path,
        ]);
        return ['status' => 'success', 'message' => 'Meter reading added successfully!'];
    } catch (Exception $e) {
        // If DB insert fails, delete the uploaded image if it was saved
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        if ($e->getCode() == 23000) {
            return ['status' => 'error', 'message' => 'A reading for this meter already exists on this date.'];
        }
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Updates an existing meter reading.
 * Handles replacing the old image if a new one is uploaded.
 */
function updateMeterReading(array $data, array $file = null, int $id): array
{
    $db = DB::getInstance();
    
    // Fetch current reading data to get the old image path
    $current_data = $db->query("SELECT image_path FROM meter_readings WHERE id = :id", [':id' => $id])->fetch();
    if (!$current_data) {
        return ['status' => 'error', 'message' => 'Reading not found.'];
    }
    $old_image_path = $current_data['image_path'];

    // Handle new file upload
    $new_image_path = $old_image_path; // Default to old path
    if ($file && isset($file['image_path']) && $file['image_path']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/meter_readings/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($file['image_path']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($file['image_path']['tmp_name'], $target_file)) {
            // If new file is uploaded successfully, delete the old one
            if ($old_image_path && file_exists($old_image_path)) {
                unlink($old_image_path);
            }
            $new_image_path = $target_file;
        }
    }

    try {
        $sql = "UPDATE meter_readings SET 
                    reading_date = :reading_date, 
                    current_reading = :current_reading, 
                    per_unit_rate = :per_unit_rate, 
                    image_path = :image_path
                WHERE id = :id";
        $db->query($sql, [
            ':reading_date' => getInput($data, 'reading_date'),
            ':current_reading' => (int)getInput($data, 'current_reading'),
            ':per_unit_rate' => (float)getInput($data, 'per_unit_rate'),
            ':image_path' => $new_image_path,
            ':id' => $id,
        ]);
        return ['status' => 'success', 'message' => 'Meter reading updated successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Fetches all meter readings, calculating units and total amount on the fly.
 */
function getMeterReadings(): array
{
    $db = DB::getInstance();
    $sql = "
        SELECT 
            mr.id, mr.meter_id, mr.reading_date, mr.previous_reading, mr.current_reading, 
            mr.per_unit_rate, mr.image_path,
            (mr.current_reading - mr.previous_reading) AS units_consumed,
            (mr.current_reading - mr.previous_reading) * mr.per_unit_rate AS total_amount,
            m.meter_name, m.meter_type,
            b.name as building_name
        FROM meter_readings mr
        LEFT JOIN meters m ON mr.meter_id = m.id
        LEFT JOIN buildings b ON m.building_id = b.id
        ORDER BY mr.reading_date DESC, mr.id DESC
    ";
    return $db->query($sql)->fetchAll();
}

/**
 * Deletes a meter reading and its associated image file.
 */
function deleteMeterReading(int $id): array
{
    $db = DB::getInstance();
    try {
        // Fetch the image path before deleting the record
        $reading = $db->query("SELECT image_path FROM meter_readings WHERE id = :id", [':id' => $id])->fetch();
        
        $db->query("DELETE FROM meter_readings WHERE id = :id", [':id' => $id]);

        // Delete the image file from the server if it exists
        if ($reading && !empty($reading['image_path']) && file_exists($reading['image_path'])) {
            unlink($reading['image_path']);
        }

        return ['status' => 'success', 'message' => 'Meter reading deleted successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Fetches all rent history records, joined with unit and building info.
 * Corrected to use 'b.name' for the building table column.
 */
function getRentHistory(): array
{
    $db = DB::getInstance();
    $sql = "SELECT rh.*, u.unit_name, b.name AS building_name 
            FROM rent_history rh
            JOIN units u ON rh.unit_id = u.id
            JOIN buildings b ON u.building_id = b.id
            ORDER BY b.name, u.unit_name, rh.effective_from DESC";
    return $db->query($sql)->fetchAll();
}

/**
 * Adds a new rent history entry.
 * This function is smart: it finds the previous "current" rent for the unit
 * and sets its `effective_to` date to the day before the new rent starts.
 */
function addRentHistory(array $data): array
{
    $db = DB::getInstance();
    $unit_id = (int)$data['unit_id'];
    $rent_amount = (float)$data['rent_amount'];
    $effective_from = $data['effective_from'];
    $effective_to = !empty($data['effective_to']) ? $data['effective_to'] : null;

    // --- NEW: Backend Validation ---
    if ($effective_to && strtotime($effective_from) > strtotime($effective_to)) {
        return ['status' => 'error', 'message' => 'The "Effective From" date cannot be after the "Effective To" date.'];
    }
    // --- End of Validation ---

    try {
        $db->beginTransaction();

        // 1. Find the currently active rent for this unit
        $stmt = $db->query("SELECT id FROM rent_history WHERE unit_id = :unit_id AND effective_to IS NULL ORDER BY effective_from DESC LIMIT 1", [':unit_id' => $unit_id]);
        $current_rent = $stmt->fetch();

        if ($current_rent) {
            // 2. Update its `effective_to` date
            $new_end_date = date('Y-m-d', strtotime($effective_from . ' -1 day'));
            $update_sql = "UPDATE rent_history SET effective_to = :new_end_date WHERE id = :id";
            $db->query($update_sql, [':new_end_date' => $new_end_date, ':id' => $current_rent['id']]);
        }

        // 3. Insert the new rent history record
        $insert_sql = "INSERT INTO rent_history (unit_id, rent_amount, effective_from, effective_to) 
                        VALUES (:unit_id, :rent_amount, :effective_from, NULL)";
        $db->query($insert_sql, [
            ':unit_id' => $unit_id,
            ':rent_amount' => $rent_amount,
            ':effective_from' => $effective_from,
        ]);

        $db->commit();
        return ['status' => 'success', 'message' => 'Rent history added successfully!'];

    } catch (Exception $e) {
        $db->rollBack();
        // You might want to log the actual error here for debugging
        // error_log($e->getMessage()); 
        return ['status' => 'error', 'message' => 'Database error: Could not save rent history.'];
    }
}

/**
 * Updates an existing rent history entry.
 */
function updateRentHistory(array $data, int $id): array
{
    $db = DB::getInstance();
    $effective_from = $data['effective_from'];
    $effective_to = !empty($data['effective_to']) ? $data['effective_to'] : null;

    // --- NEW: Backend Validation ---
    if ($effective_to && strtotime($effective_from) > strtotime($effective_to)) {
        return ['status' => 'error', 'message' => 'The "Effective From" date cannot be after the "Effective To" date.'];
    }
    // --- End of Validation ---

    $sql = "UPDATE rent_history SET 
                unit_id = :unit_id, 
                rent_amount = :rent_amount, 
                effective_from = :effective_from,
                effective_to = :effective_to
            WHERE id = :id";
    try {
        $db->query($sql, [
            ':unit_id' => (int)$data['unit_id'],
            ':rent_amount' => (float)$data['rent_amount'],
            ':effective_from' => $effective_from,
            ':effective_to' => $effective_to,
            ':id' => $id
        ]);
        return ['status' => 'success', 'message' => 'Rent history updated successfully!'];
    } catch (Exception $e) {
        // error_log($e->getMessage());
        return ['status' => 'error', 'message' => 'Database error: Could not update rent history.'];
    }
}

/**
 * Deletes a rent history entry.
 * Prevents deletion of the currently active rent.
 */
function deleteRentHistory(int $id): array
{
    $db = DB::getInstance();
    $check = $db->query("SELECT id FROM rent_history WHERE id = :id AND effective_to IS NULL", [':id' => $id])->fetch();
    if ($check) {
        return ['status' => 'error', 'message' => 'Cannot delete the currently active rent history.'];
    }

    try {
        $db->query("DELETE FROM rent_history WHERE id = :id", [':id' => $id]);
        return ['status' => 'success', 'message' => 'Rent history deleted successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Fetches all meter-to-tenant mappings with details.
 */
/**
 * Fetches all meter-to-tenant mappings with details.
 */
function getMeterTenantMappings(): array
{
    $db = DB::getInstance();
    // CORRECTED: Changed t.tenant_name to t.name
    $sql = "SELECT mtm.*, m.meter_name, b.name AS building_name, t.name AS tenant_name 
            FROM meter_tenant_mapping mtm
            JOIN meters m ON mtm.meter_id = m.id
            JOIN tenants t ON mtm.tenant_id = t.id
            JOIN buildings b ON m.building_id = b.id
            ORDER BY b.name, m.meter_name, mtm.effective_from DESC";
    return $db->query($sql)->fetchAll();
}

/**
 * Adds a new meter-to-tenant mapping.
 * Since sharing is allowed, we simply insert the new record.
 */
function addMeterTenantMapping(array $data): array
{
    $db = DB::getInstance();
    $effective_from = $data['effective_from'];
    $effective_to = !empty($data['effective_to']) ? $data['effective_to'] : null;

    // Backend Validation
    if ($effective_to && strtotime($effective_from) > strtotime($effective_to)) {
        return ['status' => 'error', 'message' => 'The "Effective From" date cannot be after the "Effective To" date.'];
    }

    try {
        $sql = "INSERT INTO meter_tenant_mapping (meter_id, tenant_id, effective_from, effective_to) 
                VALUES (:meter_id, :tenant_id, :effective_from, :effective_to)";
        $db->query($sql, [
            ':meter_id' => (int)$data['meter_id'],
            ':tenant_id' => (int)$data['tenant_id'],
            ':effective_from' => $effective_from,
            ':effective_to' => $effective_to,
        ]);
        return ['status' => 'success', 'message' => 'Mapping added successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Database error: Could not save mapping.'];
    }
}

/**
 * Updates an existing meter-to-tenant mapping.
 */
function updateMeterTenantMapping(array $data, int $id): array
{
    $db = DB::getInstance();
    $effective_from = $data['effective_from'];
    $effective_to = !empty($data['effective_to']) ? $data['effective_to'] : null;

    // Backend Validation
    if ($effective_to && strtotime($effective_from) > strtotime($effective_to)) {
        return ['status' => 'error', 'message' => 'The "Effective From" date cannot be after the "Effective To" date.'];
    }

    try {
        $sql = "UPDATE meter_tenant_mapping SET 
                    meter_id = :meter_id, 
                    tenant_id = :tenant_id, 
                    effective_from = :effective_from,
                    effective_to = :effective_to
                WHERE id = :id";
        $db->query($sql, [
            ':meter_id' => (int)$data['meter_id'],
            ':tenant_id' => (int)$data['tenant_id'],
            ':effective_from' => $effective_from,
            ':effective_to' => $effective_to,
            ':id' => $id
        ]);
        return ['status' => 'success', 'message' => 'Mapping updated successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Database error: Could not update mapping.'];
    }
}

/**
 * Deletes a meter-to-tenant mapping.
 * Prevents deletion of a currently active mapping.
 */
function deleteMeterTenantMapping(int $id): array
{
    $db = DB::getInstance();
    $check = $db->query("SELECT id FROM meter_tenant_mapping WHERE id = :id AND effective_to IS NULL", [':id' => $id])->fetch();
    if ($check) {
        return ['status' => 'error', 'message' => 'Cannot delete a currently active mapping.'];
    }

    try {
        $db->query("DELETE FROM meter_tenant_mapping WHERE id = :id", [':id' => $id]);
        return ['status' => 'success', 'message' => 'Mapping deleted successfully!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Database error: Could not delete mapping.'];
    }
}

/**
 * Generates a new bill for a tenant for a specific month.
 * This creates the initial record with a balance. It does NOT handle payments.
 *
 * @param array $data Contains 'tenant_id', 'billing_month', 'other_charges', 'adjustment_amount'.
 * @return array
 */
function generateBill(array $data): array
{
    $db = DB::getInstance();

    $tenant_id = (int)$data['tenant_id'];
    $billing_month = $data['billing_month']; // format: Y-m-01
    $rent_amount = (float)($data['rent_amount'] ?? 0);
    $electricity_amount = (float)($data['electricity_amount'] ?? 0);
    $other_charges = (float)($data['other_charges'] ?? 0);
    $adjustment_amount = (float)($data['adjustment_amount'] ?? 0);

    // Total bill amount
    $total_amount = $rent_amount + $electricity_amount + $other_charges + $adjustment_amount;

    if ($total_amount == 0) {
        return ['status' => 'error', 'message' => 'Nothing to bill.'];
    }

    $db->beginTransaction();

    try {

        // 1️⃣ Insert / Update billing table (ONLY for structure, not balance)
        $existing = $db->query(
            "SELECT id FROM billing WHERE tenant_id = ? AND billing_month = ?",
            [$tenant_id, $billing_month]
        )->fetch();

        if ($existing) {
            $db->query(
                "UPDATE billing 
                 SET rent_amount = ?, electricity_amount = ?, other_charges = ?, adjustment_amount = ?
                 WHERE id = ?",
                [$rent_amount, $electricity_amount, $other_charges, $adjustment_amount, $existing['id']]
            );
        } else {
            $db->query(
                "INSERT INTO billing 
                (tenant_id, billing_month, rent_amount, electricity_amount, other_charges, adjustment_amount) 
                VALUES (?, ?, ?, ?, ?, ?)",
                [$tenant_id, $billing_month, $rent_amount, $electricity_amount, $other_charges, $adjustment_amount]
            );
        }

        // 2️⃣ Insert transaction (MAIN LEDGER ENTRY)
        $db->query(
            "INSERT INTO transactions 
            (tenant_id, billing_month, type, amount, description) 
            VALUES (?, ?, 'charge', ?, ?)",
            [
                $tenant_id,
                $billing_month,
                $total_amount, // ✅ POSITIVE
                "Monthly bill generated"
            ]
        );

        $db->commit();

        return [
            'status' => 'success',
            'message' => 'Bill generated successfully.'
        ];

    } catch (Exception $e) {
        $db->rollBack();
        return [
            'status' => 'error',
            'message' => 'Failed to generate bill: ' . $e->getMessage()
        ];
    }
}

/**
 * Fetches all bills with tenant, unit, and building details.
 * The 'status' is now derived from the 'balance' in the view.
 */
function getBills(array $filters = []): array
{
    $db = DB::getInstance();

    try {

        $where = [];
        $params = [];

        // Optional filter: tenant_id
        if (!empty($filters['tenant_id'])) {
            $where[] = "b.tenant_id = ?";
            $params[] = (int)$filters['tenant_id'];
        }

        // Optional filter: month (Y-m format)
        if (!empty($filters['billing_month'])) {
            $where[] = "DATE_FORMAT(b.billing_month, '%Y-%m') = ?";
            $params[] = $filters['billing_month'];
        }

        $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
        SELECT
            b.id,
            b.tenant_id,
            b.billing_month,
            b.rent_amount,
            b.electricity_amount,
            b.other_charges,
            b.adjustment_amount,

            t.name AS tenant_name,
            u.unit_name,
            bd.name AS building_name,

            (
                SELECT COALESCE(SUM(tr.amount),0)
                FROM transactions tr
                WHERE tr.tenant_id = b.tenant_id
                AND tr.billing_month <= b.billing_month
            ) AS running_balance

        FROM billing b

        JOIN tenants t ON b.tenant_id = t.id

        JOIN tenant_unit_mapping tum 
            ON t.id = tum.tenant_id
            AND tum.effective_from <= b.billing_month
            AND (tum.effective_to IS NULL OR tum.effective_to >= b.billing_month)

        JOIN units u ON tum.unit_id = u.id
        JOIN buildings bd ON u.building_id = bd.id

        $where_sql

        ORDER BY b.tenant_id ASC, b.billing_month DESC
        ";

        $records = $db->query($sql, $params)->fetchAll();

        // Optional formatting (clean for UI)
        foreach ($records as &$row) {

            $row['month_label'] = date('M Y', strtotime($row['billing_month']));

            $row['total_amount'] =
                (float)$row['rent_amount'] +
                (float)$row['electricity_amount'] +
                (float)$row['other_charges'] +
                (float)$row['adjustment_amount'];

            $row['running_balance'] = (float)$row['running_balance'];
        }

        return [
            'status' => 'success',
            'data' => $records
        ];

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Failed to fetch bills: ' . $e->getMessage()
        ];
    }
}

/**
 * Processes a payment, applying it to the oldest outstanding balance first.
 * It updates the billing table and creates a transaction record.
 *
 * @param array $data Contains 'tenant_id', 'payment_amount', 'payment_date'.
 * @return array
 */
function processPayment(array $data): array
{
    $db = DB::getInstance();
    $tenant_id = (int)$data['tenant_id'];
    $payment_amount = (float)$data['payment_amount'];
    $payment_date = $data['payment_date'];

    if ($payment_amount <= 0) {
        return ['status' => 'error', 'message' => 'Payment amount must be positive.'];
    }

    $db->beginTransaction();

    try {
        $remaining_payment = $payment_amount;
        $payment_log = [];

        // Get all months where tenant has bills (oldest first)
        $bills = $db->query(
            "SELECT billing_month 
             FROM billing 
             WHERE tenant_id = ? 
             ORDER BY billing_month ASC",
            [$tenant_id]
        )->fetchAll();

        foreach ($bills as $bill) {

            if ($remaining_payment <= 0) break;

            $billing_month = $bill['billing_month'];

            // 🔥 Calculate running balance till this month
            $balance = $db->query(
                "SELECT COALESCE(SUM(amount),0) as balance 
                 FROM transactions 
                 WHERE tenant_id = ? AND billing_month <= ?",
                [$tenant_id, $billing_month]
            )->fetch()['balance'];

            // Skip if no due
            if ($balance <= 0) continue;

            $amount_to_apply = min($remaining_payment, $balance);

            // ✅ Insert NEGATIVE payment
            $db->query(
                "INSERT INTO transactions 
                (tenant_id, billing_month, type, amount, description, created_at) 
                VALUES (?, ?, 'payment', ?, ?, ?)",
                [
                    $tenant_id,
                    $billing_month,
                    -$amount_to_apply,
                    "Payment received via dashboard",
                    $payment_date
                ]
            );

            $payment_log[] = "Applied ₹" . number_format($amount_to_apply, 2) . " to " . date('F Y', strtotime($billing_month));

            $remaining_payment -= $amount_to_apply;
        }

        // ✅ If extra payment → just store as advance (negative balance automatically)
        if ($remaining_payment > 0) {

            $current_month = date('Y-m-01');

            $db->query(
                "INSERT INTO transactions 
                (tenant_id, billing_month, type, amount, description, created_at) 
                VALUES (?, ?, 'payment', ?, ?, ?)",
                [
                    $tenant_id,
                    $current_month,
                    -$remaining_payment,
                    "Advance payment",
                    $payment_date
                ]
            );

            $payment_log[] = "Remaining ₹" . number_format($remaining_payment, 2) . " stored as advance";
        }

        $db->commit();

        return [
            'status' => 'success',
            'message' => "Payment processed successfully! " . implode('; ', $payment_log)
        ];

    } catch (Exception $e) {
        $db->rollBack();
        return [
            'status' => 'error',
            'message' => 'Transaction failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Adds a one-time charge (e.g., late fee, maintenance) to a tenant's bill.
 * This updates the balance and creates a transaction.
 *
 * @param array $data Contains 'tenant_id', 'billing_month', 'amount', 'description'.
 * @return array
 */
function addCharge(array $data): array
{
    $db = DB::getInstance();
    $tenant_id = (int)$data['tenant_id'];
    $billing_month = date('Y-m-01', strtotime($data['billing_month']));
    $amount = (float)$data['amount'];
    $description = $data['description'] ?? 'One-time charge';

    if ($amount <= 0) {
        return ['status' => 'error', 'message' => 'Charge amount must be positive.'];
    }

    $db->beginTransaction();

    try {
        // Update the balance for the corresponding bill
        $db->query("UPDATE billing SET balance = balance + ? WHERE tenant_id = ? AND billing_month = ?", [$amount, $tenant_id, $billing_month]);

        // Create a transaction record
        $db->query(
            "INSERT INTO transactions (tenant_id, billing_month, type, amount, description) VALUES (?, ?, 'charge', ?, ?)",
            [$tenant_id, $billing_month, $amount, $description]
        );

        $db->commit();
        return ['status' => 'success', 'message' => 'Charge added successfully!'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['status' => 'error', 'message' => 'Failed to add charge: ' . $e->getMessage()];
    }
}



/**
 * Helper function to check for duplicate bills.
 */
function checkDuplicateBill(int $tenant_id, string $billing_month): bool
{
    $db = DB::getInstance();
    $firstDayOfMonth = date('Y-m-01', strtotime($billing_month));
    $stmt = $db->prepare("SELECT id FROM billing WHERE tenant_id = ? AND billing_month = ?");
    $stmt->execute([$tenant_id, $firstDayOfMonth]);
    return $stmt->fetch() !== false;
}

/**
 * Helper function to calculate electricity amount for a tenant in a given month.
 * (This function remains the same as it was already well-written)
 */
function calculateElectricityAmount(int $tenant_id, string $billing_month): float
{
    $db = DB::getInstance();
    $electricity_amount = 0;

    $meters = $db->query("
        SELECT m.id, m.meter_type
        FROM meter_tenant_mapping mtm
        JOIN meters m ON mtm.meter_id = m.id
        WHERE mtm.tenant_id = :tenant_id
        AND mtm.effective_from <= :billing_month_start
        AND (mtm.effective_to IS NULL OR mtm.effective_to >= :billing_month_end)
    ", [
        ':tenant_id' => $tenant_id,
        ':billing_month_start' => $billing_month,
        ':billing_month_end' => $billing_month
    ])->fetchAll();

    foreach ($meters as $meter) {
        $meter_id = $meter['id'];
        $meter_type = $meter['meter_type'];

        $reading = $db->query("
            SELECT previous_reading, current_reading, per_unit_rate
            FROM meter_readings
            WHERE meter_id = :meter_id
            AND DATE_FORMAT(reading_date,'%Y-%m') = DATE_FORMAT(:billing_month,'%Y-%m')
            LIMIT 1
        ", [
            ':meter_id' => $meter_id,
            ':billing_month' => $billing_month
        ])->fetch();

        if (!$reading) continue;

        $units = $reading['current_reading'] - $reading['previous_reading'];
        $cost = $units * $reading['per_unit_rate'];

        if ($meter_type === 'personal') {
            $electricity_amount += $cost;
        } else {
            $tenant_count = $db->query("
                SELECT COUNT(DISTINCT mtm2.tenant_id) as total
                FROM meter_tenant_mapping mtm2
                WHERE mtm2.meter_id = :meter_id
                AND mtm2.effective_from <= :billing_month_start
                AND (mtm2.effective_to IS NULL OR mtm2.effective_to >= :billing_month_end)
            ", [
                ':meter_id' => $meter_id,
                ':billing_month_start' => $billing_month,
                ':billing_month_end' => $billing_month
            ])->fetch();

            $share = ($tenant_count && $tenant_count['total'] > 0) ? $cost / $tenant_count['total'] : 0;
            $electricity_amount += $share;
        }
    }
    return $electricity_amount;
}












/**
 * Phase 1: The FIFO Distribution Engine
 */
/**
 * Phase 1: The Bucket-Filling FIFO Engine
 */
/**
 * Phase 1: The Bucket-Filling FIFO Engine
 */
/**
 * Phase 2: The Synced Entry Engine
 */
function processFIFOLedgerEntry($data) {
    $db = DB::getInstance();
    
    $tenant_id = (int)$data['tenant_id'];
    $rent_pool = (float)($data['rent_amount'] ?? 0);
    $elec_pool = (float)($data['electricity_amount'] ?? 0); 
    $adj_pool  = (float)($data['adjustment_amount'] ?? 0);

    if ($tenant_id <= 0) return "Invalid Tenant.";

    try {
        $db->beginTransaction();
        $truth = getTenantMonthlyStructure($tenant_id);

        $last_valid_elec_month = null;

        foreach ($truth as $month_key => $month_data) {
            if ($rent_pool <= 0 && $elec_pool <= 0) break;

            $target_month = $month_data['meta']['date'];
            $to_insert_rent = 0;
            $to_insert_elec = 0;
            $to_insert_adj  = 0; 

            // --- A. Rent: Standard FIFO (Now includes Adjustments) ---
            if ($rent_pool > 0) {
                $expected_rent = (float)$month_data['rent'];
                // UPDATED: Now looks at both Paid + Adjustments
                $existing = $db->query("SELECT SUM(rent_amount) as paid, SUM(adjustment_amount) as adj 
                                        FROM billing WHERE tenant_id = :tid AND billing_month = :m", 
                                       [':tid' => $tenant_id, ':m' => $target_month])->fetch();
                
                $already_settled_rent = (float)($existing['paid'] ?? 0) + (float)($existing['adj'] ?? 0);
                $rent_gap = $expected_rent - $already_settled_rent;

                if ($rent_gap > 0) {
                    $to_insert_rent = min($rent_pool, $rent_gap);
                    $rent_pool -= $to_insert_rent;
                }
            }

            // --- B. Electricity: Adjusted Reading-Driven FIFO ---
            if ($elec_pool > 0 && !empty($month_data['electricity'])) {
                $last_valid_elec_month = $target_month; 
                $expected_elec = array_sum(array_column($month_data['electricity'], 'my_share'));
                
                // UPDATED: Fetch existing payments AND existing adjustments for this specific month
                $existing = $db->query("SELECT SUM(electricity_amount) as paid, SUM(adjustment_amount) as adj 
                                        FROM billing WHERE tenant_id = :tid AND billing_month = :m", 
                                       [':tid' => $tenant_id, ':m' => $target_month])->fetch();
                
                $already_paid_elec = (float)($existing['paid'] ?? 0);
                $already_adj_elec = (float)($existing['adj'] ?? 0);

                // The logic: (Expected + Current Input Adj + Existing DB Adj) - Already Paid
                // This ensures if -75 exists in DB, the gap becomes 0.
                $elec_gap = ($expected_elec + $adj_pool + $already_adj_elec) - $already_paid_elec;

                if ($elec_gap > 0) {
                    $to_insert_elec = min($elec_pool, $elec_gap);
                    $elec_pool -= $to_insert_elec;
                    
                    $to_insert_adj = $adj_pool;
                    $adj_pool = 0; // Global adjustment consumed
                }
            }

            // --- C. Save Row ---
            if ($to_insert_rent > 0 || $to_insert_elec > 0 || $to_insert_adj != 0) {
                $db->query("INSERT INTO billing (tenant_id, billing_month, rent_amount, electricity_amount, adjustment_amount) 
                            VALUES (:tid, :m, :rent, :elec, :adj)", 
                            [
                                ':tid'   => $tenant_id, 
                                ':m'     => $target_month, 
                                ':rent'  => $to_insert_rent,
                                ':elec'  => $to_insert_elec, 
                                ':adj'   => $to_insert_adj 
                            ]);
            }
        }

        // --- D. Final Overflow (Advance Parking) ---
        // If money is still left in pools, it goes to the NEXT month
        if ($rent_pool > 0 || $elec_pool > 0) {
            $rent_month = date('Y-m-01', strtotime("+1 month", strtotime(array_key_last($truth))));
            $elec_month = $last_valid_elec_month ?? array_key_last($truth);

            if ($rent_pool > 0) {
                $db->query("INSERT INTO billing (tenant_id, billing_month, rent_amount, electricity_amount, adjustment_amount) 
                            VALUES (:tid, :m, :rent, 0, 0)", [':tid' => $tenant_id, ':m' => $rent_month, ':rent' => $rent_pool]);
            }
            if ($elec_pool > 0) {
                 $db->query("INSERT INTO billing (tenant_id, billing_month, rent_amount, electricity_amount, adjustment_amount) 
                            VALUES (:tid, :m, 0, :elec, 0)", [':tid' => $tenant_id, ':m' => $elec_month, ':elec' => $elec_pool]);
            }
        }

        $db->commit();
        return "Ledger updated successfully.";
    } catch (Exception $e) {
        $db->rollback();
        die("Database Error: " . $e->getMessage()); 
    }
}

/**
 * Updated Helper for Electricity
 */
function getOldestTargetMonth($tenant_id, $type) {
    $db = DB::getInstance();
    $col = ($type == 'rent') ? 'rent_amount' : 'electricity_amount';
    
    $last = $db->query("SELECT MAX(billing_month) as m FROM billing WHERE tenant_id = :tid AND $col > 0", [':tid' => $tenant_id])->fetch();
    return $last['m'] ? date('Y-m-01', strtotime("+1 month", strtotime($last['m']))) : '2026-01-01';
}

/**
 * Phase 2: The String Builder
 */
/**
 * Calculates the current pending debt for a tenant by comparing 
 * the 'Monthly Structure' (Truth) against the 'Billing Table' (Reality).
 * * Logic: (Expected from Beginning) + (Adjustments) - (Cash Paid)
 */
function getTenantDebtString($tenant_id, $type = 'rent') {
    $db = DB::getInstance();
    $structure = getTenantMonthlyStructure($tenant_id);
    
    $ledger = $db->query("SELECT SUM(rent_amount) as r, SUM(electricity_amount) as e, SUM(adjustment_amount) as a 
                          FROM billing WHERE tenant_id = ?", [$tenant_id])->fetch();

    $rent_paid = (float)($ledger['r'] ?? 0);
    $elec_paid = (float)($ledger['e'] ?? 0);
    $total_adj = (float)($ledger['a'] ?? 0);

    if ($type === 'rent') {
        $total_expected = 0;
        $pending_months = [];
        foreach ($structure as $month => $data) {
            if ($month > date('Y-m')) break;
            if ($data['rent'] === null) {
                return [
                    'total' => 'Not Fixed', 
                    'formula_with_sums' => 'Check Rent History', 
                    'is_advance' => false, 
                    'is_warning' => true
                ];
            }
            $rate = (float)$data['rent'];
            if (($total_expected + $rate) > $rent_paid) {
                $pending_months[] = date('M y', strtotime($month));
            }
            $total_expected += $rate;
        }
        $gap = $total_expected - $rent_paid;
        $count = count($pending_months);
        $formula = $count > 0 ? implode(", ", $pending_months) . " | (₹" . number_format($gap/$count,0) . " x $count = ₹" . number_format($gap, 0) . ")" : "";
        
        return [
            'total' => ($gap <= 0) ? "Settled" : "₹" . number_format($gap, 2), 
            'formula_with_sums' => $formula, 
            'is_advance' => ($gap < 0)
        ];

    } else {
        $total_elec_expected = 0;
        $elec_parts = [];
        $has_any_reading = false;

        foreach ($structure as $month => $data) {
            if ($month > date('Y-m')) break;
            $m_label = date('M y', strtotime($month));
            
            if (!empty($data['electricity'])) {
                $has_any_reading = true;
                foreach ($data['electricity'] as $m) {
                    $share = (float)$m['my_share'];
                    $elec_parts[] = "$m_label: ({$m['units']} u x {$m['rate']}" . ($m['sharer_count'] > 1 ? " / {$m['sharer_count']}" : "") . " = " . number_format($share, 0) . ")";
                    $total_elec_expected += $share;
                }
            } else {
                $elec_parts[] = "<span class='text-danger' style='font-size:0.7rem;'>$m_label: No Meter Mapped</span>";
            }
        }

        $elec_gap = ($total_elec_expected + $total_adj) - $elec_paid;
        
        if (!$has_any_reading && $elec_paid == 0) {
            return [
                'total' => 'No Meter', 
                'formula_with_sums' => implode("<br>", $elec_parts), 
                'is_warning' => true,
                'is_advance' => false // ADDED THIS TO FIX LINE 169
            ];
        }

        return [
            'total' => ($elec_gap <= 0 && $has_any_reading) ? 'Settled' : "₹" . number_format($elec_gap, 2),
            'formula_with_sums' => implode("<br>", $elec_parts) . (($total_adj != 0) ? " (Adj: $total_adj)" : ""),
            'is_advance' => ($elec_gap < 0)
        ];
    }
}

function getTenantMonthlyStructure($tenant_id) {
    $db = DB::getInstance();
    
    // 1. Check earliest Unit Mapping
    $history = $db->query("SELECT MIN(effective_from) as start FROM tenant_unit_mapping 
                           WHERE tenant_id = :tid", [':tid' => $tenant_id])->fetch();
    
    // 2. Check earliest Meter Mapping (This catches Saket's Jan 01 entry)
    $m_history = $db->query("SELECT MIN(effective_from) as start FROM meter_tenant_mapping 
                             WHERE tenant_id = :tid", [':tid' => $tenant_id])->fetch();
    
    // Pick the earliest of the two
    $dates = array_filter([$history['start'] ?? null, $m_history['start'] ?? null]);
    $start_date = !empty($dates) ? min($dates) : '2026-01-01';
    
    $current_month = date('Y-m-01', strtotime($start_date));
    $today = date('Y-m-01');
    $statement = [];

    while ($current_month <= $today) {
        $month_key = date('Y-m', strtotime($current_month));
        
        $statement[$month_key] = [
            'meta' => ['date' => $current_month, 'label' => date('F Y', strtotime($current_month))],
            'rent' => null, 
            'electricity' => []
        ];

        // A. Unit/Rent Logic
        $unitMapping = $db->query("SELECT unit_id FROM tenant_unit_mapping 
                                    WHERE tenant_id = :tid 
                                    AND :m BETWEEN effective_from AND IFNULL(effective_to, '2099-12-31') 
                                    LIMIT 1", [':tid' => $tenant_id, ':m' => $current_month])->fetch();

        if ($unitMapping) {
            $uid = $unitMapping['unit_id'];
            $rentRecord = $db->query("SELECT rent_amount FROM rent_history 
                                      WHERE unit_id = :uid 
                                      AND :m BETWEEN effective_from AND IFNULL(effective_to, '2099-12-31') 
                                      LIMIT 1", [':uid' => $uid, ':m' => $current_month])->fetch();
            
            if ($rentRecord) {
                $statement[$month_key]['rent'] = (float)$rentRecord['rent_amount'];
            }
        }

        // B. Electricity Logic (This will now find Meter 2 for Saket in Jan/Feb)
        $mappings = $db->query("SELECT meter_id FROM meter_tenant_mapping 
                                WHERE tenant_id = :tid 
                                AND :m BETWEEN effective_from AND IFNULL(effective_to, '2099-12-31')", 
                                [':tid' => $tenant_id, ':m' => $current_month])->fetchAll();

        foreach ($mappings as $m) {
            $mid = $m['meter_id'];
            $reading_start = date('Y-m-01', strtotime("+1 month", strtotime($current_month)));
            $reading_end   = date('Y-m-t', strtotime($reading_start));
            
            $reading = $db->query("SELECT * FROM meter_readings WHERE meter_id = :mid AND reading_date BETWEEN :s AND :e LIMIT 1", 
                                 [':mid' => $mid, ':s' => $reading_start, ':e' => $reading_end])->fetch();

            if ($reading) {
                $sharers = $db->query("SELECT GROUP_CONCAT(tenant_id) as ids, COUNT(tenant_id) as total 
                                       FROM meter_tenant_mapping 
                                       WHERE meter_id = :mid 
                                       AND :m BETWEEN effective_from AND IFNULL(effective_to, '2099-12-31')", 
                                       [':mid' => $mid, ':m' => $current_month])->fetch();

                $units = (float)$reading['current_reading'] - (float)$reading['previous_reading'];
                $total_cost = $units * (float)$reading['per_unit_rate'];
                $tenant_share = ($sharers['total'] > 0) ? ($total_cost / (int)$sharers['total']) : 0;

                $statement[$month_key]['electricity'][] = [
                    'meter_id'     => $mid,
                    'meter_type'   => ($sharers['total'] > 1) ? 'common' : 'personal',
                    'reading_date' => $reading['reading_date'],
                    'prev'         => $reading['previous_reading'],
                    'curr'         => $reading['current_reading'],
                    'units'        => $units,
                    'rate'         => $reading['per_unit_rate'],
                    'total_amount' => $total_cost,
                    'sharer_ids'   => $sharers['ids'],
                    'sharer_count' => $sharers['total'],
                    'my_share'     => $tenant_share
                ];
            }
        }
        $current_month = date('Y-m-d', strtotime("+1 month", strtotime($current_month)));
    }
    return $statement;
}

/**
 * Fetches all active tenants with their Location Context
 * Replace your existing simple SELECT with this.
 */
/**
 * Fetches active tenants with their current Building and Unit context.
 * Uses the mapping table to find the current active residence.
 */
function getActiveTenantsWithContext() {
    $db = DB::getInstance();
    $today = date('Y-m-d');
    
    $sql = "SELECT 
                t.id, 
                t.name as tenant_name, 
                u.unit_name, 
                b.name as building_name 
            FROM tenants t
            JOIN tenant_unit_mapping tum ON t.id = tum.tenant_id
            JOIN units u ON tum.unit_id = u.id
            JOIN buildings b ON u.building_id = b.id
            WHERE t.status = 'active'
            AND :today BETWEEN tum.effective_from AND IFNULL(tum.effective_to, '2099-12-31')
            ORDER BY b.name ASC, u.unit_name ASC";
            
    return $db->query($sql, [':today' => $today])->fetchAll();
}


/**
 * Fetch all financial movements for a specific tenant
 */




/**
 * Fetch all billing records for a tenant (Newest on top)
 */
function getTenantPaymentHistory($tenant_id) {
    $db = DB::getInstance();
    $sql = "SELECT id, billing_month, rent_amount, electricity_amount, adjustment_amount, created_at 
            FROM billing 
            WHERE tenant_id = ? 
            ORDER BY created_at DESC";
    return $db->fetchAll($sql, [$tenant_id]);
}

/**
 * Update a specific billing row from the edit modal
 */
function updateBillPayment($data) {
    $db = DB::getInstance();
    $id = (int)$data['bill_id'];
    
    $updateData = [
        'rent_amount'        => (float)($data['rent_amount'] ?? 0),
        'electricity_amount' => (float)($data['electricity_amount'] ?? 0),
        'adjustment_amount'  => (float)($data['adjustment_amount'] ?? 0),
    ];

    try {
        $db->update('billing', $updateData, "id = :id", [':id' => $id]);
        return ['status' => 'success', 'message' => 'Record updated successfully.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()];
    }
}

/**
 * Delete a billing row
 */
function deleteBill($id) {
    $db = DB::getInstance();
    try {
        $db->delete('billing', "id = ?", [$id]);
        return ['status' => 'success', 'message' => 'Record deleted successfully.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Deletion failed.'];
    }
}
function getTenantById($id) {
    $db = DB::getInstance();
    $sql = "SELECT id, name FROM tenants WHERE id = ? LIMIT 1";
    return $db->fetch($sql, [$id]);
}
?>