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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#metersTable').DataTable({
                 responsive: true,
                scrollX: true,
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

<?php
function meterReadingsJS() {
?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
        $(document).ready(function() {

            // Initialize DataTable
            $('#readingsTable').DataTable({
                responsive: true,
                autoWidth: false,
                order: [[0, 'desc']] // Sort by the first column (ID) descending
            });

            // --- CALCULATION LOGIC ---
            function calculateTotals() {
                let current = parseFloat($('#current_reading').val()) || 0;
                let previous = parseFloat($('#previous_reading').val()) || 0;
                let rate = parseFloat($('#per_unit_rate').val()) || 0;

                // Ensure units are not negative
                let units = (current - previous > 0) ? (current - previous) : 0;
                let total = units * rate;

                $('#units_consumed').val(units);
                $('#total_amount').val(total.toFixed(2));
            }

            // Recalculate when current reading or rate changes
            $('#current_reading, #per_unit_rate').on('input', calculateTotals);


            // --- LOAD PREVIOUS READING LOGIC ---
            $('#meter_id').on('change', function() {
                let meterId = $(this).val();

                // Reset fields if no meter is selected
                if (!meterId) {
                    $('#previous_reading').val(0);
                    $('#last_reading_info').text('');
                    calculateTotals();
                    return;
                }

                // Fetch the last reading for the selected meter via AJAX
                $.ajax({
                    url: 'api/get_last_reading.php',
                    type: 'GET',
                    data: { meter_id: meterId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            let prev = response.data.current_reading;
                            $('#previous_reading').val(prev);
                            
                            let d = new Date(response.data.reading_date);
                            let formattedDate = d.toLocaleDateString('en-IN', {
                                day: 'numeric',
                                month: 'short',
                                year: 'numeric'
                            });
                            $('#last_reading_info').text('Last Reading: ' + prev + ' (' + formattedDate + ')');
                        } else {
                            // If no previous reading, set to 0
                            $('#previous_reading').val(0);
                            $('#last_reading_info').text('No previous reading found for this meter.');
                        }
                        calculateTotals();
                    },
                    error: function() {
                        $('#previous_reading').val(0);
                        $('#last_reading_info').text('Error loading previous reading.');
                        calculateTotals();
                    }
                });
            });

            // --- VALIDATION LOGIC ---
            $('#current_reading').on('blur', function() {
                let previous = parseFloat($('#previous_reading').val()) || 0;
                let current = parseFloat($(this).val()) || 0;

                if (current < previous) {
                    alert('Current reading cannot be less than the previous reading.');
                    $(this).val(''); // Clear the invalid input
                    calculateTotals(); // Recalculate to clear units/total
                }
            });

            // --- EDIT MODE LOGIC ---
            // This function is called from the "Edit" button in the table
            window.editMeterReading = function(data) {
                 console.log("Edit Data:", data);
    console.log("Meter ID:", data.meter_id);
                // Populate form fields with data from the table row
                $('#reading_id').val(data.id);
                $('#meter_id').val(data.meter_id).trigger('change'); // Trigger change to load last reading info
                $('#reading_date').val(data.reading_date);
                $('#current_reading').val(data.current_reading);
                $('#per_unit_rate').val(data.per_unit_rate);

                // In edit mode, we manually set the previous reading and don't rely on the AJAX call
                $('#previous_reading').val(data.previous_reading);
                
                // Show the current image if it exists
                if (data.image_path) {
                    $('#current_image_preview').attr('src', data.image_path);
                    $('#current_image_container').show();
                } else {
                    $('#current_image_container').hide();
                }

                // Update UI for editing mode
                $('#submitBtn').text('Update Reading');
                $('#cancelBtn').show();
                
                // Trigger calculation to update the totals
                calculateTotals();
                
                // Scroll to the form for better user experience
                $('html, body').animate({
                    scrollTop: $("#readingForm").offset().top
                }, 500);
            };

            // --- RESET FORM LOGIC ---
            // This function resets the form back to "Add" mode
            window.resetForm = function() {
                $('#readingForm')[0].reset(); // Resets all form fields
                $('#reading_id').val(''); // Clears the hidden ID
                $('#last_reading_info').text(''); // Clears the last reading info text
                $('#current_image_container').hide(); // Hides the image preview
                $('#submitBtn').text('Save Reading'); // Resets button text
                $('#cancelBtn').hide(); // Hides the cancel button
                calculateTotals(); // Clears calculated values
            };

            // Attach the resetForm function to the cancel button's click event
            $('#cancelBtn').on('click', resetForm);

        });
    </script>

<?php
}
?>