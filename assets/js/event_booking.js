
function LoadBaseClientBookingForm(eventid, timeslotid, dayid, timeslottime = null)
{
    const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/partial/base_booking_form.html';
            
        jQuery.get(templateUrl, function(templateHtml) {

            const formTemplate = _.template(templateHtml);
            const renderedFormTemplate = formTemplate({ 
                eventid: eventid,
                timeslotid: timeslotid,
                dayid: dayid,
                timeslottime: timeslottime
            });

            const formSpace = document.getElementById('tmp-loaded-form');
            formSpace.innerHTML = renderedFormTemplate; 

            const phoneInput = document.querySelector("#idphone");

            window.iti = window.intlTelInput(phoneInput, {
                initialCountry: "it",  
                preferredCountries: ["it", "us", "gb"],
                separateDialCode: true,
                loadUtils: () => import("https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.0/build/js/utils.js"),
            });
        });
}

function submitBooking() {
    
    const identification   = document.getElementById("identification").value.trim();
    const guests  = document.getElementById("guests").value.trim();
    const idphone = document.getElementById("idphone");
    const extramessage = document.getElementById("extramessage").value; //check this one
    const condition = document.getElementById("condition").checked;

   
    const eventid = document.getElementById("eventid").value;
    const timeslotid = document.getElementById("timeslotid").value;
    const dayid = document.getElementById("dayid").value;
    const timeslottime =  document.getElementById("timeslottime").value;

    const iti = window.iti;

    let valid = true;
    let messages = [];
    const errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];


    if (!identification) {
        valid = false;
        messages.push("Nome completo è obbligatorio.");
    }
    if (!guests || isNaN(guests) || parseInt(guests) < 1) {
        valid = false;
        messages.push("Inserisci un numero di partecipanti valido (1-20).");
    }


    if (!idphone.value.trim() || !iti.isValidNumber()) {
        valid = false;
        const errorCode = iti.getValidationError();
        const msg = errorMap[errorCode] || "Invalid number";
        messages.push(msg);
    }
    if (!condition) {
        valid = false;
        messages.push("Devi accettare le condizioni di utilizzo.");
    }

    if (!valid) {

        DisplayErrorMessage('client-form-advise', messages.join("\n"))
        return false;
    }

    const phoneNumber = iti.getNumber();


    let dataForm = {

        timeslotid: timeslotid,
        eventid: eventid,
        dayid: dayid,
        timeslottime: timeslottime,

        extramessage: extramessage,
        identification: identification,
        guests: guests,
        idphone: phoneNumber,
        condition: condition ? 1 : 0,
    };

    //console.log(dataForm)

    jQuery.ajax({
        url : TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'tfip_confirmBooking',
            'data_form': dataForm  
        },
        success: function(response) {

            //console.log(response);

            if(response.resolution == 1)
            {
                const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/partial/confirm_booking.html';

                jQuery.get(templateUrl, function (templateHtml) {
                    const templateCompiled = _.template(templateHtml);
                    const renderedTemplate = templateCompiled(
                        {
                            message: response.message,
                            resolution: response.resolution,
                            booking: response.obj
                        }
                    );
                    document.getElementById('form-space').innerHTML = renderedTemplate;
                });


            }else
            {
                DisplayErrorMessage('client-form-advise', response.message)
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            alert("Errore durante l'invio del form. Riprova più tardi.");
        }
    });
}

function ConfirmCodeBooking()
{
    const identification = document.getElementById("identification").value.trim();
    const participants = document.getElementById("participants").value.trim();  
    const timeslotid = document.getElementById("timeslotid").value.trim();
    const dayid = document.getElementById("dayid").value.trim();        
    const phone = document.getElementById("phone").value.trim();       
    const extra = document.getElementById("extra").value.trim();       
    const eventid = document.getElementById("eventid").value.trim();   
    const bookingcode = document.getElementById("codeb").value.trim();   
    const timebooking = document.getElementById("timebook").value.trim(); 
    
    let dataForm = {
        identification: identification,
        participants: participants,
        timeslotid: timeslotid,
        dayid: dayid,
        phone: phone,
        extra: extra,
        eventid: eventid,
        bcode: bookingcode,
        timebook: timebooking
    };

    console.log(dataForm);
    
    jQuery.ajax({
        url : TFIP_Ajax_Obj.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'tfip_confirmBookingClient',
            'data_form': dataForm  
        },
        success: function(response) {

            if(response.resolution == 1)
            {
                const templateUrl = TFIP_Ajax_Obj.templatesUrl + '/partial/booking_success.html';

                jQuery.get(templateUrl, function (templateHtml) {
                    const templateCompiled = _.template(templateHtml);
                    const renderedTemplate = templateCompiled();
                    document.getElementById('final-form-booking').innerHTML = renderedTemplate;
                });

            }else
            {
                DisplayErrorMessage('client-form-advise-final', response.message)
            }
            
            
        },
        error: function(xhr, status, error) {
            document.getElementById('confirm_booking_form').innerHTML = "Errore durante l'invio del form. Riprova più tardi."
        }
    });

}