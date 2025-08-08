<?php
/**
 * Price Drop Notifier Form Handler
 *
 * Handles the form submission for price drop notifications.
 *
 * @package PriceDropNotifier
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// handle form submission 
add_action('wp_ajax_pdn_subscribe', 'pdn_handle_subscription');
add_action('wp_ajax_nopriv_pdn_subscribe', 'pdn_handle_subscription');

require_once PDN_PATH . 'includes/class-pdn-subscriber.php';

function pdn_handle_subscription() {
    // Settings
    $show_for_logged_in = get_option('pdn_display_for_logged_in', true);
    $show_for_guests = get_option('pdn_display_for_guests', true);
    $max_per_day = (int) get_option('pdn_max_notifications_per_day', 1);
    $ask_expected_price = get_option('pdn_ask_expected_price', 'yes');
    $expected_price_type = get_option('pdn_expected_price_type', 'custom');

    $email = sanitize_email($_POST['email']);
    $product_id = intval($_POST['product_id']);
    $expected_price = isset($_POST['expected_price']) ? floatval($_POST['expected_price']) : null;
    $expected_price_percentage = isset($_POST['expected_price_percentage']) ? floatval($_POST['expected_price_percentage']) : null;

    // User type restrictions
    if (is_user_logged_in() && !$show_for_logged_in) {
        echo "Subscription is disabled for logged-in users.";
        wp_die();
    }
    if (!is_user_logged_in() && !$show_for_guests) {
        echo "Subscription is disabled for guest users.";
        wp_die();
    }

    if (!is_email($email) || !$product_id) {
        echo "Invalid email or product ID.";
        wp_die();
    }

    // Notification limit per day
    global $wpdb;
    $table = $wpdb->prefix . 'price_drop_notifier';
    $today = date('Y-m-d');
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_email = %s AND product_id = %d AND DATE(created_at) = %s",
        $email, $product_id, $today
    ));
    if ($count >= $max_per_day) {
        echo sprintf(__('You can only subscribe %d time(s) per day for this product.', 'price-drop-notifier'), $max_per_day);
        wp_die();
    }

    // Handle expected price logic
    $args = [
        'email' => $email,
        'product_id' => $product_id,
        'user_id' => is_user_logged_in() ? get_current_user_id() : null // Add user ID if logged in

    ];
    if ($ask_expected_price === 'yes') {
        if ($expected_price_type === 'custom') {
            $args['desired_price'] = $expected_price;
        } elseif ($expected_price_type === 'percentage') {
            $args['desired_price_percentage'] = $expected_price_percentage;
        } else { // custom_or_percentage
            if (!empty($expected_price)) {
                $args['desired_price'] = $expected_price;
            } elseif (!empty($expected_price_percentage)) {
                $args['desired_price_percentage'] = $expected_price_percentage;
            }
        }
    }

    if (PDN_Subscriber::exists($email, $product_id, $args['user_id'])) {
        echo "You're already subscribed!";
    } else {
        $inserted = PDN_Subscriber::add_advanced($args);
        if ($inserted) {
            echo "You're subscribed!";
        } else {
            echo "Subscription failed. Please try again.";
        }
    }
    wp_die();
}
