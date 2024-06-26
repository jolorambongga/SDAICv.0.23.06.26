<?php
$title = "SDAIC - New Appointment";
$active_index = "";
$active_profile = "";
$active_your_appointments = "";
$active_new_appointment = "active";
include_once('header.php');
include_once('handles/auth.php');
checkAuth();
?>

<!-- date picker -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<div class="my-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="text-start">Make your new appointment</h1>
            </div>
        </div>
        <!-- start multi-step form -->
        <div class="row justify-content-center bg- p-jp-md-5">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="wrapper mt-5">
                    <form id="appointment-form">
                        <!-- Step 1: Select Procedure -->
                        <div id="step-1" class="form-step">
                            <div class="title">Your Procedure</div>
                            <div class="box mb-3">
                                <select id="procedure-select" class="form-control">
                                    <!-- Options will be loaded here by jQuery -->
                                </select>
                            </div>
                            <button type="button" class="btn btn-warning next-btn float-end mt-3">Next</button>
                        </div>

                        <!-- Step 2: Upload Image -->
                        <div id="step-2" class="form-step" style="display:none;">
                            <div class="title">Upload Photo of Your Request</div>
                            <div class="box mb-3">
                                <input accept="image/jpeg, image/png, image/gif" type="file" name="request_image" id="request_image" class="form-control">              
                            </div>
                            <button type="button" class="btn btn-warning next-btn float-end mt-3">Next</button>
                            <button type="button" class="btn btn-danger prev-btn float-end mt-3 me-2">Previous</button>
                        </div>

                        <!-- Step 3: Select Date and Time -->
                        <div id="step-3" class="form-step" style="display:none;">
                            <div class="title">Select Date and Time</div>
                            <div class="box mb-3">
                                <label for="appointment_date">Select Appointment Date:</label>
                                <input type="text" id="appointment_date" name="appointment_date" class="form-control">
                                <pre></pre>

                                <select class="form-select" aria-label="Default select example">
                                    <option value disabled selected>Select Time</option>
                                    <option value="1">One</option>
                                    <option value="2">Two</option>
                                    <option value="3">Three</option>
                                </select>

                            </div>

                            <button type="button" class="btn btn-warning next-btn float-end mt-3">Next</button>
                            <button type="button" class="btn btn-danger prev-btn float-end mt-3 me-2">Previous</button>
                        </div>

                        <!-- Step 4: Review and Submit -->
                        <div id="step-4" class="form-step" style="display:none;">
                            <div class="title">Review and Submit</div>
                            <div id="review-box" class="box mb-3">
                                <!-- Review content will be populated here -->
                            </div>
                            <button type="submit" class="btn btn-success float-end mt-3">Submit</button>
                            <button type="button" class="btn btn-danger prev-btn float-end mt-3 me-2">Previous</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- end multi-step form -->
        <div id="load_spinner" class="d-flex justify-content-center" style="display: none;">

        </div>

        <input type="hidden" id="schedule" name="schedule"/>
        <div id="schedule_body"></div>
    </div>
</div>


<script>
$(document).ready(function () {
    var schedule = []; // Define the schedule array globally

    // Function to fetch and save schedule data
    function saveSchedule(service_id, doctor_id) {
        $.ajax({
            type: 'GET',
            url: 'handles/fetch_schedule.php',
            dataType: 'json',
            data: { service_id: service_id, doctor_id: doctor_id },
            success: function(response) {
                console.log(response);
                var body = $('#schedule_body');
                body.empty();
                schedule = []; // Clear schedule array

                response.schedule_data.forEach(function(data) {
                    var scheduleItem = {
                        day_of_week: data.day_of_week,
                        start_time: data.start_time,
                        end_time: data.end_time
                    };
                    schedule.push(scheduleItem); // Push schedule item to array

                    const schedule_html = `
                    <div>
                    <p>DAY OF WEEK: ${data.day_of_week}</p>
                    <p>START TIME: ${data.start_time}</p>
                    <p>END TIME: ${data.end_time}</p>
                    </div>
                    <hr>
                    `;
                    body.append(schedule_html); // Append schedule HTML to body
                });

                console.log("Schedule List:", schedule);
                updateDatePickerAvailability(); // Call function to update datepicker
            },
            error: function(error) {
                console.log("Error fetching schedule:", error);
            }
        });
    }

    // Function to update datepicker availability based on schedule
    function updateDatePickerAvailability() {
        $("#appointment_date").datepicker("destroy"); // Destroy existing datepicker instance
        
        // Array to map numeric day index to string day name
        var dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $("#appointment_date").datepicker({
            dateFormat: 'yy-mm-dd',
            showButtonPanel: true,
            minDate: 0,
            changeMonth: true,
            changeYear: true,
            beforeShowDay: function(date) {
                var day = date.getDay(); // Get day of the week (0 - Sunday, 1 - Monday, ..., 6 - Saturday)

                // Check if day is enabled based on schedule
                var isEnabled = schedule.some(function(item) {
                    // Use the day index directly for comparison
                    var scheduleDay = dayNames.indexOf(item.day_of_week);
                    return scheduleDay === day;
                });

                return [isEnabled, isEnabled ? '' : 'disabled']; // Return [true/false, 'custom-css-class'] based on isEnabled
            },
            onSelect: function(dateText) {
                // Implement any additional logic on date selection if needed
            }
        });
    }

    // Event handler for procedure selection change
    $(document).on('change', '#procedure-select', function() {
        var service_id = $(this).val();
        var doctor_id = $(this).find(':selected').data('doctor-id');

        console.log("Procedure selected:", service_id, doctor_id);
        saveSchedule(service_id, doctor_id); // Fetch and save schedule for selected procedure
    });

    // Initial loading of procedures
    loadProcedures();

    function loadProcedures() {
        $.ajax({
            type: 'GET',
            url: 'handles/read_services.php',
            dataType: 'JSON',
            success: function(response) {
                console.log(response);
                $('#procedure-select').empty();
                $('#procedure-select').append('<option value="" disabled selected>Select a procedure</option>');

                $.each(response.data, function(key, value) {
                    const option = `<option value="${value.service_id}" data-service-name="${value.service_name}" data-service-duration="${value.duration}" data-service-max="${value.max}" data-doctor-id="${value.doctor_id}">${value.service_name}</option>`;
                    $('#procedure-select').append(option);
                });

                // Initialize datepicker after loading procedures
                $("#appointment_date").datepicker({
                    dateFormat: 'yy-mm-dd',
                    showButtonPanel: true,
                    minDate: 0,
                    changeMonth: true,
                    changeYear: false
                });
            },
            error: function(error) {
                console.log("Error loading procedures:", error);
            }
        });
    }

    // Event handler for next button clicks (form navigation)
    $('.next-btn').click(function() {
        var $currentStep = $(this).closest('.form-step');
        if ($currentStep.attr('id') === 'step-1') {
            if ($('#procedure-select').val() === null) {
                alert('Please select a procedure before proceeding.');
                return;
            }
        } else if ($currentStep.attr('id') === 'step-2') {
            if ($('#request_image')[0].files.length === 0) {
                alert('Please upload an image before proceeding.');
                return;
            }
        } else if ($currentStep.attr('id') === 'step-3') {
            if ($('#appointment_date').val() === '' || $('.form-select').val() === null) {
                alert('Please select a date and time before proceeding.');
                return;
            }
            var appointmentTime = new Date($('#appointment_date').val() + 'T' + $('.form-select').val());
            var now = new Date();
            now.setHours(now.getHours() + 1);

            if (appointmentTime < now) {
                alert('Appointment time must be at least 1 hour from now.');
                return;
            }
        }
        $currentStep.hide().next('.form-step').show();

        // Update review box content on step 3
        if ($currentStep.attr('id') === 'step-3') {
            populateReviewBox();
        }
    });

    // Event handler for previous button clicks (form navigation)
    $('.prev-btn').click(function() {
        $(this).closest('.form-step').hide().prev('.form-step').show();
    });

    // Function to populate review box content
    function populateReviewBox() {
        var service_id = $('#procedure-select').val();
        var service_name = $('#procedure-select option:selected').data('service-name');
        var request_image = $('#request_image')[0].files[0];
        var appointment_date = $('#appointment_date').val();
        var appointment_time = $('.form-select').val();

        $('#review-box').html(`
            <p><strong>Procedure:</strong> ${service_name}</p>
            <p><strong>Image:</strong> ${request_image ? request_image.name : 'No image uploaded'}</p>
            <p><strong>Appointment Date:</strong> ${appointment_date}</p>
            <p><strong>Appointment Time:</strong> ${appointment_time}</p>
        `);
    }

    // Event handler for form submission
    $('#appointment-form').submit(function(e) {
        e.preventDefault();
        var service_id = $('#procedure-select').val();
        var service_name = $('#procedure-select option:selected').data('service-name');
        var request_image = $('#request_image')[0].files[0];
        var appointment_date = $('#appointment_date').val();
        var appointment_time = $('.form-select').val();
        var formData = new FormData();
        formData.append('user_id', <?php echo($_SESSION['user_id']); ?>);
        formData.append('full_name', "<?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>");
        formData.append('email', "<?php echo($_SESSION['email']); ?>");
        formData.append('service_id', service_id);
        formData.append('service_name', service_name);
        formData.append('appointment_date', appointment_date);
        formData.append('appointment_time', appointment_time);
        formData.append('request_image', request_image);
        $.ajax({
            type: 'POST',
            url: 'handles/submit_appointment.php',
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            beforeSend: function() {
                $('#load_spinner').show();
            },
            success: function(response) {
                $('#load_spinner').hide();
                alert('Appointment submitted successfully!');
                window.location.href = "your_appointments.php";
            },
            error: function(error) {
                $('#load_spinner').hide();
                console.log("Error submitting appointment:", error);
            }
        });
    });
});
</script>


<?php
include_once('footer_script.php');
?>