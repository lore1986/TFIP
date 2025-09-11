<div class="calendario-prenotazione TFIP-style">
    <div id="container-booking">
        <div id="tmp-loaded-form">
            <input type="number"  id="datestamp" name="datestamp" value="<?php echo esc_html( $objdata->date_stamp ); ?>"   />
            <div class="row">
                <div class="col-7">
                    <input type="text" 
                        class="form-control time-input" 
                        id="date_str" 
                        name="date_str" 
                        value="<?php esc_html( $objdata->date_stamp ) ?>" />
                </div>

                <div class="col-5">
                    <select id="client_time" name="client_time" class="form-control form-select">
                    <option value="0">Seleziona orario</option>
                    <?php if (!empty($objdata->timeslots)) { ?>
                        <?php foreach ($objdata->timeslots as $slotWrapper) { ?>
                            <?php $slot = $slotWrapper['ts'];  ?>
                            <option value="<?php echo esc_html($slot['timeslotstart']); ?>">
                                <?php echo esc_html($slot['timeslotTimeStr']); ?>
                            </option>
                        <?php } ?>
                    <?php } ?>
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
    
    const date_str = document.getElementById('date_str');

    document.addEventListener('DOMContentLoaded', function () {
        flatpickr(date_str, {
            dateFormat: "d-m-Y",
            minDate: "today",
            defaultDate: "<?= esc_html( $objdata->date_str ) ?>"
        });
    });

    date_str.addEventListener("change", (event) => {
        
        let n_date_str = document.getElementById('date_str').value;
        
        jQuery.ajax({
            url: TFIP_Ajax_Obj.ajaxUrl,
            method: 'POST',
            data: {
                action: 'tfip_update_timestamp',  
                date: n_date_str,
                nonce: TFIP_Ajax_Obj.nonce
            },
            success: function(response) {

                console.log(response)
                document.getElementById('datestamp').value = response.datestamp;
            },
            error: function(xhr, status, error) {
                console.error('Error fetching timeslots:', error);
            }
        });
        

        updateTimeslots(n_date_str, 'client_time');
    });



    function BookNoEvent()
    {
        var date = document.getElementById('date_str').value;
        var time = document.getElementById('client_time').value;
        var datestamp = document.getElementById('datestamp').value;

        LoadBaseClientBookingForm(null, null, datestamp, time);

    }
</script>
