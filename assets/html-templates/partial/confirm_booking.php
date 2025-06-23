<?php
// Ensure this script is only accessed through WordPress
if (!defined('ABSPATH')) {
    exit;
}

$html = '<a name="prenota"><div class="prenota-online">
            <form id="confirm_booking_form" action="#prenota-online" method="POST">
                <input id="idbooking" style="display:none;" name="idbooking" value="' . esc_attr($resulted_booking) . '" />
                <div class="form-group form-prenotazione">
                    <label for="code"><div class="alert alert-info info-whatsapp" role="alert">Inserisci il <b>codice di conferma</b> che arriverà entro 2 minuti <b>via Whatsapp</b> al numero di telefono che hai fornito: <i class="fa-solid fa-arrow-down rimbalza"></i></div></label>
                    <input type="text" class="form-control codice-whatsapp" id="code" name="code" placeholder="000000" maxlength="6" pattern="[A-Za-z0-9]{6}" title="Please enter a 6-character alphanumeric code" required>
                </div>

                <button type="submit" class="btn btn-success invia-prenotazione" formaction="#prenota-online">Conferma prenotazione</button>

            </form></div>
        <script>
        jQuery(document).ready(function($) {
            document.getElementById("confirm_booking_form").addEventListener("submit", function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                const jsonData = {};
                formData.forEach(function(value, key) {
                    jsonData[key] = value;
                });
                BookingConfirm(jsonData);
            });
            function BookingConfirm(codeconfirm) {
                $.ajax({
                    url: ajaxurl,
                    method: \'POST\',
                    data: {
                        action: \'tf_ipf_confirm_booking\',
                        data_data: codeconfirm
                    },
                    success: function(response) {
                        $("#regForm").html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        alert(\'booking not confirmed\');
                    }
                });
            }
        });
        </script>';
echo $html;
?>
