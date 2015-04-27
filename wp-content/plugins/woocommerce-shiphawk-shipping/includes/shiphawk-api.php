<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function getShiphawkRate($from_zip, $to_zip, $items, $rate_filter,$from_type) {

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];
    //$url_api_rates = 'https://sandbox.shiphawk.com/api/v1/rates/full?api_key=3331b35952ec7d99338a1cc5c496b55c';

    //$url_api_rates = $api_url . 'rates/full?api_key=' . $api_key;
    $url_api_rates = $api_url . 'rates/standard?api_key=' . $api_key;
    $curl = curl_init();

    $items_array = array(
        'from_zip'=> $from_zip,
        'to_zip'=> $to_zip,
        'rate_filter' => $rate_filter,
        'items' => $items,
        'from_type' => $from_type
    );

    $items_array =  json_encode($items_array);

    curl_setopt($curl, CURLOPT_URL, $url_api_rates);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $items_array);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($items_array)
        )
    );

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);
    curl_close($curl);
    return $arr_res;
}

function toBook($order_id, $rate_id, $order, $_items = array())
{
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];
    $url_api = $api_url . 'shipments/book?api_key=' . $api_key;

    $order_email = $order->billing_email;
    if ($plugin_settings['receipts_to'] == 'administrator') {
        $order_email = $plugin_settings['shiphawk_admin_email'];
    }

    // get origin id from first item
    $origin_id = $_items[0]['product_origin'];
    $default_origin_address = getDefaultOriginAddress ();

    $origin_address = getOriginAddress($origin_id);
    $curl = curl_init();

    $next_bussines_day = date('Y-m-d', strtotime('now +1 Weekday'));
    $items_array = array(
        'rate_id'=> $rate_id,
        'order_email'=> $order_email,
        'xid'=>$order_id,
        'origin_address' =>
            array(
                'first_name' => ($origin_address['first_name']) ? $origin_address['first_name'] : $default_origin_address['first_name'],
                'last_name' => ($origin_address['last_name']) ? $origin_address['last_name'] : $default_origin_address['last_name'],
                'address_line_1' => ($origin_address['origin_address']) ? $origin_address['origin_address'] : $default_origin_address['origin_address'],
                'address_line_2' => ($origin_address['origin_address2']) ? $origin_address['origin_address2'] : '',
                'phone_num' => ($origin_address['origin_phone']) ? $origin_address['origin_phone'] : $default_origin_address['origin_phone'],
                'city' => ($origin_address['origin_city']) ? $origin_address['origin_city'] : $default_origin_address['origin_city'],
                'state' => ($origin_address['origin_state']) ? $origin_address['origin_state'] : $default_origin_address['origin_state'],
                'zipcode' => ($origin_address['origin_zipcode']) ? $origin_address['origin_zipcode'] : $default_origin_address['origin_zipcode']
            ),
        'destination_address' =>
            array(
                'first_name' => $order->shipping_first_name,
                'last_name' => $order->shipping_last_name,
                'address_line_1' => $order->shipping_address_1,
                'address_line_2' => $order->shipping_address_2,
                'phone_num' => $order->shipping_phone,
                'city' => $order->shipping_city,
                'state' => $order->shipping_state,
                'zipcode' => $order->shipping_postcode
            ),
        'billing_address' =>
            array(
                'first_name' => $order->billing_first_name,
                'last_name' => $order->billing_last_name,
                'address_line_1' => $order->billing_address_1,
                'address_line_2' => $order->billing_address_2,
                'phone_num' => $order->billing_phone,
                'city' => $order->billing_city,
                'state' => $order->billing_state,
                'zipcode' => $order->billing_postcode
            ),
        'pickup' =>
            array(
                array(
                    'start_time' => $next_bussines_day.'T04:00:00.645-07:00',
                    'end_time' => $next_bussines_day.'T20:00:00.645-07:00',
                ),
                array(
                    'start_time' => $next_bussines_day.'T04:00:00.645-07:00',
                    'end_time' => $next_bussines_day.'T20:00:00.646-07:00',
                )
            ),

        'accessorials' => array()

    );

    $items_array =  json_encode($items_array);

    curl_setopt($curl, CURLOPT_URL, $url_api);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $items_array);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($items_array)
        )
    );

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);

    curl_close($curl);
    return $arr_res;
}

// Suggest product item type from ShipHawk
add_action( 'wp_ajax_my_action', 'my_action_callback' );
function my_action_callback() {
    $search_tag = $_POST['type_item'];
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];

    $url_api = $api_url . 'items/search/' . $search_tag . '?api_key=' . $api_key;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $url_api,
        CURLOPT_POST => false
    ));

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);
    $responce_array = array();
    $responce = array();

    if(($arr_res->error) || ($arr_res['error'])) {
        $responce_html = '';
        $responce['shiphawk_error'] = $arr_res->error;
    }else{
        foreach ((array) $arr_res as $el) {
            $responce_array[$el->id] = $el->name.' ('.$el->category.')';
        }

        $responce_html="<ul>";

        foreach($responce_array as $key=>$value) {
            $responce_html .='<li class="type_link" id='.$key.' onclick="setItemid(this)" >'.$value.'</li>';
        }

        $responce_html .="</ul>";
    }

    curl_close($curl);

    $responce['responce_html'] = $responce_html;

    echo json_encode($responce);
    wp_die();
}

// Suggest product item type from ShipHawk
add_action( 'wp_ajax_set_item_type', 'set_item_type_callback' );
function set_item_type_callback() {

    $type_item = $_POST['type_item'];
    $type_item_value = $_POST['type_item_value'];
    $post_id = $_POST['post_id'];

    update_post_meta($post_id, 'shiphawk_product_item_type', $type_item );
    update_post_meta($post_id, 'shiphawk_product_item_type_value', $type_item_value );

    wp_die();
}

add_action( 'woocommerce_order_action_shiphawk_book_manual', 'process_shiphawk_book_manual' );

/* Manual Book */
function process_shiphawk_book_manual( $order ) {

    $order_id = $order->id;

    //check if Book Id exist
    $ship_hawk_book_id = get_post_meta( $order->id, 'ship_hawk_book_id', true );
    if ($ship_hawk_book_id) {
        return;
    }

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
    $shipping_method = $order->get_shipping_method();
    $shipping_amount = $order->get_total_shipping();

    $items = array();
    foreach ($order->get_items() as $products) {

        $pa_shiphawk_item_type_value = get_post_meta( $products['product_id'], 'shiphawk_product_item_type_value', true );
        $woocommerce_dimension_unit = get_option('woocommerce_dimension_unit');
        $woocommerce_weight_unit = get_option('woocommerce_weight_unit');
        $product_origin = get_post_meta( $products['product_id'], 'shipping_origin', true );
        $product_length = round(convertToInchLbs(get_post_meta( $products['product_id'], '_length', true), $woocommerce_dimension_unit), 2);
        $product_width = round(convertToInchLbs(get_post_meta( $products['product_id'], '_width', true), $woocommerce_weight_unit), 2);
        $product_height = round(convertToInchLbs(get_post_meta( $products['product_id'], '_height', true), $woocommerce_dimension_unit), 2);
        $product_weight = round(convertToInchLbs(get_post_meta( $products['product_id'], '_weight', true), $woocommerce_weight_unit), 2);
        $product_price = (get_post_meta( $products['product_id'], '_sale_price', true)) ? (get_post_meta( $products['product_id'], '_sale_price', true)) : get_post_meta( $products['product_id'], '_regular_price', true);
        $items[] = array(
            'width' => $product_width,
            'length' => $product_length,
            'height' => $product_height,
            'weight' => $product_weight,
            'value' => $product_price,
            'quantity' => $products['qty'],
            'packed' => $plugin_settings['packing'],
            'id' => $pa_shiphawk_item_type_value,
            'product_id'=> $products['product_id'],
            'xid'=> $products['product_id'],
            'product_origin' => $product_origin
        );
    }

    $grouped_items_by_origin = getGroupedItemsByOrigin($items);
    $is_multiorigin = (count($grouped_items_by_origin) > 1) ? true : false;

    $to_zip = $order->shipping_postcode;

    $rate_filter = $plugin_settings['rate_filter'];//consumer best
    $from_type  = $plugin_settings['origin_location_type'];


    foreach ($grouped_items_by_origin as $origin_id=>$_items) {
        $from_zip = (get_post_meta( $origin_id, 'origin_zipcode', true )) ? get_post_meta( $origin_id, 'origin_zipcode', true ) : $plugin_settings['origin_zipcode'];

        if ($is_multiorigin) $rate_filter = 'best';

        $ship_rates = getShiphawkRate($from_zip, $to_zip, $_items, $rate_filter, $from_type);

        //if ($shipping_rate->summary->service == $shipping_method) {
        foreach ($ship_rates as $shipping_rate) {
            if (!$is_multiorigin) {
                // check price
                if ($shipping_rate->summary->price == $shipping_amount) {
                    update_post_meta( $order_id, 'ship_hawk_order_id', $shipping_rate->id);

                    $book_id = toBook($order_id, $shipping_rate->id, $order, $_items);

                    if($book_id->shipment_id) {
                        update_post_meta( $order_id, 'ship_hawk_book_id', $book_id->shipment_id);
                        $order->add_order_note( __( 'Book Id: ' . $book_id->shipment_id, 'woocommerce' ) );
                    }

                    if ($book_id->shipment_id) {
                        SubscribeToTrackInfo($book_id->shipment_id, $order);
                    }else{
                        $order->add_order_note( __( 'No ShipHawk id ', 'woocommerce' ) );
                    }

                }
            }else {
                update_post_meta( $order_id, 'ship_hawk_order_id', $shipping_rate->id);

                $book_id = toBook($order_id, $shipping_rate->id, $order, $_items);

                if($book_id->shipment_id) {
                    update_post_meta( $order_id, 'ship_hawk_book_id', $book_id->shipment_id);
                    $order->add_order_note( __( 'Book Id: ' . $book_id->shipment_id, 'woocommerce' ) );
                }

                if ($book_id->shipment_id) {
                    SubscribeToTrackInfo($book_id->shipment_id, $order);
                }else{
                    $order->add_order_note( __( 'No ShipHawk id ', 'woocommerce' ) );
                }
            }
        }

    }

}

function SubscribeToTrackInfo($ship_hawk_book_id, $order) {

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];

    $subscribe_url = $api_url . 'shipments/' . $ship_hawk_book_id . '/subscribe?api_key=' . $api_key;
    //TODO subscribe url = http://www.woohawk.devigor.wdgtest.com/wp-content/plugins/woocommerce-shiphawk-shipping/interface.php?api_key=344
    $callback_url = 'http://www.woohawk.devigor.wdgtest.com/wp-content/plugins/woocommerce-shiphawk-shipping/interface.php';

    $items_array = array(
        'callback_url'=> $callback_url
    );

    $curl = curl_init();
    $items_array =  json_encode($items_array);

    curl_setopt($curl, CURLOPT_URL, $subscribe_url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $items_array);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($items_array)
        )
    );

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);

    if (!empty($arr_res) && (is_object($arr_res))) {
        // add order note
        if ($arr_res->error) {
            $order->add_order_note( __( 'Tracking error : ' . $arr_res->error, 'woocommerce' ) );
        }else{
            $order->add_order_note( __( 'Tracking id: ' . $arr_res->id . ', ' . $arr_res->resource_name . ',  created at: ' . $arr_res->created_at , 'woocommerce' ) );
        }


    }
    curl_close($curl);
}