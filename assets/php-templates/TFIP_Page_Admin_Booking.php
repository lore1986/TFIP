<?php 
/* Template Name: Calendario Prenotazioni */
?>

<?php
      wp_head();
      $daysArr = ["Lunedi", "Martedi", "Mercoledi", "Giovedi", "Venerdi", "Sabato", "Domenica"];
      get_header();
?>


<div id="content" class="site-content" role="main">
  <div id="primary" class="content-area">

    <div class="TFIP-style">
      <div class="container-fluid">

        <div class="row">
          <div class="col-12"  id="main-calendar">
              <div class="row align-items-center mb-3">
                <div class="col-lg-4 col-8 d-flex justify-content-between align-items-center">
                  <button class="btn btn-primary" onclick="ajax_admin_call_calendar(0)" id="prev-month">&lt;</button>
                  <h4 id="date-text-val" class="mb-0 text-center flex-grow-1"></h4>
                  <button class="btn btn-primary" onclick="ajax_admin_call_calendar(1)" id="next-month">&gt;</button>
                  <p id="date-val" class="d-none"><?php echo strtotime(date('d-m-Y')) ?></p>
                </div>
                <div class="col-lg-8 col-4 text-end">
                  <button type="button" class="btn btn-success" id="booking-form-caller" onclick="CallBookingForm()">
                    + Add Booking
                  </button>
                </div>
              </div>
              <!-- Calendar Container -->
              <div class="row" style="padding: 10px;" id="calendar-container">
                <!-- The calendar will render inside here -->
              </div>
          </div>
        </div>
        

        <div class="row">
          <div class="col-12 booking-form-container" id="booking-form-container_id"><!-- Admin Booking Form will render inside here --></div>
          <div class="col-12" id="container-timeslots-bookings" hidden><!-- Bookings for single day will render inside here --></div>
          <div class="col-12" id="container-events" hidden><!-- Events for single day will render inside here --></div>
          <div class="col-12" id="container-timeslots-edit" hidden><!-- Admin Edit Delete Create Timeslots will render inside here --></div>
          
        </div> <!-- /.row -->

      </div> <!-- /.container-fluid -->
    </div> <!-- /.TFIP-style -->

  </div> <!-- /#primary -->
</div> <!-- /#content -->



<script>
  window.onload = function() {
    ajax_admin_call_calendar(-1);
  };

  function Hide_Calendar_Bookings() {
    document.getElementById("calendar-container").style.display = "none";
    document.getElementById("single-day-calendar-bookings").style.display = "block";
  }

  function Show_Calendar_Bookings() {
    document.getElementById("calendar-container").style.display = "initial";
    document.getElementById("single-day-calendar-bookings").style.display = "none";
  }

</script>

<?php get_footer(); ?>
