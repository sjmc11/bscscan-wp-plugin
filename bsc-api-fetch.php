<?php
/*
Plugin Name: BSCScan simple token stats
Plugin URI: https://github.com/sjmc11/bscscan-wp-plugin
Description: Periodically fetch token stats from bscscan api and update ACF values. Requires ACF Pro.
Author: JC
Version: 0.0.1
Author URI: https://github.com/sjmc11/
*/


/**
 * This plugin relies on ACF to provide meta fields, an admin page, db entries etc..
 * In future this could be integrated directly.
 * This serves to cover the core functionality of fetching from the bscscan API every x hours
 */

class bscScan {

//    public static $tokenDecimal;
//    public static $tokenAddress;
//
//
//    function __construct() {
//        bscScan::$tokenDecimal = $GLOBALS['tokenDecimal'];
//        bscScan::$tokenAddress = $GLOBALS['tokenAddress'];
//        bscScan::$tokenAddress = "0x5xbeea03923266ca8af5ad3821b2j150b33a25a5";
//    }

    static function handleActivate() {

        // Bail if ACF is not activated
        if (!is_plugin_active('advanced-custom-fields/acf.php')) {
            die('This plugin requires Advanced Custom fields to be active.');
        }

        // Bail if activating from network, or bulk
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            die('Does not support remote activate or multi-site');
        }

        // Setup schedule
        if (! wp_next_scheduled ( 'bsc_fetch_event' )) {
            wp_schedule_event(time(), 'hourly', 'bsc_fetch_event');
        }

       if(get_field('bscscan_api_key', 'option') && get_field('bscscan_token_address', 'option') && get_field('bscscan_burn_address', 'option')){
        self::fetchTokenStats();
       }

    }

    static function handleDeactivate() {
        // Destroy schedule
        $timestamp = wp_next_scheduled("bsc_fetch_event");
        wp_unschedule_event( $timestamp,"bsc_fetch_event");
    }

    /**
     * Add ACF admin page
     * @return void
     */
    static function registerAcfAdminPage(){

        if( function_exists('acf_add_options_page') ) {
            acf_add_options_page(array(
                'page_title' => 'BSC Token Stats',
                'menu_title' => 'BSC Token Stats',
                'menu_slug' => 'bscscan-stats',
                'capability' => 'edit_posts',
                'redirect' => false,
                'icon_url' => 'dashicons-chart-pie',
                'position' => 34
            ));
        }

        if( function_exists('acf_add_local_field_group') ){
            acf_add_local_field_group(array (
                'key' => 'bscscan_stats',
                'title' => 'BSC Token Stats',
                'fields' => array (
                    array (
                        'key' => 'total_supply',
                        'label' => 'Total supply',
                        'name' => 'total_supply',
                        'type' => 'number',
                        'required' => 0,
                        'wrapper' => array (
                            'width' => '33',
                        ),
                        'placeholder' => '',
                        'readonly' => 0,
                    ),
                    array (
                        'key' => 'total_burned',
                        'label' => 'Total burned',
                        'name' => 'total_burned',
                        'type' => 'number',
                        'required' => 0,
                        'wrapper' => array (
                            'width' => '33',
                        ),
                        'placeholder' => '',
                        'readonly' => 0,
                    ),
                    array (
                        'key' => 'circ_supply',
                        'label' => 'Circulating supply',
                        'name' => 'circ_supply',
                        'type' => 'number',
                        'required' => 0,
                        'wrapper' => array (
                            'width' => '33',
                        ),
                        'placeholder' => '',
                        'readonly' => 0,
                    ),
                    array (
                        'key' => 'bscscan_api_key',
                        'label' => 'BscScan API key',
                        'name' => 'bscscan_api_key',
                        'type' => 'text',
                        'required' => 1,
                        'wrapper' => array (
                            'width' => '33',
                        ),
                        'placeholder' => '',
                        'readonly' => 0,
                    ),
                    array (
                        'key' => 'bscscan_token_address',
                        'label' => 'BscScan Token Address',
                        'name' => 'bscscan_token_address',
                        'type' => 'text',
                        'required' => 1,
                        'wrapper' => array (
                            'width' => '33',
                        ),
                        'placeholder' => '',
                        'readonly' => 0,
                    ),
                    array (
                        'key' => 'bscscan_burn_address',
                        'label' => 'BscScan Token Burn Address',
                        'name' => 'bscscan_burn_address',
                        'type' => 'text',
                        'required' => 1,
                        'wrapper' => array (
                            'width' => '33',
                        ),
                        'placeholder' => '',
                        'default' => '0x000000000000000000000000000000000000dead',
                        'readonly' => 0,
                    ),
                ),
                'location' => array (
                    array (
                        array (
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'bscscan-stats',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
            ));
        }
    }

    /**
     * @return void
     */
    static function fetchTokenStats(){
        self::fetchCircSupply();
        self::fetchBurnAmount();
    }

    /**
     * Fetch circulating supply
     * @return string|void
     */
    private static function fetchCircSupply(){

        $tokenDecimal = 9;
        $tokenAddress = get_field('bscscan_token_address', 'option');
        $apiUrl = "https://api.bscscan.com/api?module=stats&action=tokensupply&contractaddress=" . $tokenAddress .  "&apikey=" . get_field('bscscan_api_key', 'option');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            $bscanResponse = json_decode($response, true);
            update_field('total_supply', $bscanResponse['result'] / (10 ** $tokenDecimal), 'option');
        }
    }

    /**
     * Fetch burn address amount
     * @return string|void
     */
    private static function fetchBurnAmount(){
        $tokenDecimal = 9;
        $tokenAddress = get_field('bscscan_token_address', 'option');
        $burnAddress = get_field('bscscan_burn_address', 'option');
        $apiUrl = "https://api.bscscan.com/api?module=account&action=tokenbalance&contractaddress=" . $tokenAddress .  "&address=" . $burnAddress . "&apikey=" . get_field('bscscan_api_key', 'option');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            $bscanResponse = json_decode($response, true);
            update_field('total_burned', $bscanResponse['result'] / (10 ** $tokenDecimal), 'option');
            update_field('circ_supply', get_field('total_supply', 'option') - get_field('total_burned', 'option'), 'option');
        }
    }

}



register_activation_hook( __FILE__, array( 'bscScan', 'handleActivate' ) );
register_deactivation_hook( __FILE__, array( 'bscScan', 'handleDeactivate' ) );

add_action( 'acf/init', array( 'bscScan', 'registerAcfAdminPage'));

add_action('bsc_fetch_event', array( 'bscScan', 'fetchTokenStats'));

// Uncomment to fetch stats on refresh
//add_action('init', array( 'bscScan', 'fetchTokenStats'));