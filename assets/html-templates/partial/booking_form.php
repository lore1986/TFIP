<?php

if (!defined('ABSPATH')) {
    exit;
}

$html = '';

if (isset($_POST['bookingdate']) && isset($_POST['bookingtime']) && !empty($_POST['bookingtime']) && !empty($_POST['bookingdate'])) {
    $time = date('H:i', strtotime(sanitize_text_field(esc_attr($_POST['bookingtime']))));
    $sandate = sanitize_text_field(esc_attr($_POST['bookingdate']));
    $sandate = DateTime::createFromFormat('d/m/y', $sandate);
    $date = strtotime($sandate->format('Y-m-d'));
    $format_date = date('Y-m-d', $date);
    $object_date = strtotime($format_date . " " . $time);

    if ($date == false) {
        $response = array(
            'succeded' => 1,
            'htmlToPrint' => $this->_ipfDatabase->PrintErrorMessage(" date is not valid")
        );
        $encoded_answer = json_encode($response);
        header('Content-Type: application/json');
        echo $encoded_answer;
        exit();
    }

    $html .= '<div class="row">
                <div class="col-12 mini-riepilogo">
                    <div class="alert alert-success" role="alert">
                        <div class="data">
                            <i class="fa-solid fa-calendar-days"></i> ' . date('d-m-Y ', strtotime($format_date)) . ' <i class="fa-solid fa-clock"></i> ' . $time . '</i>
                        </div>
                    </div>
                </div>
                <a name="prenota"></a>
                <div class="col-12">
                    <form id="regForm" action="#prenota-online">
                        <input style="display:none;" name="bookingdate" value="' . $object_date . '">
                        <div class="tab form-row">
                            <div class="form-group col-12">
                                <label for="nomecompleto">Nome completo</label>
                                <input type="text" id="uname" name="uname"  class="form-control" placeholder="Nome completo" >
                            </div>
                            <div class="form-group ">
                                <label for="exampleFormControlSelect1">Persone</label>
                                <input type="number" min="1" max="20" class="form-control"  id="uguest" name="uguest" placeholder="Numero di partecipanti">
                            </div>
                        </div>
                        <div class="tab">
                            <div class="form-group">
                                <label for="numerotelefono">N. di Telefono</label><br>
                                <input type="text" class="form-control" placeholder="Telefono"  id="uphone" name="uphone"  ><br>
                                <small id="telefono" class="form-text text-muted">Riceverai un messaggio di conferma per la tua prenotazione.</small>
                            </div>
                            <div class="form-group">
                                <label >Richieste particolari</label>
                                <textarea id="uspecial" name="uspecial" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition" name="condition" onclick="SetValueCheckBox(this)" value="0" >
                                <label class="form-check-label" for="condition">
                                Accetto le <a>condizioni di utilizzo</a> e ho letto l\'<a>informativa privacy</a>.
                                </label>
                            </div>
                        </div>
                    <div style="overflow:auto;">
                        <button type="button" id="prevBtn" onclick="nextPrev(-1)" class="btn btn-outline-primary indietro">Indietro</button>
                        <button type="button" id="nextBtn" onclick="nextPrev(1)" class="btn btn-success">Avanti</button>
                    </div>
                    <div style="text-align:center;margin-top:40px;">
                        <span class="step"></span>
                        <span class="step"></span>
                        <span class="step"></span>
                    </div>
                    </form>

                    <script>

                        var currentTab = 0;
                        showTab(currentTab);

                        var input = document.querySelector("#uphone");

                        var iti = window.intlTelInput(input, {
                            allowDropdown: true,
                            initialCountry: "it",
                            autoPlaceholder: "polite",
                            separateDialCode: true,
                            utilsScript: "https://raw.githack.com/jackocnr/intl-tel-input/master/build/js/utils.js"

                        });


                    </script>';

    $response = array(
        'succeded' => 1,
        'htmlToPrint' => $html
    );

    $encoded_answer = json_encode($response);
    header('Content-Type: application/json');
    echo $encoded_answer;
    exit();
}