<?php

namespace WeAreHausTech\WpProductSync\Admin;

class RestApi
{
    public static function init(): void
    {
        add_action('rest_api_init', [__CLASS__, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        register_rest_route(
            'wp-products-sync/v1',
            '/vendure-sync-settings',
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [__CLASS__, 'getSettings'],
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                ],
                [
                    'methods'             => 'POST',
                    'callback'            => [__CLASS__, 'saveSettings'],
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                    'args'                => [
                        'settings' => [
                            'required'          => true,
                            'type'              => 'object',
                            'validate_callback' => function ($param) {
                                return is_array($param);
                            },
                        ],
                    ],
                ],
            ]
        );
    }

    public static function getSettings(): \WP_REST_Response
    {
        $option_name = 'wp_products_sync_settings';
        $settings    = get_option($option_name, '');

        if (empty($settings)) {
            return new \WP_REST_Response(
                [
                    'success' => true,
                    'data'    => [
                        'taxonomies' => [],
                        'settings'   => [
                            'flushLinks'              => false,
                            'softDelete'              => false,
                            'taxonomySyncDescription' => false,
                        ],
                    ],
                ],
                200
            );
        }

        $decoded = json_decode($settings, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error(
                'json_decode_error',
                __('Failed to decode settings data', 'wp-products-sync'),
                ['status' => 500]
            );
        }

        if (!is_array($decoded)) {
            $decoded = [
                'taxonomies' => [],
                'settings'   => [
                    'flushLinks'              => false,
                    'softDelete'              => false,
                    'taxonomySyncDescription' => false,
                ],
            ];
        }

        return new \WP_REST_Response(
            [
                'success' => true,
                'data'    => $decoded,
            ],
            200
        );
    }

    public static function saveSettings(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $settings = $request->get_param('settings');

        if (!is_array($settings)) {
            return new \WP_Error(
                'invalid_settings',
                __('Settings must be an object', 'wp-products-sync'),
                ['status' => 400]
            );
        }

        $option_name = 'wp_products_sync_settings';
        $json_string = wp_json_encode($settings);

        if ($json_string === false) {
            return new \WP_Error(
                'json_encode_error',
                __('Failed to encode settings data', 'wp-products-sync'),
                ['status' => 500]
            );
        }

        $option_exists  = get_option($option_name) !== false;
        $existing_value = $option_exists ? get_option($option_name, '') : '';
        $value_changed  = $existing_value !== $json_string;

        if (!$option_exists) {
            $updated = add_option($option_name, $json_string);
            if ($updated === false) {
                return new \WP_Error(
                    'save_failed',
                    __('Failed to save settings', 'wp-products-sync'),
                    ['status' => 500]
                );
            }
        } else {
            $updated = update_option($option_name, $json_string);

            if ($value_changed && $updated === false) {
                $verify_value = get_option($option_name, '');
                if ($verify_value !== $json_string) {
                    return new \WP_Error(
                        'save_failed',
                        __('Failed to save settings', 'wp-products-sync'),
                        ['status' => 500]
                    );
                }
            }
        }

        $message = $value_changed
            ? __('Settings saved successfully', 'wp-products-sync')
            : __('Settings are already saved (no changes detected)', 'wp-products-sync');

        return new \WP_REST_Response(
            [
                'success' => true,
                'message' => $message,
            ],
            200
        );
    }
}
