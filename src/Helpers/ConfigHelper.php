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

        $configFilePath = WP_PRODUCTS_SYNC_PLUGIN_DIR . '/config.php';
        $customFieldsConfig = file_exists($configFilePath) ? require $configFilePath : [];

        $option_name = 'wp_products_sync_settings';
        $settings_json = get_option($option_name, '');

        $taxonomies = [];
        $settings = [
            'flushLinks'              => false,
            'softDelete'              => false,
            'taxonomySyncDescription' => false,
        ];

        if (!empty($settings_json)) {
            $decoded = json_decode($settings_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Process taxonomies from the new format
                if (isset($decoded['taxonomies']) && is_array($decoded['taxonomies'])) {
                    foreach ($decoded['taxonomies'] as $taxonomy) {
                        $type = $taxonomy['vendureTaxonomyType'] ?? null;
                        $wp_taxonomy = $taxonomy['vendureTaxonomyWp'] ?? '';

                        if (!$type || !$wp_taxonomy) {
                            continue;
                        }

                        if ($type === 'facet') {
                            $facet_code = $taxonomy['vendureTaxonomyFacetCode'] ?? '';
                            if ($facet_code) {
                                $taxonomies[$facet_code] = [
                                    'wp'              => $wp_taxonomy,
                                    'vendure'         => $facet_code,
                                    'type'            => 'facet',
                                    'rootCollectionId' => null,
                                ];
                            }
                        } elseif ($type === 'collection') {
                            $collection_id = $taxonomy['vendureTaxonomyCollectionId'] ?? '';
                            if ($collection_id) {
                                $taxonomies[$collection_id] = [
                                    'wp'              => $wp_taxonomy,
                                    'vendure'         => null,
                                    'type'            => 'collection',
                                    'rootCollectionId' => $collection_id,
                                ];
                            }
                        }
                    }
                }

                // Process settings
                if (isset($decoded['settings']) && is_array($decoded['settings'])) {
                    $settings = [
                        'flushLinks'              => isset($decoded['settings']['flushLinks']) ? (bool) $decoded['settings']['flushLinks'] : false,
                        'softDelete'              => isset($decoded['settings']['softDelete']) ? (bool) $decoded['settings']['softDelete'] : false,
                        'taxonomySyncDescription' => isset($decoded['settings']['taxonomySyncDescription']) ? (bool) $decoded['settings']['taxonomySyncDescription'] : false,
                    ];
                }
            }
        }

        $baseConfig = [
            'taxonomies' => $taxonomies,
            'settings'   => $settings,
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

