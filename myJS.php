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
            responsive:true,
            scrollX:true,
            autoWidth: false
        });

        window.openTenantModal = function(){
            $('#modalTitle').text('Add Tenant');
            $('#tenantForm')[0].reset();
            $('#tenant_id').val('');
            $('#tenantModal').show();
        }

        window.closeTenantModal = function(){
            $('#tenantModal').hide();
        }

        window.editTenant = function(t){
            $('#modalTitle').text('Edit Tenant');
            $('#tenant_id').val(t.id);
            $('#tenant_name').val(t.name);
            $('#mobile').val(t.mobile);
            $('#status').val(t.status);
            $('#tenantModal').show();
        }

        $('#tenantForm').on('submit', function(e){
            let mobileVal = $('#mobile').val().trim();
            if(mobileVal && !/^[6-9]\d{9}$/.test(mobileVal)){
                alert('Mobile number must be valid 10 digits');
                e.preventDefault();
            }
        });
    });
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