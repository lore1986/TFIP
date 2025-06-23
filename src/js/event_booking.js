
function SetValueCheckBox(el)
{
    if(el.value == 1)
    {
        document.getElementById('condition').value = 0;
        el.checked = false;
    }else
    {
        el.checked = true;
        document.getElementById('condition').value = 1;
    }



}

function showTab(n) {

    var alltabs = document.getElementsByClassName("tab");

    alltabs[n].style.display = "block";
    if (n == 0) {
        document.getElementById("prevBtn").style.display = "none";
    } else {
        document.getElementById("prevBtn").style.display = "inline";
    }
    if (n == (alltabs.length - 1)) {
        document.getElementById("nextBtn").innerHTML = "Conferma prenotazione";
    } else {
        document.getElementById("nextBtn").innerHTML = "Avanti";
    }

    fixStepIndicator(n)
}

function fixStepIndicator(n) {

    var i, allsteps = document.getElementsByClassName("step");

    for (i = 0; i < allsteps.length; i++) {
        allsteps[i].className = allsteps[i].className.replace(" active", "");
    }

    allsteps[n].className += " active";
}

function nextPrev(n) {

    var alltabs = document.getElementsByClassName("tab");

    if (n == 1 && !validateForm()) return false;

    alltabs[currentTab].style.display = "none";
    currentTab = currentTab + n;


    if (currentTab >= alltabs.length) {

  
        var form = document.getElementById("regForm");
        const formData = new FormData(form);

        const jsonData = {};
        formData.forEach(function(value, key) {
            if(key == "uphone")
            {


                //value = phoneNumber;
                const countryData = iti.getSelectedCountryData();
                jsonData["countrycode"] = countryData.iso2;
                jsonData["uphone"] = value;
                jsonData["dialCode"] = countryData.dialCode;
            }
            jsonData[key] = value;
        });

        BookingEventForm(jsonData);
        return false;
    }

    showTab(currentTab);
}

function validateForm() {

    var x, y, i, valid = true;
    x = document.getElementsByClassName("tab");
    y = x[currentTab].getElementsByTagName("input");

    for (i = 0; i < y.length; i++) {

        if (y[i].value == "" && y[i].name != "newsletter" && !y[i].classList.contains('iti__search-input')) {
            y[i].className += " invalid";
            valid = false;
        }

        if(y[i].name == "uguest")
        {
            var maxParticipants = y[i].getAttribute('data-max');
            maxParticipants = parseInt(maxParticipants);

            if(parseInt(y[i].value) > maxParticipants)
            {
                y[i].className += " invalid";
                valid = false;
            }
        }
    }

    if (valid) {
    document.getElementsByClassName("step")[currentTab].className += " finish";
    }

    return valid;
}




// function BookingEventForm(dform)
// {

//     jQuery.ajax({
//         url : ajaxurl,
//         method: 'POST',
//         dataType: 'json',
//         data: {
//             action: 'tfip_confirmBooking',
//             data_form: dform
//         },
//         success: function(response) {
            

//             var succeded = response.succeded;
//             var htmlToPrint = response.htmlToPrint;
//             var code = response.code;
            
//             if(succeded == 1)
//             {
//                 jQuery("#regForm").html(htmlToPrint);
//                 const element = document.getElementById("regForm");
      
//                 element.scrollIntoView();
//             }else
//             {
//                 //this is temp
//                 jQuery("#regForm").html(htmlToPrint);
//             }
//         },
//         error: function(xhr, status, error) {
//             console.error(error);
//         }
//     });
// };

    

    
