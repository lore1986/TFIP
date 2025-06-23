function ExtendDates(){


    jQuery.ajax({
        url : ajaxurl,
        type: 'POST',
        data: {
            action: 'tfipf_extend_dates_instances',
            //nonce: tfipf_ajax_object.nonce
        },
        success: function(response) {

            // var succeded = response.succeded;
            var ddate = response.date;

            alert("Success, update dates last date now is: " + ddate);
        },
        error: function(xhr, status, error) {
            // console.log(error)
            //alert('An error occurred: ' + error);
        }
    });
}

const fpConf = {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    time_24hr: true,
    defaultHour: 17,
    // minTime: "17:00",
    // maxTime: "23:59",
    minuteIncrement: 1
};

document.addEventListener('DOMContentLoaded', function () {

    flatpickr(".time-input", fpConf);

    const form = document.querySelector('form');


    form.addEventListener('submit', function (e) {
        const inputs = document.querySelectorAll('.ts_instance');
        let prevEndTime = null;
        let isValid = true;
        let message = '';
        
        let sumofall = 0;

        for (let i = 0; i < inputs.length; i++) {
            const startInput = inputs[i].querySelector('input[name$="[start]"]');
            const endInput = inputs[i].querySelector('input[name$="[end]"]');
            const capacityInput = inputs[i].querySelector('input[name$="[capacity]"]');


            const start = startInput.value;
            const end = endInput.value;
            const capacity = capacityInput.value;
            
            sumofall += capacity;
 

            //query ajax for max capacity

            if (!start || !end) {
                isValid = false;
                message = `Timeslot ${i}: both start and end must be filled.`;
                break;
            }

            const startTime = convertTimeToMinutes(start);
            const endTime = convertTimeToMinutes(end);

            if (startTime >= endTime) {
                isValid = false;
                message = `Timeslot ${i}: start time must be before end time.`;
                break;
            }

            if (prevEndTime !== null && startTime <= prevEndTime) {
                isValid = false;
                message = `Timeslot ${i}: must start after previous timeslot ends.`;
                break;
            }

            prevEndTime = endTime;
        }

        if (!isValid) {
            e.preventDefault();
            alert(message);
        }
    });

    function convertTimeToMinutes(time) {
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    }
});

function Create_New_Timeslot_HTML_Instance(i) {
    let count_ts_instances = i + 1;

    const container = document.createElement('div');
    container.className = "ts_instance";
    container.id = `${count_ts_instances}_id_ts`;

    container.innerHTML = `
        <div><p>Timeslot ${count_ts_instances}</p></div>
        <div><input class="time-input" type="text" id="${count_ts_instances}_timeslot_start" name="tfip_timeslots[${count_ts_instances}][start]"></div>
        <div><input class="time-input" type="text" id="${count_ts_instances}_timeslot_end" name="tfip_timeslots[${count_ts_instances}][end]"></div>
        <div><input type="number" id="${count_ts_instances}_timeslot_capacity" name="tfip_timeslots[${count_ts_instances}][capacity]"></div>
        <div>
            <button type="button" class="button button-primary" id="${count_ts_instances}_add_timeslot_instance_button">Add Timeslot</button>
            <button type="button" class="button button-primary" id="${count_ts_instances}_delete_timeslot_instance_button">Remove Timeslot</button>
        </div>
    `;

    document.getElementById('ts-container').appendChild(container);

    document.getElementById(`${count_ts_instances}_timeslot_start`).flatpickr(fpConf);
    document.getElementById(`${count_ts_instances}_timeslot_end`).flatpickr(fpConf);

    const prevAdd = document.getElementById(`${i}_add_timeslot_instance_button`);
    const prevDel = document.getElementById(`${i}_delete_timeslot_instance_button`);
    if (prevAdd) prevAdd.style.display = "none";
    if (prevDel) prevDel.style.display = "none";

    document.getElementById(`${count_ts_instances}_add_timeslot_instance_button`)
        .addEventListener('click', () => Create_New_Timeslot_HTML_Instance(count_ts_instances));
    document.getElementById(`${count_ts_instances}_delete_timeslot_instance_button`)
        .addEventListener('click', () => Delete_Timeslot_HTML_Instance(count_ts_instances));
}

function Delete_Timeslot_HTML_Instance(i) {
    const thisInstance = document.getElementById(`${i}_id_ts`);
    if (thisInstance) thisInstance.remove();

    const prevIndex = i - 1;
    const prevAdd = document.getElementById(`${prevIndex}_add_timeslot_instance_button`);
    const prevDel = document.getElementById(`${prevIndex}_delete_timeslot_instance_button`);

    if (prevAdd) prevAdd.style.display = "inline-block";
    if (prevDel && prevIndex !== 0) prevDel.style.display = "inline-block";
}
