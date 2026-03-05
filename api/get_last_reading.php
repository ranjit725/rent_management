<?php

require_once '../config/master.php';

header('Content-Type: application/json');

if (!isset($_GET['meter_id']) || !is_numeric($_GET['meter_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid Meter ID is required.'
    ]);
    exit;
}

$meter_id = (int) $_GET['meter_id'];

if ($meter_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid Meter ID.'
    ]);
    exit;
}

$last_reading = getLastReadingForMeter($meter_id);

if ($last_reading) {
    echo json_encode([
        'success' => true,
        'data' => $last_reading
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No readings found for this meter.'
    ]);
}