<?php
class PDN_Subscriber {
    /**
     * Add a subscriber for a product.
     */
    public static function add($email, $product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'price_drop_notifier';
        if (!self::exists($email, $product_id)) {
            $wpdb->insert($table, [
                'user_email' => $email,
                'product_id' => $product_id,
            ]);
            return true;
        }
        return false;
    }

    /**
     * Check if a subscriber already exists for a product.
     */
    public static function exists($email, $product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'price_drop_notifier';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_email = %s AND product_id = %d",
            $email, $product_id
        ));
        return $result > 0;
    }

    /**
     * Get all subscribers for a product.
     */
    public static function get_subscribers($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'price_drop_notifier';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT user_email FROM $table WHERE product_id = %d",
            $product_id
        ));
    }
}
