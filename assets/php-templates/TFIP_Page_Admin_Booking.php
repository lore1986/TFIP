<?php 
/* Template Name: Calendario Prenotazioni */
?>

<div id="content" class="site-content" role="main">
  <div id="primary" class="content-area">

    <?php
      wp_head();
      $daysArr = ["Lunedi", "Martedi", "Mercoledi", "Giovedi", "Venerdi", "Sabato", "Domenica"];
      get_header();

      if (current_user_can('editor') || current_user_can('administrator')) {
          echo '<header class="entry-header"><h1 class="ciao-admin">Prenotazioni</h1></header>';
      } else {
          echo '<h1>Ciao</h1>';
      }
    ?>

    <div class="TFIP-style">
      <div class="container">
        <div class="row admin-prenotazione" id="row-admin-prenotazione" style="display: none;">
          <div class="col-12" id="message-admin"></div>
          <div class="col-12">
            <div id="form-booking-admin"></div>
          </div>
        </div>
      </div>

      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="admin-calendario" id="admin-calendario">
              <div class="row text-center mt-3">
                <div class="col-10">
                  <div class="row">
                    <div class="col-2">
                      <button class="btn btn-primary" onclick="ajax_admin_call_calendar(0)" id="prev-month">&lt;</button>
                    </div>
                    <div class="col-8 center">
                      <h4 id="date-text-val"></h4>
                      <p id="date-val" style="display:none;"><?php echo strtotime(date('d-m-Y')) ?></p>
                    </div>
                    <div class="col-2 center">
                      <button class="btn btn-primary" onclick="ajax_admin_call_calendar(1)" id="next-month">&gt;</button>
                    </div>
                  </div>
                </div>
                <div class="col-2 text-center">
                  <button type="button" class="btn btn-success" id="booking-form-caller" onclick="CallBookingForm(1)">+ Add Booking</button>
                </div>
              </div>

              <div class="row" id="calendar-container">
                <!-- The calendar will render inside here -->
              </div>
            </div>

            <div id="container-booking" style="display: none;">
              <div class="row">
                <div class="col-6" id="container-timeslots-bookings" style="display: none;"></div>
                <div class="col-6" id="container-side" style="display: none;"></div>
              </div>
              <div class="row">
                <div class="col-12" id="container-events" style="display: none;"></div>
              </div>
            </div>

            <div id="container-timeslots-edit" style="display: none;">
              <!-- Here single booking will render -->
            </div>

          </div> <!-- /.col-12 -->
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
