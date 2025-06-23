function validateFormAdminBooking(formId) {


    const form = document.getElementById(formId);
    const elements = form;


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

        // check if time is ok
        if (tag === 'select' && name === 'time_booking') {
            if (parseInt(element.value) === 0) {
                return false;
            }
            continue;
        }

        // Skip disabled fields like code
        if (element.disabled) continue;

        if (
            element.hasAttribute('required') &&
            (!element.value || element.value.trim() === '')
        ) {
            return false;
        }
    }

    return true;
}

function serializeForm(formId) {

    const form = document.getElementById(formId);
    const formData = new FormData(form);
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

    if (!valid) {

        let ad_message = "Errore nel form di creazione della prenotazione";
        const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/admin-response-model.html';

        jQuery.get(templateUrl, function (templateHtml) {
            const templateCompiled = _.template(templateHtml);
            const renderedTemplate = templateCompiled({
                adminmessage: ad_message,
                alertclass: 'alert-danger'
            });
            document.getElementById('message-admin').innerHTML = renderedTemplate;
        });
        
        return;
    }

    const dateid = formdata['date_id'];
    
    for (let key in formdata) {
        jsonData[key] = formdata[key];
    }

    if (jsonData.phone && typeof itln !== 'undefined') {
        jsonData.phone = itln.getNumber();
    }

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
        
            // Determine which template to load
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
                    alertclass: 'alert-primary',
                    day_id: response.day_id,
                    timeslot_id: response.timeslot_id
                });
                document.getElementById('message-admin').innerHTML = renderedTemplate;
            
                jQuery(document).ready(function ($) {
                    const proceedButton = $('#proceed-booking-btn');
            
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

                    ajax_admin_call_calendar(-1, dateid);
                });
            });
            
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


function updateTimeslots(date, id) {

    
    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tfip_get_form_timeslots_booking_admin',  
            date: date,
            bookingid: id,
            nonce: TFIP_Ajax_Obj.nonce
        },
        success: function(response) {
            
            console.log('response update')
            console.log(response)

            const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/timeslots-instance-booking-form.html';
            
            jQuery.get(templateUrl, function(templateHtml) {
     
                const timeslotTemplate = _.template(templateHtml);
                const renderedTimeslots = timeslotTemplate({ timeslots: response });
                const timeslotSelect = document.getElementById(id);
                timeslotSelect.innerHTML = '<option value="0">Seleziona orario</option>'; 
                timeslotSelect.innerHTML += renderedTimeslots; 
            });
        },
        error: function(xhr, status, error) {
            console.error('Error fetching timeslots:', error);
        }
    });
}



function load_form_admin_booking() {

    const fpConf = {
        enableTime: false,
        noCalendar: false,
        dateFormat: "d-m-Y"
    };

    const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/main/admin-booking-form-creation.html';

    jQuery.get(templateUrl, function(templateHtml){

        const templateCompiled = _.template(templateHtml);
        const rendered_template = templateCompiled();
        document.getElementById('form-booking-admin').innerHTML = rendered_template;
        
        var phoneInput = document.querySelector("#admin_phone");
        window.intlTelInput(phoneInput, {
            allowDropdown: true,
            initialCountry: "it",
            autoPlaceholder: "polite",
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/25.3.1/build/js/utils.min.js"
        });
        
        updateTimeslots(null, 'admin_time');
        
        const dateInput = document.getElementById('admin_date_id');
        dateInput.flatpickr(fpConf);

        dateInput.addEventListener('change', function(event) {
            const selectedDate = event.target.value; 
            updateTimeslots(selectedDate,'admin_time');
        });

    });
}

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
    
    // console.log("Form Data :" + formData);

    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tfip_create_timeslot',
            nonce: TFIP_Ajax_Obj.nonce,
            ...formObject
        },
        success: function(response) {
            
            // console.log(response)
            let warning_slot = document.getElementById('timeslot_errors');

            if(warning_slot.classList.contains("alert-warning") && response.resolution == 1)
            {
                warning_slot.classList.remove('alert-warning');
                warning_slot.classList.add('alert-success');

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
            defaultHour: 17,
            // minTime: "17:00",
            // maxTime: "23:59",
            minuteIncrement: 1
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
            // console.log(response);
            
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

function findBookingById(bookingsArray, id) {
    return bookingsArray.find(booking => booking.idbooking === id);
}


function Switch_Back_To_Calendar(id_call, id_day)
{
    ajax_admin_call_calendar(-1, id_day);

    switch (id_call) {
        case 0:
            document.getElementById('admin-calendario').style.display = 'inherit';
            document.getElementById('booking-form-caller').style.display = 'block';

            document.getElementById('container-timeslots-bookings').innerHTML = '';
            document.getElementById('container-side').innerHTML = '';
            document.getElementById('container-events').innerHTML = '';
            document.getElementById('container-booking').style.display = 'none';
            
            break;
        case 1:
            document.getElementById('admin-calendario').style.display = 'block';
            document.getElementById('booking-form-caller').style.display = 'block';
            document.getElementById('container-timeslots-edit').innerHTML = "";
            document.getElementById('container-timeslots-edit').style.display = 'none';
            break;
        default:
            break;
    }
    
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

    const valid = validateFormAdminBooking('update-booking-form')

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


    const form = document.getElementById('update-booking-form')
    const formData = new FormData(form);

    

    const selectTimeslot = document.getElementById('admin_time_update')
    const selectedIndex = selectTimeslot.options['selectedIndex'];
    const selected_slot = selectTimeslot.options[selectedIndex].getAttribute('data-idtimeslot');

    formData.append('id_new_timeslot', selected_slot);
    const formObject = Object.fromEntries(formData.entries());

    formObject.action = 'tfip_admin_update_booking';

    fetch(TFIP_Ajax_Obj.ajaxUrl, {
        method: 'POST',
        body: new URLSearchParams(formObject),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {


        const dateTimestamp = data.date_booking;
        let $class_warning = 'txt';
        ad_message =  data.message;
        data.resolution == 1 ? $class_warning = 'alert-success' : $class_warning = 'alert-warning';

        jQuery.get(templateUrl, function (templateHtml) {
            const templateCompiled = _.template(templateHtml);
            const renderedTemplate = templateCompiled({
                adminmessage: ad_message,
                alertclass: $class_warning
            });
            document.getElementById('form-error-booking').innerHTML = renderedTemplate;
        });

        PrintDayBookings(dateTimestamp)
        
        return;


    })
    .catch(error => {
        console.error('AJAX error:', error);
    });
}



function CallBookingDetails(el) {
    
    const bookingId = el.getAttribute('data-booking-id');
    const slotId = el.getAttribute('data-slot-id');

    jQuery.ajax({
        url: TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        data: {
            action: 'tfip_get_single_booking',
            nonce: TFIP_Ajax_Obj.nonce,
            bookingId: bookingId
        },
        success: function (data) {
            

            console.log('response success')
            console.log(data)


            const templateUrlbooking = TFIP_Ajax_Obj.templatesUrl + 'internal/partial/single-booking-view-edit.html';

            const dateObj = new Date(parseInt(data.timeslot.id_date) * 1000);
            const day = String(dateObj.getDate()).padStart(2, '0');
            const month = String(dateObj.getMonth() + 1).padStart(2, '0'); // Months are 0-based
            const year = dateObj.getFullYear();
            const dateStr = `${day}-${month}-${year}`; // Format: d-m-Y

            jQuery.get(templateUrlbooking, function(templateHtml) {
                const single_booking = _.template(templateHtml);

                const rendered_single = single_booking({
                    booking: {
                        ...data.booking,
                        date_booking: dateStr
                    },
                    postevent: data.postevent,
                    timeslotid: data.timeslot.id
                });

                const booking_panel = document.getElementById('container-side');
                booking_panel.innerHTML = rendered_single;
                booking_panel.style.display = "block";

                const phoneInput = document.querySelector("#admin_phone");
                window.intlTelInput(phoneInput, {
                    allowDropdown: true,
                    initialCountry: "it",
                    autoPlaceholder: "polite",
                    separateDialCode: true,
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/25.3.1/build/js/utils.min.js"
                });

                flatpickr("#admin_date_update", {
                    enableTime: false,
                    dateFormat: "d-m-Y"
                });

                //updateTimeslots(dateStr, 'admin_time_update');

                const dateInput = document.getElementById('admin_date_update');

                console.log('admin date update')
                console.log(dateInput)

                if (dateInput) {
                    dateInput.addEventListener('change', function(event) {
                        const selectedDate = event.target.value;
                        updateTimeslots(selectedDate, 'admin_time_update');
                    });
                }
            });


            const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/internal/partial/timeslots-instance-booking-form.html';
            
            jQuery.get(templateUrl, function(templateHtml) {
     
                const timeslotTemplate = _.template(templateHtml);
                const renderedTimeslots = timeslotTemplate({ timeslots: data.alltimeslots });
                const timeslotSelect = document.getElementById('admin_time_update');
                timeslotSelect.innerHTML = '<option value="0">Seleziona orario</option>'; 
                timeslotSelect.innerHTML += renderedTimeslots; 
            });

        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });
}



function PrintDayBookings(timestampdate) {



    const templateUrl = TFIP_Ajax_Obj.templatesUrl + 'internal/main/admin-single-booking.html';
    const templateEventsUrl = TFIP_Ajax_Obj.templatesUrl + 'internal/main/admin-panel-events.html';

    document.getElementById('admin-calendario').style.display = 'none';
    document.getElementById('container-booking').style.display = 'inherit';

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

            document.getElementById('admin-calendario').style.display = 'none';
            document.getElementById('container-timeslots-edit').innerHTML = rendered_underscore;
            document.getElementById('container-timeslots-edit').style.display = 'inherit';



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


    document.getElementById('row-admin-prenotazione').style.display = 'none';

    if (diff > clickTime) {
      el.dispatchEvent(longClickEvent);
    } else {
      PrintDayBookings(date);
    }
  }

function ajax_admin_call_calendar(direction = null, date = null) {
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
  


// function ajax_call_calendar(_maxnum)
// {
//     jQuery.ajax({
//         url : ajaxUrl,
//         method: 'POST',
//         data: {
//             action: 'get_calendar_html',
//             maxnum: _maxnum
//         },
//         success: function(response) {

//             jQuery('#container-list-events').html(response);

//         },
//         error: function(xhr, status, error) {
//             console.error(error);
//         }
//     });
// }


// function BookNoEvent()
// {
//     var date = document.getElementById('client_date').value;
//     var time = document.getElementById('client_time').value;

//     jQuery.ajax({
//         url : ajaxUrl,
//         method: 'POST',
//         dataType: 'json',
//         data: {
//             action: 'tfip_getBookingData',
//             bookingdate: date,
//             bookingtime: time
//         },
//         success: function(response) {


//             var succeded = response.succeded;
//             var htmlToPrint = response.htmlToPrint;

//             if(succeded == 1)
//             {
//                 jQuery('#container-booking').html(htmlToPrint);
//             }else
//             {
//                 jQuery('#error-booking-noevent').html(htmlToPrint);
//             }


//         },
//         error: function(xhr, status, error) {
//             console.error(error);
//         }
//     });
// }


// function RetrieveBooking(el)
// {
//     var _bookingid = el.getAttribute('data-booking-id');

//     document.getElementById('single-booking').innerHTML = "";

//     jQuery.ajax({
//         url : ajaxUrl,
//         method: 'POST',
//         dataType: 'json',
//         data: {
//             action: 'tfipf_return_edit_booking_form_ajax',
//             bookingid: _bookingid,
//         },
//         success: function(response) {


//             var succeded = response.succeded;
//             var htmlToPrint = response.htmlToPrint;
//             var date_id = response.date_id;

//             if(succeded == 1)
//             {
//                 jQuery('#single-booking').html(htmlToPrint);
//             }else
//             {
//                 jQuery('#single-booking').html(htmlToPrint);
//             }


//             jQuery('#date_id').datepicker({
//                 autoclose: true,
//                 format: "dd/mm/yy",
//                 beforeShowDay: function(date) {

//                     var nowDate = new Date(date_id + 'T00:00:00');

//                     if (date.getTime() === nowDate.getTime()) {
//                         return [true, 'highlight-date', 'day'];
//                     } else {
//                         return [true, '', ''];
//                     }
//                 }
//             }).datepicker('update', new Date(date_id + 'T00:00:00' ));

//         },
//         error: function(xhr, status, error) {
//             console.error(error);
//         }
//     });
// }


// function save_admin_booking()
// {
//     var formdata = serializeForm('add-booking-admin-form');
//     var jsonData = {};

//     document.getElementById('message-admin').innerHTML = '';
//     var valid = validateFormAdminBooking('add-booking-admin-form');

//     if(valid)
//     {
//         formdata.forEach(element => {

            
//             if(element.name.toLowerCase() == "phone")
//             {
            
//                 const phoneNumber = itln.getNumber();
//                 jsonData[element.name] = phoneNumber;
                
//             }else
//             {
//                 jsonData[element.name] = element.value;
//             }
            
            
//         });
    
    
//         jQuery.ajax({
//             url: ajaxUrl,
//             method: 'POST',
//             dataType: 'json',
//             data: {
//                 action: 'tfipf_admin_create_booking',
//                 formdata: JSON.stringify(jsonData) 
//             },
//             success: function(response) {
    
    
//                 var succeded = response.succeded;
//                 var ddate = response.date_id;
    
    
//                 if(succeded == 1)
//                 {
//                     document.getElementById('message-admin').innerHTML = '<div class="alert alert-success" role="alert"> \
//                         La prenotazione e\' stata salvata! \
//                     </div>';

//                     CleanFormAdminBooking('add-booking-admin-form');
//                     ajax_admin_call_calendar(3, ddate);
//                     PrintDayBookings(ddate);

//                 }else
//                 {
//                     document.getElementById('message-admin').innerHTML= '<div class="alert alert-danger" role="alert"> \
//                         La prenotazione non e\' stata salvata: ' + response.message  + ' \
//                     </div>';
//                 }
                
                
    
//             },
//             error: function(xhr, status, error) {
//                 console.error(error);
    
//             }
//         });
//     }else
//     {
//         document.getElementById('message-admin').innerHTML= '<div class="alert alert-danger" role="alert"> \
//                         Errore nel form di creazione della prenotazione \
//                     </div>';
//     }

    
// }






// function CleanFormAdminBooking(formId) {

//     var form = document.getElementById(formId);
//     var elements = form.elements;

//     for (var i = 0; i < elements.length; i++) {
//         var element = elements[i];

//         if(element.classList.contains('iti__search-input') || element.name.toLowerCase() == "code" || element.tagName.toLowerCase() == "button")
//         {
//             continue;
//         }else
//         {
//             switch (element.tagName.toLowerCase()) {
//                 case 'select':
//                 {
//                     element.value = 0
//                 }
//                 break;
//                 default:
//                 {
//                     element.value = "";
//                 }
//                break;
//             }
//         }

//     }
// }


// function serializeForm(formId) {
    
//     var form = document.getElementById(formId);
//     var formData = [];
//     var elements = form.elements;

//     for (var i = 0; i < elements.length; i++) {
//         var element = elements[i];
//         if (element.tagName.toLowerCase() !== 'button' && element.name) {
//             if (element.type === 'checkbox' || element.type === 'radio') {
//                 if (element.checked) {
//                     formData.push({ name: element.name, value: element.value });
//                 }
//             } else if (element.type === 'select-multiple') {
//                 for (var j = 0; j < element.options.length; j++) {
//                     if (element.options[j].selected) {
//                         formData.push({ name: element.name, value: element.options[j].value });
//                     }
//                 }
//             } else {
//                 formData.push({ name: element.name, value: element.value });
//             }
//         }
//     }

//     return formData;
// }


// function save_edit_form_data (){
//     document.getElementById('edit_alert_booking').innerHTML = "";
//     var formData = serializeForm('editBookingForm');
//     var res = validateFormAdminBooking('editBookingForm')
   
//     if(res)
//     {
//         var jsonData = {};

//         formData.forEach(element => {
//             if(element.name.toLowerCase() == "phone")
//             {
            
//                 const phoneNumber = iti.getNumber();
//                 jsonData[element.name] = phoneNumber;
                
//             }else
//             {
//                 jsonData[element.name] = element.value;
//             }
//         });


//         jQuery.ajax({
//             url: ajaxUrl,
//             method: 'POST',
//             dataType: 'json',
//             data: {
//                 action: 'ifpsave_edit_booking', // WordPress action hook
//                 formData: JSON.stringify(jsonData) // Convert JSON object to string
//             },
//             success: function(response) {

//                 var succeded = response.succeded;
//                 var htmlToPrint = response.htmlToPrint;
//                 var newdate = response.newdate;

//                 jQuery('#single-booking').html(htmlToPrint);
//                 PrintDayBookings(newdate)

//             },
//             error: function(xhr, status, error) {
//                 console.error(error);

//             }
//         });
//     }else
//     {
//         document.getElementById('edit_alert_booking').innerHTML = '<div class="alert alert-danger" role="alert"> \
//                         Errore nel form di modifica della prenotazione \
//                     </div>';
//     }

    
// };






// function EditMaxCapacity(el)
// {
//     var timestamp = el.getAttribute('data-id');
//     var maxp = el.getAttribute('data-max');

//     var reset = document.getElementById('reset_' + timestamp);
//     reset.classList.remove('ciao');
//     reset.classList.add('ciao');

//     var edit = document.getElementById('edit_' + timestamp);
//     edit.classList.remove('ciao');
//     edit.classList.add('ciao');

//     edit.innerHTML = 
//     '<div class="row">\
//         <div class="col-8">\
//             <input value="' + maxp + '" id="input_timestamp_edit" class="form-control" />\
//         </div>\
//         <div class="col-4 text-right">\
//             <i onclick="IpdEditMaxCapacity(this)" data-id="' + timestamp + '" class="fa fa-check text-success"></i>\
//         </div>\
//     </div>';


// }

// function Deletebooking(elD)
// {
//     jQuery('#confirmDeleteModal').modal('show');
//     var idbooking = elD.getAttribute('data-booking-id');
//     var ddate = elD.getAttribute('data-ddate');
    
//     jQuery('#confirmDeleteBtn').attr('data-bk', idbooking);
//     jQuery('#confirmDeleteBtn').attr('data-ddate', ddate);
// }

// function CloseModal()
// {
//     jQuery('#confirmDeleteModal').modal('hide');
// }

// function ConfirmDeleteBooking(el)
// {
//     var idbooking = el.getAttribute('data-bk');
//     var ddate = el.getAttribute('data-ddate');


//     jQuery.ajax({
//         url: ajaxUrl,
//         method: 'POST',
//         data: {
//             action: 'tfipf_delete_booking',
//             booking_id: idbooking
//         },
//         success: function(response) {
            
//             jQuery('#confirmDeleteModal').modal('hide');

//             ajax_admin_call_calendar(3, ddate);
//             PrintDayBookings(ddate)



//         },
//         error: function(xhr, status, error) {
//             console.error(error);
//         }
//     });
// }


// function BookConf(elN, elD){

  
//     jQuery.ajax({
//         url: ajaxUrl,
//         method: 'POST',
//         //dataType: 'html',
//         data: {
//             action: 'tfipf_conf_book',
//             confirm: elN,
//             date_d: elD
//         },
//         success: function(response) {

//           ajax_admin_call_calendar(3, elD);
//           PrintDayBookings(elD);

//         },
//         error: function(xhr, status, error) {
//             console.error(error);
//         }
//     });
    
// };


// function IpdEditMaxCapacity (el){

//     var timestamp = el.getAttribute('data-id');
//     var capacity = document.getElementById('input_timestamp_edit').value;

//     if(parseInt(capacity) && parseInt(capacity) > 0)
//     {
//         jQuery.ajax({
//             url: ajaxUrl,
//             method: 'POST',
//             dataType: 'html',
//             data: {
//                 action: 'ipf_editMaxCapacity',
//                 daydate: timestamp,
//                 capacity: capacity
//             },
//             success: function(response) {

//                 jQuery('#edit_' + timestamp).html(response);

//                 var reset = jQuery('#reset_' + timestamp);
//                 reset.removeClass('ciao').addClass('ciao');

//                 var edit = jQuery('#edit_' + timestamp);
//                 edit.removeClass('ciao').addClass('ciao');


//                 ajax_admin_call_calendar(3);

//             },
//             error: function(xhr, status, error) {
//                 console.error(error);

//             }
//         });
//     }

// };
