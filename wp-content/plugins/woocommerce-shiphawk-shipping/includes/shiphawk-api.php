<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function getShiphawkRate($from_zip, $to_zip, $items, $rate_filter,$from_type) {

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];

    $url_api_rates = $api_url . 'rates?api_key=' . $api_key;
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

function toBook($order_id, $rate_id, $order, $_items)
{
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];
    $url_api = $api_url . 'shipments?api_key=' . $api_key;

    $order_email = $order->billing_email;
    if ($plugin_settings['receipts_to'] == 'administrator') {
        $order_email = $plugin_settings['shiphawk_admin_email'];
    }

    // get origin id from first item
    $origin_id = $_items[0]['product_origin'];


    $origin_address = getOriginAddress($origin_id, $_items[0]['xid']);

    $curl = curl_init();

    $next_bussines_day = date('Y-m-d', strtotime('now +1 Weekday'));
    $items_array = array(
        'rate_id'=> $rate_id,
        'order_email'=> $order_email,
        'xid'=>$order_id,
        'origin_address' =>
            $origin_address,
        'destination_address' =>
            array(
                'first_name' => $order->shipping_first_name,
                'last_name' => $order->shipping_last_name,
                'address_line_1' => $order->shipping_address_1,
                'address_line_2' => $order->shipping_address_2,
                'phone_num' => $order->billing_phone,
                'city' => $order->shipping_city,
                'state' => $order->shipping_state,
                'zipcode' => $order->shipping_postcode,
                'email' => $order->billing_email
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
                'zipcode' => $order->billing_postcode,
                'email' => $order->billing_email
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

    $url_api = $api_url . 'items/search?q=' . $search_tag . '&api_key=' . $api_key;

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
            $responce_array[$el->id] = $el->name.' ('.$el->category_name.')';
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

// get BOL pdf
add_action( 'wp_ajax_get_bolpdf', 'get_bolpdf_callback' );
function get_bolpdf_callback() {

    $book_id = $_POST['book_id'];

    $upload_dir = wp_upload_dir();

    $responce['bol_url']= '';
    $responce['shiphawk_error'] = '';

    $responce_BOL = getBOLpdf($book_id);

    if ($responce_BOL->url) {
        //$io = new Varien_Io_File();
        //$path_to_save_bol_pdf = Mage::getBaseDir('media'). DS .'shiphawk'. DS .'bol';
        $path_to_save_bol_pdf = $upload_dir['path'];
        $BOLpdf = $path_to_save_bol_pdf . '/' .  $book_id . '.pdf';

        if (file_get_contents($BOLpdf)) {
            $responce['bol_url'] = $upload_dir['url'] . '/' . $book_id . '.pdf';
        }else{
            file_put_contents($BOLpdf, file_get_contents($responce_BOL->url));
            //$responce = file_get_contents($BOLpdf);
            $responce['bol_url'] = $responce['bol_url'] = $upload_dir['url'] . '/' . $book_id . '.pdf';
        }

    }else{
        $responce['shiphawk_error'] = $responce_BOL->error;
    }

    //$responce['shiphawk_error'] = 'error';

    echo json_encode($responce);

    wp_die();
}

add_action( 'woocommerce_order_action_shiphawk_book_manual', 'process_shiphawk_book_manual' );

/* Manual Book */
function process_shiphawk_book_manual( $order ) {

    $order_id = $order->id;

    //check if Book Id exist
    $ship_hawk_book_id = get_post_meta( $order->id, 'ship_hawk_book_id', true );

    if (!empty($ship_hawk_book_id)) {
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
            'value' => getShipHawkItemValue($products['product_id'],$product_price),
            'quantity' => $products['qty'],
            'packed' => getIsPacked($products['product_id']),
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
    $book_ids = array();

    $shipping_code = (string) get_post_meta( $order_id, '_shipping_code_original_amount', true );

    foreach ($grouped_items_by_origin as $origin_id=>$_items) {
        // PER PRODUCT
        $from_zip = (get_post_meta( $origin_id, 'origin_zipcode', true )) ? get_post_meta( $origin_id, 'origin_zipcode', true ) : $plugin_settings['origin_zipcode'];

        // PER PRODUCT
        $from_type = (get_post_meta( $origin_id, 'origin_location_type', true )) ? get_post_meta( $origin_id, 'origin_location_type', true ) : $plugin_settings['origin_location_type'];

        if ($is_multiorigin) $rate_filter = 'best';


        $ship_rates = getShiphawkRate($from_zip, $to_zip, $_items, $rate_filter, $from_type);
        //if ($shipping_rate->summary->service == $shipping_method) {

        foreach ($ship_rates as $shipping_rate) {
            if (!$is_multiorigin) {

                //if (round(getPrice($shipping_rate),3) == round($shipping_amount,3)) {
                // check price
                $shipping_price_from_rate = (string) round(getPrice($shipping_rate),2);
                wlog($shipping_price_from_rate, 'ssssss5');

                if (getOriginalShipHawkShippingPrice($shipping_code, $shipping_price_from_rate)) {
                    update_post_meta( $order_id, 'ship_hawk_order_id', $shipping_rate->id);

                    $book_id = toBook($order_id, $shipping_rate->id, $order, $_items);
                    wlog($book_id, 'ssssss3');

                    if($book_id->details->id) {
                        //update_post_meta( $order_id, 'ship_hawk_book_id', $book_id->details->id);
                        $order->add_order_note( __( 'Book Id: ' . $book_id->details->id, 'woocommerce' ) );
                        $book_ids[] = $book_id->details->id;
                    }

                    if ($book_id->details->id) {
                        SubscribeToTrackInfo($book_id->details->id, $order);
                    }else{
                        $order->add_order_note( __( 'No ShipHawk id ', 'woocommerce' ) );
                    }

                }
            }else {
                update_post_meta( $order_id, 'ship_hawk_order_id', $shipping_rate->id);

                $book_id = toBook($order_id, $shipping_rate->id, $order, $_items);

                if($book_id->details->id) {
                    //update_post_meta( $order_id, 'ship_hawk_book_id', $book_id->details->id);
                    $order->add_order_note( __( 'Book Id: ' . $book_id->details->id, 'woocommerce' ) );
                    $book_ids[] = $book_id->details->id;
                }

                if ($book_id->details->id) {
                    SubscribeToTrackInfo($book_id->details->id, $order);
                }else{
                    $order->add_order_note( __( 'No ShipHawk id ', 'woocommerce' ) );
                    if($book_id->error) {
                        $order->add_order_note( __( $book_id->error, 'woocommerce' ) );
                    }

                }
            }
        }
    }

    if(count($book_ids)>0) {
        add_post_meta( $order_id, 'ship_hawk_book_id', $book_ids, true);
    }

}

function SubscribeToTrackInfo($ship_hawk_book_id, $order) {

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];

    $subscribe_url = $api_url . 'shipments/' . $ship_hawk_book_id . '/tracking?api_key=' . $api_key;

    //TODO subscribe url ?
    $callback_url = 'http://www.woohawk.devigor.wdgtest.com/wp-content/plugins/woocommerce-shiphawk-shipping/interface.php';

    $items_array = array(
        'callback_url'=> $callback_url
    );

    $curl = curl_init();
    $items_array =  json_encode($items_array);

    curl_setopt($curl, CURLOPT_URL, $subscribe_url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
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

    //get and set shipment status
    $status_response = getShipmentStatus($ship_hawk_book_id);

    if($status_response->status) {
        add_post_meta($order->id, 'current_status_of_shipment', $status_response->status);
        $status_message = $ship_hawk_book_id . ' - ' . 'status has been changed to: ' . $status_response->status;
        $order->add_order_note($status_message);

    }

}

/*
Retrieve the current status of a shipment, $shipment_id - book id
*/
function getShipmentStatus($shipment_id) {

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');
    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];

    $status_url = $api_url . 'shipments/' . $shipment_id . '/tracking?api_key=' . $api_key;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $status_url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);

    return $arr_res;

    //{"id":1016671,"status":"ordered","status_updates":[],"tracking_number":null}

}

/* get url to BOL pdf from shiphawk */
function getBOLpdf($shipment_id) {

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];

    $bol_url = $api_url . 'shipments/' . $shipment_id . '/bol?api_key=' . $api_key;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $bol_url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);

    return $arr_res;
}