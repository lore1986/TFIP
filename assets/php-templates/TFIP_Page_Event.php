<?php

    /* Template Name: Calendario Eventi */
    //;
    wp_head();


    ?>

<?php 
	
    get_header();
    $post_id = get_the_ID();

    $path_check = plugin_dir_path( __DIR__) . 'squadre/';
    $path = plugin_dir_url( __DIR__) . 'squadre/';
    $post_content = get_post_field('post_content', $post_id);
	$post_meta = get_post_meta($post_id);   
    $event_extended_date = $post_meta['_TFIP_event_date'][0];
    $event_timestamp_date = DateTime::createFromFormat('d-m-Y', $post_meta['_TFIP_event_date'][0]);
    $event_timestamp_date->setTime(0, 0, 0); 
    $event_timestamp = $event_timestamp_date->getTimestamp();

    if($post_meta['_TFIP_event_type'][0] == "sport")
    {
        $teamoneimage = $path_check . $post_meta['_TFIP_event_TeamOne'][0] . '.png';
        $teamtwoimage = $path_check . $post_meta['_TFIP_event_TeamTwo'][0] . '.png';

        if (!file_exists($teamoneimage)) 
        {
            $teamoneimage = $path . 'Neutral.png';
        }else
        {
            $teamoneimage = $path . $post_meta['_TFIP_event_TeamOne'][0]  . '.png';
        }

        if(!file_exists($teamtwoimage))
        {
            $teamtwoimage = $path . 'Neutral.png';
        }else
        {
            $teamtwoimage = $path . $post_meta['_TFIP_event_TeamTwo'][0] . '.png';
        }
    }
    
?>


<div id="primary">
    <div id="content" class="site-content TFIP-style <?php echo $post_meta['_TFIP_event_type'][0] ?>" role="main">
        <div class="dett-ev-cont container">
            <div class="dett-ev">
                <?php 
                    if($post_meta['_TFIP_event_type'][0] != "sport")
                    {
                        ?>
                            <div class="dett-music row">
                                <div class="col-12 col-md-6 immagine-evento">
                                    <?php
                                        $featured_image_url = get_the_post_thumbnail_url($post_id, 'full');
                                        if ($featured_image_url) {
                                            echo '<img src="' . $featured_image_url . '" alt="Featured Image">';
                                        }
                                    ?>
                                </div>
                                <div class="col-12 col-md-6">
                                    <h1><?php echo get_the_title($post_id) ?></h1>
                                    <h2><?php echo $event_extended_date ?></h2>
                                    <h4><?php echo $post_meta['_TFIP_event_time'][0]?></h4><hr>
                                    <p class="descrizione-music"><?php echo $post_content ?></p>
                                </div>
                            </div>
                        <?php
                    }
                ?>
                <div class="row">
                    <?php 
                    if($post_meta['_TFIP_event_type'][0] == "sport")
                    {
                        ?>
                        <div class="col-12 col-md-4 squadre">
                            <img src="<?php echo $teamoneimage ?>" alt="<?php echo  $post_meta['_TFIP_event_TeamOne'][0] ?>"> 
                            <img src="<?php echo $teamtwoimage ?>" alt="<?php echo $post_meta['_TFIP_event_TeamTwo'][0] ?>">
                        </div>
                        <div class="col-12 col-md-8 squadre">
                            <h1><?php echo  $post_meta['_TFIP_event_TeamOne'][0] ?> - <?php echo $post_meta['_TFIP_event_TeamTwo'][0] ?></h1>
                        </div>
                        <div class="descrizione-sport">
                            <p>Presso il nostro locale troverai due maxischermi <br>per seguire in diretta la partita  <br>
                            <b><img class="evento-sport-img" src="<?php echo $teamoneimage ?>" alt="<?php echo  $post_meta['_TFIP_event_TeamOne'][0] ?>">  
                            <?php echo  $post_meta['_TFIP_event_TeamOne'][0] ?>  </b> <span > vs </span>
                            <b><?php echo $post_meta['_TFIP_event_TeamTwo'][0] ?></b>
                            <img  class="evento-sport-img"  src="<?php echo $teamtwoimage ?>" alt="<?php echo $post_meta['_TFIP_event_TeamTwo'][0] ?>">
                            
                            <br> che verrà proiettata il giorno<br>
                            <b> <?php echo $event_extended_date ?></b> alle ore 
                            <b><?php echo $post_meta['_TFIP_exact_event_time'][0] ?> </b> 
                            in fascia oraria: 
                            <b><?php echo $post_meta['_TFIP_event_time'][0] ?> </b> 
                            
                        </div>
                        <?php
                    }; 
                    ?>
                    <div id="tmp-loaded-form"> </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>






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
