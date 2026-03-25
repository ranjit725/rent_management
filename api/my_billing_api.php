<?php
require_once '../config/master.php';

$action = $_POST['action'] ?? '';
$tenant_id = (int)($_POST['tenant_id'] ?? 0);

if ($action === 'get_quick_summary' && $tenant_id > 0) {
    // Calling your existing functions from master.php
    $rent_due = getTenantDebtString($tenant_id, 'rent');
    $elec_due = getTenantDebtString($tenant_id, 'electricity');

    echo json_encode([
        'rent' => $rent_due,
        'elec' => $elec_due
    ]);
    exit;
}

if ($action === 'get_full_ledger' && $tenant_id > 0) {
    // This is for your expandable row logic
    // Add your existing ledger table generation here
    exit;
}