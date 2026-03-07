<?php
require_once '../config/master.php';

header('Content-Type: application/json');

if (!isset($_GET['unit_id']) || empty($_GET['unit_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unit ID is required.']);
    exit;
}

 $unit_id = (int)$_GET['unit_id'];

// Use a simple query to get the current active rent
 $sql = "SELECT rent_amount FROM rent_history WHERE unit_id = :unit_id AND effective_to IS NULL ORDER BY effective_from DESC LIMIT 1";
 $result = DB::getInstance()->query($sql, [':unit_id' => $unit_id])->fetch();

if ($result) {
    echo json_encode(['success' => true, 'data' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'No current rent found for this unit.']);
}