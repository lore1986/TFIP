function convert_date_to_string(times_date) {
    const dateObj = new Date(parseInt(times_date) * 1000);
    const day = String(dateObj.getDate()).padStart(2, '0');
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const year = dateObj.getFullYear();
    const dateStr = `${day}-${month}-${year}`;
    return dateStr
}

function AttachUpdateTimeslotEvent(selectTagId, selfRefObject, resetTimeDivIn)
{
    const dateInput = document.getElementById(selectTagId);
    
    dateInput.addEventListener('change', function (event) {
        const selectedDate = event.target.value;
        const resetTimeDiv = document.getElementById(resetTimeDivIn);
        resetTimeDiv.innerHTML = '<option value="0">Change to update time values</option>';
        updateTimeslots(selectedDate, selfRefObject);
    });
}





function AttachExactTimeEvent(selectETimeTagId, divName)
{
    const select = document.getElementById(selectETimeTagId);

    select.addEventListener('change', function() {

        const selectedOption = this.options[this.selectedIndex];

        if(selectedOption != 0)
        {
            const idStart = selectedOption.getAttribute('data-idstart');
            const idEnd = selectedOption.getAttribute('data-idend');
            
            updateExactBookingTime(idStart, idEnd, divName);
        }
    });
}


function LoadExactTimesTemplate(exacttimes, divname) {
    const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/exact-time-instances.html';

    jQuery.get(templateUrl, function (templateHtml) {

        const timeslotTemplate = _.template(templateHtml);
        const renderedTimeslots = timeslotTemplate({ etimes: exacttimes });
        const timeslotSelect = document.getElementById(divname);
        timeslotSelect.innerHTML = renderedTimeslots;
    });
}

function Load_Timeslots(timeslots, divName) {
    return new Promise((resolve, reject) => {
        try {

            const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/timeslots-instance-booking-form.html';

            jQuery.get(templateUrl, function (templateHtml) {

                const timeslotTemplate = _.template(templateHtml);
                const renderedTimeslots = timeslotTemplate({ timeslots: timeslots });
                const timeslotSelect = document.getElementById(divName);
                timeslotSelect.innerHTML = '<option value="0">Seleziona Fascia Oraria</option>';
                timeslotSelect.innerHTML += renderedTimeslots;

                resolve();
            });

        } catch (err) {
            reject();
        }
    })

}

function updateTimeslots(date, object_name, timeslotid = null, booking_time = null) {

    return new Promise((resolve, reject) => {
        try {
            jQuery.ajax({
                url: TFIP_Ajax_Obj.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'TFIP_Calendar_Get_Timeslots',
                    date: date,
                    slotid: timeslotid,
                    time_s: booking_time,
                    nonce: TFIP_Ajax_Obj.nonce
                },
                success: function (response) {

                    console.log("update timeslot")
                    console.log(response);

                    Load_Timeslots(response, object_name)

                    resolve(response)
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching timeslots:', error);
                    reject(error || status);
                }
            });
        } catch (err) {
            reject(err);
        }
    })

}

function updateExactBookingTime(start, end, divname) {
    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'TFIP_Calendar_Exact_Booking_Time',
            timestart: start,
            timeend: end
        },
        success: function (response) {

            console.log(response)

            const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/exact-time-instances.html';

            jQuery.get(templateUrl, function (templateHtml) {

                const timeslotTemplate = _.template(templateHtml);
                const renderedTimeslots = timeslotTemplate({ etimes: response });
                const timeslotSelect = document.getElementById(divname);
                timeslotSelect.innerHTML = renderedTimeslots;
            });

            return 1;
        },
        error: function (xhr, status, error) {
            console.error('Error fetching timeslots:', error);
            return 0;
        }
    });
}


