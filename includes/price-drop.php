<?php
// Handles price drop detection and notification sending.
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('save_post_product', 'pdn_check_price_drop_on_save', 10, 3);

function pdn_check_price_drop_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!$update) return;
    $new_price = get_post_meta($post_id, '_regular_price', true);
    $old_price = get_post_meta($post_id, '_pdn_last_price', true);
    if ($old_price && floatval($new_price) < floatval($old_price)) {
        pdn_send_price_drop_notifications($post_id, $old_price, $new_price);
    }
    update_post_meta($post_id, '_pdn_last_price', $new_price);
}

require_once PDN_PATH . 'includes/class-pdn-subscriber.php';

function pdn_send_price_drop_notifications($product_id, $old_price, $new_price) {
    $subscribers = PDN_Subscriber::get_subscribers($product_id);
    if (empty($subscribers)) return;
    $product = wc_get_product($product_id);
    $product_name = $product ? $product->get_name() : 'Product';
    foreach ($subscribers as $subscriber) {
        $to = $subscriber->email;
        $subject = "Price Drop Alert for $product_name!";
        $message = "Good news! The price of <strong>$product_name</strong> has dropped from ₹$old_price to ₹$new_price. <br><br>
        <a href='" . get_permalink($product_id) . "'>View Product</a>";
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($to, $subject, $message, $headers);
    }
    // Optional: Clean up subscribers if you don't want to send again
    // PDN_Subscriber::delete_all_for_product($product_id); // implement if needed
}
