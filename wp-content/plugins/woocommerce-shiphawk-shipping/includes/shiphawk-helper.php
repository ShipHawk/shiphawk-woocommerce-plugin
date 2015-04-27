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
    $origin_address['origin_address']         = $plugin_settings['origin_address'];
    $origin_address['origin_address2']         = $plugin_settings['origin_address_2'];
    $origin_address['origin_state']           = $plugin_settings['origin_state'];
    $origin_address['origin_city']        = $plugin_settings['origin_city'];
    $origin_address['origin_zipcode']         = $plugin_settings['origin_zipcode'];
    $origin_address['origin_location_type']           = $plugin_settings['origin_location_type'];
    $origin_address['origin_phone']           = $plugin_settings['origin_phone'];
    $origin_address['origin_email']           = $plugin_settings['origin_email'];

    return $origin_address;
}

function getOriginAddress ($origin_id) {
    $origin_address = array();

    $origin_address['first_name']          = get_post_meta( $origin_id, 'origin_first_name', true);
    $origin_address['last_name']           = get_post_meta( $origin_id, 'origin_last_name', true);
    $origin_address['origin_address']         = get_post_meta( $origin_id, 'origin_address', true);
    $origin_address['origin_address2']         = get_post_meta( $origin_id, 'origin_address2', true);
    $origin_address['origin_state']           = get_post_meta( $origin_id, 'origin_state', true);
    $origin_address['origin_city']        = get_post_meta( $origin_id, 'origin_city', true);
    $origin_address['origin_zipcode']         = get_post_meta( $origin_id, 'origin_zipcode', true);
    $origin_address['origin_location_type']           = get_post_meta( $origin_id, 'origin_location_type', true);
    $origin_address['origin_phone']           = get_post_meta( $origin_id, 'origin_phone', true);
    $origin_address['origin_email']           = get_post_meta( $origin_id, 'origin_email', true);

    return $origin_address;
}

function getGroupedItemsByOrigin($items) {
    $tmp = array();
    foreach($items as $item) {
        $tmp[$item['product_origin']][] = $item;
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
    if ( $object->summary->carrier_type == "Small Parcel" ) {
        return $object->summary->service;
    }

    if ( $object->summary->carrier_type == "Blanket Wrap" ) {
        return "Standard White Glove Delivery (3-6 weeks)";
    }

    if ( ( ( $object->summary->carrier_type == "LTL" ) || ( $object->summary->carrier_type == "3PL" ) || ( $object->summary->carrier_type == "Intermodal" ) ) && ($object->details->price->delivery == 0) ) {
        return "Curbside delivery (1-2 weeks)";
    }

    if ( ( ( $object->summary->carrier_type == "LTL" ) || ( $object->summary->carrier_type == "3PL" ) || ( $object->summary->carrier_type == "Intermodal" ) ) && ($object->details->price->delivery > 0) ) {
        return "Expedited White Glove Delivery (2-3 weeks)";
    }

    return $object->summary->service;
}
