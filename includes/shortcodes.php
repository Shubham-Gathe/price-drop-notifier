<?php
// filepath: /home/saitama/Local Sites/wordpress/app/public/wp-content/plugins/price-drop-notifier/includes/shortcodes.php

// Function to display subscribed products with an unsubscribe option
function pdn_display_subscribed_products() {
    // Check if the user is logged in
    if (!is_user_logged_in()) {
        return '<p>You need to log in to view your subscribed products.</p>';
    }

    // Get the current user ID
    $user_id = get_current_user_id();

    // Fetch subscribed products from the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'price_drop_notifier'; // Replace with your actual table name
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT id, product_id FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    // Check if the user has any subscriptions
    if (empty($results)) {
        return '<p>You have no subscribed products.</p>';
    }

    // Generate the output
    $output = '<h2>Your subscribed products</h2>';
    $output .= '<ul class="pdn-subscribed-products">';
    foreach ($results as $row) {
        $product_id = $row->product_id;
        $subscription_id = $row->id; // Assuming 'id' is the unique identifier for the subscription
        $product = wc_get_product($product_id); // WooCommerce function to get product details
        if ($product) {
            $output .= '<li>';
            $output .= '<a href="' . get_permalink($product_id) . '">' . $product->get_name() . '</a>';
            $output .= ' <a href="' . esc_url(add_query_arg(['unsubscribe' => $subscription_id])) . '" class="pdn-unsubscribe" style="color: red;">[Unsubscribe]</a>';
            $output .= '</li>';
        }
    }
    $output .= '</ul>';

    return $output;
}

// Handle the unsubscribe action
function pdn_handle_unsubscribe() {
    if (isset($_GET['unsubscribe']) && is_user_logged_in()) {
        $subscription_id = intval($_GET['unsubscribe']);
        $user_id = get_current_user_id();

        // Remove the subscription from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'price_drop_notifier'; // Replace with your actual table name
        $wpdb->delete($table_name, ['id' => $subscription_id, 'user_id' => $user_id]);

        // Redirect to avoid duplicate actions
        wp_redirect(remove_query_arg('unsubscribe'));
        exit;
    }
}
add_action('init', 'pdn_handle_unsubscribe');

// Register the shortcode
add_shortcode('price_drop_subscribed_products', 'pdn_display_subscribed_products');