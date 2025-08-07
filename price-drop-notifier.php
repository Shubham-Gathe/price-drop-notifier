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
 add_action('woocommerce_after_add_to_cart_form','pdn_show_subscription_form');

    /**
     * Show the subscription form for price drop notifications.
     */
    function pdn_show_subscription_form(){
        if (!is_product()) return;

        ?>
        <form id="pdn-subscribe-form">
            <p>
                <label for="pdn_email">Get notified when price drops:</label><br>
                <input type="email" name="pdn_email" id="pdn_email" required placeholder="Enter your email">
            </p>
            <button type="submit">Notify Me</button>
            <p id="pdn-message" style="margin-top: 10px;"></p>
        </form>
        <script>
            document.getElementById('pdn-subscribe-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const email = document.getElementById('pdn_email').value;

                const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'pdn_subscribe',
                        email: email,
                        product_id: '<?php echo get_the_ID(); ?>'
                    })
                });

                const result = await response.text();
                document.getElementById('pdn-message').innerText = result;
            });
        </script>
        <?php
    }

