<?php
/**
 * @package woocommerce-shiphawk-shipping
 */
/*if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}*/
//include_once __DIR__ . '/woocommerce-shiphawk-shipping.php';

subscribeToShiphawk();

//log_me('test');

function subscribeToShiphawk() {

    //global $woocommerce;
    print_r ($_GET['api_key']);
    print_r ('<br>');
    print_r ($_GET['order_id']);
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    echo $api_key = $plugin_settings['api_key'];

}

function log_me($message) {
    //if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    //}
}