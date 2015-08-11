<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function convertToInchLbs($dimension, $dimension_unit) {
    switch ($dimension_unit) {
        case "kg":
            return 2.20462262 * $dimension;
            break;
        case "g":
            return 0.00220462262 * $dimension;
            break;
        case "lbs":
            return $dimension;
            break;
        case "oz":
            return 0.0625 * $dimension;
            break;
        case "m":
            return 39.3700787 * $dimension;
            break;
        case "cm":
            return 0.393700787 * $dimension;
            break;
        case "mm":
            return 0.0393700787 * $dimension;
            break;
        case "in":
            return $dimension;
            break;
        case "yd":
            return 36 * $dimension;
            break;
    }

    return $dimension;
}

function getDefaultOriginAddress () {
    $origin_address = array();
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $origin_address['first_name']          = $plugin_settings['origin_first_name'];
    $origin_address['last_name']           = $plugin_settings['origin_last_name'];
    $origin_address['street1']         = $plugin_settings['origin_address'];
    $origin_address['street2']         = $plugin_settings['origin_address_2'];
    $origin_address['state']           = $plugin_settings['origin_state'];
    $origin_address['city']        = $plugin_settings['origin_city'];
    $origin_address['zip']         = $plugin_settings['origin_zipcode'];
    $origin_address['origin_location_type']           = $plugin_settings['origin_location_type'];
    $origin_address['phone_number']           = $plugin_settings['origin_phone'];
    $origin_address['email']           = $plugin_settings['origin_email'];

    return $origin_address;
}

function getOriginAddress ($origin_id, $product_id) {
    $origin_address = array();

    $default_origin_address = getDefaultOriginAddress();

    /* if product has all required origin attributes */
    if (checkProductOriginAttributes($product_id)) {
        $origin_address['first_name']          = get_post_meta( $product_id, 'origin_first_name', true);
        $origin_address['last_name']           = get_post_meta( $product_id, 'origin_last_name', true);
        $origin_address['street1']         = get_post_meta( $product_id, 'origin_address', true);
        $origin_address['street2']         = (get_post_meta( $product_id, 'origin_address2', true)) ? get_post_meta( $product_id, 'origin_address2', true) : '';
        $origin_address['state']           = get_post_meta( $product_id, 'origin_state', true);
        $origin_address['city']        = get_post_meta( $product_id, 'origin_city', true);
        $origin_address['zip']         = get_post_meta( $product_id, 'origin_zipcode', true);
        $origin_address['origin_location_type']           = get_post_meta( $product_id, 'origin_location_type', true);
        $origin_address['phone_number']           = get_post_meta( $product_id, 'origin_phone', true);
        $origin_address['email']           = get_post_meta( $product_id, 'origin_email', true);

        return $origin_address;
    }

    /* if product has origin id, get origin attributes from origin */
    if($origin_id) {

        $origin_address['first_name']          = get_post_meta( $origin_id, 'origin_first_name', true);
        $origin_address['last_name']           = get_post_meta( $origin_id, 'origin_last_name', true);
        $origin_address['street1']         = get_post_meta( $origin_id, 'origin_address', true);
        $origin_address['street2']         = (get_post_meta( $origin_id, 'origin_address2', true)) ? get_post_meta( $origin_id, 'origin_address2', true) : '';
        $origin_address['state']           = get_post_meta( $origin_id, 'origin_state', true);
        $origin_address['city']        = get_post_meta( $origin_id, 'origin_city', true);
        $origin_address['zip']         = get_post_meta( $origin_id, 'origin_zipcode', true);
        $origin_address['origin_location_type']           = get_post_meta( $origin_id, 'origin_location_type', true);
        $origin_address['phone_number']           = get_post_meta( $origin_id, 'origin_phone', true);
        $origin_address['email']           = get_post_meta( $origin_id, 'origin_email', true);

        return $origin_address;
    }

    return $default_origin_address;
}

function getGroupedItemsByOrigin($items) {
    $tmp = array();
    foreach($items as $item) {
        $tmp[$item['product_origin']][] = $item;
    }
    return $tmp;
}

function getGroupedItemsByZip($items) {
    $tmp = array();
    foreach($items as $item) {
        $tmp[$item['zip_code']][] = $item;
    }
    return $tmp;
}

/*
1. If carrier_type = "Small Parcel" display name should be what's included in field [Service] (example: Ground)
2. If carrier_type = "Blanket Wrap" display name should be:
"Standard White Glove Delivery (3-6 weeks)"
3. If carrier_type = "LTL","3PL","Intermodal" AND delivery field inside [details][price]=$0.00 display name should be:
"Curbside delivery (1-2 weeks)"
4. If carrier_type = "LTL","3PL" "Intermodal" AND delivery field inside [details][price] > $0.00 display name should be:
"Expedited White Glove Delivery (2-3 weeks)"
*/
function _getServiceName($object) {

    if ( $object->shipping->service == "Small Parcel" ) {
        return $object->shipping->service;
    }

    if ( $object->shipping->carrier_type == "Blanket Wrap" ) {
        return "Standard White Glove Delivery (3-6 weeks)";
    }

    if ( ( ( $object->shipping->carrier_type == "LTL" ) || ( $object->shipping->carrier_type == "3PL" ) || ( $object->shipping->carrier_type == "Intermodal" ) ) && ($object->delivery->price == 0) ) {
        return "Curbside delivery (1-2 weeks)";
    }

    if ( ( ( $object->shipping->carrier_type == "LTL" ) || ( $object->shipping->carrier_type == "3PL" ) || ( $object->shipping->carrier_type == "Intermodal" ) ) && ($object->delivery->price > 0) ) {
        return "Expedited White Glove Delivery (2-3 weeks)";
    }

    if ( $object->shipping->carrier_type == "Home Delivery" ) {
        return "Home Delivery - " . $object->shipping->service . " (1-2 weeks)";
    }

    return $object->shipping->service;
}

function checkProductOriginAttributes( $product_id ) {
    $required_origin_attributes = array('origin_first_name', 'origin_last_name', 'origin_address', 'origin_state', 'origin_city', 'origin_zipcode', 'origin_location_type', 'origin_phone', 'origin_email' );

    foreach ($required_origin_attributes as $origin_attribute) {
        $origin_attribute_value = get_post_meta( $product_id, $origin_attribute, true);
        if(empty($origin_attribute_value)) {
            return false;
        }
    }

    return true;

}

function getPrice($object) {
    return $object->shipping->price + $object->packing->price + $object->pickup->price + $object->delivery->price + $object->insurance->price;
}

function discountPercentage($price) {

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
    $discountPercentage = $plugin_settings['discount_percentage'];

    if(!empty($discountPercentage)) {
        $price = $price + ($price * ($discountPercentage/100));
    }

    return $price;
}

function discountFixed($price) {

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
    $discountFixed = $plugin_settings['discount_fixed'];

    if(!empty($discountFixed)) {
        $price = $price + ($discountFixed);
    }

    return $price;
}

function getDiscountShippingPrice($price) {
    $price = discountPercentage($price);
    $price = discountFixed($price);

    if($price <= 0) {
        return 0;
    }

    return $price;
}

function getOriginalShipHawkShippingPrice($shipping_code, $shipping_method_price) {
    $result = strpos($shipping_code, $shipping_method_price);
    return $result;
}

/*
 * log function
 *  */
function wlog($var, $file_name = 'wlog.php') {
    $var_str = var_export($var, true);
    $var = "<?php\n\n\$ = $var_str;\n\n?>";
    $file = plugin_dir_path( __FILE__ ) . '/log/' . $file_name;
    //file_put_contents($file, $var);
    $f = fopen($file, 'a+');
    fwrite($f, $var);
    fclose($f);
    chmod($file, 0777);
}