<?php

class TFIP_Templater
{
    protected $templates;


    function __construct()
    {
        $this->templates = array();

        add_filter( 'theme_page_templates', array($this, 'TFIP_Templater_Add_Template')); //
        add_filter('wp_insert_post_data', array($this, 'TFIP_Templater_Register_Template'));
        add_filter('template_include', array($this, 'TFIP_Templater_Check_Template'));

        $this->templates[0] = array('../assets/php-templates/TFIP_Page_Event.php' => 'Evento');
        $this->templates[1] = array('../assets/php-templates/TFIP_Page_Admin_Booking.php' => 'Prenotazioni');
    }


    /*
        Add Template to available templates
    */

    public function TFIP_Templater_Add_Template($post_templates)
    {
        foreach ($this->templates as $single_t)
        {
            $post_templates = array_merge($post_templates, $single_t);
        }

        return $post_templates;
    }


    /*
        Register templates with funky funny tricky action by loading it inside the theme templates
    */
    public function TFIP_Templater_Register_Template($atts)
    {
        $cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());
       
        $templates = wp_get_theme()->get_page_templates();

        if(empty($templates))
        {
            $templates = array();
        }

        wp_cache_delete( $cache_key, 'themes' );

        foreach ($this->templates as $single_t)
        {
            $templates = array_merge($templates, $single_t);
            wp_cache_add( $cache_key, $templates, 'themes', 1800 );
        }

        return $atts;
        
    }


    /*
        Load correct template for correct page if custom type or if booking calendar
    */
    public function TFIP_Templater_Check_Template($template)
    {
        global $post;
        
        if(!$post){return $template;}

        if(is_page("Prenotazioni"))
        {
            $file = plugin_dir_path( __FILE__ ) . '../assets/php-templates/TFIP_Page_Admin_Booking.php';
            return $file;

        }else if('tfipfevent' == get_post_type())
        {
            $file = plugin_dir_path( __FILE__ ) . '../assets/php-templates/TFIP_Page_Event.php';
            return $file;
        }
        
        return $template;
    }
}
