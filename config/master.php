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
function getDashboardStats(): array
{
    $db = DB::getInstance();

    $totalBuildings = $db->fetch("SELECT COUNT(*) as total FROM buildings")['total'] ?? 0;
    $totalUnits     = $db->fetch("SELECT COUNT(*) as total FROM units")['total'] ?? 0;
    $totalTenants   = $db->fetch("SELECT COUNT(*) as total FROM tenants WHERE status = 'active'")['total'] ?? 0;

    return [
        'buildings' => $totalBuildings,
        'units'     => $totalUnits,
        'tenants'   => $totalTenants
    ];
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
            u.unit_name, u.building_name
        FROM tenants t
        LEFT JOIN tenant_unit_mapping m ON t.id = m.tenant_id
        LEFT JOIN units u ON m.unit_id = u.id
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

/**
 * Creates a new tenant and assigns them to a unit with a date range.
 */
function createTenant(array $data, array $file_data): array
{
    $db = DB::getInstance();
    
    // 1. Validate Mobile Number
    $mobile = getInput($data, 'mobile');
    if ($mobile && !preg_match('/^[6-9]\d{9}$/', $mobile)) {
        return ['status' => 'error', 'message' => 'Mobile number must be a valid 10-digit number starting with 6-9.'];
    }

    // 2. Handle File Upload
    $upload_result = handleIdProofUpload($file_data['id_proof'] ?? null);
    if ($upload_result['status'] === 'error') {
        return $upload_result;
    }
    $id_proof_filename = $upload_result['status'] === 'success' ? $upload_result['filename'] : null;

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

        // Create tenant record
        $db->query("INSERT INTO tenants (name, mobile, id_proof, status) VALUES (:name, :mobile, :id_proof, :status)", [
            ':name'     => getInput($data, 'name'),
            ':mobile'   => $mobile,
            ':id_proof' => $id_proof_filename,
            ':status'   => getInput($data, 'status', 'active')
        ]);
        $tenant_id = $db->lastInsertId();

        // Create unit mapping if a unit was selected
        if ($tenant_id && $unit_id > 0 && !empty($effective_from)) {
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
        if ($id_proof_filename && file_exists('uploads/id_proofs/' . $id_proof_filename)) {
            unlink('uploads/id_proofs/' . $id_proof_filename);
        }
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Updates a tenant's details and handles unit moves or date edits.
 */
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