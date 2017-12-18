<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function convertToInchLbs($dimension, $dimension_unit)
{
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

function _getServiceName($object)
{
    return $object->carrier . ' - ' . $object->service_name;
}

function statusMapping($status)
{
    switch ($status) {
        case 'cancelled':
            return 'cancelled';
        case 'complete':
            return 'shipped';
        case 'processing':
            return 'partially_shipped';
        default:
            return 'new';
    }
}

function log_me($message)
{
    if (defined('WP_DEBUG') && true === WP_DEBUG) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}