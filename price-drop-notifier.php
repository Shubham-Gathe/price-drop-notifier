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

// Add settings link and register settings
add_action('admin_menu', function() {
    add_options_page(
        __('Price Drop Notifier Settings', 'price-drop-notifier'),
        __('Price Drop Notifier', 'price-drop-notifier'),
        'manage_options',
        'pdn-settings',
        'pdn_render_settings_page'
    );
});

add_action('admin_init', function() {
    register_setting('pdn_settings_group', 'pdn_max_notifications_per_day', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 1
    ]);
    register_setting('pdn_settings_group', 'pdn_display_for_logged_in', [
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true
    ]);
    register_setting('pdn_settings_group', 'pdn_display_for_guests', [
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true
    ]);
    register_setting('pdn_settings_group', 'pdn_display_subscribed_products', [
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true
    ]);
    register_setting('pdn_settings_group', 'pdn_ask_expected_price', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'yes'
    ]);
    register_setting('pdn_settings_group', 'pdn_expected_price_type', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'custom'
    ]);

    add_settings_section('pdn_main_section', '', null, 'pdn-settings');
    add_settings_field(
        'pdn_max_notifications_per_day',
        __('Max notifications per user per day', 'price-drop-notifier'),
        function() {
            $value = get_option('pdn_max_notifications_per_day', 1);
            echo '<input type="number" min="1" name="pdn_max_notifications_per_day" value="' . esc_attr($value) . '" />';
        },
        'pdn-settings',
        'pdn_main_section'
    );
    add_settings_field(
        'pdn_display_for_logged_in',
        __('Display Price Drop Notifier Form for Logged-In Users', 'price-drop-notifier'),
        function() {
            $checked = checked(get_option('pdn_display_for_logged_in', true), true, false);
            echo '<input type="checkbox" name="pdn_display_for_logged_in" value="1" ' . $checked . ' />';
        },
        'pdn-settings',
        'pdn_main_section'
    );
    add_settings_field(
        'pdn_display_for_guests',
        __('Display Price Drop Notifier Form for Guest Users', 'price-drop-notifier'),
        function() {
            $checked = checked(get_option('pdn_display_for_guests', true), true, false);
            echo '<input type="checkbox" name="pdn_display_for_guests" value="1" ' . $checked . ' />';
        },
        'pdn-settings',
        'pdn_main_section'
    );
    add_settings_field(
        'pdn_display_subscribed_products',
        __('Display Subscribed Products on Logged-in User’s My Account Page', 'price-drop-notifier'),
        function() {
            $checked = checked(get_option('pdn_display_subscribed_products', true), true, false);
            echo '<input type="checkbox" name="pdn_display_subscribed_products" value="1" ' . $checked . ' />';
        },
        'pdn-settings',
        'pdn_main_section'
    );
    add_settings_field(
        'pdn_ask_expected_price',
        __('Ask Expected price to the subscribers', 'price-drop-notifier'),
        function() {
            $value = get_option('pdn_ask_expected_price', 'yes');
            echo '<select name="pdn_ask_expected_price">
                <option value="yes"' . selected($value, 'yes', false) . '>Yes</option>
                <option value="no"' . selected($value, 'no', false) . '>No</option>
            </select>';
        },
        'pdn-settings',
        'pdn_main_section'
    );
    add_settings_field(
        'pdn_expected_price_type',
        __('Expected Price Type', 'price-drop-notifier'),
        function() {
            $ask = get_option('pdn_ask_expected_price', 'yes');
            $value = get_option('pdn_expected_price_type', 'custom');
            $disabled = ($ask === 'no') ? 'disabled' : '';
            echo '<select name="pdn_expected_price_type" ' . $disabled . '>
                <option value="custom"' . selected($value, 'custom', false) . '>Custom Price (Fixed Price)</option>
                <option value="percentage"' . selected($value, 'percentage', false) . '>Percentage</option>
                <option value="custom_or_percentage"' . selected($value, 'custom_or_percentage', false) . '>Custom or Percentage</option>
            </select>';
        },
        'pdn-settings',
        'pdn_main_section'
    );
});

function pdn_render_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Price Drop Notifier Settings', 'price-drop-notifier') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('pdn_settings_group');
    do_settings_sections('pdn-settings');
    submit_button();
    echo '</form>';
    echo '</div>';
}


add_action('woocommerce_after_add_to_cart_form','pdn_show_subscription_form');

// Optionally hide subscribed products from My Account page
add_action('init', function() {
    if (!get_option('pdn_display_subscribed_products', true)) {
        remove_action('woocommerce_account_dashboard', 'pdn_show_subscribed_products', 20);
    }
});

    /**
     * Show the subscription form for price drop notifications.
     */
    function pdn_show_subscription_form(){
        if (!is_product()) return;

        $show_for_logged_in = get_option('pdn_display_for_logged_in', true);
        $show_for_guests = get_option('pdn_display_for_guests', true);
        $ask_expected_price = get_option('pdn_ask_expected_price', 'yes');
        $expected_price_type = get_option('pdn_expected_price_type', 'custom');

        if ((is_user_logged_in() && !$show_for_logged_in) || (!is_user_logged_in() && !$show_for_guests)) {
            return;
        }
        ?>
        <style>
        #pdn-subscribe-form {
            background: #f9f9f9;
            border: 1px solid #e1e1e1;
            padding: 20px;
            border-radius: 8px;
            max-width: 350px;
            margin-top: 20px;
        }
        #pdn-subscribe-form label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }
        #pdn-subscribe-form input[type="email"],
        #pdn-subscribe-form input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        #pdn-subscribe-form button {
            background: #0071a1;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        #pdn-subscribe-form button:hover {
            background: #005177;
        }
        #pdn-message {
            margin-top: 10px;
            color: #0071a1;
        }
        form#pdn-subscribe-form p {
            display: inline-block;
            margin: 0px;
        }
        p#pdn-message {
            margin-top: 10px !important;
            font-size: 14px;
        }
        </style>
        <form id="pdn-subscribe-form">
            <p>
                <label for="pdn_email">Get notified when price drops:</label>
                <input type="email" name="pdn_email" id="pdn_email" required placeholder="Enter your email">
            </p>
            <?php if ($ask_expected_price === 'yes') : ?>
                <p>
                    <label for="pdn_expected_price">Expected Price:</label>
                    <?php if ($expected_price_type === 'custom') : ?>
                        <input type="number" name="pdn_expected_price" id="pdn_expected_price" min="0" step="0.01" placeholder="Enter expected price">
                    <?php elseif ($expected_price_type === 'percentage') : ?>
                        <input type="number" name="pdn_expected_price_percentage" id="pdn_expected_price_percentage" min="1" max="100" step="1" placeholder="Enter percentage (e.g. 10 for 10%)">
                    <?php else : ?>
                        <input type="number" name="pdn_expected_price" id="pdn_expected_price" min="0" step="0.01" placeholder="Enter expected price">
                        <span style="margin:0 8px;">or</span>
                        <input type="number" name="pdn_expected_price_percentage" id="pdn_expected_price_percentage" min="1" max="100" step="1" placeholder="Enter percentage (e.g. 10 for 10%)">
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <button type="submit">Notify Me</button>
            <p id="pdn-message"></p>
        </form>
        <script>
            document.getElementById('pdn-subscribe-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const email = document.getElementById('pdn_email').value;
                let expected_price = '';
                let expected_price_percentage = '';
                if (document.getElementById('pdn_expected_price')) {
                    expected_price = document.getElementById('pdn_expected_price').value;
                }
                if (document.getElementById('pdn_expected_price_percentage')) {
                    expected_price_percentage = document.getElementById('pdn_expected_price_percentage').value;
                }
                const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'pdn_subscribe',
                        email: email,
                        product_id: '<?php echo get_the_ID(); ?>',
                        expected_price: expected_price,
                        expected_price_percentage: expected_price_percentage
                    })
                });
                const result = await response.text();
                document.getElementById('pdn-message').innerText = result;
            });
        </script>
        <?php
    }


// Show subscribed products on My Account page if enabled
if (!function_exists('pdn_show_subscribed_products')) {
    function pdn_show_subscribed_products() {
        if (!is_user_logged_in()) return;
        $user = wp_get_current_user();
        global $wpdb;
        $table = $wpdb->prefix . 'price_drop_notifier';
        $subs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_email = %s", $user->user_email));
        if ($subs && count($subs)) {
            echo '<h3>' . esc_html__('Your Price Drop Subscriptions', 'price-drop-notifier') . '</h3>';
            echo '<ul class="pdn-subscribed-products">';
            foreach ($subs as $sub) {
                $product = wc_get_product($sub->product_id);
                if ($product) {
                    echo '<li>' . esc_html($product->get_name());
                    if (!empty($sub->desired_price)) {
                        echo ' (' . esc_html__('Desired Price:', 'price-drop-notifier') . ' ' . wc_price($sub->desired_price) . ')';
                    } elseif (!empty($sub->current_price)) {
                        echo ' (' . esc_html__('Desired % Drop:', 'price-drop-notifier') . ' ' . esc_html($sub->current_price) . '%)';
                    }
                    echo '</li>';
                }
            }
            echo '</ul>';
        }
    }
}

if (get_option('pdn_display_subscribed_products', true)) {
    add_action('woocommerce_account_dashboard', 'pdn_show_subscribed_products', 20);
}