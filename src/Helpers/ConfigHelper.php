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

        $configFilePath = HAUS_ECOM_PLUGIN_PATH . '/config.php';
        $customFieldsConfig = file_exists($configFilePath) ? require $configFilePath : [];


        $flushLinks = get_field('vendure-settings_vendure-settings-flushlinks', 'option') ?? false;
        $softDelete = get_field('vendure-settings_vendure-settings-softDelete', 'option') ?? false;
        $vendure_taxonomies = get_field('vendure-taxonomies', 'option');

        $taxonomies = [];

        if ($vendure_taxonomies) {
            foreach ($vendure_taxonomies as $taxonomy) {
                $taxonomies[$taxonomy['vendure-taxonomy-type']] = [
                    "wp" => $taxonomy['vendure-taxonomy-wp'],
                    "vendure" => $taxonomy['vendure-taxonomy-facetCode'] ?? null,
                    "type" => $taxonomy['vendure-taxonomy-type'],
                    "rootCollectionId" => $taxonomy['vendure-taxonomy-collectionId'] ?? null
                ];
            }
        }

        $baseConfig = [
            "taxonomies" => $taxonomies,
            'settings' => [
                'flushLinks' => $flushLinks,
                'softDelete' => $softDelete
            ]
        ];

        $config = array_merge_recursive($baseConfig, $customFieldsConfig);
        self::$cachedConfig = $config;

        return $config;
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

    public function getCollectionTaxonomyType()
    {
        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty((array) $taxonomies)) {
            return false;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['type'] === 'collection') {
                return $taxonomy['wp'];
            }
        }
        return '';
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

        if (isset($config['productSync']['settings'])) {
            return $config['productSync']['settings'];
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

