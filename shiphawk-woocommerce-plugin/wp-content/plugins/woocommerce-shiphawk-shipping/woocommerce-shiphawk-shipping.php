<?php
/*
Plugin Name: ShipHawk Shipping
Description: ShipHawk Shipping for Woocommerce.
Version: 1.1
Author: ShipHawk
Author URI: https://shiphawk.com/
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include(plugin_dir_path(__FILE__) . 'includes/shiphawk-api.php');
include(plugin_dir_path(__FILE__) . 'includes/shiphawk-helper.php');

add_action('plugins_loaded', 'init_shiphawk_shipping', 0);

function init_shiphawk_shipping()
{

    if (!class_exists('WC_Shipping_Method')) return;

    class shiphawk_shipping extends WC_Shipping_Method
    {

        function __construct()
        {

            $this->id = 'shiphawk_shipping';
            $this->method_title = __('ShipHawk Shipping', 'woocommerce');
            $this->admin_page_heading = __('ShipHawk Shipping', 'woocommerce');
            $this->admin_page_description = __('ShipHawk Shipping', 'woocommerce');

            add_action('woocommerce_update_options_shipping_' . $this->id, array(&$this, 'process_admin_options'));

            $this->init();
        }

        /**
         * init function
         */
        function init()
        {

            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->settings['enabled'];

            $this->api_key = $this->settings['api_key'];
            $this->gateway_mode = $this->settings['gateway_mode'];

            $this->manual_shipping = $this->settings['manual_import_order'];
            //$this->origin_postcode = $this->settings['origin_postcode'];

        }

        function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable this shipping method', 'woocommerce'),
                    'default' => 'no',
                ),
                'api_key' => array(
                    'title' => __('Api Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Api Key', 'woocommerce'),
                    'default' => __('', 'woocommerce'),
                    'required' => true,
                ),
                'gateway_mode' => array(
                    'title' => __('Gateway Mode', 'woocommerce'),
                    'type' => 'select',
                    'description' => '',
                    'default' => 'https://sandbox.shiphawk.com/api/v4/',
                    'options' => array(
                        'https://shiphawk.com/api/v4/' => __('Live', 'woocommerce'),
                        'https://sandbox.shiphawk.com/api/v4/' => __('Sandbox', 'woocommerce'),
                    ),
                ),
                'manual_import_order' => array(
                    'title' => __('Manual import order', 'woocommerce'),
                    'type' => 'select',
                    'description' => 'Manual Import Order',
                    'default' => '1',
                    'options' => array(
                        '1' => __('yes', 'woocommerce'),
                        '0' => __('no', 'woocommerce'),
                    ),
                ),
                'origin_postcode' => array(
                    'title' => __('Postcode / ZIP', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('For versions <= 3.1.2 this parameter is required as WooCommerce does not have this option out of the box. This value will be used in newer versions if default ZIP field is left empty.', 'woocommerce'),
                    'default' => __('', 'woocommerce'),
                    'required' => false,
                )

            );
        }

        /*
        * This method is called when shipping is calculated (or re-calculated)
        */
        function calculate_shipping($package = array())
        {

            global $woocommerce;
            $cart_objct = $woocommerce->cart;
            $from_zip = get_option('woocommerce_store_postcode', '');
            $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
            if (empty($from_zip)) {
                $from_zip = $plugin_settings['origin_postcode'];
            }
            $items = array();
            foreach ($cart_objct->cart_contents as $products) {

                $_pf = new WC_Product_Factory();
                $_product = $_pf->get_product($products['product_id']);

                $woocommerce_dimension_unit = get_option('woocommerce_dimension_unit');
                $woocommerce_weight_unit = get_option('woocommerce_weight_unit');

                $item_weight = round(convertToInchLbs($_product->get_weight(), $woocommerce_weight_unit), 2);
                $items[] = array(
                    'product_sku' => $_product->get_sku(),
                    'value' => $_product->get_price(),
                    'quantity' => $products['quantity'],
                    'width' => round(convertToInchLbs($_product->get_width(), $woocommerce_dimension_unit), 2),
                    'length' => round(convertToInchLbs($_product->get_length(), $woocommerce_dimension_unit), 2),
                    'height' => round(convertToInchLbs($_product->get_height(), $woocommerce_dimension_unit), 2),
                    'weight' => $item_weight,
                    'item_type' => $item_weight <= 70 ? 'parcel' : 'handling_unit',
                    'handling_unit_type' => $item_weight <= 70 ? '' : 'box'
                );
            }

            $to_zip = $woocommerce->customer->get_shipping_postcode();

            $shiphawk_shipping_rates = array();

            $rateRequest = array(
                'items' => $items,
                'origin_address' => array(
                    'zip' => $from_zip
                ),
                'destination_address' => array(
                    'zip' => $to_zip,
                    'is_residential' => 'true'
                ),
                'apply_rules' => 'true'
            );

            $ship_rates = getShiphawkRate($rateRequest);

            if (($ship_rates) && (is_object($ship_rates))) {
                if (property_exists($ship_rates, 'rates')) {
                    foreach ($ship_rates->rates as $ship_rate) {

                        $shipping_label = _getServiceName($ship_rate);
                        $rate = array(
                            'id' => $ship_rate->id,
                            'label' => $shipping_label,
                            'cost' => $ship_rate->price,
                            'taxes' => '',
                            'calc_tax' => "per_order",
                            'shiphawk_rate_id' => $ship_rate->id,
                        );
                        $this->add_rate($rate);
                        $shiphawk_shipping_rates[] = $rate;
                    }
                }
            }

            WC()->session->set('shiphawk_shipping_rates', $shiphawk_shipping_rates);
        }

        public function admin_options()
        {

            ?>
            <h3><?php _e('ShipHawk Shipping & Orders', 'woocommerce'); ?></h3>
            <p><?php _e('ShipHawk Shipping & Import Orders', 'woocommerce'); ?></p>
            <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table><!--/.form-table-->
            <?php
        }
    } // end shiphawk_shipping
}


// Check ShipHawk API configuration
function action_woocommerce_settings_save()
{
    if (($_REQUEST['tab'] == 'shipping') && ($_REQUEST['section'] == 'shiphawk_shipping')) {
        $result = checkApiUser();
        if ($result) {
            if (property_exists($result, 'error')) {

                do_action('woocommerce_shiphawk_error');
            } else {

                do_action('woocommerce_shiphawk_notice');
            }
        } else {
            do_action('woocommerce_shiphawk_error');
        }
    }

}

add_action("woocommerce_settings_saved", 'action_woocommerce_settings_save', 10, 0);

function show_shiphawk_error()
{
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e('Unable to authenticate ShipHawk API key.', 'sample-text-domain'); ?></p>
    </div>
    <?php

}

add_action("woocommerce_shiphawk_error", 'show_shiphawk_error', 10, 0);

function show_shiphawk_notice()
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Your account is successfully linked with ShipHawk.', 'sample-text-domain'); ?></p>
    </div>
    <?php

}

add_action("woocommerce_shiphawk_notice", 'show_shiphawk_notice', 10, 0);

function order_status_pending($order_id)
{
    updateStatusOrder('new', $order_id);
}

function order_status_failed($order_id)
{
//
}

function order_status_hold($order_id)
{
    updateStatusOrder('new', $order_id);
}

function order_status_processing($order_id)
{
    updateStatusOrder('processing', $order_id);
}

function order_status_completed($order_id)
{
    updateStatusOrder('complete', $order_id);
}

function order_status_refunded($order_id)
{
//
}

function order_status_cancelled($order_id)
{
    updateStatusOrder('cancelled', $order_id);
}

add_action('woocommerce_order_status_pending', 'order_status_pending', 10, 1);
add_action('woocommerce_order_status_failed', 'order_status_failed', 10, 1);
add_action('woocommerce_order_status_on-hold', 'order_status_hold', 10, 1);
add_action('woocommerce_order_status_processing', 'order_status_processing', 10, 1);
add_action('woocommerce_order_status_completed', 'order_status_completed', 10, 1);
add_action('woocommerce_order_status_refunded', 'order_status_refunded', 10, 1);
add_action('woocommerce_order_status_cancelled', 'order_status_cancelled', 10, 1);


/**
 * Add shipping method to WooCommerce
 **/
function add_shiphawk_shipping($methods)
{
    $methods[] = 'shiphawk_shipping';
    return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_shiphawk_shipping');
/**
 * Update the order meta with field value
 **/
add_action('woocommerce_checkout_update_order_meta', 'import_order_to_shiphawk');

function import_order_to_shiphawk($order_id)
{
    global $woocommerce;

    $order = new WC_Order($order_id);
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
    $shiphawk_shipping_rates = $woocommerce->session->get('shiphawk_shipping_rates');

    if (is_array($shiphawk_shipping_rates)) {
        foreach ($shiphawk_shipping_rates as $rate) {
            if ($order->has_shipping_method($rate['shiphawk_rate_id'])) {
                update_post_meta($order_id, 'shiphawk_rate_id', $rate['shiphawk_rate_id']);
                continue;
            }
        }
    }

    // import order
    if (($plugin_settings['manual_import_order'] == '0') && ($plugin_settings['enabled'] == 'yes')) {
        process_shiphawk_order_import($order);
    }
}


// add our own item to the order actions meta box
add_action('woocommerce_order_actions', 'add_order_meta_box_actions');

// define the item in the meta box by adding an item to the $actions array
function add_order_meta_box_actions($actions)
{
    $actions['shiphawk_order_import'] = __('ShipHawk Order Import');
    return $actions;
}

register_activation_hook(__FILE__, 'shiphook_shipping_activation');
function shiphook_shipping_activation()
{
    wp_clear_scheduled_hook('shiphawk_import_orders');

    wp_schedule_event(time(), 'daily', 'shiphawk_import_orders');
}


add_action('shiphawk_import_orders', 'import_orders_process');
function import_orders_process()
{
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    if ($plugin_settings['enabled'] != 'yes') {
        return;
    }

    $params = array(
        'date_before' => date('Y-m-d', strtotime('today')),
        'date_after'  => date('Y-m-d', strtotime('-14 days'))
    );

    $orders = wc_get_orders($params);

    foreach ( $orders as $order ) {
        process_shiphawk_order_import($order);
    }
}


register_deactivation_hook(__FILE__, 'shiphook_shipping_deactivation');
function shiphook_shipping_deactivation()
{
    wp_clear_scheduled_hook('shiphawk_import_orders');
}