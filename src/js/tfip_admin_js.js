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
