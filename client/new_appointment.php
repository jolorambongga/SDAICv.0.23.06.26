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

    var schedule = [];

    console.log("SCHEDULE:", schedule);

    function saveSchedule(service_id, doctor_id) {
        $.ajax({
            type: 'GET',
            url: 'handles/fetch_schedule.php',
            dataType: 'json',
            data: {service_id: service_id, doctor_id: doctor_id},
            success: function(response) {
                console.log(response);
                var body = $('#schedule_body');
                body.empty();
                schedule = [];
                response.schedule_data.forEach(function(data) {

                    var scheduleItem = {
                        day_of_week: data.day_of_week,
                        start_time: data.start_time,
                        end_time: data.end_time
                    };

                    schedule.push(scheduleItem);

                    const schedule_html = `
                        DAY OF WEEK: ${data.day_of_week} <hr>
                        START TIME: ${data.start_time} <hr>
                        END TIME: ${data.end_time} <hr>
                    `;
                    body.append(schedule_html);
                });

                console.log("ANG AKING SCHEDULE LIST:", schedule);
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    $(document).on('change', '#procedure-select', function() {
        var service_id = $('#procedure-select').val();
        var doctor_id = $(this).find(':selected').data('doctor-id');

        console.log("ON CHANGE FUNCTION", service_id, doctor_id);

        saveSchedule(service_id, doctor_id);

        
    });

    $("#appointment_date").datepicker({
        dateFormat: 'yy-mm-dd', 
        showButtonPanel: true, 
        minDate: 0, 
        changeMonth: true, 
        changeYear: true 
    });

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
                
                $.each(response.data, function(key, value){
                    const option = `
                    <option value="${value.service_id}" data-service-name="${value.service_name}" data-service-duration="${value.duration}" data-service-max="${value.max}" data-doctor-id="${value.doctor_id}">${value.service_name}</option>
                    `;
                    $('#procedure-select').append(option);
                });
            },
            error: function(error) {
                console.log("ERROR SA LOAD PROCEDURES:", error);
            }
        });
    }

    $('.next-btn').click(function(){
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

        if ($currentStep.attr('id') === 'step-3') {
            populateReviewBox();
        }
    });

    $('.prev-btn').click(function(){
        $(this).closest('.form-step').hide().prev('.form-step').show();
    });

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

    $('#appointment-form').submit(function(e){
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
                console.log("ERROR SA SUBMIT APPOINTMENT:", error);
            }
        });
    });
});

</script>


<?php
include_once('footer_script.php');
?>