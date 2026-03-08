<?php
// This file is an API endpoint, so it doesn't need a full HTML structure
require_once '../config/master.php';

header('Content-Type: application/json');

if (!isset($_GET['meter_id'])) {
    echo json_encode(['success' => false, 'message' => 'Meter ID is required.']);
    exit;
}

 $meter_id = (int)$_GET['meter_id'];
 $db = DB::getInstance();

 $sql = "SELECT t.name 
        FROM meter_tenant_mapping mtm
        JOIN tenants t ON mtm.tenant_id = t.id
        WHERE mtm.meter_id = :meter_id AND mtm.effective_to IS NULL";

try {
    $stmt = $db->query($sql, [':meter_id' => $meter_id]);
    $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Fetch only the 'tenant_name' column

    if (empty($tenants)) {
        echo json_encode(['success' => true, 'data' => 'No tenants currently mapped.']);
    } else {
        echo json_encode(['success' => true, 'data' => implode(', ', $tenants)]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}