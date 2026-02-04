<?php

namespace WeAreHausTech\WpProductSync\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

class ElementorIntegration
{
    /**
     * Initialize Elementor integration if Elementor is installed and active
     */
    public static function init()
    {
        add_action('init', function () {

            if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Core\DynamicTags\Manager') || !class_exists('\Elementor\Core\DynamicTags\Tag')) {
                return;
            }
            add_action('elementor/dynamic_tags/register', [__CLASS__, 'registerDynamicTagGroup']);
            add_action('elementor/dynamic_tags/register_tags', [__CLASS__, 'registerDynamicTags']);
        }, 20);
    }

    /**
     * Register custom Elementor Dynamic Tag group "Haus Storefront"
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager
     */
    public static function registerDynamicTagGroup($dynamic_tags_manager)
    {
        if (!class_exists('\Elementor\Core\DynamicTags\Manager')) {
            return;
        }

        $dynamic_tags_manager->register_group('haus-storefront', [
            'title' => __('Haus Storefront', 'wp-products-sync'),
        ]);
    }

    /**
     * Register Elementor Dynamic Tags
     * Add new tag classes to the $tags array to register them
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager
     */
    public static function registerDynamicTags($dynamic_tags_manager)
    {
        if (!class_exists('\Elementor\Core\DynamicTags\Tag')) {
            return;
        }
        $tags = [
            VendureDescriptionTag::class,
            VendureProductNameTag::class,
        ];

        foreach ($tags as $tag_class) {
            if (class_exists($tag_class)) {
                $dynamic_tags_manager->register(new $tag_class());
            }
        }
    }
}
