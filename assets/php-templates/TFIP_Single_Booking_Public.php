<?php
/* Template Name: Booking */

get_header();

if (isset($obj_booking) && $obj_booking) : ?>
    <div id="content" class="site-content container py-5 mt-5 TFIP-style" role="main">
        <div id="primary" class="content-area">
            <div class="card">
                <?php
                $header_class = 'bg-success text-white';
                ?>
                <div class="card-header <?php echo esc_attr($header_class); ?>">
                    <h4 class="mb-0">Booking Details</h4>
                </div>
                
                <div class="card-body">
                    <p class="mb-2"><strong>Identification:</strong> <?php echo esc_html($obj_booking->identification); ?></p>
                    <p class="mb-2"><strong>Participants:</strong> <?php echo esc_html($obj_booking->participants); ?></p>
                    <p class="mb-2"><strong>Time:</strong> <?php echo date('H:i', strtotime($obj_booking->time_booking)); ?></p>
                    <p class="mb-2"><strong>Date:</strong> <?php echo date('d/m/Y', $obj_booking->date_id); ?></p>
                    <p class="mb-2"><strong>Extra Message:</strong> <?php echo esc_html($obj_booking->extra_message); ?></p>
                    <p class="mb-2"><strong>Code:</strong> <?php echo esc_html($obj_booking->code); ?></p>

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
        #whatsapp-confirm-button {
            background-color: #ffc107;
            color: black;
        }
    </style>

    <script>
        document.getElementById('whatsapp-share-button').addEventListener('click', function(event) {
            event.preventDefault();
            var pageUrl = window.location.href;
            var whatsappUrl = 'https://api.whatsapp.com/send?text=' + encodeURIComponent('Check out this booking details: ' + pageUrl);
            window.open(whatsappUrl, '_blank');
        });

    </script>

<?php else: ?>
    <p>No booking found.</p>
<?php endif; ?>

<?php get_footer(); ?>
