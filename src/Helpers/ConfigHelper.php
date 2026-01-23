<?php

namespace WeAreHausTech\WpProductSync\Helpers;

use WeAreHausTech\WpProductSync\Helpers\WpmlHelper;

class ConfigHelper
{
    public $defaultLang = '';
    public $useWpml = false;

    private static $cachedConfig = null;

    public function __construct()
    {
        $wpmlHelper = new WpmlHelper();
        $this->useWpml = $wpmlHelper->hasWpml();
        $this->defaultLang = $wpmlHelper->getDefaultLanguage();
    }

    public static function getConfig()
    {

        if (self::$cachedConfig !== null) {
            return self::$cachedConfig;
        }

        if (!class_exists('ACF')) {
            return [];

        }

        $configFilePath = WP_PRODUCTS_SYNC_PLUGIN_DIR . '/config.php';
        $customFieldsConfig = file_exists($configFilePath) ? require $configFilePath : [];

        $facets = get_field('vendure-taxonomies-facet', 'option');
        $collections = get_field('vendure-taxonomies-collection', 'option');

        $taxonomies = [];

        if ($facets) {
            foreach ($facets as $taxonomy) {
                $taxonomies[$taxonomy['vendure-taxonomy-facetCode']] = [
                    "wp" => $taxonomy['vendure-taxonomy-wp'],
                    "vendure" => $taxonomy['vendure-taxonomy-facetCode'] ?? null,
                    "type" => 'facet',
                    "rootCollectionId" => null
                ];
            }
        }
        if ($collections) {
            foreach ($collections as $taxonomy) {
                $taxonomies[$taxonomy['vendure-taxonomy-collectionId']] = [
                    "wp" => $taxonomy['vendure-taxonomy-wp'],
                    "vendure" => null,
                    "type" => 'collection',
                    "rootCollectionId" => $taxonomy['vendure-taxonomy-collectionId'] ?? null
                ];
            }
        }

        $baseConfig = [
            "taxonomies" => $taxonomies,
            'settings' => [
                'flushLinks' => (new self())->functionGetFieldValue('vendure-settings_vendure-settings-flushlinks'),
                'softDelete' => (new self())->functionGetFieldValue('vendure-settings_vendure-settings-softDelete'),
                'taxonomySyncDescription' => (new self())->functionGetFieldValue('vendure-settings_vendure-settings-taxonomySyncDescription'),
            ]
        ];

        $config = array_merge_recursive($baseConfig, $customFieldsConfig);
        self::$cachedConfig = $config;

        return $config;
    }

    public function functionGetFieldValue($field)
    {
        $value = get_field($field, 'option') ?? false;
        return $value === "1" || $value === true;
    }

    public function getTaxonomiesFromConfig()
    {
        $config = self::getConfig();

        if (!isset($config)) {
            WP_CLI::error('No config file found');
        }

        if (isset($config) && isset($config['taxonomies'])) {
            return $config['taxonomies'];
        }

        return [];
    }

    public function hasCollection()
    {
        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty((array) $taxonomies)) {
            return false;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['type'] === 'collection') {
                return true;
            }
        }
        return false;
    }

    public function hasFacets()
    {
        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty((array) $taxonomies)) {
            return false;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['type'] === 'facet') {
                return true;
            }
        }
        return false;
    }

    public function getFacetTypesInWP()
    {
        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty((array) $taxonomies)) {
            return false;
        }

        $wpFacetTypes = [];

        foreach ($taxonomies as $taxonomy) {
            if (isset($taxonomy['type']) && $taxonomy['type'] === 'facet') {
                $wpFacetTypes[] = $taxonomy['wp'];
            }
        }

        return $wpFacetTypes;
    }

    public function isCollection($postType)
    {

        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty((array) $taxonomies)) {
            return false;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['wp'] === $postType && $taxonomy['type'] === 'collection') {
                return true;
            }
        }
        return false;
    }

    public function getCollectionTaxonomyPostTypes()
    {

        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty((array) $taxonomies)) {
            return false;
        }

        $collections = [];

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['type'] === 'collection') {
                $collections[] = $taxonomy['wp'];
            }
        }

        return $collections;
    }

    public function getFacetTaxonomyPostTypes()
    {
        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty($taxonomies)) {
            return false;
        }

        $facets = [];

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['type'] === 'facet') {
                $facets[] = $taxonomy['wp'];
            }
        }

        return $facets;
    }

    static function getSettings()
    {
        $config = self::getConfig();

        if (isset($config['settings'])) {
            return $config['settings'];
        }

        return [];
    }


    static function getSettingByKey($key)
    {
        $settings = self::getSettings();

        if (isset($settings[$key])) {
            return $settings[$key];
        }

        return false;
    }
}

