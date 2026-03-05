<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">

    <?php 
    include_once("myCSS.php");

    // Per-page CSS switch
    switch(basename($_SERVER['PHP_SELF'])) {
        case "tenants.php":
            if (function_exists('tenantsCSS')) tenantsCSS();
            echo '<title>Tenants</title>';
            break;

        case "buildings.php":
            if (function_exists('buildingsCSS')) buildingsCSS();
            echo '<title>Buildings</title>';
            break;

        case "units.php":
            if (function_exists('unitsCSS')) unitsCSS();
            echo '<title>Units</title>';
            break;

        case "meters.php":
            if (function_exists('metersCSS')) metersCSS();
            echo '<title>Meters</title>';
            break;

        case "meter_readings.php":
            if (function_exists('meterReadingsCSS')) meterReadingsCSS();
            echo '<title>Meter Readings</title>';
            break;
        // Add more pages as needed
    }
    ?>
</head>
<body>