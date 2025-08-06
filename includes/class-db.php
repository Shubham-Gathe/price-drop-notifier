<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class PDN_DB {

    private static $table_name;

    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'price_drop_notifier';
    }

    public static function create_table() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'price_drop_notifier';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . self::$table_name . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_email VARCHAR(255) NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            desired_price DECIMAL(10, 2) DEFAULT NULL,
            current_price DECIMAL(10, 2) DEFAULT NULL,
            notified TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY user_email (user_email)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Add more functions like get_notifications(), delete_notification(), mark_notified() etc. as needed.
}
