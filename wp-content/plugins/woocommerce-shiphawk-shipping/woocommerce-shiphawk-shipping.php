<?php
/*
Plugin Name: ShipHawk Shipping
Description: ShipHawk Shipping for Woocommerce.
Version: 1.3
Author: ShipHawk
Author URI: https://shiphawk.com/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

include( plugin_dir_path( __FILE__ ) . 'includes/shiphawk-api.php');
include( plugin_dir_path( __FILE__ ) . 'includes/shiphawk-helper.php');

add_action('plugins_loaded', 'init_shiphawk_shipping', 0);

function init_shiphawk_shipping() {

    if ( ! class_exists( 'WC_Shipping_Method' ) ) return;
    
class shiphawk_shipping extends WC_Shipping_Method {

    function __construct() { 
     
        $this->id           = 'shiphawk_shipping';
        $this->method_title         = __( 'ShipHawk Shipping', 'woocommerce' );
        $this->admin_page_heading   = __( 'ShipHawk Shipping', 'woocommerce' );
        $this->admin_page_description   = __( 'ShipHawk Shipping', 'woocommerce' );

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_options' ) );

        $this->init();
    }

    /**
     * init function
     */
    function init() {
    
        $this->init_form_fields();
        $this->init_settings();

        $this->enabled        = $this->settings['enabled'];
        $this->title          = $this->settings['title'];
        $this->api_key        = $this->settings['api_key'];
        $this->gateway_mode           = $this->settings['gateway_mode'];
        $this->rate_filter        = $this->settings['rate_filter'];
        $this->packing        = $this->settings['packing'];
        $this->manual_shipping        = $this->settings['manual_shipping'];
        $this->receipts_to        = $this->settings['receipts_to'];
        $this->origin_first_name          = $this->settings['origin_first_name'];
        $this->origin_last_name           = $this->settings['origin_last_name'];
        $this->origin_address         = $this->settings['origin_address'];
        $this->origin_state           = $this->settings['origin_state'];
        $this->origin_city        = $this->settings['origin_city'];
        $this->origin_zipcode         = $this->settings['origin_zipcode'];
        $this->origin_location_type           = $this->settings['origin_location_type'];
        $this->origin_phone           = $this->settings['origin_phone'];
        $this->origin_email           = $this->settings['origin_email'];

    }
    
    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'woocommerce' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this shipping method', 'woocommerce' ),
                'default'       => 'no',
                                    ),
            'title' => array(
                'title'         => __( 'Method Title', 'woocommerce' ),
                'type'          => 'text',
                'disabled'          => true,
                'description'   => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default'       => __( 'ShipHawk Shipping', 'woocommerce' ),
                'required'      => true,
                                    ),
            'api_key' => array(
                'title'         => __( 'Api Key', 'woocommerce' ),
                'type'          => 'text',
                'description'   => __( 'Api Key', 'woocommerce' ),
                'default'       => __( '', 'woocommerce' ),
                'required'      => true,
            ),
            'gateway_mode' => array(
                    'title'         => __( 'Gateway Mode', 'woocommerce' ),
                    'type'          => 'select',
                    'description'   => '',
                    'default'       => 'https://shiphawk.com/api/v3/',
                    'options'       => array(
                        'https://shiphawk.com/api/v3/'  => __( 'Live', 'woocommerce' ),
                        'https://sandbox.shiphawk.com/api/v3/'      => __( 'Sandbox', 'woocommerce' ),
                    ),
                ),
            'rate_filter' => array(
                'title'         => __( 'Rate Filter', 'woocommerce' ),
                'type'          => 'select',
                'description'   => '',
                'default'       => 'consumer',
                'options'       => array(
                    'consumer'  => __( 'consumer', 'woocommerce' ),
                    'best'      => __( 'best', 'woocommerce' ),
                ),
            ),
            'packing' => array(
                'title'         => __( 'Default packing setting', 'woocommerce' ),
                'type'          => 'select',
                'description'   => 'Default packing setting',
                'default'       => '1',
                'options'       => array(
                    'true'     => __( 'yes', 'woocommerce' ),
                    'false'         => __( 'no', 'woocommerce' ),
                ),
            ),
            'manual_shipping' => array(
                'title'         => __( 'Manual book shipment', 'woocommerce' ),
                'type'          => 'select',
                'description'   => 'Manual book shipment',
                'default'       => '1',
                'options'       => array(
                    '1'     => __( 'yes', 'woocommerce' ),
                    '0'         => __( 'no', 'woocommerce' ),
                ),
            ),
            'receipts_to' => array(
                'title'         => __( 'Send order receipts to', 'woocommerce' ),
                'type'          => 'select',
                'description'   => 'Send order receipts to',
                'default'       => '1',
                'options'       => array(
                    'customer'     => __( 'customer', 'woocommerce' ),
                    'administrator'         => __( 'administrator', 'woocommerce' ),
                ),
            ),
            'shiphawk_admin_email' => array(
                'title'         => __( 'Administrator email', 'woocommerce' ),
                'type'          => 'text',
            ),

            'cart_threshold' => array(
                'title'         => __( 'Cart threshold', 'woocommerce' ),
                'type'          => 'text',
                'default'       => 0.00,
            ),

            'discount_fixed' => array(
                'title'         => __( 'Markup or Discount Flat Amount', 'woocommerce' ),
                'type'          => 'text',
                'default'       => 0,
                'description'   => __( 'possible values from -∞ to ∞', 'woocommerce' ),
            ),

            'discount_percentage' => array(
                'title'         => __( 'Markup or Discount Percentage', 'woocommerce' ),
                'type'          => 'text',
                'default'       => 0,
                'description'   => __( 'possible values from -100 to 100', 'woocommerce' ),
            ),

            'origin_title' => array(
                'title'         => __( 'Primary Origin Details', 'woocommerce' ),
                'type'          => 'title',
                'required'      => false,
            ),
            'origin_first_name' => array(
                'title'         => __( 'Origin First Name', 'woocommerce' ),
                'type'          => 'text',
                'required'      => true,
            ),
            'origin_last_name' => array(
                'title'         => __( 'Origin Last Name', 'woocommerce' ),
                'type'          => 'text',
                'required'      => true,
            ),
            'origin_address' => array(
                'title'         => __( 'Origin Address', 'woocommerce' ),
                'type'          => 'text',
                'required'      => true,
            ),
            'origin_address_2' => array(
                'title'         => __( 'Origin Address 2', 'woocommerce' ),
                'type'          => 'text',
            ),
            /* usa state option select */
            'origin_state' => array(
                'title'         => __( 'Origin State', 'woocommerce' ),
                'type'          => 'text',
                'description'   => __( 'Origin State (2 letter abbrev)', 'woocommerce' ),
                'required'      => true,
            ),
            'origin_city' => array(
                'title'         => __( 'Origin City', 'woocommerce' ),
                'type'          => 'text',
                'required'      => true,
            ),
            'origin_zipcode' => array(
                'title'         => __( 'Origin Zipcode', 'woocommerce' ),
                'type'          => 'text',
                'description'   => __( '' ),
                'required'      => true,
            ),
            'origin_location_type' => array(
                'title'         => __( 'Origin Location Type', 'woocommerce' ),
                'type'          => 'select',
                'description'   => '',
                'default'       => 'commercial',
                'options'       => array(
                    'commercial'    => __( 'commercial', 'woocommerce' ),
                    'residential'       => __( 'residential', 'woocommerce' ),
                ),
            ),
            'origin_phone' => array(
                'title'         => __( 'Origin Phone', 'woocommerce' ),
                'type'          => 'text',
                'description'   => __( '' ),
                'required'      => true,
            ),
            'origin_email' => array(
                'title'         => __( 'Origin Email', 'woocommerce' ),
                'type'          => 'text',
                'description'   => __( '' ),
            ),
        );
    }


    /*
    * This method is called when shipping is calculated (or re-calculated)
    */  
    function calculate_shipping($package = array()) {

        global $woocommerce;
        $cart_objct = $woocommerce->cart;

        $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
        $cart_content_total = $cart_objct->cart_contents_total;
        $cart_threshold = $plugin_settings['cart_threshold'];

        $items = array();
        foreach ($cart_objct->cart_contents as $products) {

            $pa_shiphawk_item_type_value = get_post_meta( $products['product_id'], 'shiphawk_product_item_type_value', true );

            $_pf = new WC_Product_Factory();
            $_product = $_pf->get_product($products['product_id']);

            $woocommerce_dimension_unit = get_option('woocommerce_dimension_unit');
            $woocommerce_weight_unit = get_option('woocommerce_weight_unit');

            $product_origin = get_post_meta( $products['product_id'], 'shipping_origin', true );

            $items[] = array(
                'width' => round(convertToInchLbs($_product->width, $woocommerce_dimension_unit), 2),
                'length' => round(convertToInchLbs($_product->length, $woocommerce_dimension_unit), 2),
                'height' => round(convertToInchLbs($_product->height, $woocommerce_dimension_unit), 2),
                'weight' => round(convertToInchLbs($products['data']->weight, $woocommerce_weight_unit), 2),
                'value' => getShipHawkItemValue($products['product_id'],$products['data']->price),
                'quantity' => $products['quantity'],
                'packed' => getIsPacked($products['product_id']),
                'id' => $pa_shiphawk_item_type_value,
                'product_id'=> $products['product_id'],
                'xid'=> $products['product_id'],
                'product_origin' => $this->getProductOrigin($products['product_id']),
                'zip_code' => $this->getProductOriginZip($products['product_id'])
            );
        }

        $grouped_items_by_origin = getGroupedItemsByOrigin($items);

        $to_zip = $woocommerce->customer->get_shipping_postcode();

        $rate_filter = $this->rate_filter;//consumer best

        $is_multi_origin = (count($grouped_items_by_origin) > 1) ? true : false;
        $shiphawk_shipping_rates = array();

        $name_service = '';
        $summ_price = 0;
        $shiphawk_error = false;
        foreach ($grouped_items_by_origin as $origin_id=>$_items) {

            /* get origin zip from first product  */
            $from_zip = $_items[0]['zip_code'];

            /* get origin location type from first product */
            $from_type  = $this->getProductOriginType($_items[0]['xid']);

            if($is_multi_origin) $rate_filter = 'best';

            $ship_rates = getShiphawkRate($from_zip, $to_zip, $_items, $rate_filter, $from_type);

            if(is_object($ship_rates)) {
                if($ship_rates->error) {
                    $shiphawk_error = true;
                }
            }else{
                $shiphawk_shipping_rates[$origin_id]['ship_rates'] = $ship_rates;
                $shiphawk_shipping_rates[$origin_id]['items']= $_items;

                foreach ($ship_rates as $ship_rate) {

                    //check cart threshold

                    $shipping_price = $shipping_original_price = getPrice($ship_rate);

                    if($cart_content_total >= $cart_threshold) {
                        $shipping_price = getDiscountShippingPrice($shipping_price);
                    }

                    $shipping_label = _getServiceName($ship_rate);
                    $shipping_rate_id = str_replace(' ', '_', $shipping_label) . $shipping_original_price;
                    $rate = array(
                        'id' => $shipping_rate_id,
                        'label' => $shipping_label,
                        'cost' => $shipping_price,
                        'taxes' => '',
                        'calc_tax' => "per_order"
                    );
                    if (!$is_multi_origin) {
                        $this->add_rate( $rate );
                    }else{
                        //$name_service .= $shipping_label . ', ';
                        $summ_price += $shipping_price;
                    }
                }
            }

        }

        if($shiphawk_error) {
            //some error from shiphawk
        }else{
            if ($is_multi_origin) {
                $name_service = 'Shipping from multiple locations';
                $shipping_rate_id = str_replace(' ', '_', $name_service);
                $rate = array(
                    'id' => $shipping_rate_id,
                    'label' => $name_service,
                    'cost' => $summ_price,
                    'taxes' => '',
                    'calc_tax' => "per_order"
                );
                $this->add_rate( $rate );
            }

            WC()->session->set( 'shiphawk_shipping_id', $shiphawk_shipping_rates );
            WC()->session->set( 'is_multi_origin', $is_multi_origin );
        }

    }

    public function getProductOriginZip($product_id) {

        if (checkProductOriginAttributes($product_id)) {
            return get_post_meta( $product_id, 'origin_zipcode', true );
        }

        $shipping_origin = get_post_meta( $product_id, 'shipping_origin', true );
        if(!empty($shipping_origin)) {
            return get_post_meta( $shipping_origin, 'origin_zipcode', true );
        }

        return $this->origin_zipcode;
    }

    public function getProductOriginType($product_id) {

        if (checkProductOriginAttributes($product_id)) {
            return get_post_meta( $product_id, 'origin_location_type', true );
        }

        $shipping_origin = get_post_meta( $product_id, 'shipping_origin', true );
        if(!empty($shipping_origin)) {
            return get_post_meta( $shipping_origin, 'origin_location_type', true );
        }

        return $this->origin_location_type;
    }


    public function getProductOrigin($product_id) {

        if (checkProductOriginAttributes($product_id)) {
            return 'per_product';
        }

        $shipping_origin = get_post_meta( $product_id, 'shipping_origin', true );
        if(!empty($shipping_origin)) {
            return get_post_meta( $shipping_origin, 'origin_zipcode', true );
        }

        return null;
    }

    public function admin_options() {

        ?>
        <h3><?php _e('ShipHawk Shipping', 'woocommerce'); ?></h3>
        <p><?php _e('ShipHawk Shipping', 'woocommerce'); ?></p>
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

add_filter( 'woocommerce_product_data_tabs', 'woo_new_product_tab' );
function woo_new_product_tab( $tabs ) {
    // Adds the new tab
    $tabs['shiphawk_tab'] = array(
        'label' 	=> __( 'Shiphawk Attributes', 'woocommerce' ),
        'target' => 'shiphawk_product_data',
        'class'  => array(),
    );

    return $tabs;
}

add_action('woocommerce_product_data_panels', 'woo_new_product_tab_content');
function woo_new_product_tab_content() {
global $post, $wpdb;

    $shipping_origins = $wpdb->get_results( "SELECT id, post_title FROM {$wpdb->prefix}posts WHERE (post_status = 'publish' AND post_type = 'origins')" );

    $shiphawk_shipping_origin = get_post_meta( $post->ID, 'shipping_origin', true );


    echo '<div id="shiphawk_product_data" class="panel woocommerce_options_panel wc-metaboxes-wrapper">
     <div class="wc-metabox">

     <table cellpadding="0" cellspacing="0" class="woocommerce_attribute_data wc-metabox-content" style="display: table;">
            <tbody>
                <tr>
                    <td class="attribute_name">
                        <strong>Shiphawk type of product:</strong>
                    </td>
                    <td>';
    $shiphawk_product_item_type = get_post_meta( $post->ID, 'shiphawk_product_item_type', true );
                        echo '<input type="text" name="shiphawk_type_of_product" value="' . $shiphawk_product_item_type . '" id="shiphawk_type_of_product" post_id="' . $post->ID  .'" >';
                    echo '</td>
                </tr>';
    echo '<tr><td><strong>Shipping origins:</strong></td>';

        echo '<td><select id="select_shipping_origin" name="shipping_origin">';
                $default_origin_name = 'Primary';
                echo '<option selected value="">' . $default_origin_name .  '</option>';
                foreach ($shipping_origins as $origin) {
                    if ($origin->id == $shiphawk_shipping_origin) {
                        echo '<option selected value="' . $origin->id .'">' . $origin->post_title .  '</option>';
                    }else{
                        echo '<option value="' . $origin->id .'">' . $origin->post_title .  '</option>';
                    }
                }
        echo '</select></td></tr>';
        echo '<tr>
        <td class="attribute_name">
                        <strong>Number of items per product:</strong>
                    </td>
                  <td>';
            $shiphawk_number_of_item = get_post_meta( $post->ID, 'shiphawk_number_of_item', true );

            $shiphawk_number_of_item = ($shiphawk_number_of_item) ? $shiphawk_number_of_item : 1 ;

            echo '<input type="text" name="shiphawk_number_of_item" value="' . $shiphawk_number_of_item . '" id="shiphawk_number_of_item" post_id="' . $post->ID  .'" >';

        echo '<td></tr>';
        echo '<tr>
                <td class="attribute_name">
                        <strong>Item value:</strong>
                    </td>
                      <td>';
        $shiphawk_item_value = get_post_meta( $post->ID, 'shiphawk_item_value', true );
        echo '<input type="text" name="shiphawk_item_value" value="' . $shiphawk_item_value . '" id="shiphawk_item_value" post_id="' . $post->ID  .'" >';
        echo '<td></tr>';

    echo '<tr><td><strong>Item is packed:</strong></td>';

    echo '<td><select id="shiphawk_item_is_packed" name="shiphawk_item_is_packed">';
            $shiphawk_item_is_packed = get_post_meta( $post->ID, 'shiphawk_item_is_packed', true );

            if ($shiphawk_item_is_packed == 1) {
                echo '<option selected value="1">yes</option>';
            }else{
                echo '<option  value="1">yes</option>';
            }
            if ($shiphawk_item_is_packed == 0) {
                echo '<option selected value="0">no</option>';
            }else{
                echo '<option  value="0">no</option>';
            }

            if ($shiphawk_item_is_packed == 2) {
                echo '<option selected value="2">Use config</option>';
            }else{
                echo '<option  value="2">Use config</option>';
            }

    echo '</select></td></tr>';

    echo '<tr><td class="attribute_name"><strong>Shipping Origin:</strong></td><td>';

    /* ------ Shipping Origin -------- $required_origin_attributes = array('origin_first_name', 'origin_last_name', 'origin_address', 'origin_state', 'origin_city', 'origin_zipcode', 'origin_location_type', 'origin_phone', 'origin_email' ); */
    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin First Name:</strong>
                    </td>
                      <td>';
    $shiphawk_first_name = get_post_meta( $post->ID, 'origin_first_name', true );
    echo '<input type="text" name="origin_first_name" value="' . $shiphawk_first_name . '" id="origin_first_name" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin Last Name:</strong>
                    </td>
                      <td>';
    $shiphawk_origin_last_name = get_post_meta( $post->ID, 'origin_last_name', true );
    echo '<input type="text" name="origin_last_name" value="' . $shiphawk_origin_last_name . '" id="origin_last_name" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin Address:</strong>
                    </td>
                      <td>';
    $shiphawk_origin_address = get_post_meta( $post->ID, 'origin_address', true );
    echo '<input type="text" name="origin_address" value="' . $shiphawk_origin_address . '" id="origin_address" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin Address Line 2:</strong>
                    </td>
                      <td>';
    $shiphawk_origin_address2 = get_post_meta( $post->ID, 'origin_address2', true );
    echo '<input type="text" name="origin_address2" value="' . $shiphawk_origin_address2 . '" id="origin_address2" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin City:</strong>
                    </td>
                      <td>';
    $shiphawk_origin_city = get_post_meta( $post->ID, 'origin_city', true );
    echo '<input type="text" name="origin_city" value="' . $shiphawk_origin_city . '" id="origin_city" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin State:</strong>
                    </td>
                      <td>';
    $shiphawk_origin_state = get_post_meta( $post->ID, 'origin_state', true );
    echo '<input type="text" name="origin_state" value="' . $shiphawk_origin_state . '" id="origin_state" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin Zip Code:</strong>
                    </td>
                      <td>';
    $shiphawk_origin_zipcode = get_post_meta( $post->ID, 'origin_zipcode', true );
    echo '<input type="text" name="origin_zipcode" value="' . $shiphawk_origin_zipcode . '" id="origin_zipcode" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    //location type

    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin Phone:</strong>
                    </td>
                      <td>';
    $shiphawk_origin_phone = get_post_meta( $post->ID, 'origin_phone', true );
    echo '<input type="text" name="origin_phone" value="' . $shiphawk_origin_phone . '" id="origin_phone" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    echo '<tr>
                <td class="attribute_name">
                        <strong>Origin Email:</strong>
                    </td>
                      <td>';
    $shiphawk_origin_email = get_post_meta( $post->ID, 'origin_email', true );
    echo '<input type="text" name="origin_email" value="' . $shiphawk_origin_email . '" id="origin_email" post_id="' . $post->ID  .'" >';
    echo '<td></tr>';

    echo '<tr><td><strong>Origin Location Type:</strong></td>';

    echo '<td><select id="origin_location_type" name="origin_location_type">';
    $shiphawk_origin_location_type = get_post_meta( $post->ID, 'origin_location_type', true );

    if ($shiphawk_origin_location_type == 'commercial') {
        echo '<option selected value="commercial">commercial</option>';
    }else{
        echo '<option  value="commercial">commercial</option>';
    }
    if ($shiphawk_origin_location_type == 'residential') {
        echo '<option selected value="residential">residential</option>';
    }else{
        echo '<option  value="residential">residential</option>';
    }

    echo '</select></td></tr>';

            echo '</tbody>
        </table>';

    echo '</div>';
    echo '</div>';
}

/**
 * Add shipping method to WooCommerce
 **/
function add_shiphawk_shipping( $methods ) {
    $methods[] = 'shiphawk_shipping'; return $methods;
}

add_action( 'admin_enqueue_scripts', 'my_enqueue' );
function my_enqueue($hook) {
    if( 'post.php' != $hook ) {
        // Only applies to edit post
        return;
    }

    wp_enqueue_script( 'ajax-script', plugins_url( 'assets/shiphawk.js', __FILE__ ), array('jquery') );

    wp_register_style( 'ShipHawkStylesheet', plugins_url('assets/style.css', __FILE__) );
    wp_enqueue_style( 'ShipHawkStylesheet' );

    // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
    wp_localize_script( 'ajax-script', 'ajax_object',
        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

// Add notice in admin (updated error)
function my_admin_notice(){
    global $pagenow, $post;

    if (( $pagenow == 'post.php' ) && (get_post_type( $post ) == 'shop_order')) {
        $ship_hawk_book_id = get_post_meta( $post->ID, 'ship_hawk_book_id' );
        if (!count($ship_hawk_book_id)>0) {
        echo '<div class="error">
             <p>Order does not have ShipHawk book Id.</p>
         </div>';
        }
    }
}
add_action('admin_notices', 'my_admin_notice');

/**
 * Update the order meta with field value
 **/
add_action('woocommerce_checkout_update_order_meta', 'ShipHawk_custom_checkout_field_update_order_meta');

/* auto book on checkout */
function ShipHawk_custom_checkout_field_update_order_meta( $order_id ) {
    global $woocommerce;

    $ship_hawk_order_id_arrays = $woocommerce->session->get('shiphawk_shipping_id');
    $is_multiorigin = $woocommerce->session->get('is_multi_origin');


    // shipping code with shipping original amount
    $shipping_code = (string) $_POST['shipping_method'][0];
    add_post_meta($order_id, 'shipping_code_original_amount', $shipping_code);

    $order = new WC_Order( $order_id );

    $shipping_method = $order->get_shipping_method();
    $shipping_amount = $order->get_total_shipping();
    //origin_address - get from plugin settings

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $book_ids = array();

        foreach ($ship_hawk_order_id_arrays as $origin_id) {
            foreach ($origin_id['ship_rates'] as $shipping_rate) {

                //if ($shipping_rate->summary->service == $shipping_method) {
                if (!$is_multiorigin) {
                    // check price
                    $shipping_price_from_rate = (string) round(getPrice($shipping_rate),2);

                    if (getOriginalShipHawkShippingPrice($shipping_code, $shipping_price_from_rate)) {
                    //if (round(getPrice($shipping_rate),3) == round($shipping_amount,3)) {
                        //package info
                        $package_info = $shipping_rate->shipping->service . ': ';
                        foreach($shipping_rate->packing->packages as $package) {
                            $package_info .= $package->dimensions->length . 'x' . $package->dimensions->width . 'x' . $package->dimensions->height . ', ' . $package->dimensions->weight . 'lbs ,';
                        }

                        //update_post_meta( $order_id, 'package_info', $package_info);
                        add_post_meta($order_id, 'package_info', $package_info);

                        // manual shipping - NO
                        if ($plugin_settings['manual_shipping'] == '0') {
                                update_post_meta( $order_id, 'ship_hawk_order_id', $shipping_rate->id);

                                $_items  =  $origin_id['items'];

                                $book_id = toBook($order_id, $shipping_rate->id, $order, $_items );

                                if($book_id->details->id) {
                                    //update_post_meta( $order_id, 'ship_hawk_book_id', $book_id->details->id);
                                    $book_ids[] = $book_id->details->id;
                                    $order->add_order_note( __( 'Book Id: ' . $book_id->details->id, 'woocommerce' ) );
                                }

                                if ($book_id->details->id) {
                                    SubscribeToTrackInfo($book_id->details->id, $order);
                                }else{
                                    $order->add_order_note( __( 'No ShipHawk id ', 'woocommerce' ) );
                                }
                        }
                    }
                } else {

                    //package info
                    $package_info = $shipping_rate->shipping->service . ': ';
                    foreach($shipping_rate->packing->packages as $package) {
                        $package_info .= $package->dimensions->length . 'x' . $package->dimensions->width . 'x' . $package->dimensions->height . ', ' . $package->dimensions->weight . 'lbs ,';
                    }

                    //update_post_meta( $order_id, 'package_info', $package_info);
                    add_post_meta($order_id, 'package_info', $package_info);

                    //// manual shipping - NO
                    if ($plugin_settings['manual_shipping'] == '0') {
                        update_post_meta( $order_id, 'ship_hawk_order_id', $shipping_rate->id);
                        $_items  =  $origin_id['items'];

                        $book_id = toBook($order_id, $shipping_rate->id, $order, $_items);

                        if($book_id->details->id) {
                            //update_post_meta( $order_id, 'ship_hawk_book_id', $book_id->details->id);
                            $book_ids[] = $book_id->details->id;
                            $order->add_order_note( __( 'Book Id: ' . $book_id->details->id, 'woocommerce' ) );
                        }

                        if ($book_id->details->id) {
                            SubscribeToTrackInfo($book_id->details->id, $order);
                        }else{
                            $order->add_order_note( __( 'No ShipHawk id ', 'woocommerce' ) );
                        }
                    }
                }

            }

            if(count($book_ids)>0) {
                add_post_meta( $order_id, 'ship_hawk_book_id', $book_ids);
            }
    }
}


add_filter( 'woocommerce_shipping_methods', 'add_shiphawk_shipping' );


/**
 * Display field value on the order edition page
 **/
add_action( 'woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );

function my_custom_checkout_field_display_admin_order_meta($order){
    $order_id = $order->id;

    $ship_hawk_order_id = get_post_meta( $order_id, 'ship_hawk_order_id', true );
    $ship_hawk_book_id = get_post_meta( $order_id, 'ship_hawk_book_id', true );

    //echo '<p><strong>'.__('ShipHawk order id').':</strong> ' . $ship_hawk_order_id . '</p>';
    //echo '<p><strong>'.__('ShipHawk book id').':</strong> ' . $ship_hawk_book_id . '</p>';
}


/**
 * Add get BOL button
 **/
function get_BOL_PDF_action($order){

    $order_id = $order->id;

    $ship_hawk_book_ids = get_post_meta( $order_id, 'ship_hawk_book_id', true );

    //1016194 test book id with
    //$ship_hawk_book_id = 1016194;
    if(count($ship_hawk_book_ids)>0) {
        foreach($ship_hawk_book_ids as $ship_hawk_book_id) {
            echo '<a onclick="getBolPdf(this)" class="bol_link" id="' . $ship_hawk_book_id .'">Get BOL PDF for ' . $ship_hawk_book_id .' shipment</a></br>';
        }
    }

}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'get_BOL_PDF_action', 10, 1);

// add our own item to the order actions meta box
add_action( 'woocommerce_order_actions', 'add_order_meta_box_actions' );
// define the item in the meta box by adding an item to the $actions array
function add_order_meta_box_actions( $actions ) {
    $actions['shiphawk_book_manual'] = __( 'ShipHawk Book' );
    //$actions['shiphawk_subscribe_tracking'] = __( 'ShipHawk Subscribe Tracking' );
    return $actions;
}

// Our custom post type function
function create_origins() {

    register_post_type( 'origins',
        // CPT Options
        array(
            'labels' => array(
                'name' => __( 'Shipping origins' ),
                'singular_name' => __( 'Origins' ),
                'add_new_item'       => __( 'Add New Shipping Origin' ),//TODO More labels
            ),
            'public' => false,
            'show_ui'             => true,
            'show_in_menu'        => current_user_can( 'manage_woocommerce' ) ? 'woocommerce' : true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'rewrite' => array('slug' => 'origins'),
        )
    );

}
// Hooking up our function to theme setup
add_action( 'init', 'create_origins' );

add_action( 'add_meta_boxes', 'add_origin_fileds' );
function add_origin_fileds() {
    add_meta_box( 'origin_fileds', 'Shipping origins', 'shiphawk_shipping_origin_fields', 'origins', 'normal', 'high' );
}

function shiphawk_shipping_origin_fields( $post ) {
    if( $post->post_type == "origins" ) {

    $origin_first_name = get_post_meta( $post->ID, 'origin_first_name', true);
    echo '<ul>';
    echo 'First Name';
    ?>
    <li>
    <input type="text" required name="origin_first_name" value="<?php echo esc_attr( $origin_first_name ); ?>" />
    </li>
<?php

    $origin_last_name = get_post_meta( $post->ID, 'origin_last_name', true);
    echo 'Last Name';
    ?>
    <li>
    <input type="text" required name="origin_last_name" value="<?php echo esc_attr( $origin_last_name ); ?>" />
    </li>
<?php
    $origin_address = get_post_meta( $post->ID, 'origin_address', true);
    echo 'Address';
    ?>
    <li>
        <input type="text" required name="origin_address" value="<?php echo esc_attr( $origin_address ); ?>" />
    </li>
    <?php

    $origin_address2 = get_post_meta( $post->ID, 'origin_address2', true);
    echo 'Address 2nd line';
    ?>
    <li>
        <input type="text"  name="origin_address2" value="<?php echo esc_attr( $origin_address2 ); ?>" />
    </li>
    <?php

    $origin_state = get_post_meta( $post->ID, 'origin_state', true);
    echo 'State (2 letter abbrev.)';
    ?>
    <li>
        <input type="text" required name="origin_state" value="<?php echo esc_attr( $origin_state ); ?>" />
    </li>
    <?php

        $origin_city = get_post_meta( $post->ID, 'origin_city', true);
        echo 'City';
        ?>
        <li>
            <input type="text" required name="origin_city" value="<?php echo esc_attr( $origin_city ); ?>" />
        </li>
        <?php

        $origin_zipcode = get_post_meta( $post->ID, 'origin_zipcode', true);
        echo 'Zipcode';
        ?>
        <li>
            <input type="text" required name="origin_zipcode" value="<?php echo esc_attr( $origin_zipcode ); ?>" />
        </li>
        <?php

        $origin_location_type = get_post_meta( $post->ID, 'origin_location_type', true);
        echo 'Location Type';
        ?>
        <li>
            <select name="origin_location_type">
                <option value="commercial" <?php echo (esc_attr( $origin_location_type ) == 'commercial') ? 'selected' : ''; ?>>commercial</option>
                <option value="residential" <?php echo (esc_attr( $origin_location_type ) == 'residential') ? 'selected' : ''; ?>>residential</option>
            </select>
        </li>
        <?php

        $origin_phone = get_post_meta( $post->ID, 'origin_phone', true);
        echo 'Phone';
        ?>
        <li>
            <input type="text" required name="origin_phone" value="<?php echo esc_attr( $origin_phone ); ?>" />
        </li>
        <?php

        $origin_email = get_post_meta( $post->ID, 'origin_email', true);
        echo 'Email';
        ?>
        <li>
            <input type="text"  name="origin_email" value="<?php echo esc_attr( $origin_email ); ?>" />
        </li>

        <?php

    echo '</ul>';
    }
}

add_action( 'save_post', 'save_shipping_origins_meta' );
function save_shipping_origins_meta( $post_ID ) {
    global $post;
    if( $post->post_type == "origins" ) {
        if (isset( $_POST ) ) {
            update_post_meta( $post_ID, 'origin_first_name', strip_tags( $_POST['origin_first_name'] ) );
            update_post_meta( $post_ID, 'origin_last_name', strip_tags( $_POST['origin_last_name'] ) );

            update_post_meta( $post_ID, 'origin_address', strip_tags( $_POST['origin_address'] ) );
            update_post_meta( $post_ID, 'origin_address2', strip_tags( $_POST['origin_address2'] ) );
            update_post_meta( $post_ID, 'origin_state', strip_tags( $_POST['origin_state'] ) );
            update_post_meta( $post_ID, 'origin_city', strip_tags( $_POST['origin_city'] ) );
            update_post_meta( $post_ID, 'origin_zipcode', strip_tags( $_POST['origin_zipcode'] ) );
            update_post_meta( $post_ID, 'origin_location_type', strip_tags( $_POST['origin_location_type'] ) );

            update_post_meta( $post_ID, 'origin_phone', strip_tags( $_POST['origin_phone'] ) );
            update_post_meta( $post_ID, 'origin_email', strip_tags( $_POST['origin_email'] ) );
        }
    }

    //save product shihawk origin
    if( $post->post_type == "product" ) {
        if (isset( $_POST ) ) {
            update_post_meta( $post_ID, 'shipping_origin', strip_tags( $_POST['shipping_origin'] ) );
            update_post_meta( $post_ID, 'shiphawk_number_of_item', strip_tags( $_POST['shiphawk_number_of_item'] ) );

            update_post_meta( $post_ID, 'shiphawk_item_value', strip_tags( $_POST['shiphawk_item_value'] ) );
            update_post_meta( $post_ID, 'origin_email', strip_tags( $_POST['shiphawk_number_of_item'] ) );
            update_post_meta( $post_ID, 'shiphawk_item_is_packed', strip_tags( $_POST['shiphawk_item_is_packed'] ) );

            update_post_meta( $post_ID, 'shiphawk_item_is_packed', strip_tags( $_POST['shiphawk_item_is_packed'] ) );

            update_post_meta( $post_ID, 'shiphawk_product_item_type', strip_tags( $_POST['shiphawk_type_of_product'] ) );

            //shiphawk_product_item_type

            /* origins */
            update_post_meta( $post_ID, 'origin_first_name', strip_tags( $_POST['origin_first_name'] ) );
            update_post_meta( $post_ID, 'origin_last_name', strip_tags( $_POST['origin_last_name'] ) );
            update_post_meta( $post_ID, 'origin_address', strip_tags( $_POST['origin_address'] ) );
            update_post_meta( $post_ID, 'origin_address2', strip_tags( $_POST['origin_address2'] ) );

            update_post_meta( $post_ID, 'origin_state', strip_tags( $_POST['origin_state'] ) );
            update_post_meta( $post_ID, 'origin_city', strip_tags( $_POST['origin_city'] ) );
            update_post_meta( $post_ID, 'origin_zipcode', strip_tags( $_POST['origin_zipcode'] ) );
            update_post_meta( $post_ID, 'origin_location_type', strip_tags( $_POST['origin_location_type'] ) );
            update_post_meta( $post_ID, 'origin_phone', strip_tags( $_POST['origin_phone'] ) );
            update_post_meta( $post_ID, 'origin_email', strip_tags( $_POST['origin_email'] ) );
            /* origins */

        }
    }
}

 function getIsPacked($product_id) {
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
    $default_is_packed = $plugin_settings['packing'];
    $product_is_packed = get_post_meta( $product_id, 'shiphawk_item_is_packed', true);
    $product_is_packed = ($product_is_packed == 2) ? $default_is_packed : $product_is_packed;

    return ($product_is_packed ? 'true' : 'false');
    //return $product_is_packed;
}

 function getShipHawkItemValue($product_id, $product_price) {
     $shiphawk_number_of_item = get_post_meta( $product_id, 'shiphawk_number_of_item', true);
     $item_value = get_post_meta( $product_id, 'shiphawk_item_value', true);
    if($shiphawk_number_of_item > 0) {
        $price_value = $product_price/$shiphawk_number_of_item;
    }else{
        $price_value = $product_price;
    }
    $item_value = ($item_value > 0) ? $item_value : $price_value;
    return round($item_value,3);
}