<?php include 'components/header.php'; ?>
<?php include 'components/sidebar.php'; ?>

<?php
require_once 'config/master.php';
$stats = getDashboardStats();
?>

<div class="main-content">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid mt-4">

        <div class="row g-4">

            <div class="col-md-3 col-sm-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6>Total Buildings</h6>
                        <h3><?= $stats['buildings'] ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6>Total Tenants</h6>
                        <h3><?= $stats['tenants'] ?></h3>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<?php include 'components/footer.php'; ?>