<?php
function dashboardCSS() {
    ?>
    <!-- Add this line in components/header.php -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card { border-radius: 10px; }
        .dashboard-card .card-body { padding: 20px; }
        .dashboard-card h5 { font-size: 18px; margin-bottom: 15px; }
        .dashboard-card .metric { font-size: 24px; font-weight: bold; }
        .dashboard-card .icon { font-size: 30px; opacity: 0.2; }
    </style>
    <?php
}
?>

<?php

function tenantsCSS() {
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #tenantsTable { width: 100% !important;}
        #tenantsTable img { max-height:30px; max-width:50px; object-fit:contain; }
        #tenantsTable tr.table-danger { background-color:#f8d7da !important; }
        .modal-dialog { max-width:95%; margin:1.75rem auto; }
        .table-responsive { overflow-x:auto; width: 100%;}
    </style>
    <?php
}
?>

<?php

function metersCSS() {
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #metersTable { width: 100% !important;}
        #metersTable img { max-height:30px; max-width:50px; object-fit:contain; }
        #metersTable tr.table-danger { background-color:#f8d7da !important; }
        .modal-dialog { max-width:95%; margin:1.75rem auto; }
        .table-responsive { overflow-x:auto; width: 100%;}
    </style>
    <?php
}
?>


<?php

function meterReadingsCSS() {
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #readingsTable { width: 100% !important;}
        #readingsTable img { max-height:30px; max-width:50px; object-fit:contain; }
        #readingsTable tr.table-danger { background-color:#f8d7da !important; }
        .modal-dialog { max-width:95%; margin:1.75rem auto; }
        .table-responsive { overflow-x:auto; width: 100%;}
    </style>
    <?php
}
?>

<?php

function rentHistoryCSS() {
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #historyTable { width: 100% !important;}
        #historyTable img { max-height:30px; max-width:50px; object-fit:contain; }
        #historyTable tr.table-danger { background-color:#f8d7da !important; }
        .modal-dialog { max-width:95%; margin:1.75rem auto; }
        .table-responsive { overflow-x:auto; width: 100%;}
    </style>
    <?php
}
?>

<?php
function meterTenantMappingCSS() {
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #mappingTable { width: 100% !important;}
        #mappingTable img { max-height:30px; max-width:50px; object-fit:contain; }
        #mappingTable tr.table-danger { background-color:#f8d7da !important; }
        .modal-dialog { max-width:95%; margin:1.75rem auto; }
        .table-responsive { overflow-x:auto; width: 100%;}
    </style>
    <?php
}
?>

<?php
function billingCSS() {
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        /* Force table to stay within container bounds */
        #billingDataTable { width: 100% !important; margin: 0 !important; }
        
        /* Wrap long names/buildings on mobile so they don't push the table wide */
        #billingDataTable td:first-child {
            white-space: normal !important;
            min-width: 150px;
        }

        /* Styling the Mobile Expanded Row */
        table.dataTable > tbody > tr.child { background-color: #f8f9fa; }
        table.dataTable > tbody > tr.child ul.dtr-details {
            display: block;
            list-style-type: none;
            margin: 0;
            padding: 10px;
        }
        table.dataTable > tbody > tr.child li {
            border-bottom: 1px solid #dee2e6;
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
        }
        table.dataTable > tbody > tr.child li:last-child { border-bottom: none; }
        table.dataTable > tbody > tr.child span.dtr-title {
            font-weight: 600;
            color: #495057;
        }

        /* Blue (+) icon for expanding rows */
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before {
            background-color: #0d6efd !important;
            box-shadow: none !important;
        }

        /* Custom Header Search */
        .border-primary-subtle { border-color: #cfe2ff !important; }
        
        /* Summary hints */
        #hint_rent, #hint_elec { font-size: 0.85rem; margin-top: 4px; }
    </style>
    <?php
}
?>

<?php
function tenantHistoryCSS() {
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
   
    <style>
/* Force search bar and pagination to the right */
.dataTables_filter {
    text-align: right !important;
}
.dataTables_filter input {
    margin-left: 0.5em;
    display: inline-block;
    width: auto;
}
.dataTables_paginate {
    float: right !important;
}
/* Fix for the "Greyed Out" issue - ensures modal is above backdrop */
.modal-backdrop {
    z-index: 1040 !important;
}
#editModal {
    z-index: 1050 !important;
}
</style>
    <?php
}

