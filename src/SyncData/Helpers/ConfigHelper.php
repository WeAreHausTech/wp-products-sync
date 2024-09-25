<?php

namespace WeAreHausTech\SyncData\Helpers;

use WeAreHausTech\SyncData\Helpers\WpmlHelper;

class ConfigHelper
{
    public $defaultLang = '';
    public $useWpml = false;

    public function __construct()
    {
        $wpmlHelper = new WpmlHelper();
        $this->useWpml = $wpmlHelper->hasWpml();
        $this->defaultLang = $wpmlHelper->getDefaultLanguage();
    }

    public function getTaxonomiesFromConfig()
    {
        $config = require(HAUS_ECOM_PLUGIN_PATH . '/config.php');

        if (!isset($config)) {
            WP_CLI::error('No config file found');
        }

        if (isset($config['productSync']) && isset($config['productSync']['taxonomies'])) {
            return $config['productSync']['taxonomies'];
        }
        
        return [];
    }

    public function hasCollection(){
        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty( (array) $taxonomies)) {
           return false;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['type'] === 'collection') {
                return true;
            }
        }
        return false;
    }

    public function hasFacets(){
        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty( (array) $taxonomies)) {
           return false;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['type'] === 'facet') {
                return true;
            }
        }
        return false;
    }

    public function isCollection($postType) {

        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty( (array) $taxonomies)) {
           return false;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['wp'] === $postType && $taxonomy['type'] === 'collection') {
                return true;
            }
        }
        return false;
    }

    public function getCollectionTaxonomyType(){
        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty( (array) $taxonomies)) {
           return false;
        }

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['type'] === 'collection') {
                return $taxonomy['wp'];
            }
        }
        return '';
    }

    public function getCollectionTaxonomyPostTypes() {

        $taxonomies = $this->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty( (array) $taxonomies)) {
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
    public function getFacetTaxonomyPostTypes() {
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
}