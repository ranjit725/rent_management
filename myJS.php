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
                autoWidth: false,
    language: {
        emptyTable: "No tenants found."
    }
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
                autoWidth: false,
    language: {
        emptyTable: "No Meter found."
    }
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

<?php

/**
 * JavaScript for the Rent History page.
 */
/**
 * JavaScript for the Rent History page.
 */
function rentHistoryJS() {
    ?>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#historyTable').DataTable({
                responsive: true,
                autoWidth: false,
                order: [[1, 'desc']] // Sort by Effective From date descending
            });

            // --- EDIT & RESET LOGIC ---
            window.editRentHistory = function(data) {
                $('#history_id').val(data.id);
                $('#unit_id').val(data.unit_id);
                $('#rent_amount').val(data.rent_amount);
                $('#effective_from').val(data.effective_from);
                $('#effective_to').val(data.effective_to);

                $('#submitBtn').text('Update History');
                $('#cancelBtn').show();

                $('html, body').animate({
                    scrollTop: $("#historyForm").offset().top
                }, 500);
            };

            window.resetForm = function() {
                $('#historyForm')[0].reset();
                $('#history_id').val('');
                $('#submitBtn').text('Save Rent History');
                $('#cancelBtn').hide();
            };

            $('#cancelBtn').on('click', resetForm);

            // --- SMART FORM LOGIC ---
            $('#unit_id').on('change', function() {
                let unitId = $(this).val();
                if (!unitId) {
                    $('#rent_amount').val('');
                    return;
                }

                $.ajax({
                    url: 'api/get_current_rent.php',
                    type: 'GET',
                    data: { unit_id: unitId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            $('#rent_amount').val(response.data.rent_amount);
                        } else {
                            $('#rent_amount').val('');
                        }
                    },
                    error: function() {
                        $('#rent_amount').val('');
                    }
                });
            });

        });
    </script>
    <?php
}
?>

<?php
/**
 * JavaScript for the Meter-Tenant Mapping page.
 */
function meterTenantMappingJS() {
    ?>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#mappingsTable').DataTable({
                responsive: true,
                autoWidth: false,
                order: [[1, 'desc']] // Sort by Effective From date descending
            });

            // --- EDIT & RESET LOGIC ---
            window.editMapping = function(data) {
                $('#mapping_id').val(data.id);
                $('#meter_id').val(data.meter_id);
                $('#tenant_id').val(data.tenant_id);
                $('#effective_from').val(data.effective_from);
                $('#effective_to').val(data.effective_to);

                $('#submitBtn').text('Update Mapping');
                $('#cancelBtn').show();

                // Trigger change to show current tenants info
                $('#meter_id').trigger('change');

                $('html, body').animate({
                    scrollTop: $("#mappingForm").offset().top
                }, 500);
            };

            window.resetForm = function() {
                $('#mappingForm')[0].reset();
                $('#mapping_id').val('');
                $('#submitBtn').text('Save Mapping');
                $('#cancelBtn').hide();
                $('#current_tenants_info').text('');
            };

            $('#cancelBtn').on('click', resetForm);

            // --- SMART FORM LOGIC ---
            $('#meter_id').on('change', function() {
                let meterId = $(this).val();
                if (!meterId) {
                    $('#current_tenants_info').text('');
                    return;
                }

                $.ajax({
                    url: 'api/get_current_tenants_for_meter.php',
                    type: 'GET',
                    data: { meter_id: meterId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#current_tenants_info').text('Currently mapped to: ' + response.data);
                        } else {
                            $('#current_tenants_info').text('Could not fetch tenant info.');
                        }
                    },
                    error: function() {
                        $('#current_tenants_info').text('Error fetching tenant info.');
                    }
                });
            });

        });
    </script>
    <?php
}
?>

<?php

/**
 * JavaScript for the Billing page.
 */
function billingJS() {
    ?>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            let table;

            if ($('#billingDataTable').length) {
                table = $('#billingDataTable').DataTable({
                    responsive: true,
                    dom: 'rtip', 
                    "pageLength": 10,
                    columnDefs: [
                        { responsivePriority: 1, targets: 0 },
                        { responsivePriority: 2, targets: 3 },
                        { 
                            targets: 0, 
                            className: 'text-wrap',
                            width: '40%' 
                        }
                    ],
                    "language": {
                        "paginate": {
                            "previous": "Prev",
                            "next": "Next"
                        }
                    }
                });

                $('#customTableSearch').on('keyup', function() {
                    table.search(this.value).draw();
                });

                // --- THE FIX FOR ROTATION/RESIZE ---
                // This triggers whenever the window size changes
                $(window).on('resize orientationchange', function() {
                    if (table) {
                        table.columns.adjust().responsive.recalc();
                    }
                });
            }
            
            $('#fifo_tenant_select').on('change', function() {
                const tenantId = $(this).val();
                const hintRent = $('#hint_rent');
                const hintElec = $('#hint_elec');
                
                if(!tenantId) {
                    hintRent.html('');
                    hintElec.html('');
                    return;
                }

                $.post('api/my_billing_api.php', { action: 'get_quick_summary', tenant_id: tenantId }, function(response) {
                    try {
                        const data = JSON.parse(response);
                        
                        // Rent Hint Logic
                        let rentHtml = '';
                        const rentTotal = String(data.rent.total);

                        if (data.rent.is_advance) {
                            rentHtml = '<span class="text-success">Advance: ' + rentTotal + '</span>';
                        } else if (rentTotal === "Settled" || rentTotal === "₹0.00") {
                            rentHtml = '<span class="text-success">Settled</span>';
                        } else if (rentTotal === "Not Fixed") {
                            rentHtml = '<span class="text-warning">Rent Not Fixed</span>';
                        } else {
                            rentHtml = '<span class="text-danger">Pending: ' + rentTotal + '</span>';
                        }
                        hintRent.html(rentHtml);

                        // Elec Hint Logic
                        let elecHtml = '';
                        const elecTotal = String(data.elec.total);

                        if (data.elec.is_advance) {
                            elecHtml = '<span class="text-success">Advance: ' + elecTotal + '</span>';
                        } else if (elecTotal === "Settled" || elecTotal === "₹0.00") {
                            elecHtml = '<span class="text-success">Settled</span>';
                        } else if (elecTotal === "No Meter") {
                            elecHtml = '<span class="text-danger">No Meter Mapped</span>';
                        } else {
                            elecHtml = '<span class="text-danger">Pending: ' + elecTotal + '</span>';
                        }
                        hintElec.html(elecHtml);

                    } catch (e) {
                        console.error("Failed to parse billing summary:", e);
                    }
                });
            });
        });
    </script>
    <?php
}
?>
<?php
function tenantHistoryJS() {
    ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
   <script>
$(document).ready(function() {
    // 1. Initialize DataTable with Alignment Fixes
    if ($.fn.DataTable.isDataTable('#historyTable')) {
        $('#historyTable').DataTable().destroy();
    }

    var table = $('#historyTable').DataTable({
        responsive: true,
        autoWidth: false,
        order: [[4, 'desc']], // Entry Date column
        // 'f' is search, 'p' is pagination. This wraps them in Bootstrap rows/cols
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records..."
        }
    });

    // 2. Event Delegation for the Edit Button
    $('#historyTable tbody').on('click', '.edit-btn', function(e) {
        e.preventDefault();
        const data = $(this).data('item');
        
        // Fill fields
        $('#edit_id').val(data.id);
        $('#edit_rent').val(data.rent_amount);
        $('#edit_elec').val(data.electricity_amount);
        $('#edit_adj').val(data.adjustment_amount);

        // Header Label
        const d = new Date(data.billing_month);
        const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        $('#modal_month_label').text(months[d.getMonth()] + " " + d.getFullYear());

        // 3. Show Modal manually to avoid "double-trigger" grey screens
        var myModal = new bootstrap.Modal(document.getElementById('editModal'));
        myModal.show();
    });
});
</script>


    <?php
}