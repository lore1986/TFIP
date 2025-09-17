<?php

    /* Template Name: Calendario Eventi */
    //;
    wp_head();


    ?>

<?php 
	
    get_header();
    $post_id = get_the_ID();


    $post_content = get_post_field('post_content', $post_id);
	$post_meta = get_post_meta($post_id);   
    $event_extended_date = $post_meta['_TFIP_event_date'][0];

    $team_one_img = null;
    $team_two_img = null;
    
    $event_timestamp_date = DateTime::createFromFormat('d-m-Y', $post_meta['_TFIP_event_date'][0]);
    $event_timestamp_date->setTime(0, 0, 0); 
    $event_timestamp = $event_timestamp_date->getTimestamp();

    if($post_meta['_TFIP_event_type'][0] == "sport")
    {
        $path_url = plugin_dir_url(__DIR__) . 'squadre/';
        $path_file = plugin_dir_path(__DIR__) . 'squadre/';

        $team_one_filename = TFIP_Utils::TFIP_Utils_Normalize_Team_Name($post_meta['_TFIP_event_TeamOne'][0]);
        $team_two_filename = TFIP_Utils::TFIP_Utils_Normalize_Team_Name($post_meta['_TFIP_event_TeamTwo'][0]);

        $team_one_img = $path_url . $team_one_filename;
        $team_two_img = $path_url . $team_two_filename;

        $team_one_img_file = $path_file . $team_one_filename;
        $team_two_img_file = $path_file . $team_two_filename;


        if (!file_exists($team_one_img_file)) {
            $team_one_img = $path_url . 'Neutral.png';
        }

        if (!file_exists($team_two_img_file)) {
            $team_two_img = $path_url . 'Neutral.png';
        }
    }
    
?>


<div id="primary">
    <div id="content" class="site-content TFIP-style <?php echo esc_attr($post_meta['_TFIP_event_type'][0]); ?>" role="main">
    <div class="dett-ev-cont container"
        <?php if ($post_meta['_TFIP_event_type'][0] != "sport") : ?>
            style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'full')); ?>'); 
                    background-size: cover; 
                    background-position: center; 
                    background-repeat: no-repeat;"
        <?php endif; ?>
    >
            <div class="dett-ev row">
                <div class="col-12">
                    <?php if ($post_meta['_TFIP_event_type'][0] != "sport") : ?>
                        <div class="dett-music row text-center">
                            <div class="col-12 col-md-2 immagine-evento">
                                <?php
                                $featured_image_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
                                if ($featured_image_url) {
                                    echo '<img src="' . esc_url($featured_image_url) . '" alt="Featured Image">';
                                }
                                ?>
                            </div>
                            <div class="col-12 col-md-8">
                                <h1><?php echo esc_html(get_the_title($post_id)); ?></h1>
                                <h2><?php echo esc_html($event_extended_date); ?></h2>
                                <h4><?php echo esc_html($post_meta['_TFIP_event_time'][0]); ?></h4>
                                <hr>
                                <p class="descrizione-music"><?php echo wp_kses_post($post_content); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($post_meta['_TFIP_event_type'][0] == "sport") : ?>
                        <div class="row squadre text-center">
                            <div class="col-12 col-md-2">
                                <img src="<?php echo esc_url($team_one_img); ?>" alt="<?php echo esc_attr($post_meta['_TFIP_event_TeamOne'][0]); ?>"> 
                                <img src="<?php echo esc_url($team_two_img); ?>" alt="<?php echo esc_attr($post_meta['_TFIP_event_TeamTwo'][0]); ?>">
                            </div>
                            <div class="col-12 col-md-8">
                                <h1>
                                    <?php echo esc_html($post_meta['_TFIP_event_TeamOne'][0]); ?> - 
                                    <?php echo esc_html($post_meta['_TFIP_event_TeamTwo'][0]); ?>
                                </h1>
                            </div>
                        </div>

                        <div class="descrizione-sport">
                            <p>
                                Presso il nostro locale troverai due maxischermi <br>
                                per seguire in diretta la partita  <br>
                                <b>
                                    <img class="evento-sport-img" src="<?php echo esc_url($team_one_img); ?>" alt="<?php echo esc_attr($post_meta['_TFIP_event_TeamOne'][0]); ?>">  
                                    <?php echo esc_html($post_meta['_TFIP_event_TeamOne'][0]); ?>
                                </b> 
                                <span> vs </span>
                                <b><?php echo esc_html($post_meta['_TFIP_event_TeamTwo'][0]); ?></b>
                                <img class="evento-sport-img" src="<?php echo esc_url($team_two_img); ?>" alt="<?php echo esc_attr($post_meta['_TFIP_event_TeamTwo'][0]); ?>">
                                <br> che verrà proiettata il giorno<br>
                                <b><?php echo esc_html($event_extended_date); ?></b> alle ore 
                                <b><?php echo esc_html($post_meta['_TFIP_exact_event_time'][0]); ?></b> 
                            </p>
                        </div>
                    <?php endif; ?>

                    <div id="tmp-loaded-form"></div>

                </div> <!-- /.col-12 -->
            </div> <!-- /.dett-ev row -->
        </div> <!-- /.dett-ev-cont container -->
    </div> <!-- /#content -->
</div> <!-- /#primary -->







<?php get_footer(); ?>
<script>
addEventListener("DOMContentLoaded", (event) => {

    LoadBaseClientBookingForm( 
        <?php echo json_encode($post_id); ?>, 
        <?php echo json_encode(get_post_meta($post_id, '_TFIP_event_timeslot', true)); ?>,
        <?php echo json_encode($event_timestamp); ?>,
        <?php echo json_encode($post_meta['_TFIP_event_time'][0]) ?> 
    );
    
})
</script>
