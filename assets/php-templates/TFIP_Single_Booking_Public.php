<?php
/* Template Name: Booking */

get_header();

$booking_id = get_query_var('booking_code');

if ($booking_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ipf_bookings'; 
    
    $booking_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s",
            $booking_id
        )
    );

    if ($booking_data) {
        ?>
        <div id="content" class="site-content container py-5 mt-5" role="main">
            <div id="primary" class="content-area">
                <div class="card">
                    <?php
                    $header_class = 'bg-primary text-white';
                    if ($booking_data->status == 'forwarded') {
                        $header_class = 'bg-warning text-dark';
                    } elseif ($booking_data->status == 'confirmed') {
                        $header_class = 'bg-success text-white';
                    }
                    ?>
                    <div class="card-header <?php echo $header_class; ?>">
                        <h4 class="mb-0">Booking Details</h4>
                    </div>
                    
                    <div class="card-body">
                        <p class="mb-2"><strong>Identification:</strong> <?php echo esc_html($booking_data->identification); ?></p>
                        <p class="mb-2"><strong>Participants:</strong> <?php echo esc_html($booking_data->participants); ?></p>
                        <p class="mb-2"><strong>Time:</strong> <?php echo date('H:i', strtotime($booking_data->time_booking)); ?></p>
                        <p class="mb-2"><strong>Date:</strong> <?php echo date('d/m/Y', $booking_data->date_id); ?></p>
                        <p class="mb-2"><strong>Extra Message:</strong> <?php echo esc_html($booking_data->extra_message); ?></p>
                        <p class="mb-2"><strong>Code:</strong> <?php echo esc_html($booking_data->code); ?></p>
                        <p class="mb-2"><strong>Status:</strong> <?php echo esc_html($booking_data->status); ?></p>
                        <?php if ($booking_data->status == 'forwarded') { ?>
                            <a 
                                href="#" 
                                id="whatsapp-confirm-button" 
                                class="btn whatsapp-button mt-3"
                                target="_blank">
                                <i class="fab fa-whatsapp"></i> Conferma
                            </a>
                        <?php } ?>
						<a 
                            href="#" 
                            id="whatsapp-share-button" 
                            class="btn whatsapp-button mt-3"
                            target="_blank">
                            <i class="fab fa-whatsapp"></i> Condividi
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .whatsapp-button {
                background-color: #25D366;
                color: white;
            }
			#whatsapp-confirm-button{
				background-color:#ffc107;
				color:black;
			}
        </style>
        <script>
            document.getElementById('whatsapp-share-button').addEventListener('click', function(event) {
                event.preventDefault();
                var pageUrl = window.location.href;
                var whatsappUrl = 'https://api.whatsapp.com/send?text=' + encodeURIComponent('Check out this booking details: ' + pageUrl);
                window.open(whatsappUrl, '_blank');
            });
			
			 document.getElementById('whatsapp-confirm-button').addEventListener('click', function(event) {
                event.preventDefault();
                var message = "<?php echo esc_html($booking_data->code); ?> + perfavore conferma la mia prenotazione";
                var whatsappUrl = 'https://api.whatsapp.com/send?phone=+393514333117&text=' + encodeURIComponent(message);
                window.open(whatsappUrl, '_blank');
            });
        </script>
        <?php
    } else {
        echo '<p>No booking found for this ID.</p>';
    }
} else {
    echo '<p>No booking ID provided.</p>';
}

get_footer();
?>
