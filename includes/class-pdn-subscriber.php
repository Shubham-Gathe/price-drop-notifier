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
    public static function exists($email, $product_id, $user_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'price_drop_notifier';

        if ($user_id) {
            // Check by user_id if the user is logged in
            $result = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE user_id = %d AND product_id = %d",
                $user_id, $product_id
            ));
        } else {
            // Fallback to check by email for guest users
            $result = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE user_email = %s AND product_id = %d",
                $email, $product_id
            ));
        }

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

    /**
     * Add a subscriber with advanced options.
     */
    public static function add_advanced($args) {
        global $wpdb;
        $table = $wpdb->prefix . 'price_drop_notifier';
        $data = [
            'user_email' => $args['email'],
            'product_id' => $args['product_id'],
            'user_id' => isset($args['user_id']) ? $args['user_id'] : null, // Optional user ID
        ];
        if (isset($args['desired_price'])) {
            $data['desired_price'] = $args['desired_price'];
        }
        // Store percentage in current_price if needed (or add a new column if you want to extend the DB)
        if (isset($args['desired_price_percentage'])) {
            $data['current_price'] = $args['desired_price_percentage'];
        }
        if (!self::exists($args['email'], $args['product_id'], $args['user_id'] ?? null)) {
            $wpdb->insert($table, $data);
            return true;
        }
        return false;
    }
}