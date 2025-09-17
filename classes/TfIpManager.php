<?php


class TfIpManager
{
    private $version; 
    private $code_id;
    private $access_token;
    private $endpoint_url;

    function __construct() 
    {
        $this->version = 'v19.0'; 
        $this->code_id = '331689876690438';
        $this->access_token =  get_option( 'tfip_whatsapp_token' );
        $this->endpoint_url = 'https://graph.facebook.com/' . $this->version . '/' . $this->code_id . '/messages';
    }   

    function tf_ipf_send_code($phone, $identification, $code, $lang) {

        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json',
        );

        $la_lang = 'en_GB';
        $template = 'code_confirm';

        if($lang == 'it')
        {
            $la_lang = 'it';
            $template = 'code_confirm_italian';
        }

        $body = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => array(
                'name' => $template,
                'language' => array('code' => $la_lang),
                'components' => array(
                    array(
                        'type' => 'body',
                        'parameters' => array(
                            array('type' => 'text', 'text' => $identification),
                            array('type' => 'text', 'text' => $code),
                        )
                    )
                )
            )
        );

        $response = wp_remote_post(
            $this->endpoint_url,
            array(
                'method'    => 'POST',
                'headers' => $headers,
                'body' => wp_json_encode($body),
                'timeout'   => 45,
                //'sslverify' => false,
            )
        );

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return false;
        } else {
            // Get response body
            $response_body = wp_remote_retrieve_body($response);
            return true;
        }
    }

    function tf_ipf_send_confirmation($phone, $identification, $code, $lang) {

        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json',
        );

        $la_lang = 'en_GB';
        $template = 'confirm_booking';

        if($lang == 'it')
        {
            $la_lang = 'it';
            $template = 'confirm_booking_ita';
        }

        $body = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => array(
                'name' => $template,
                'language' => array('code' => $la_lang),
                'components' => array(
                    array(
                        'type' => 'header',
                        'parameters' => array(
                            array(
                                'type' => 'location',
                                'location' => array(
                                    'latitude' => 43.781663136827426,
                                    'longitude' => 11.25619279365323,
                                    'name' => 'The Florence Irish Pub',
                                    'address' => 'Via Santa Caterina D\'Alessandria 16A'
                                )
                            )
                        )
                    ),
                    array(
                        'type' => 'body',
                        'parameters' => array(
                            array('type' => 'text', 'text' => $identification),
                        )
                    ),
                    array(
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => '0',
                        'parameters' => array(
                            array('type' => 'text', 'text' => $code)
                        )
                    )
                )
            )
        );

        $response = wp_remote_post(
            $this->endpoint_url,
            array(
                'method'    => 'POST',
                'headers' => $headers,
                'body' => wp_json_encode($body),
                'timeout'   => 45,
                //'sslverify' => false,
            )
        );

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return false;
        } else {
            // Get response body
            $response_body = wp_remote_retrieve_body($response);
            return true;
        }
    }

    function tf_ipf_communicate_booking($code, $identification, $datebooking, $timebooking, $participants, $requests, $phoneu) {

		$request_not_empty = "Nessuna";
        
        if(is_string($requests) && $requests != "")
        {
            $request_not_empty = $requests;
        }
		
        // Set request headers
        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json',
        );

    
        $body = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => '+393514333117',
            'type' => 'template',
            'template' => array(
                'name' => 'comunicate',
                'language' => array('code' => 'it'),
                'components' => array(
                    array(
                        'type' => 'body',
                        'parameters' => array(
                            array('type' => 'text', 'text' => $identification),
                            array('type' => 'text', 'text' => date('d/m/Y', $datebooking)),
                            array('type' => 'text', 'text' => $timebooking),
                            array('type' => 'text', 'text' => $participants),
                            array('type' => 'text', 'text' => $phoneu),
                            array('type' => 'text', 'text' => $request_not_empty)
                        )
                    ),
                    array(
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => '0',
                        'parameters' => array(
                            array('type' => 'text', 'text' => $code)
                        )
                    )
                )
            )
        );
        

        $response = wp_remote_post(
            $this->endpoint_url,
            array(
                'method'    => 'POST',
                'headers' => $headers,
                'body' => wp_json_encode($body),
                'timeout'   => 45,
            )
        );

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return false;
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return true;
        }
    }
    
}



