<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function getShiphawkRate($rateRequest)
{

    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];

    $url_api_rates = $api_url . 'rates?api_key=' . $api_key;
    $curl = curl_init();
    log_me($rateRequest);

    $items_array = json_encode($rateRequest);

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

function pushOrder($orderRequest)
{
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];
    $params = http_build_query(['api_key' => $api_key]);

    $url_api_rates = $api_url . 'orders?' . $params;
    $curl = curl_init();

    $jsonOrderRequest = json_encode($orderRequest);

    curl_setopt($curl, CURLOPT_URL, $url_api_rates);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonOrderRequest);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonOrderRequest)
        )
    );

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);
    curl_close($curl);
    return $arr_res;
}

function updateStatusOrder($status, $order_id)
{
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];
    $params = http_build_query(['api_key' => $api_key]);

    $url_api_rates = $api_url . 'orders/' . $order_id . '/cancelled?' . $params;
    $curl = curl_init();

    $orderRequest = json_encode(
        array(
            'source_system' =>              'Woocommerce',
            'source_system_id' =>           $order_id,
            'source_system_processed_at' => '',
            'canceled_at' =>                date('Y-m-d h:i A'),
            'status' =>                     statusMapping($status),
        )
    );

    curl_setopt($curl, CURLOPT_URL, $url_api_rates);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $orderRequest);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($orderRequest)
        )
    );

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);

    curl_close($curl);
    return $arr_res;
}

function checkApiUser()
{
    $plugin_settings = get_option('woocommerce_shiphawk_shipping_settings');

    $api_key = $plugin_settings['api_key'];
    $api_url = $plugin_settings['gateway_mode'];
    $params = http_build_query(['api_key' => $api_key]);

    $url_api_rates = $api_url . 'user?' . $params;
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url_api_rates);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $resp = curl_exec($curl);
    $arr_res = json_decode($resp);

    curl_close($curl);
    return $arr_res;
}


/* Manual Import ShipHawk Orders */

add_action('woocommerce_order_action_shiphawk_order_import', 'process_shiphawk_order_import');

function process_shiphawk_order_import($order)
{
    global $woocommerce;

    $order_items = $order->get_items();

    $itemsRequest = getItems($order_items);

    $orderRequest =
        array(
            'order_number' =>               $order->get_id(),
            'source' =>                     'woocommerce',
            'source_system' =>              'wordpress',
            'source_system_id' =>           $order->get_id(),
            'source_system_processed_at' => $order->get_date_created(),
            'requested_rate_id' =>          get_post_meta($order->get_id(), 'shiphawk_rate_id', true),
            'requested_shipping_details' => $order->get_shipping_method(),
            'origin_address' =>             getStoreAddress(),
            'destination_address' =>        getCustomerAddress($order),
            'order_line_items' =>           $itemsRequest,
            'total_price' =>                $order->get_total(),
            'shipping_price' =>             $order->get_shipping_total(),
            'tax_price' =>                  $order->get_line_tax($order),
            'items_price' =>                $order->get_subtotal(),
            'status' =>                     statusMapping($order->get_status())

    );

    $import_response = pushOrder($orderRequest);

    if($import_response) {
        if (is_object($import_response) && (property_exists($import_response, 'error'))) {
            log_me($import_response->error);
            $order->add_order_note($import_response->error);
        }else{
            $order->add_order_note('Order successfully imported to ShipHawk');
        }
    }
}

function getCustomerAddress($order)
{
    $state_name     = _getStateFromCode($order->get_billing_country(), $order->get_billing_state());
    $country_name   = _getCountryFromCode($order->get_billing_country());

    return array(
        'name' =>           $order->get_billing_first_name() . ' '
                            . $order->get_billing_last_name(),
        'company' =>        $order->get_billing_company(),
        'street1' =>        $order->get_billing_address_1(),
        'street2' =>        $order->get_billing_address_2(),
        'phone_number' =>   $order->get_billing_phone(),
        'city' =>           $order->get_billing_city(),
        'state' =>          $state_name,
        'country' =>        $country_name,
        'zip' =>            $order->get_billing_postcode(),
        'email' =>          $order->get_billing_email(),
        'is_residential' => 'true'
    );
}

function getStoreAddress()
{
    $default_country        = get_option('woocommerce_default_country', '');
    $state_code             = '';
    $country_code           = '';

    if ($default_country) {
        $state_country      = explode(':', $default_country);
        $state_code         = array_pop($state_country);
        $country_code       = array_shift($state_country);
    }

    return array(
        'name' =>           get_option('blogname', ''),
        'phone_number' =>   '',
        'street1' =>        get_option('woocommerce_store_address', ''),
        'street2' =>        get_option('woocommerce_store_address_2', ''),
        'city' =>           get_option('woocommerce_store_city', ''),
        'state' =>          _getStateFromCode($country_code, $state_code),
        'country' =>        _getCountryFromCode($country_code),
        'zip' =>            get_option('woocommerce_store_postcode', ''),
    );
}

function getItems($order_items)
{
    $itemsRequest = array();
    $woocommerce_dimension_unit = get_option('woocommerce_dimension_unit');
    $woocommerce_weight_unit = get_option('woocommerce_weight_unit');

    foreach ($order_items as $item) {
         $_pf = new WC_Product_Factory();
         $_product = $_pf->get_product($item->get_product_id());
         $item_weight = round(convertToInchLbs($_product->get_weight(), $woocommerce_weight_unit), 2);;
         $itemsRequest[] = array(
                'name' => $item->get_name(),
                'sku' => $_product->get_sku(),
                'quantity' => $item->get_quantity(),
                'value' => $_product->get_price(),
                'length' => round(convertToInchLbs($_product->get_length(), $woocommerce_dimension_unit), 2),
                'width' => round(convertToInchLbs($_product->get_width(), $woocommerce_dimension_unit), 2),
                'height' => round(convertToInchLbs($_product->get_height(), $woocommerce_dimension_unit), 2),
                'weight' => $item_weight <= 70 ? $item_weight * 16 : $item_weight,
                'can_ship_parcel' => true,
                'item_type' => $item_weight <= 70 ? 'parcel' : 'handling_unit',
                'handling_unit_type' => $item_weight <= 70 ? '' : 'box',
                'source_system_id' => $item->get_product_id()
         );
    }

    return $itemsRequest;
}

function _getCountryFromCode($country_code) {

    if(empty($country_code)) return '';

    $countries_obj = new WC_Countries();
    $countries_array = $countries_obj->get_countries();
    $country_name = $countries_array[$country_code];

    return $country_name;
}

function _getStateFromCode($country_code, $state_code) {

    if(empty($country_code) || empty($state_code)) return '';

    $countries_obj = new WC_Countries();
    $country_states_array = $countries_obj->get_states();
    $state_name = $country_states_array[$country_code][$state_code];

    return $state_name;
}