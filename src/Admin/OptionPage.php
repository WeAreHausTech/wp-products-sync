<?php

namespace WeAreHausTech\WpProductSync\Admin;

class OptionPage
{
    /**
     * Check if the main Haus Storefront plugin is installed
     * Checks for the menu slug and also for potential constants/functions
     *
     * @return bool
     */
    private static function isMainPluginInstalled(): bool
    {
        // First check if a constant from the main plugin exists
        if (defined('HAUS_ECOM_PLUGIN_PATH')) {
            return true;
        }

        // Check if a function from the main plugin exists
        if (function_exists('haus_storefront_render_page')) {
            return true;
        }

        // Fallback: Check if the main plugin menu exists in the global menu array
        global $menu;
        if (isset($menu) && is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && $menu_item[2] === 'haus-storefront') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get plugin version for cache busting
     */
    private static function getVersion(): string
    {
        if (function_exists('get_plugin_data')) {
            $plugin_data = get_plugin_data(WP_PRODUCTS_SYNC_PLUGIN_DIR . '/wp-products-sync.php');
            if (!empty($plugin_data['Version']) && $plugin_data['Version'] !== '{{VERSION}}') {
                return $plugin_data['Version'];
            }
        }

        $manifestPath = WP_PRODUCTS_SYNC_PLUGIN_DIR . '/apps/vendure-sync/dist/.vite/manifest.json';
        if (file_exists($manifestPath)) {
            return (string) filemtime($manifestPath);
        }

        return (string) time();
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueueAssets(string $hook): void
    {
        // Only load on our admin page
        if ($hook !== 'toplevel_page_vendure-sync' && $hook !== 'haus-storefront_page_vendure-sync') {
            return;
        }

        $manifestPath = WP_PRODUCTS_SYNC_PLUGIN_DIR . '/apps/vendure-sync/dist/.vite/manifest.json';

        if (!file_exists($manifestPath)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>'
                    . esc_html__('Vendure Sync build files not found. Please run: yarn build', 'wp-products-sync')
                    . '</p></div>';
            });
            return;
        }

        $manifestContent = file_get_contents($manifestPath);
        $manifest = json_decode($manifestContent, true);

        if (!$manifest || !is_array($manifest)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>'
                    . esc_html__('Vendure Sync manifest file is invalid. Please check your build: yarn build', 'wp-products-sync')
                    . '</p></div>';
            });
            return;
        }

        $version = self::getVersion();

        $entry = null;
        foreach ($manifest as $key => $manifestEntry) {
            if (isset($manifestEntry['isEntry']) && $manifestEntry['isEntry'] === true) {
                $entry = $manifestEntry;
                break;
            }
            // Also check for 'main' entry
            if (strpos($key, 'main') !== false && isset($manifestEntry['file'])) {
                $entry = $manifestEntry;
                break;
            }
        }

        if (!$entry) {
            // Try to find any entry file
            foreach ($manifest as $manifestEntry) {
                if (isset($manifestEntry['file']) && strpos($manifestEntry['file'], '.js') !== false) {
                    $entry = $manifestEntry;
                    break;
                }
            }
        }

        if (!$entry || !isset($entry['file'])) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>'
                    . esc_html__('Vendure Sync main entry point not found. Please check your build: yarn build', 'wp-products-sync')
                    . '</p></div>';
            });
            return;
        }

        wp_enqueue_script(
            'vendure-sync',
            WP_PRODUCTS_SYNC_PLUGIN_URL . 'apps/vendure-sync/dist/' . $entry['file'],
            [],
            $version,
            true
        );

        if (function_exists('wp_script_add_data')) {
            wp_script_add_data('vendure-sync', 'type', 'module');
        }

        if (isset($entry['css']) && is_array($entry['css'])) {
            foreach ($entry['css'] as $cssFile) {
                wp_enqueue_style(
                    'vendure-sync-' . basename($cssFile, '.css'),
                    WP_PRODUCTS_SYNC_PLUGIN_URL . 'apps/vendure-sync/dist/' . $cssFile,
                    [],
                    $version
                );
            }
        }

        if (isset($entry['imports']) && is_array($entry['imports'])) {
            foreach ($entry['imports'] as $importKey) {
                if (isset($manifest[$importKey]['file'])) {
                    wp_enqueue_script(
                        'vendure-sync-' . $importKey,
                        WP_PRODUCTS_SYNC_PLUGIN_URL . 'apps/vendure-sync/dist/' . $manifest[$importKey]['file'],
                        [],
                        $version,
                        true
                    );
                    if (function_exists('wp_script_add_data')) {
                        wp_script_add_data('vendure-sync-' . $importKey, 'type', 'module');
                    }
                }
            }
        }

        wp_register_style('vendure-sync-inline-style', false);
        wp_enqueue_style('vendure-sync-inline-style');


        // Inject API configuration
        $api_url = rest_url('wp-products-sync/v1/vendure-sync-settings');
        $nonce = wp_create_nonce('wp_rest');
        wp_add_inline_script(
            'vendure-sync',
            sprintf(
                'window.vendureSync = { apiUrl: %s, nonce: %s };',
                wp_json_encode($api_url),
                wp_json_encode($nonce)
            ),
            'before'
        );
    }

    /**
     * Render the admin settings page
     */
    public static function renderPage(): void
    {
        ?>
        <div id="haus-storefront-container" style="padding: 20px;">
            <div id="haus-storefront-navigation"></div>
            <div id="vendure-sync-root">
                <div class="vendure-sync-loading-message" style="text-align:center;">
                    <span>Loading Vendure Sync Settings...</span>
                </div>
            </div>
        </div>
        <?php
    }

    public static function init(): void
    {
        // Enqueue assets
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueueAssets']);

        // Use admin_menu hook with late priority to ensure main plugin menu is registered first
        add_action('admin_menu', function () {
            if (self::isMainPluginInstalled()) {
                // Add as submenu under Haus Storefront
                add_submenu_page(
                    'haus-storefront',
                    __('Sync Settings', 'wp-products-sync'),
                    __('Sync Settings', 'wp-products-sync'),
                    'manage_options',
                    'vendure-sync',
                    [__CLASS__, 'renderPage']
                );
            } else {
                add_menu_page(
                    __('Sync Settings', 'wp-products-sync'),
                    __('Sync Settings', 'wp-products-sync'),
                    'manage_options',
                    'vendure-sync',
                    [__CLASS__, 'renderPage']
                );
            }
        }, 100);
    }
}
