<?php 

class TFIP_Pages{

    public function TFIP_Pages_Create_Page_Booking() {
        
        $booking_admin_page = 'Prenotazioni';

        $args = array(
            'post_type' => 'page',
            'pagename' => $booking_admin_page
        );

        $content = '';
        $query = new WP_Query($args);

        $idpage = 0;

        if (!$query->have_posts()) {
            $pgg = array(
                'post_title'   => $booking_admin_page,
                'post_content' => $content,
                'post_status'  => 'private',
                'post_type'    => 'page',
            );

            $idpage = wp_insert_post($pgg);

        }

        return $idpage;
    }
}