<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4>Rental Panel</h4>
    </div>

    <ul class="sidebar-menu">
        <li class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a>
        </li>

        <li class="has-submenu <?= in_array($currentPage, ['buildings.php','units.php']) ? 'active' : '' ?>">
            <a href="javascript:void(0);" onclick="toggleSubmenu(this)">
                <i class="fa fa-building"></i> Buildings <i class="fa fa-caret-down submenu-arrow"></i>
            </a>
            <ul class="submenu">
                <li class="<?= $currentPage === 'buildings.php' ? 'active' : '' ?>">
                    <a href="buildings.php"><i class="fa fa-list"></i> All Buildings</a>
                </li>
                <li class="<?= $currentPage === 'units.php' ? 'active' : '' ?>">
                    <a href="units.php"><i class="fa fa-layer-group"></i> Units</a>
                </li>
            </ul>
        </li>

        <li class="<?= $currentPage === 'rent_history.php' ? 'active' : '' ?>">
            <a href="rent_history.php"><i class="fa fa-history"></i> Rent History</a>
        </li>
        <li class="<?= $currentPage === 'tenants.php' ? 'active' : '' ?>">
            <a href="tenants.php"><i class="fa fa-users"></i> Tenants</a>
        </li>
        <li class="<?= $currentPage === 'meters.php' ? 'active' : '' ?>">
            <a href="meters.php"><i class="fa fa-bolt"></i> Meters</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="meter_readings.php">
                <i class="fa fa-clipboard-list"></i> Meter Readings
            </a>
        </li>
        <li class="<?= $currentPage === 'billing.php' ? 'active' : '' ?>">
            <a href="billing.php"><i class="fa fa-file-invoice"></i> Billing</a>
        </li>
    </ul>
</div>

<div id="overlay" class="overlay" onclick="toggleSidebar()"></div>