<!-- JS: Common for all pages -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>

<?php
include_once("myJS.php");
switch(basename($_SERVER['PHP_SELF'])) {
    case "tenants.php":
        if(function_exists('tenantsJS')) tenantsJS();
        break;
    case "buildings.php":
        if(function_exists('buildingsJS')) buildingsJS(); 
        break;
    case "units.php":
        if(function_exists('unitsJS')) unitsJS(); // Make sure this line is active
        break;
    // add other page-specific JS as needed
    case "meters.php":
        if(function_exists('metersJS')) metersJS();
        break;
}
?>

</body>
</html>