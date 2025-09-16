<div class="calendario-prenotazione TFIP-style">
    <div id="container-booking">
        <div class="row">
            <div class="alert alert-warning" role="alert" id="alert-booking" hidden></div>
        </div>
        <div id="tmp-loaded-form">
            <input type="number" hidden id="datestamp" name="datestamp" value="<?php echo esc_html( $objdata->date_stamp ); ?>"  hidden  />
            <div class="row">
                <div class="col-12 col-md-5 mb-2">
                    <input type="text" 
                        class="form-control time-input" 
                        id="date_str" 
                        name="date_str" 
                        value="<?php echo esc_html( $objdata->date_str ) ?>" />
                </div>

                <div class="col-12 col-md-3 mb-2">
                    <select id="client_timeslot" name="client_timeslot" class="form-control form-select">
                        <option value="0">Seleziona Fascia Oraria</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 mb-2">
                    <select id="exact_client_time" name="exact_client_time" class="form-control form-select">
                        <option value="0">Specifica un orario</option>
                    </select>
                </div>
            </div>
            <button type="button" id="button-no-event-booking"  onclick="BookNoEvent()" class="btn button-no-event-booking btn-success">
                Prenota
            </button>                    
        </div>
        
    </div>
    <div id="error-booking-noevent"></div>
</div>

<script>
    

    document.addEventListener('DOMContentLoaded', function () {

        const fpConf = {
            dateFormat: "d-m-Y",
            minDate: "today",
            allowInput: true,
            disableMobile: true
        };
    
        const datecontrol = document.getElementById('date_str');

        datecontrol.flatpickr(fpConf);
        

        Load_Timeslots(<?= wp_json_encode( $objdata->timeslots ) ?>, 'client_timeslot').then(
            ()=>{
                AttachUpdateTimeslotEvent('date_str', 'client_timeslot', 'exact_client_time');
                AttachExactTimeEvent('client_timeslot', 'exact_client_time');
            }
        )
    });



    function BookNoEvent()
    {
        var date = document.getElementById('date_str').value;
        var timeslot = document.getElementById('client_timeslot').value;
        var exactTime = document.getElementById('exact_client_time').value;
        var datestamp = document.getElementById('datestamp').value;
        const alertb = document.getElementById('alert-booking');


        if(date != '' && timeslot != 0 && exactTime != 0)
        {
            jQuery.ajax({
            url: TFIP_Ajax_Obj.ajaxUrl,
            method: 'POST',
            data: {
                action: 'tfip_Check_If_Closed_or_Full',
                nonce: TFIP_Ajax_Obj.nonce,
                dayId: datestamp,
                btime: exactTime,
                tstime: timeslot
            },
            success: function(response) {

                if(response.resolution == 1)
                {
                    alertb.innerText = response.message;
                    alertb.hidden = true;
                    LoadBaseClientBookingForm(null, null, datestamp, exactTime);

                }else
                {
                    alertb.innerText = response.message;
                    alertb.hidden = false;
                }
                            
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        })}else
        {
            alertb.innerText = "Please choose date, a timeslot and time";
            alertb.hidden = false;
        }
        
    }
</script>