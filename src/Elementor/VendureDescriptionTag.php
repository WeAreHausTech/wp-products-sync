<?php

namespace WeAreHausTech\WpProductSync\Elementor;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (class_exists('\Elementor\Core\DynamicTags\Tag')) {
    class VendureDescriptionTag extends \Elementor\Core\DynamicTags\Tag
    {
        public function get_name()
        {
            return 'vendure-product-description';
        }

        public function get_title()
        {
            return __('Product Description', 'wp-products-sync');
        }

        public function get_group()
        {
            return ['haus-storefront'];
        }

        public function get_categories()
        {
            return [
                \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
                \Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY
            ];
        }

        public function render()
        {

            if (function_exists('haus_get_current_product_post_id')) {
                $post_id = haus_get_current_product_post_id();

            } else {
                $post_id = get_the_ID();
            }

            $description = get_post_meta($post_id, 'vendure_description', true);

            if (empty($description)) {
                return;
            }

            $description = apply_filters('wp_products_sync_vendure_description', $description, $post_id);

            echo wp_kses_post($description);
        }
    }
}
