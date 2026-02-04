<?php

namespace WeAreHausTech\WpProductSync\Elementor;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Only define the class if Elementor is active
if (class_exists('\Elementor\Core\DynamicTags\Tag')) {
    class VendureProductNameTag extends \Elementor\Core\DynamicTags\Tag
    {
        public function get_name()
        {
            return 'vendure-product-name';
        }

        public function get_title()
        {
            return __('Product Name', 'wp-products-sync');
        }

        public function get_group()
        {
            return ['haus-storefront'];
        }

        public function get_categories()
        {
            return [
                \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            ];
        }

        public function render()
        {
            // Use same logic as description tag for consistency
            if (function_exists('haus_get_current_product_post_id')) {
                $post_id = haus_get_current_product_post_id();
            } else {
                $post_id = get_the_ID();
            }

            if (!$post_id) {
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    echo esc_html__('Product Name', 'wp-products-sync');
                }
                return;
            }

            $product_name = get_the_title($post_id);

            if (empty($product_name)) {
                return;
            }

            $product_name = apply_filters('wp_products_sync_vendure_product_name', $product_name, $post_id);

            echo esc_html($product_name);
        }
    }
}
