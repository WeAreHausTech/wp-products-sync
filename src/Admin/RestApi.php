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
                    'methods' => 'GET',
                    'callback' => [__CLASS__, 'getSettings'],
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                ],
                [
                    'methods' => 'POST',
                    'callback' => [__CLASS__, 'saveSettings'],
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    },
                    'args' => [
                        'settings' => [
                            'required' => true,
                            'type' => 'object',
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
        $settings = get_option($option_name, '');

        if (empty($settings)) {
            return new \WP_REST_Response(
                [
                    'success' => true,
                    'data' => [
                        'taxonomies' => [],
                        'settings' => [
                            'flushLinks' => false,
                            'softDelete' => false,
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
                'settings' => [
                    'flushLinks' => false,
                    'softDelete' => false,
                    'taxonomySyncDescription' => false,
                ],
            ];
        }

        return new \WP_REST_Response(
            [
                'success' => true,
                'data' => $decoded,
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

        // Sanitize settings using config-based sanitization
        $sanitized_settings = self::sanitizeSettings($settings);

        $option_name = 'wp_products_sync_settings';
        $json_string = wp_json_encode($sanitized_settings);

        if ($json_string === false) {
            return new \WP_Error(
                'json_encode_error',
                __('Failed to encode settings data', 'wp-products-sync'),
                ['status' => 500]
            );
        }

        $option_exists = get_option($option_name) !== false;
        $existing_value = $option_exists ? get_option($option_name, '') : '';
        $value_changed = $existing_value !== $json_string;

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

    /**
     * Validation and sanitization configuration
     * @return array Field validation config
     */
    private static function getValidationConfig(): array
    {
        $vendureTaxonomyTypes = [
            'collection',
            'facet',
        ];

        return [
            'taxonomies' => [
                'vendureTaxonomyWp' => 'sanitize_key',
                'vendureTaxonomyType' => function ($value) use ($vendureTaxonomyTypes) {
                    $value = sanitize_text_field($value);
                    return in_array($value, $vendureTaxonomyTypes, true);
                },
                'vendureTaxonomyCollectionId' => 'sanitize_text_field',
                'vendureTaxonomyFacetCode' => 'sanitize_key',
            ],
            'settings' => [
                'flushLinks' => 'sanitize_boolean',
                'softDelete' => 'sanitize_boolean',
                'taxonomySyncDescription' => 'sanitize_boolean',
            ],
        ];
    }

    /**
     * Sanitize settings using validation config
     *
     * @param array $settings Raw settings data
     * @return array Sanitized settings
     */
    private static function sanitizeSettings(array $settings): array
    {
        $config = self::getValidationConfig();

        // Sanitize taxonomies
        if (isset($settings['taxonomies']) && is_array($settings['taxonomies'])) {
            foreach ($settings['taxonomies'] as $key => $taxonomy) {
                if (!is_array($taxonomy)) {
                    unset($settings['taxonomies'][$key]);
                    continue;
                }
                foreach ($config['taxonomies'] as $field => $sanitizer) {
                    if (isset($taxonomy[$field])) {
                        $settings['taxonomies'][$key][$field] = self::applySanitizer($taxonomy[$field], $sanitizer);
                    }
                }
            }
        }

        // Sanitize settings
        if (isset($settings['settings']) && is_array($settings['settings'])) {
            foreach ($config['settings'] as $field => $sanitizer) {
                if (isset($settings['settings'][$field])) {
                    $settings['settings'][$field] = self::applySanitizer($settings['settings'][$field], $sanitizer);
                }
            }
        }

        return $settings;
    }

    /**
     * Apply sanitizer to a value
     *
     * @param mixed $value Value to sanitize
     * @param callable|string $sanitizer Sanitizer function or name
     * @return mixed Sanitized value
     */
    private static function applySanitizer($value, $sanitizer)
    {
        if (is_callable($sanitizer)) {
            return $sanitizer($value);
        }

        switch ($sanitizer) {
            case 'sanitize_key':
                return sanitize_key($value);
            case 'sanitize_text_field':
                return sanitize_text_field($value);
            case 'sanitize_boolean':
                return function_exists('rest_sanitize_boolean') ? rest_sanitize_boolean($value) : (bool) $value;
            default:
                return sanitize_text_field($value);
        }
    }
}
