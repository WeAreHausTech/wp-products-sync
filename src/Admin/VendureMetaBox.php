<?php

namespace WeAreHausTech\WpProductSync\Admin;

use WeAreHausTech\WpProductSync\Helpers\ConfigHelper;

class VendureMetaBox
{
    /**
     * Initialize Vendure meta box for posts and taxonomies
     */
    public static function init()
    {
        add_action('add_meta_boxes', [__CLASS__, 'addPostMetaBox']);
        add_action('admin_init', [__CLASS__, 'addTaxonomyMetaBox']);
    }

    /**
     * Add meta box for product post type
     */
    public static function addPostMetaBox()
    {
        add_meta_box(
            'vendure_meta_fields',
            __('Vendure Meta Fields', 'wp-products-sync'),
            [__CLASS__, 'renderPostMetaBox'],
            'produkter',
            'normal',
            'default'
        );
    }

    /**
     * Add meta box for taxonomies
     */
    public static function addTaxonomyMetaBox()
    {
        $configHelper = new ConfigHelper();
        $taxonomies = $configHelper->getTaxonomiesFromConfig();

        if (empty((array) $taxonomies)) {
            return;
        }

        foreach ($taxonomies as $taxonomy) {
            add_action("{$taxonomy['wp']}_edit_form", [__CLASS__, 'renderTaxonomyMetaBox'], 10, 2);
        }
    }

    /**
     * Render meta box for posts
     *
     * @param \WP_Post $post
     */
    public static function renderPostMetaBox($post)
    {
        $meta_fields = self::getVendureMetaFields($post->ID, 'post');

        if (empty($meta_fields)) {
            return;
        }

        self::renderMetaBoxMarkup($meta_fields);
    }

    /**
     * Render meta box for taxonomies
     *
     * @param \WP_Term $term
     * @param string $taxonomy
     */
    public static function renderTaxonomyMetaBox($term, $taxonomy)
    {
        $meta_fields = self::getVendureMetaFields($term->term_id, 'term');

        if (empty($meta_fields)) {
            return;
        }

        self::renderMetaBoxMarkup($meta_fields);
    }

    /**
     * Shared HTML markup for Vendure meta fields (posts and terms)
     *
     * @param array $meta_fields
     */
    private static function renderMetaBoxMarkup(array $meta_fields)
    {
        echo '<div class="vendure-meta-box" style="width: 100%; margin-top: 20px; padding: 0;">';
        echo '<table class="widefat fixed striped" style="width: 100%;">';
        echo '<thead><tr><th style="width: 200px;">' . esc_html__('Meta Key', 'wp-products-sync') . '</th><th>' . esc_html__('Meta Value', 'wp-products-sync') . '</th></tr></thead>';
        echo '<tbody>';

        foreach ($meta_fields as $key => $value) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($key) . '</strong></td>';
            echo '<td>' . self::formatMetaValue($value) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Get all meta fields with vendure_ prefix
     *
     * @param int $id Post ID or Term ID
     * @param string $type 'post' or 'term'
     * @return array
     */
    private static function getVendureMetaFields($id, $type = 'post')
    {
        $meta_fields = [];

        if ($type === 'post') {
            $all_meta = get_post_meta($id);
        } else {
            $all_meta = get_term_meta($id);
        }

        if (empty($all_meta)) {
            return $meta_fields;
        }

        foreach ($all_meta as $key => $values) {
            if (strpos($key, 'vendure_') === 0) {
                $value = $type === 'post' ? get_post_meta($id, $key, true) : get_term_meta($id, $key, true);
                $meta_fields[$key] = $value;
            }
        }
        ksort($meta_fields);

        return $meta_fields;
    }

    /**
     * Format meta value for display
     *
     * @param mixed $value
     * @return string
     */
    private static function formatMetaValue($value)
    {
        if (is_array($value)) {
            return '<pre>' . esc_html(print_r($value, true)) . '</pre>';
        }

        if (is_bool($value)) {
            return $value ? esc_html__('Yes', 'wp-products-sync') : esc_html__('No', 'wp-products-sync');
        }

        if (is_numeric($value)) {
            return esc_html($value);
        }

        $json = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return '<pre>' . esc_html(json_encode($json, JSON_PRETTY_PRINT)) . '</pre>';
        }

        if ($value !== strip_tags($value)) {
            return wp_kses_post($value);
        }

        return esc_html($value);
    }
}