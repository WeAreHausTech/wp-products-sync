<?php
/**
 * Plugin Name: Haus Storefront Product Sync
 * Plugin URI: https://haus.se
 * Description: Haus Storefront product and taxonomies sync
 * Version: 2.0.8
 * Author: Haus Tech
 * Author URI: https://haus.se
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-products-sync
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WP_PRODUCTS_SYNC_VERSION', '2.0.8');
define('WP_PRODUCTS_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_PRODUCTS_SYNC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Initialize the plugin
add_action('plugins_loaded', function () {
    \WeAreHausTech\WpProductSync\BaseSyncProducts::init();
}, 10);
