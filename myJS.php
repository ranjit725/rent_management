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
            // Enable/disable effective_to based on unit selection
            function toggleEffectiveTo() {
                const unitSelected = $('#unit_id').val() !== "";
                $('#effective_to').prop('disabled', !unitSelected);
                if (!unitSelected) {
                    $('#effective_to').val('');
                }
            }
            $('#unit_id').on('change', toggleEffectiveTo);
            toggleEffectiveTo(); // Initial check

            // Form submission validation
            $('#tenantForm').on('submit', function(e){
                let mobileVal = $('#mobile').val().trim();
                if(mobileVal && !/^[6-9]\d{9}$/.test(mobileVal)){
                    alert('Mobile number must be a valid 10-digit number starting with 6-9.');
                    e.preventDefault();
                }
            });

            $('#tenantsTable').DataTable({
                responsive: true,
                scrollX: true,
                autoWidth: false
            });
        });

        window.editTenant = function(t) {
            // Populate all form fields
            $('#formTitle').text('Edit Tenant');
            $('#tenant_id').val(t.id);
            $('#name').val(t.name);
            $('#mobile').val(t.mobile);
            $('#status').val(t.status);

            // Handle the ID proof file display
            const proofContainer = $('#currentProofContainer');
            proofContainer.empty();
            if (t.id_proof) {
                proofContainer.html(`<br><small>Current file: <a href="uploads/id_proofs/${t.id_proof}" target="_blank">${t.id_proof}</a></small>`);
            }

            // Populate unit assignment fields
            if (t.unit_id) {
                $('#unit_id').val(t.unit_id);
                $('#effective_from').val(t.effective_from);
                $('#effective_to').val(t.effective_to || '');
            } else {
                $('#unit_id').val('');
                $('#effective_from').val('');
                $('#effective_to').val('');
            }

            // Set the correct state for effective_to field
            const unitSelected = $('#unit_id').val() !== "";
            $('#effective_to').prop('disabled', !unitSelected);

            $('#submitBtn').text('Update Tenant');
        }

        window.openAddTenantForm = function() {
            $('#formTitle').text('Add Tenant');
            $('#tenantForm')[0].reset();
            $('#tenant_id').val('');
            $('#currentProofContainer').empty();
            $('#effective_to').prop('disabled', true);
            $('#submitBtn').text('Add Tenant');
        }
    </script>
    <?php
}
?>


<?php
function metersJS() {
    ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#metersTable').DataTable({
                responsive: true,
                autoWidth: false
            });

            // Handle form submission with AJAX
            $('#meterForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: 'meters.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        // Close modal and reload page on success
                        $('#meterModal').modal('hide');
                        location.reload(); 
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });

        // Function to open the Add Meter form
        window.openAddMeterForm = function() {
            $('#formTitle').text('Add Meter');
            $('#meterForm')[0].reset();
            $('#meter_id').val('');
            $('#status').prop('checked', true); // Default to active
            $('#submitBtn').text('Add Meter');
        }

        // Function to edit a meter
        window.editMeter = function(m) {
            $('#formTitle').text('Edit Meter');
            $('#meter_id').val(m.id);
            $('#building_id').val(m.building_id);
            $('#meter_name').val(m.meter_name);
            
            // Set the correct radio button for meter type
            $('input[name="meter_type"][value="' + m.meter_type + '"]').prop('checked', true);
            
            // Set the status switch
            $('#status').prop('checked', m.status == 1);
            
            $('#submitBtn').text('Update Meter');
            $('#meterModal').modal('show');
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