function ajax_call_calendar(_maxnum)
{
    jQuery.ajax({
        url : TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'get_calendar_html',
            maxnum: _maxnum
        },
        success: function(response) {

            jQuery('#container-list-events').html(response);

        },
        error: function(xhr, status, error) {
            console.error(error);
        }
    });
}


function validateFormAdminBooking(formId) {


    const form = document.getElementById(formId);
    const elements = form;

    let output = {
        res: false,
        message: "empty"
    }

    for (let element of Array.from(elements)) {

        const tag = element.tagName.toLowerCase();
        const type = element.type;
        const name = element.name?.toLowerCase();

        if (
            tag === 'button' ||
            element.classList.contains('iti__search-input') ||
            name === 'code'
        ) {
            continue;
        }

        // check if time is okvalidateFormAdminBooking
        if (tag === 'select' && name === 'time_booking') {
            if (parseInt(element.value) === 0) {
                output.message = "L'orario della prenotazione mancante o sbagliato";
                return output;
            }
            continue;
        }

        // Skip disabled fields like code
        if (element.disabled) continue;

        if (
            element.hasAttribute('required') &&
            (!element.value || element.value.trim() === '')
        ) {
            console.log("element is required")

            output.message = "Elemento " + element.name  + " manca ed e' obbligatorio";

            return output;
        }
    }

    output.res = true;
    return output;
}

function serializeForm(formId) {

    const form = document.getElementById(formId);
    const formData = new FormData(form);

    console.log(formData)


    const result = {};

    for (let [key, value] of formData.entries()) {

        if (result.hasOwnProperty(key)) {
            if (!Array.isArray(result[key])) {
                result[key] = [result[key]];
            }
            result[key].push(value);
        } else {
            result[key] = value;
        }
    }

    return result;
}


function save_form_admin_booking() {

    const formId = 'add-booking-admin-form';
    const formdata = serializeForm(formId);
    const jsonData = {};

    document.getElementById('message-admin').innerHTML = '';

    const valid = validateFormAdminBooking(formId);


    if (!valid.res) {

        
        const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/admin-response-model.html';

        jQuery.get(templateUrl, function (templateHtml) {
            const templateCompiled = _.template(templateHtml);
            const renderedTemplate = templateCompiled({
                adminmessage: valid.message,
                alertclass: 'alert-danger'
            });
            document.getElementById('message-admin').innerHTML = renderedTemplate;
        });
        
        return;
    }

    const dateid = formdata['admin_date_id'];
    
    for (let key in formdata) {
        jsonData[key] = formdata[key];
    }

    if (jsonData.phone && typeof itln !== 'undefined') {
        jsonData.phone = itln.getNumber();
    }

    console.log(jsonData)

    
    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'tfip_admin_create_booking',
            formdata: JSON.stringify(jsonData),
            nonce: TFIP_Ajax_Obj.nonce
        },
        success: function (response) {
            
            let admin_message = response.message;
            
            document.getElementById('message-admin').innerHTML = '';
        
            let templateUrl;
            switch (response.resolution) {
                case 3:
                    //blocked day
                    templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/unblock-day.html';
                    break;
                case 5:
                    //blocked timeslot
                    templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/unblock-timeslot.html';
                    break;
                default:
                    templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/admin-response-model.html';
                    break;
            }
            
            jQuery.get(templateUrl, function (templateHtml) {
                const templateCompiled = _.template(templateHtml);
                const renderedTemplate = templateCompiled({
                    adminmessage: admin_message,
                    alertclass: response.resolution !== 1 ? 'alert-danger' : 'alert-primary',
                    day_id: response.day_id,
                    timeslot_id: response.timeslot_id
                });
                document.getElementById('message-admin').innerHTML = renderedTemplate;
            });

            if(response.resolution !== 1)
            {
                if (response.resolution === 3 || response.resolution === 5) {
                    proceedButton.on('click', function () {
                        const action = response.resolution === 3 ? 'tfip_unblock_day' : 'tfip_unblock_timeslot';
                        const idKey = response.resolution === 3 ? 'day_id' : 'timeslot_id';
                        const idValue = $(this).data(idKey.replace('_', '-'));
        
                        $.ajax({
                            url: TFIP_Ajax_Obj.ajaxUrl,
                            method: 'POST',
                            data: {
                                action: action,
                                [idKey]: idValue,
                                security: TFIP_Ajax_Obj.nonce
                            },
                            success: function (res) {
                        
                                const messageElement = document.getElementById('a-message');
        
                                if (res.resolution === 1) {
                                    messageElement.textContent = res.message;
                                    proceedButton.hide();


                                } else {
                                    messageElement.innerHTML = res.message + " . Please try again or refresh page";
                                }
                            },
                            error: function () {
                                alert('An error occurred while processing the booking.');
                            }
                        });
                    });
                }
            }else
            {
                jQuery(document).ready(function ($) {
                    const proceedButton = $('#proceed-booking-btn');

                    if(response.resolution === 1)
                    {
                        const [day, month, year] = dateid.split("-");

                        const dateObj = new Date(`${year}-${month}-${day}`);
                        const dateTimestamp = (dateObj.getTime()) / 1000; 

                        console.log(dateTimestamp)
                        hide_show_layers(['container-timeslots-bookings'])
                        PrintDayBookings(dateTimestamp)
                    }
                });
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });
}

function EnableDisableDay(el)
{
    const dDayId = el.getAttribute('data-day-id');
    const newStatus = el.getAttribute('data-day-status');

    console.log(dDayId)

    jQuery.ajax({
        
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tfip_get_disable_day',  
            dateId : dDayId,
            newStatus : newStatus,
            nonce: TFIP_Ajax_Obj.nonce
        },
        success: function(response) {
            
            console.log(response)

            if(response.resolution == 1)
            {
                PrintDayBookings(response.dateid);
            }
            
            
        },
        error: function(xhr, status, error) {
            console.error('Error fetching timeslots:', error);
        }
    })
}


const list_containers = [
    'main-calendar',
    'booking-form-container_id',
    'container-timeslots-bookings',
    'container-timeslots-edit',
    'booking-form-caller',
    'booking-form-container_id',
    'container-events'
]

function hide_show_layers(show_list) {

    list_containers.forEach(layer => {
        const element = document.getElementById(layer);
        if (!element) return;

        if (show_list.includes(layer)) {
            element.hidden = false;  
        } else {
            element.hidden = true;  
        }
    });
}

function CallBookingForm() {
    
    const show_list = ['booking-form-container_id']
    hide_show_layers(show_list)


    const now = new Date();
    const todayUTC = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), 0, 0, 0));
    const dayId = Math.floor(todayUTC.getTime() / 1000);
    const date_str = convert_date_to_string(dayId);

    const newObject = {
        dayStr: date_str,
        dayId: dayId,
        timeslotid: null,
        resolution: 1,
        message: "OK",
        booking: null,
        timeslot: null,
        alltimeslots: null,
        postevent: null
      };

    load_form_admin_booking('booking-form-container_id', 0, newObject);

    

}

function HideBookingForm() {

    const btn_hide = document.getElementById('hide_form_booking_id')
    const show_list = btn_hide.getAttribute('data-list-containers');

    hide_show_layers(show_list)

}



  
function CallBookingFormFromTimeslot(el) {
    
    const timeslotId = el.getAttribute('data-timeslot-id');
    const dayidId = el.getAttribute('data-day-id');
    console.log(dayidId)

    const date = new Date(dayidId * 1000);
    const day = String(date.getUTCDate()).padStart(2, '0');
    const month = String(date.getUTCMonth() + 1).padStart(2, '0'); 
    const year = date.getUTCFullYear();
    const date_str = `${day}-${month}-${year}`;

    const newObject = {
        dayStr: date_str,
        dayId: dayidId,
        timeslotid: timeslotId,
        resolution: 1,
        message: "OK",
        booking: null,
        timeslot: null,
        alltimeslots: null,
        postevent: null
      };


    load_form_admin_booking('booking-form-container_id', 2, newObject);
}


function deleteBooking(idBooking)
{
    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tfip_admin_delete_booking',
            nonce: TFIP_Ajax_Obj.nonce,
            bookingId: idBooking
        },
        success: function(response) {
            
            console.log(response)

            PrintDayBookings(response.day_id);
            
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });
}

function get_form_admin_booking(obje)
{
    return new Promise((resolve, reject) =>{
        
        console.log(obje)
        const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/main/admin-booking-form-creation.html';

        jQuery.get(templateUrl, function (templateHtml) {

            const templateCompiled = _.template(templateHtml);

            const rendered_template = templateCompiled({
                name_div: obje.name_div,
                origin_i: obje.origin_i,
                idday_i: obje.idday_i,
                day_str: obje.day_str,
            });

            document.getElementById(obje.name_div).innerHTML = rendered_template;

            var phoneInput = document.querySelector("#admin_phone");

            window.intlTelInput(phoneInput, {
                allowDropdown: true,
                initialCountry: "it",
                autoPlaceholder: "polite",
                separateDialCode: true,
                loadUtils: () =>
                    import("https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.0/build/js/utils.js"),
            });

            const dateInput = document.getElementById('admin_date_id');

            const fpConf = {
                enableTime: false,
                noCalendar: false,
                dateFormat: "d-m-Y"
            };

            flatpickr(dateInput, fpConf);

            resolve();
        });
    })
}

function load_form_admin_booking(location, origin, data) {
    
    return new Promise((resolve, reject) => {
        try {
            document.getElementById(location).hidden = false;
            
            console.log("load answer")
            console.log(data)

            if(data.resolution === 1)
            {
                const dayStr = data.dayStr;
                const dayId = data.dayId;

                const obje = {
                    name_div: location,
                    origin_i: origin,
                    idday_i: dayId,
                    day_str: dayStr,
                };
                
                get_form_admin_booking(obje).then(
                    () =>
                    {
                        const alltimeslots = data.alltimeslots;

                        console.log(data.timeslotid)
                        console.log(alltimeslots)

                        if(alltimeslots == null)
                        {
                            if(data.timeslotid == null)
                            {
                                updateTimeslots(dayStr, 'time_booking').then(
                                    (response) => {
                                        AttachUpdateTimeslotEvent('admin_date_id');
                                        AttachExactTimeEvent('time_booking', 'exact_time_booking');
                                    }
                                )
    
                            }else
                            {
                                console.log("should call me instead")
                                LoadExtraTimeslotFormData(data.timeslotid)

                                updateTimeslots(dayStr, 'time_booking', data.timeslotid).then(
                                    (response) => {
                                        
                                        var selectedTimeslot = null;

                                        for (let index = 0; index < response.length; index++) {
                                            
                                            const element = response[index];
                                            if(element.ts.timeslotSelected == 1)
                                            {
                                                selectedTimeslot = element.ts;
                                                break;
                                            }

                                        }

                                        if(selectedTimeslot != null)
                                        {
                                            LoadExactTimesTemplate(selectedTimeslot.exact_time);
                                        }

                                        AttachUpdateTimeslotEvent('admin_date_id');
                                        AttachExactTimeEvent('time_booking', 'exact_time_booking');
                                    }
                                )
                            }
                            
                        }else
                        {
                            Load_Timeslots(alltimeslots, 'time_booking').then(
                                () =>
                                {
                                    Load_Timeslots(data.alltimeslots, 'time_booking').then(

                                        () => {

                                            console.log("booking called")
                                            for (let index = 0; index < alltimeslots.length; index++) {
                                    
                                                const element = alltimeslots[index].ts;
                                                
                                                if(element.id == data.booking.id_timeslot)
                                                {
                                                    LoadExactTimesTemplate(element.exact_time);
                                                    break;
                                                }
                                            }

                                            FillBookingFormData(data.booking)
                                            AttachUpdateTimeslotEvent('admin_date_id');
                                            AttachExactTimeEvent('time_booking', 'exact_time_booking');

                                        }
                                    )
                                
                                }
                            )
                           
                        }
            
                    }
                )

                
                
                resolve();

            }else
            {
                reject("data not valid")
            }

            

        } catch (err) {
            reject(err);
        }
    });
}


// function load_form_admin_booking(location, origin, idday) {

//     document.getElementById(location).hidden = false;
    
//     var dayId = convert_date_to_string(idday);
    
//     const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/main/admin-booking-form-creation.html';

//     jQuery.get(templateUrl, function(templateHtml){

//         const templateCompiled = _.template(templateHtml);

//         const rendered_template = templateCompiled({
//             name_div: location,
//             origin_i: origin,
//             idday_i: idday,
//             day_str : dayId,
//         });


//         document.getElementById(location).innerHTML = rendered_template;

        
//         var phoneInput = document.querySelector("#admin_phone");
        
//         window.intlTelInput(phoneInput, {
//             allowDropdown: true,
//             initialCountry: "it",
//             autoPlaceholder: "polite",
//             separateDialCode: true,
//             loadUtils: () => import("https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.0/build/js/utils.js"),
//         });
        
//         const dateInput = document.getElementById('admin_date_id');

//         const fpConf = {
//             enableTime: false,
//             noCalendar: false,
//             dateFormat: "d-m-Y"
//         };

//         flatpickr(dateInput, fpConf);

    
//         dateInput.addEventListener('change', function(event) {

//             console.log(event.target.value);
//             const selectedDate = event.target.value;
//             updateTimeslots(selectedDate, 'time_booking');
//         });
//     });
// }

function Call_Ajax_On_New_Timeslot() {

    const form = document.getElementById('create-timeslot-form')
    
    document.getElementById('timeslot_errors').style.display = "none";
    document.getElementById('timeslot_errors').innerText = "";

    const formData = new FormData(form);
    const start = formData.get('start');
    const end = formData.get('end');
    

    if (start >= end) {
        document.getElementById('timeslot_errors').innerText = "Start time must be earlier than end time."
        document.getElementById('timeslot_errors').style.display = "inherit"
        return;
    }
    
    const formObject = Object.fromEntries(formData.entries());
    


    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tfip_create_timeslot',
            nonce: TFIP_Ajax_Obj.nonce,
            ...formObject
        },
        success: function(response) {
            
            console.log(response)
            
            let warning_slot = document.getElementById('timeslot_errors');

            if(warning_slot.classList.contains("alert-warning") && response.resolution == 1)
            {
                render_edit_timeslots(response.iddate);

            }else if(warning_slot.classList.contains("alert-success") && response.resolution == 0)
            {
                warning_slot.classList.remove('alert-success');
                warning_slot.classList.add('alert-warning');
            }

            warning_slot.innerText = response.message;
            warning_slot.style.display = 'inherit';

        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });
}


function addNewTimeslot() {
    
    document.querySelectorAll('.timeslot-card[data-timeslot-id="-1"]').forEach(el => el.remove());

    let idDay_in = document.getElementById('day_info').getAttribute('data-dayid');
    const templateUrl = TFIP_Ajax_Obj.templatesUrl + 'internal/partial/admin-timeslot-creation.html';

    jQuery.get(templateUrl, function(templateHtml) {

        const timeslotTemplate = _.template(templateHtml);

        const rendered_form_timeslot = timeslotTemplate(
            {
                idDay : idDay_in
            }
        );

        const fpConf = {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 1,
            disableMobile: true
        };
            

        document.getElementById('timeslot-list').innerHTML = rendered_form_timeslot;

        const timeControl = document.querySelectorAll('input[type="time"]');

        timeControl.forEach(element => {
            element.flatpickr(fpConf);
        });

    })
    
  }



function deleteTimeslot(timeslotId) {

    const existingModal = document.getElementById('deleteTimeslotModal');
    if (existingModal) existingModal.remove();

    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data:
        {
            action: 'tfip_pre_delete_timeslot',
            nonce: TFIP_Ajax_Obj.nonce,
            slot_id: timeslotId,
        },
        success: function (response)
        {
            const templateUrl = TFIP_Ajax_Obj.templatesUrl + 'internal/partial/confirm_delete_timeslot.html';

            jQuery.get(templateUrl, function(templateHtml) {
                const templateFn = _.template(templateHtml);
                const renderedHtml = templateFn({ slot: response });
        
                document.getElementById('timeslot-list').innerHTML = renderedHtml
        
            });
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    })
}


function CancelDeleteTimeslot()
{
    document.getElementById('deleteTimeslotCard').remove() 
    const day_id = document.getElementById('day_info').getAttribute('data-dayid');
    render_edit_timeslots(day_id);
}

function ConfirmDeleteTimeslot(el)
{
    const timeslotid = el.getAttribute('data-ts-id')
    const day_id = document.getElementById('day_info').getAttribute('data-dayid');
    
    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data:
        {
            action: 'tfip_confirm_delete_timeslot',
            nonce: TFIP_Ajax_Obj.nonce,
            slot_id: timeslotid,
            dayid: day_id
        },
        success: function (response)
        {            
            if(response.resolution == 1)
            {
                render_edit_timeslots(day_id);
            }else
            {
                document.getElementById('alert-text').innerText = response.message;
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    })
}


function EnableDisableSlot(el)
{
    const timeslotid = el.getAttribute('data-ts-id');
    const day_id = el.getAttribute('data-day-id');
    const status = el.getAttribute('data-status');
    
    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data:
        {
            action: 'tfip_disable_enable_timeslot',
            nonce: TFIP_Ajax_Obj.nonce,
            slot_id: timeslotid,
            dayid: day_id,
            status: status
        },
        success: function (response)
        {
            const day_id = response.day_id;

            if(response.resolution == 1)
            {
                PrintDayBookings(day_id);
            }else
            {
                document.getElementById('alert-day-booking').style.display ='inherit';
                document.getElementById('alert-day-booking').innerText = response.message;
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    })
}


function Switch_Back_To_Calendar(id_call, id_day)
{
    ajax_admin_call_calendar(-1, id_day);

    var show_list = []
    show_list = ['main-calendar', 'booking-form-caller']
    
    switch (id_call) {
        
        case 0:
            document.getElementById('container-timeslots-bookings').innerHTML = '';
            document.getElementById('container-events').innerHTML = '';
            break;
        case 1:
            document.getElementById('container-timeslots-edit').innerHTML = "";
            break;
        default:
            break;
    }

    hide_show_layers(show_list);

}

function Update_Max_Booking_For_Timeslot(el)
{
    const id_timeslot_complete = el.getAttribute('data-timeslot-form-id');
    const form = document.getElementById(id_timeslot_complete)
    const id_slot = form.getAttribute('data-timeslot-id')

    const formData = new FormData(form);
    const formObject = Object.fromEntries(formData.entries());

    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data:
        {
            action: 'tfip_update_max_timeslot',
            nonce: TFIP_Ajax_Obj.nonce,
            slot_id: id_slot,
            ...formObject
        },
        success: function (response)
        {
            
            const day_id = document.getElementById('day_info').getAttribute('data-dayid');
            render_edit_timeslots(day_id);
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    })
}



function Update_form_admin_booking()
{
    let ad_message = "Errore nel form di aggiornamento della prenotazione";
    const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/admin-response-model.html';

    const valid = validateFormAdminBooking('add-booking-admin-form')

    if (!valid) {

        jQuery.get(templateUrl, function (templateHtml) {
            const templateCompiled = _.template(templateHtml);
            const renderedTemplate = templateCompiled({
                adminmessage: ad_message,
                alertclass: 'alert-danger'
            });
            document.getElementById('form-error-booking').innerHTML = renderedTemplate;
        });
        
        return;
    }


    const form = document.getElementById('add-booking-admin-form')
    const formData = new FormData(form);

    console.log(formData)

    const selectTimeslot = document.getElementById('time_booking')
    const selectedIndex = selectTimeslot.options['selectedIndex'];
    const selected_slot = selectTimeslot.options[selectedIndex].getAttribute('data-idtimeslot');

    formData.append('id_new_timeslot', selected_slot);
    const formObject = Object.fromEntries(formData.entries());

    formObject.action = 'tfip_admin_update_booking';

    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: formObject,
        dataType: 'json',
        success: function(data) {

            console.log(data)
    
            const dateTimestamp = data.date_booking;
    
            let $class_warning = 'txt';
            const ad_message = data.message;
            $class_warning = (data.resolution == 1) ? 'alert-success' : 'alert-warning';

            jQuery.get(templateUrl, function(templateHtml) {
                const templateCompiled = _.template(templateHtml);
                const renderedTemplate = templateCompiled({
                    adminmessage: ad_message,
                    alertclass: $class_warning
                });
                jQuery('#form-error-booking').html(renderedTemplate);
            });
    
            if (data.resolution == 1) {
                PrintDayBookings(dateTimestamp);
                hide_show_layers(['container-timeslots-bookings']);
            }
    
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });

}



function CallBookingDetails(el) {
    
    const bookingId = el.getAttribute('data-booking-id');

    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tfip_get_single_booking',
            nonce: TFIP_Ajax_Obj.nonce,
            bookingId: bookingId
        },
        success: function (data) {

            console.log('data datta')
            console.log(data);

            const show_list = [
                'booking-form-container_id',
            ]
            
            hide_show_layers(show_list)

            load_form_admin_booking('booking-form-container_id', 1, data);

        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });
}

function LoadExtraTimeslotFormData(idtimeslot)
{
    const urlExtraTimeslot = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/add-booking-extra-form-data.html';

    jQuery.get(urlExtraTimeslot, function(htmlTemplate)
    {
        const extraTimeslotIdInput = _.template(htmlTemplate);
        const renderTempExtraTs = extraTimeslotIdInput({
            idtimeslot: idtimeslot
        })
        
        const extraData = document.getElementById('extra-form-data');
        extraData.innerHTML = renderTempExtraTs; 
    })
}

function FillBookingFormData(booking)
{
    return new Promise((resolve, reject) =>
    {
        try{
            const urlExtraform = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/existing-booking-extra-form-data.html';

            jQuery.get(urlExtraform, function(templateHtml) {

                const extraBookingData = _.template(templateHtml);
                const renderedextraBookingData = extraBookingData({ 
                    idbooking: booking.idbooking,
                    idtimeslot: booking.id_timeslot });

                const extraData = document.getElementById('extra-form-data');
                extraData.innerHTML = renderedextraBookingData; 


                document.getElementById('admin_identification').value = booking.identification;
                document.getElementById('admin_participants').value = booking.participants;
                document.getElementById('admin_phone').value = booking.phone;
                document.getElementById('admin_extra_message').innerText = booking.extra_message;
                document.getElementById('admin_code').value = booking.code;

                document.getElementById("admin_status").value = booking.status == 1 ? 'confirmed' : 'forwarded';
                
                resolve();
            });
        }catch(err)
        {
            reject(err);
        }
    })

}


function PrintDayBookings(timestampdate) {

    const templateUrl = TFIP_Ajax_Obj.templatesUrl + 'internal/main/admin-single-booking.html';
    const templateEventsUrl = TFIP_Ajax_Obj.templatesUrl + 'internal/main/admin-panel-events.html';

    const list_containers = [
        'container-timeslots-bookings',
        'container-events'
    ]
    hide_show_layers(list_containers)

    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tf_ipf_get_day_bookings',
            timestampdate: timestampdate,
        },
        success: function(response) {
            
            console.log(response)

            jQuery.get(templateUrl, function(templateHtml) {

                const singleDayTemplate = _.template(templateHtml);
                const rendered_single_day = singleDayTemplate({ 
                    calendars: response.calendars
                });
                
                const container = document.getElementById('container-timeslots-bookings');
                container.innerHTML = rendered_single_day; 
                container.style.display = 'block';
            });

            jQuery.get(templateEventsUrl, function(templateEvents) {
                const alleventstemplate = _.template(templateEvents);
                const rendered_events_template = alleventstemplate({ 
                    calendars: response.calendars,
                    events: response.events
                });

                const eventContainer = document.getElementById('container-events');
                eventContainer.innerHTML = rendered_events_template;   
                eventContainer.style.display = 'block';  
            });
        },
        error: function(xhr, status, error) {
            console.error(error);
        }
    });
}


function render_edit_timeslots(day_id)
{
    const templateUrl = TFIP_Ajax_Obj.templatesUrl + 'internal/main/edit-timeslots.html';
  
    jQuery.get(templateUrl, function(templateHtml) {
      const compiledTimeslots = _.template(templateHtml);
  
      jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
          action: 'tfip_get_timeslots',
          dayid: day_id,
        },
        success: function(response) {

            const rendered_underscore = compiledTimeslots({
                timeslots: response.timeslots,
                formatted_date: response.formatted_date,
                total_bookings: response.total_bookings,
                max_day_availability: response.max_day_availability == null ? "not defined" : response.max_day_availability,
                day_availability: response.day_availability,
                customers_in: response.customers_in,
                id_day : response.id_day
            });
            
            hide_show_layers(['container-timeslots-edit']);
            document.getElementById('container-timeslots-edit').innerHTML = rendered_underscore;
        },
        error: function(xhr, status, error) {
          console.error('AJAX error:', error);
        }
      });
    });
}


function handleClickEnd(el, start) {
    let diff;
    const clickTime = 200; 
    end = Date.now();
    diff = end - start;

    const date = el.getAttribute('data-date');
    
    const longClickEvent = new CustomEvent('longclick', {
      detail: {
        date: date
      }
    });


    if (diff > clickTime) {
      el.dispatchEvent(longClickEvent);
    } else {
      PrintDayBookings(date);
    }
  }


function ajax_admin_call_calendar(direction = null, date = null) {

    console.log(date);

    var currentDate = document.getElementById('date-val').innerText;
  
    if (date != null) {
      currentDate = date;
    }
  
    const templateUrl = TFIP_Ajax_Obj.templatesUrl + 'internal/main/all-bookings-calendar.html';
  
    jQuery.get(templateUrl, function(templateHtml) {
      const compiledTemplate = _.template(templateHtml);
  
      jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
          action: 'tfip_get_all_bookings_calendar',
          datestart: currentDate,
          direction: direction,
          nonce: TFIP_Ajax_Obj.nonce
        },
        success: function(response) {

            console.log(response)


          const newDate = response.newDate;
          const timestampNew = response.newTimestamp;
  
          const rendered_underscore = compiledTemplate({
            weeks: response.weeks,
          });
  
          document.getElementById('calendar-container').innerHTML = rendered_underscore;
          document.getElementById('date-text-val').innerText = newDate;
          document.getElementById('date-val').innerText = timestampNew;

          document.querySelectorAll('.clickable-day').forEach(function(element) {
            let start;
  
            element.addEventListener('mousedown', () => start = Date.now());
            element.addEventListener('mouseup', () => handleClickEnd(element, start));
            element.addEventListener('touchstart', () => start = Date.now());
            element.addEventListener('touchend', () => handleClickEnd(element, start));
  
            element.addEventListener('longclick', function(e) {
              render_edit_timeslots(e.detail.date);
            });
  
            
          });
        },
        error: function(xhr, status, error) {
          console.error('AJAX error:', error);
        }
      });
    });
  }
  