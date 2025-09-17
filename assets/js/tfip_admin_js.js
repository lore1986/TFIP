document.addEventListener('DOMContentLoaded', function () {

    const typeSelect = document.getElementById('type_event');
    const sportFields = document.getElementById('sport_fields');
    const eventDate = document.getElementById('event_date');
    const eventTime = eventDate.getAttribute('data-idtime');

    sportFields.style.display = typeSelect.value === 'sport' ? 'block' : 'none';

    typeSelect.addEventListener('change', function () {
        sportFields.style.display = this.value === 'sport' ? 'block' : 'none';
    });


    flatpickr("#event_date", {
        dateFormat: "d-m-Y",
        minDate: "today",
        allowInput: true,
        disableMobile: true
    });

    updateTimeslots(eventDate.value, 'event_time', null, null, 1).then(
        (response) =>
        {
            const timeslot_time = document.getElementById('get_event_time').value;
            const event_exact_time = document.getElementById('get_exact_event_time').value;
            
            //console.log(event_exact_time);
            //console.log(timeslot_time)

            if(timeslot_time != '')
            {
                var correctTimeslot = null;

                for (let index = 0; index < response.length; index++) {
                    const element = response[index].ts;
    
                    if(element.timeslotstart == timeslot_time)
                    {
                        correctTimeslot = element;
                        correctTimeslot.timeslotSelected = 1;
                        break;
                    }
                }
                
                if(event_exact_time != '')
                {
                    for (let index = 0; index < correctTimeslot.exact_time.length; index++) {
                        
                        const eTimes = correctTimeslot.exact_time[index].et;
                        if(eTimes.id == event_exact_time)
                        {
                            eTimes.sel = 1;
                            break;
                        }
                    }

                    LoadExactTimesTemplate(correctTimeslot.exact_time, 'exact_event_time');
                }
               
            }

    
            AttachUpdateTimeslotEvent('event_date', 'event_time', 'exact_event_time');
            AttachExactTimeEvent('event_time', 'exact_event_time' )
        }
    )


    
});