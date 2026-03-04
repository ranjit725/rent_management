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
// In config/master.php

// --- MODIFIED createTenant ---
function createTenant(array $data): array
{
    $db = DB::getInstance();
    $sql_tenant = "INSERT INTO tenants (name, mobile, id_proof, status) VALUES (:name, :mobile, :id_proof, :status)";
    $params_tenant = [
        ':name'     => getInput($data, 'name'),
        ':mobile'   => getInput($data, 'mobile'),
        ':id_proof' => getInput($data, 'id_proof'),
        ':status'   => getInput($data, 'status', 'active')
    ];

    try {
        $db->beginTransaction();

        $db->query($sql_tenant, $params_tenant);
        $tenant_id = $db->lastInsertId();

        // If a unit is assigned, create the mapping
        $unit_id = (int)getInput($data, 'unit_id');
        $effective_from = getInput($data, 'effective_from');

        if ($tenant_id && $unit_id > 0 && !empty($effective_from)) {
            $sql_mapping = "INSERT INTO tenant_unit_mapping (tenant_id, unit_id, effective_from) VALUES (:tenant_id, :unit_id, :effective_from)";
            $params_mapping = [
                ':tenant_id'      => $tenant_id,
                ':unit_id'        => $unit_id,
                ':effective_from' => $effective_from
            ];
            $db->query($sql_mapping, $params_mapping);
        }

        $db->commit();
        return ['status' => 'success', 'message' => 'Tenant created successfully!'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// --- NEW FUNCTIONS ---

function getTenantsWithUnit(): array
{
    $db = DB::getInstance();
    $sql = "SELECT
                t.*,
                tum.effective_from as assigned_from,
                u.unit_name,
                u.building_id,
                b.name as building_name
            FROM tenants t
            LEFT JOIN tenant_unit_mapping tum ON t.id = tum.tenant_id AND tum.effective_to IS NULL
            LEFT JOIN units u ON tum.unit_id = u.id
            LEFT JOIN buildings b ON u.building_id = b.id
            ORDER BY t.created_at DESC";
    return $db->query($sql)->fetchAll();
}

function updateTenant(array $data, int $id): array
{
    $db = DB::getInstance();
    $sql_tenant = "UPDATE tenants SET name=:name, mobile=:mobile, id_proof=:id_proof, status=:status WHERE id=:id";
    $params_tenant = [
        ':name'     => getInput($data, 'name'),
        ':mobile'   => getInput($data, 'mobile'),
        ':id_proof' => getInput($data, 'id_proof'),
        ':status'   => getInput($data, 'status', 'active'),
        ':id'       => $id
    ];

    try {
        $db->beginTransaction();

        // 1. Update tenant details
        $db->query($sql_tenant, $params_tenant);

        // 2. Handle unit re-assignment
        $new_unit_id = (int)getInput($data, 'unit_id');
        $effective_from = getInput($data, 'effective_from');

        if ($new_unit_id > 0 && !empty($effective_from)) {
            // Find the current active mapping for this tenant
            $current_mapping = $db->query("SELECT id, unit_id FROM tenant_unit_mapping WHERE tenant_id = :tenant_id AND effective_to IS NULL", [':tenant_id' => $id])->fetch();

            if ($current_mapping) {
                // If the unit is being changed
                if ((int)$current_mapping['unit_id'] !== $new_unit_id) {
                    // Close the old mapping
                    $db->query("UPDATE tenant_unit_mapping SET effective_to = CURDATE() WHERE id = :id", [':id' => $current_mapping['id']]);
                    // Create a new mapping
                    $db->query("INSERT INTO tenant_unit_mapping (tenant_id, unit_id, effective_from) VALUES (:tenant_id, :unit_id, :effective_from)", [
                        ':tenant_id'      => $id,
                        ':unit_id'        => $new_unit_id,
                        ':effective_from' => $effective_from
                    ]);
                }
            } else {
                // Tenant had no previous unit, just assign the new one
                $db->query("INSERT INTO tenant_unit_mapping (tenant_id, unit_id, effective_from) VALUES (:tenant_id, :unit_id, :effective_from)", [
                    ':tenant_id'      => $id,
                    ':unit_id'        => $new_unit_id,
                    ':effective_from' => $effective_from
                ]);
            }
        }

        $db->commit();
        return ['status' => 'success', 'message' => 'Tenant updated successfully!'];

    } catch (Exception $e) {
        $db->rollBack();
        return ['status' => 'error', 'message' => $e->getMessage()];
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