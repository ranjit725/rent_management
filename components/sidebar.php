<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4>Rental Panel</h4>
    </div>

    <ul class="sidebar-menu">
    <!-- 1. Dashboard -->
    <li class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <a href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a>
    </li>

    <!-- 2. Monthly Tasks (High Priority) -->
    <li class="has-submenu <?= in_array($currentPage, ['meter_readings.php', 'billing.php']) ? 'active' : '' ?>">
        <a href="javascript:void(0);" onclick="toggleSubmenu(this)">
            <i class="fa fa-tasks"></i> Monthly Tasks <i class="fa fa-caret-down submenu-arrow"></i>
        </a>
        <ul class="submenu">
            <li class="<?= $currentPage === 'meter_readings.php' ? 'active' : '' ?>">
                <a href="meter_readings.php"><i class="fa fa-clipboard-list"></i> Add Meter Readings</a>
            </li>
            <li class="<?= $currentPage === 'billing.php' ? 'active' : '' ?>">
                <a href="billing.php"><i class="fa fa-file-invoice-dollar"></i> Generate Bills</a>
            </li>
        </ul>
    </li>

    <!-- 3. Properties Management -->
    <li class="has-submenu <?= in_array($currentPage, ['buildings.php', 'units.php']) ? 'active' : '' ?>">
        <a href="javascript:void(0);" onclick="toggleSubmenu(this)">
            <i class="fa fa-building"></i> Properties <i class="fa fa-caret-down submenu-arrow"></i>
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

    <!-- 4. Tenant Management -->
    <li class="has-submenu <?= in_array($currentPage, ['tenants.php', 'tenant_unit_mapping.php', 'rent_history.php']) ? 'active' : '' ?>">
        <a href="javascript:void(0);" onclick="toggleSubmenu(this)">
            <i class="fa fa-users"></i> Tenants <i class="fa fa-caret-down submenu-arrow"></i>
        </a>
        <ul class="submenu">
            <li class="<?= $currentPage === 'tenants.php' ? 'active' : '' ?>">
                <a href="tenants.php"><i class="fa fa-user-friends"></i> All Tenants</a>
            </li>
            <li class="<?= $currentPage === 'rent_history.php' ? 'active' : '' ?>">
                <a href="rent_history.php"><i class="fa fa-history"></i> Rent Agreements</a>
            </li>
        </ul>
    </li>

    <!-- 5. Utilities Management -->
    <li class="has-submenu <?= in_array($currentPage, ['meters.php', 'meter_tenant_mapping.php']) ? 'active' : '' ?>">
        <a href="javascript:void(0);" onclick="toggleSubmenu(this)">
            <i class="fa fa-bolt"></i> Utilities <i class="fa fa-caret-down submenu-arrow"></i>
        </a>
        <ul class="submenu">
            <li class="<?= $currentPage === 'meters.php' ? 'active' : '' ?>">
                <a href="meters.php"><i class="fa fa-tachometer-alt"></i> All Meters</a>
            </li>
            <li class="<?= $currentPage === 'meter_tenant_mapping.php' ? 'active' : '' ?>">
                <a href="meter_tenant_mapping.php"><i class="fa fa-plug"></i> Meter Assignments</a>
            </li>
        </ul>
    </li>

</ul>
</div>

<div id="overlay" class="overlay" onclick="toggleSidebar()"></div>