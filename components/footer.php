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
        // buildingsJS(); // create when needed
        break;
    case "units.php":
        // unitsJS(); // create when needed
        break;
    // add other page-specific JS as needed
}
?>

</body>
</html>