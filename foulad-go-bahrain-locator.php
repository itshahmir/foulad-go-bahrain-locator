<?php

/**
 * Plugin Name: Foulad Go - Bahrain Locator
 * Description: Foulad GO - Bahrain is a plugin designed to seamlessly convert physical addresses into precise latitude and longitude coordinates on a map. This tool streamlines the process of pinpointing locations, facilitating navigation, logistics, and various location-based services with accuracy and efficiency.
 * Author: Brushed Arrow
 * Version: 1.0.0
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: foulad-go-bahrain-locator
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

define("foulad_go_path", plugin_dir_path(__FILE__));
define("foulad_go_url", plugin_dir_url(__FILE__));

require plugin_dir_path(__FILE__).'includes/util.php'; // Include Composer autoload

// Save location to session
add_action('woocommerce_checkout_process', 'custom_checkout_save_location_to_session');
function custom_checkout_save_location_to_session()
{
    if (!is_checkout()) {
        return;
    }

    $chosen_countries = WC()->countries->get_shipping_countries();
    $selected_country = WC()->checkout->get_value('shipping_country');

    if (in_array('BH', array_keys($chosen_countries)) || $selected_country == 'BH') {
        $road = preg_replace('/[^0-9]/', '', WC()->checkout->get_value('shipping_address_1'));
        $block = preg_replace('/[^0-9]/', '', WC()->checkout->get_value('shipping_address_2'));
        $building = preg_replace('/[^0-9]/', '', WC()->checkout->get_value('shipping_postcode'));

        if ($road && $block && $building) {
            $location = getLocation($building, $road, $block);
            if ($location) {
                WC()->session->set('location', json_encode($location));
            }else {
                wc_add_notice(__('Please make sure your Building, road & block number are correct', 'custom-validation') , 'error');

            }
        }
    }
}

// Add location meta to order
add_action('woocommerce_checkout_create_order', 'custom_checkout_add_location_to_order_meta_1', 10, 2);
function custom_checkout_add_location_to_order_meta_1($order, $data)
{
    $location = WC()->session->get('location');
    if ($location) {
        $order->update_meta_data('FGLocator', $location);
        WC()->session->__unset('location'); // Unset the session value

    }
}

// Display location meta in order
add_action('woocommerce_admin_order_data_after_billing_address', 'custom_checkout_display_location_meta_in_order', 10, 1);
function custom_checkout_display_location_meta_in_order($order)
{
    $location = $order->get_meta('FGLocator');
    if ($location) {

        $location = json_decode($location, true);

        echo '<p><strong>FG Locator</strong> <br>
        
        <strong>Latitiude : </strong> '. $location["Lat"]. '<br>
         <strong>Longitude : </strong> ' . $location["Long"] . '
        </p>';
    }
}

// Add location meta to order
add_action('woocommerce_checkout_create_order', 'custom_checkout_add_location_to_order_meta', 10, 2);
add_action('woocommerce_process_shop_order_meta', 'custom_checkout_add_location_to_order_meta', 10, 2); // Hook into admin order update
function custom_checkout_add_location_to_order_meta($order_id, $posted_data)
{
    $order = new WC_Order($order_id);
    error_log('Init Request ' . $order_id. json_encode($posted_data));

    if (!$order) {
        return; // Exit if order is not valid
    }
    
    $road = isset($_POST["_shipping_address_1"]) ? $_POST["_shipping_address_1"] : false;
    $block = isset($_POST["_shipping_address_2"]) ? $_POST["_shipping_address_2"] : false;
    $building = isset($_POST["_shipping_postcode"]) ? $_POST["_shipping_postcode"] : false;

    if (isset($_POST["_shipping_country"]) && $_POST["_shipping_country"] == "BH") {
        if ($road && $block && $building) {

            $road = preg_replace('/[^0-9]/', '', $road);
            $block = preg_replace('/[^0-9]/', '', $block);
            $building = preg_replace('/[^0-9]/', '', $building);

            // Assuming getLocation is defined elsewhere
            $location = getLocation($building, $road, $block);

            if ($location) {
                $order->update_meta_data('FGLocator', json_encode($location));
                $order->save(); // Save the order to persist changes
            } else {
                // Handle the case where location couldn't be retrieved
                error_log('Failed to get location for order ID: ' . $order_id);
            }
        } else {
            // Handle the case where address components are missing
            error_log('Missing address components for order ID: ' . $order_id. " " . $road. " " . $block." ".$building." ".json_encode($posted_data).$order->get_shipping_country() );
        }
    } else {
        error_log('shipping address components for order ID: ' . $order_id . " " . $road . " " . $block . " " . $building . " " . json_encode($posted_data) . $order->get_shipping_country());
    }
}




/**
 * Add custom data to WC API shop order response
 * Overriding "$object" here with $order so it's easier to access its properties
 */
function my_wc_rest_prepare_order($response, $order, $request)
{
    if (empty($response->data))
        return $response;

    $order_id = $order->get_id();
    error_log('Init API Request ' . $order_id);

    custom_checkout_add_location_to_order_meta_api($order_id, array());
    return $response;
}
add_filter('woocommerce_rest_prepare_shop_order_object', 'my_wc_rest_prepare_order', 10, 3);


function custom_checkout_add_location_to_order_meta_api($order_id, $posted_data)
{
    $order = new WC_Order($order_id);
    error_log('Init Request ' . $order_id);

    if (!$order) {
        return; // Exit if order is not valid
    }

    $chosen_countries = WC()->countries->get_shipping_countries();
    $selected_country = $order->get_shipping_country();

    if (in_array('BH', array_keys($chosen_countries)) || $selected_country == 'BH') {

        $road = preg_replace('/[^0-9]/', '', $order->get_shipping_address_1());
        $block = preg_replace('/[^0-9]/', '', $order->get_shipping_address_2());
        $building = preg_replace('/[^0-9]/', '', $order->get_shipping_postcode());

        if ($road && $block && $building) {
            // Assuming getLocation is defined elsewhere
            $location = getLocation($building, $road, $block);

            if ($location) {
                $order->update_meta_data('FGLocator', json_encode($location));
                $order->save(); // Save the order to persist changes
            } else {
                // Handle the case where location couldn't be retrieved
                error_log('Failed to get location for order ID: ' . $order_id);
            }
        } else {
            // Handle the case where address components are missing
            error_log('Missing address components for order ID: ' . $order_id . " " . $road . " " . $block . " " . $building . " " . json_encode($posted_data) . $order->get_shipping_country());
        }
    }
}
