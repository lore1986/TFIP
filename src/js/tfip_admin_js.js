console.log("OK");
console.log(TFIP_Ajax_Obj.ajaxUrl);

function Activate_Admin_Event_Options(refresh)
{
    console.log(refresh)
    const typeSelect = document.getElementById('type_event');
    const sportFields = document.getElementById('sport_fields');
    const eventDate = document.getElementById('event_date');
    const eventTime = eventDate.getAttribute('data-idtime');

    sportFields.style.display = typeSelect.value === 'sport' ? 'block' : 'none';

    typeSelect.addEventListener('change', function () {
        sportFields.style.display = this.value === 'sport' ? 'block' : 'none';
    });


    console.log(eventDate.value);
    
    if(refresh === 1)
    {
        updateTimeslots(eventDate.value, 'event_time', null, null, 1);
    }
    

    // Initialize Flatpickr
    flatpickr("#event_date", {
        dateFormat: "d-m-Y",
        minDate: "today",
        allowInput: true,
        onChange: function(selectedDates, dateStr, instance) {
            if (dateStr) {
                updateTimeslots(dateStr, 'event_time', null, null, 1);
            }
        }
    });

}

function updateTimeslots(date, object_name, timeslotid = null, booking_time = null, timedslot = null) {

    console.log(date)

    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tfip_get_form_timeslots_booking_admin',  
            date: date,
            slotid: timeslotid,
            time_s: booking_time,
            timedslot : timedslot,
            nonce: TFIP_Ajax_Obj.nonce
        },
        success: function(response) {
            
            console.log("update Timeslot response")
            console.log(response)

            const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/timeslots-instance-booking-form.html';
            
            jQuery.get(templateUrl, function(templateHtml) {
    
                const timeslotTemplate = _.template(templateHtml);
                const renderedTimeslots = timeslotTemplate({ timeslots: response });
                const timeslotSelect = document.getElementById(object_name);
                timeslotSelect.innerHTML = '<option value="0">Seleziona orario</option>'; 
                timeslotSelect.innerHTML += renderedTimeslots; 
            });
        },
        error: function(xhr, status, error) {
            console.error('Error fetching timeslots:', error);
        }
    });
}