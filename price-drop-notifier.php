<?php
/**
 * Plugin Name: WooCommerce Price Drop Notifier
 * Description: Allows customers to get notified when the price of a WooCommerce product drops.
 * Version: 1.0.0
 * Author: Shubham Gate
 * Text Domain: price-drop-notifier
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'PDN_VERSION', '1.0.0' );
define( 'PDN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PDN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once PDN_PATH . 'includes/functions.php';
require_once PDN_PATH . 'includes/class-pdn-core.php';
require_once PDN_PATH . 'includes/class-db.php';
require_once PDN_PATH . 'includes/form-handler.php';
require_once PDN_PATH . 'includes/price-drop.php';

// Activation hook for DB table
register_activation_hook(__FILE__, ['PDN_DB', 'create_table']);

// Initialize main plugin class and DB
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        PDN_DB::init();
    } else {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p><strong>WooCommerce Price Drop Notifier</strong> requires WooCommerce to be installed and active.</p></div>';
        } );
    }
});
