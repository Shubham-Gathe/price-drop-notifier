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
    $email = sanitize_email($_POST['email']);
    $product_id = intval($_POST['product_id']);

    if (!is_email($email) || !$product_id) {
        echo "Invalid email or product ID.";
        wp_die();
    }

    if (PDN_Subscriber::exists($email, $product_id)) {
        echo "You're already subscribed!";
    } else {
        $inserted = PDN_Subscriber::add($email, $product_id);
        if ($inserted) {
            echo "You're subscribed!";
        } else {
            echo "Subscription failed. Please try again.";
        }
    }
    wp_die();
}
