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
function createTenant(array $data, array $files = [], int $id = null): array
{
    $db = DB::getInstance();

    // Validate mobile
    $mobile = getInput($data, 'mobile');
    if ($mobile) {
        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return ['status' => 'error', 'message' => 'Mobile number must be valid 10 digits'];
        }
    }

    // Handle ID proof upload
    $idProofPath = null;
    if (isset($files['id_proof']) && $files['id_proof']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($files['id_proof']['name'], PATHINFO_EXTENSION);
        $filename = 'tenant_id_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/tenants/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($files['id_proof']['tmp_name'], $targetPath)) {
            $idProofPath = 'uploads/tenants/' . $filename;
        }
    }

    // Get status (default to 'active')
    $status = in_array($data['status'] ?? '', ['active','inactive']) ? $data['status'] : 'active';

    if ($id) {
        // Edit existing tenant
        $sql = "UPDATE tenants SET name=:name, mobile=:mobile, status=:status" . ($idProofPath ? ", id_proof=:id_proof" : "") . " WHERE id=:id";
        $params = [
            ':name' => getInput($data, 'tenant_name'),
            ':mobile' => $mobile,
            ':status' => $status,
            ':id' => $id
        ];
        if ($idProofPath) $params[':id_proof'] = $idProofPath;

        try {
            $db->query($sql, $params);
            return ['status' => 'success', 'id' => $id];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    } else {
        // Insert new tenant
        $sql = "INSERT INTO tenants (name, mobile, id_proof, status) VALUES (:name, :mobile, :id_proof, :status)";
        $params = [
            ':name' => getInput($data, 'tenant_name'),
            ':mobile' => $mobile,
            ':id_proof' => $idProofPath,
            ':status' => $status
        ];

        try {
            $db->query($sql, $params);
            return ['status' => 'success', 'id' => $db->lastInsertId()];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

function getTenants(): array
{
    $db = DB::getInstance();
    return $db->fetchAll("SELECT * FROM tenants ORDER BY id DESC");
}

// ======== Tenant-Unit Mapping ========
function createTenantUnitMapping(array $data, int $id = null): array {
    $db = DB::getInstance();

    $tenant_id = (int)($data['tenant_id'] ?? 0);
    $unit_id   = (int)($data['unit_id'] ?? 0);
    $effective_from = $data['effective_from'] ?? null;
    $effective_to   = $data['effective_to'] ?? null;

    // Validation
    if (!$tenant_id || !$unit_id || !$effective_from) {
        return ['status'=>'error','message'=>'Tenant, Unit and Effective From are required'];
    }
    if ($effective_to && $effective_to < $effective_from) {
        return ['status'=>'error','message'=>'Effective To cannot be before Effective From'];
    }

    try {
        if ($id) {
            // Update existing
            $sql = "UPDATE tenant_unit_mapping 
                    SET tenant_id=:tenant_id, unit_id=:unit_id, effective_from=:effective_from, effective_to=:effective_to
                    WHERE id=:id";
            $params = [
                ':tenant_id'=>$tenant_id,
                ':unit_id'=>$unit_id,
                ':effective_from'=>$effective_from,
                ':effective_to'=>$effective_to,
                ':id'=>$id
            ];
            $db->query($sql,$params);
            return ['status'=>'success','id'=>$id];
        } else {
            // Insert new
            $sql = "INSERT INTO tenant_unit_mapping (tenant_id, unit_id, effective_from, effective_to)
                    VALUES (:tenant_id, :unit_id, :effective_from, :effective_to)";
            $params = [
                ':tenant_id'=>$tenant_id,
                ':unit_id'=>$unit_id,
                ':effective_from'=>$effective_from,
                ':effective_to'=>$effective_to
            ];
            $db->query($sql,$params);
            return ['status'=>'success','id'=>$db->lastInsertId()];
        }
    } catch (Exception $e) {
        return ['status'=>'error','message'=>$e->getMessage()];
    }
}

function getTenantUnitMappings(): array {
    $db = DB::getInstance();
    return $db->fetchAll("
        SELECT tum.*, t.name as tenant_name, u.unit_name, b.name as building_name
        FROM tenant_unit_mapping tum
        INNER JOIN tenants t ON t.id = tum.tenant_id
        INNER JOIN units u ON u.id = tum.unit_id
        INNER JOIN buildings b ON b.id = u.building_id
        ORDER BY tum.id DESC
    ");
}