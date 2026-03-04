<?php

function buildingsJS() {
    ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#buildingsTable').DataTable({
                responsive: true,
                scrollX: true,
                autoWidth: false
            });
        });

        // Function to open modal and pre-fill form for editing
        window.editBuilding = function(b) {
            $('#formTitle').text('Edit Building');
            $('#building_id').val(b.id);
            $('#building_name').val(b.name);
            $('#address').val(b.address);
            $('#submitBtn').text('Update Building');
        }

        // Function to reset form to "Add" mode
        window.openAddBuildingForm = function() {
            $('#formTitle').text('Add Building');
            $('#buildingForm')[0].reset();
            $('#building_id').val('');
            $('#submitBtn').text('Add Building');
        }

        // You can call this function when you want to reset the form, for example, after a successful submission.
        // For now, the page reload handles the reset.
    </script>
    <?php
}
?>

<?php

function unitsJS() {
    ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#unitsTable').DataTable({
                responsive: true,
                scrollX: true,
                autoWidth: false
            });
        });

        // Function to open modal and pre-fill form for editing
        window.editUnit = function(u) {
            $('#formTitle').text('Edit Unit');
            $('#unit_id').val(u.id);
            $('#building_id').val(u.building_id);
            $('#unit_name').val(u.unit_name);
            $('#submitBtn').text('Update Unit');
        }

        // Function to reset form to "Add" mode
        window.openAddUnitForm = function() {
            $('#formTitle').text('Add Unit');
            $('#unitForm')[0].reset();
            $('#unit_id').val('');
            $('#submitBtn').text('Add Unit');
        }
    </script>
    <?php
}
?>

<?php
function tenantsJS() {
    ?>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tenantsTable').DataTable({
                responsive: true,
                scrollX: true,
                autoWidth: false
            });
        });

        window.editTenant = function(t) {
            $('#formTitle').text('Edit Tenant');
            $('#tenant_id').val(t.id);
            $('#name').val(t.name);
            $('#mobile').val(t.mobile);
            $('#id_proof').val(t.id_proof);
            $('#status').val(t.status);

            // Pre-fill unit assignment
            $('#unit_id').val(t.unit_id || '');
            $('#effective_from').val(t.assigned_from || '');

            $('#submitBtn').text('Update Tenant');
        }

        window.openAddTenantForm = function() {
            $('#formTitle').text('Add Tenant');
            $('#tenantForm')[0].reset();
            $('#tenant_id').val('');
            $('#submitBtn').text('Add Tenant');
        }
    </script>
    <?php
}
?>


<?php
function tenantUnitJS() { ?>
<script>
$(document).ready(function(){
    $('#mappingTable').DataTable({responsive:true, scrollX:true});

    window.openMappingModal = function(){
        $('#modalTitle').text('Add Mapping');
        $('#mappingForm')[0].reset();
        $('#mapping_id').val('');
        $('#mappingModal').show();
    }

    window.closeMappingModal = function(){
        $('#mappingModal').hide();
    }

    window.editMapping = function(m){
        $('#modalTitle').text('Edit Mapping');
        $('#mapping_id').val(m.id);
        $('#tenant_id').val(m.tenant_id);
        $('#unit_id').val(m.unit_id);
        $('#effective_from').val(m.effective_from);
        $('#effective_to').val(m.effective_to);
        $('#mappingModal').show();
    }
});
</script>
<?php } ?>