<?php
/**
 * Plugin Name: Simple Document Request
 * Plugin URI: https://elmertiman.com/simple-document-request
 * Description: A WooCommerce plugin that allows users to request authenticity documents via OTP verification
 * Version: 1.0.4
 * Author: Elmer Timan
 * Author URI: https://elmertiman.com
 * License: GPL v2 or later
 * Text Domain: simple-document-request
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SDR_VERSION', '1.0.5');
define('SDR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SDR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SDR_PLUGIN_FILE', __FILE__);

// Check if WooCommerce is active
add_action('plugins_loaded', 'sdr_check_woocommerce');
function sdr_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'sdr_woocommerce_missing_notice');
        return;
    }
    
    // Initialize plugin
    require_once SDR_PLUGIN_DIR . 'includes/class-sdr-database.php';
    require_once SDR_PLUGIN_DIR . 'includes/class-sdr-ajax.php';
    require_once SDR_PLUGIN_DIR . 'includes/class-sdr-frontend.php';
    require_once SDR_PLUGIN_DIR . 'includes/class-sdr-admin.php';
    require_once SDR_PLUGIN_DIR . 'includes/class-sdr-email.php';
    
    // Initialize classes
    SDR_Database::get_instance();
    SDR_Ajax::get_instance();
    SDR_Frontend::get_instance();
    SDR_Admin::get_instance();
    SDR_Email::get_instance();
}

function sdr_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Simple Document Request requires WooCommerce to be installed and active.', 'simple-document-request'); ?></p>
    </div>
    <?php
}

// Activation hook
register_activation_hook(__FILE__, 'sdr_activate');
function sdr_activate() {
    require_once SDR_PLUGIN_DIR . 'includes/class-sdr-database.php';
    SDR_Database::get_instance()->create_tables();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'sdr_deactivate');
function sdr_deactivate() {
    // Clean up if needed
}
