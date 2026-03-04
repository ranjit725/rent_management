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