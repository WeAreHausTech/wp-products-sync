<?php

namespace WeAreHausTech\SyncData\Helpers;

use WeAreHausTech\SyncData\Helpers\WpmlHelper;
use WeAreHausTech\SyncData\Classes\Products;
use WeAreHausTech\SyncData\Classes\Taxonomies;

class WpHelper
{

    public $defaultLang = '';

    public $useWpml = false;

    public function __construct()
    {
        $wpmlHelper = new WpmlHelper();
        $this->useWpml = $wpmlHelper->hasWpml();
        $this->defaultLang = $wpmlHelper->getDefaultLanguage();
    }

    public static function log($out)
    {
        echo implode(', ', $out) . PHP_EOL;
    }

    public function getAllProductsFromWp()
    {
        $products = $this->getProductsDefaultLang();
        $wpmlHelper = new WpmlHelper();
        $avalibleTranslations = $wpmlHelper->getAvalibleTranslations();

        foreach ($avalibleTranslations as $lang) {
            foreach ($products as $key => $value) {
                if ($lang === $this->defaultLang) {
                    continue;
                }
                if ($products[$key]) {
                    $products[$key]['translations'][$lang] = $this->getProductTranslations($value['id'], $lang);
                }
            }
        }
        return $products;
    }

    public function getProductsToExclude()
    {
        global $wpdb;

        $shouldBeExcluded = "1";
        $queryExclude = 
            "SELECT p.ID as id
             FROM {$wpdb->prefix}posts p
             LEFT JOIN {$wpdb->prefix}postmeta pm2
                 ON p.ID = pm2.post_id
                AND pm2.meta_key = 'exclude_from_sync'
             WHERE p.post_type = 'produkter'
             AND pm2.meta_value = $shouldBeExcluded";

        $exclude = $wpdb->get_results($queryExclude, ARRAY_A);

        $wpmlHelper = new WpmlHelper();
        $avalibleTranslations = $wpmlHelper->getAvalibleTranslations();

        // get the translations after because exclude_from_sync does just exist in default lang
        foreach ($exclude as $product) {
            foreach ($avalibleTranslations as $lang) {
                if ($lang === $this->defaultLang) {
                    continue;
                }
                $exclude[] = $this->getProductTranslations($product['id'], $lang);
            }
        }

        $excludedIds = [];
        foreach ($exclude as $product) {
            $excludedIds[] = intval($product['id']);
        }

        return $excludedIds;
    }

    public function deleteAllProductsWithoutVendureId()
    {
        global $wpdb;

        $query =
            "SELECT p.ID
             FROM {$wpdb->prefix}posts p
             LEFT JOIN {$wpdb->prefix}postmeta pm
                ON p.ID = pm.post_id
                AND pm.meta_key = 'vendure_id'
             WHERE p.post_type = 'produkter'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')";
        

        $productsToDelete = $wpdb->get_results($query, ARRAY_A);

        if (empty($productsToDelete)) {
            return;
        }

        $productsToExclude = $this->getProductsToExclude();
        $filteredProductsToDelete = array_filter($productsToDelete, function ($product) use ($productsToExclude) {
            return !in_array(intval($product['ID']), $productsToExclude);
        });

        foreach ($filteredProductsToDelete as $product) {
            $productsInstance = new Products();
            $productsInstance->deleteProduct($product['ID']);
        }
    }

    public function getProductsQuery()
    {

        global $wpdb;

        if ($this->useWpml) {
            return
                "SELECT p.ID as id, p.post_title, p.post_content, p.post_name, pm.meta_value as vendure_id, pm3.meta_value as vendure_updated_at, pm2.meta_value as exclude_from_sync, t.language_code as lang
                 FROM {$wpdb->prefix}posts p 
                 LEFT JOIN  {$wpdb->prefix}postmeta pm
                    ON p.ID = pm.post_id
                    AND pm.meta_key = 'vendure_id'
                LEFT JOIN {$wpdb->prefix}postmeta pm2
                    ON p.ID = pm2.post_id
                    AND pm2.meta_key = 'exclude_from_sync'
                LEFT JOIN {$wpdb->prefix}postmeta pm3
                    ON p.ID = pm3.post_id
                    AND pm3.meta_key = 'vendure_updated_at'
                LEFT JOIN {$wpdb->prefix}icl_translations t
                    ON p.ID = t.element_id
                    AND t.element_type = 'post_produkter'
                    AND t.language_code IS NOT NULL
                    WHERE t.language_code = 'sv'
                    AND post_type ='produkter'";
        } else {
            return
                "SELECT p.ID as id, p.post_title, p.post_name, p.post_content,pm.meta_value as vendure_id, pm3.meta_value as vendure_updated_at, pm2.meta_value as exclude_from_sync
                 FROM {$wpdb->prefix}posts p 
                 LEFT JOIN  {$wpdb->prefix}postmeta pm
                    ON p.ID = pm.post_id
                    AND pm.meta_key = 'vendure_id'
                LEFT JOIN {$wpdb->prefix}postmeta pm3
                    ON p.ID = pm3.post_id
                    AND pm3.meta_key = 'vendure_updated_at'
                LEFT JOIN {$wpdb->prefix}postmeta pm2
                    ON p.ID = pm2.post_id
                    AND pm.meta_key = 'exclude_from_sync'
                    WHERE post_type ='produkter'";
        }
    }

    public function getProductsDefaultLang()
    {
        $this->deleteAllProductsWithoutVendureId();
        global $wpdb;

        $query = $this->getProductsQuery();

        $products = $wpdb->get_results($query, ARRAY_A);
        return array_combine(array_column($products, 'vendure_id'), $products);
    }

    public function getProductTranslations($postId, $lang)
    {

        $translatedPostId = apply_filters('wpml_object_id', $postId, 'post', true, $lang);

        if ((int) $postId === $translatedPostId) {
            return [];
        }

        $translatedPost = get_post($translatedPostId);
        $data = [];

        if ($translatedPost) {
            $data = array(
                'post_title' => $translatedPost->post_title,
                'post_name' => $translatedPost->post_name,
                'id' => $translatedPost->ID,
            );
        }
        return $data;
    }

    public function collectionsQuery($taxonomy)
    {
        global $wpdb;
        $terms = $wpdb->prefix . 'terms';
        $termmeta = $wpdb->prefix . 'termmeta';
        if ($this->useWpml) {
            return $wpdb->prepare(
                "SELECT tt.term_id, tt.parent, t.name, t.slug, tm.meta_value as vendure_collection_id, tr.language_code as lang, tm2.meta_value as vendure_updated_at
             FROM wp_term_taxonomy tt 
             LEFT JOIN $terms t ON tt.term_id = t.term_id 
             LEFT JOIN $termmeta tm ON tt.term_id = tm.term_id
                AND tm.meta_key = 'vendure_collection_id'
             LEFT JOIN $termmeta tm2 ON tt.term_id = tm2.term_id
                    AND tm2.meta_key = 'vendure_updated_at'
             LEFT JOIN {$wpdb->prefix}icl_translations tr 
             ON tt.term_taxonomy_id = tr.element_id
             AND tr.element_type = 'tax_{$taxonomy}'
            WHERE tr.language_code IS NOT NULL
             AND taxonomy = %s",
                $taxonomy
            );
        } else {
            return $wpdb->prepare(
                "SELECT tt.term_id, tt.parent, t.name, t.slug, tm.meta_value as vendure_collection_id, tm2.meta_value as vendure_updated_at
             FROM wp_term_taxonomy tt 
             LEFT JOIN $terms t ON tt.term_id = t.term_id 
             LEFT JOIN $termmeta tm ON tt.term_id = tm.term_id
                AND tm.meta_key = 'vendure_collection_id'
             LEFT JOIN $termmeta tm2 ON tt.term_id = tm2.term_id
                    AND tm2.meta_key = 'vendure_updated_at'
             WHERE tm.meta_value IS NOT NULL
             AND taxonomy = %s",
                $taxonomy
            );
        }
    }
    public function getAllCollectionsFromWp($taxonomy)
    {
        global $wpdb;

        $terms = $wpdb->prefix . 'terms';
        $termmeta = $wpdb->prefix . 'termmeta';
        $wpCollections = [];

        $query = $this->collectionsQuery($taxonomy);

        $terms = $wpdb->get_results($query, ARRAY_A);

        // Add all translations into default lang object
        foreach ($terms as $term) {
            $vendureCollectionId = $term['vendure_collection_id'];

            if (!$this->useWpml) {
                $lang = $this->defaultLang;
            } else {
                $lang = $term['lang'];
            }

            if ($vendureCollectionId === '0' || $lang !== $this->defaultLang) {
                continue;
            }

            if (!isset($wpCollections[$vendureCollectionId])) {
                $wpCollections[$vendureCollectionId] = [
                    "term_id" => $term["term_id"],
                    "parent" => $term["parent"],
                    "name" => $term["name"],
                    "slug" => $term["slug"],
                    "vendure_collection_id" => $term["vendure_collection_id"],
                    "lang" => $this->defaultLang,
                    "vendure_updated_at" => $term["vendure_updated_at"],
                    "translations" => [],
                ];
            }
        }

        foreach ($terms as $term) {
            $vendureCollectionId = $term['vendure_collection_id'];

            if ($vendureCollectionId === '0') {
                continue;
            }

            if (!$this->useWpml) {
                $lang = $this->defaultLang;
            } else {
                $lang = $term['lang'];
            }

            if ($lang && $lang !== $this->defaultLang) {
                $wpCollections[$vendureCollectionId]['translations'][$lang] = [
                    "name" => $term["name"],
                    "slug" => $term["slug"],
                    "term_id" => $term["term_id"],
                ];
            }
        }
        return $wpCollections;
    }

    public function getTermsQuery($taxonomy)
    {

        global $wpdb;
        $terms = $wpdb->prefix . 'terms';
        $termmeta = $wpdb->prefix . 'termmeta';

        if ($this->useWpml) {
            return $wpdb->prepare(
                "SELECT tt.term_id, t.name, tm.meta_value as vendure_term_id, tr.language_code as lang, tm2.meta_value as vendure_updated_at
                FROM wp_term_taxonomy tt 
                LEFT JOIN $terms t ON tt.term_id = t.term_id 
                LEFT JOIN $termmeta tm ON tt.term_id = tm.term_id
                    AND tm.meta_key = 'vendure_term_id'
                LEFT JOIN $termmeta tm2 ON tt.term_id = tm2.term_id
                    AND tm2.meta_key = 'vendure_updated_at'
                LEFT JOIN {$wpdb->prefix}icl_translations tr 
                    ON tt.term_taxonomy_id = tr.element_id
                    AND tr.element_type = 'tax_{$taxonomy}'
                    WHERE tr.language_code IS NOT NULL
                AND tm.meta_value IS NOT NULL
                AND taxonomy= %s",
                $taxonomy
            );
        } else {
            return $wpdb->prepare(
                "SELECT tt.term_id, t.name, tm.meta_value as vendure_term_id, tm2.meta_value as vendure_updated_at
                FROM wp_term_taxonomy tt 
                LEFT JOIN $terms t ON tt.term_id = t.term_id 
                LEFT JOIN $termmeta tm ON tt.term_id = tm.term_id
                    AND tm.meta_key = 'vendure_term_id'
                LEFT JOIN $termmeta tm2 ON tt.term_id = tm2.term_id
                AND tm2.meta_key = 'vendure_updated_at'
                WHERE tm.meta_value IS NOT NULL
                AND taxonomy= %s",
                $taxonomy
            );
        }

    }

    public function getAllTermsFromWp($taxonomy)
    {
        global $wpdb;

        $wpFacets = [];

        $query = $this->getTermsQuery($taxonomy);

        $terms = $wpdb->get_results($query, ARRAY_A);

        foreach ($terms as $term) {
            $vendureFacetId = $term['vendure_term_id'];

            if (!$this->useWpml) {
                $lang = $this->defaultLang;
            } else {
                $lang = $term['lang'];
            }

            // If dobulettes exist, delete the one with default lang
            if (isset($wpFacets[$vendureFacetId]) && $lang === $this->defaultLang) {
                $taxonomiesInstance = new Taxonomies();
                $taxonomiesInstance->deleteTerm($term["term_id"], $taxonomy);
            }

            if (!isset($wpFacets[$vendureFacetId]) && $lang === $this->defaultLang) {
                $wpFacets[$vendureFacetId] = array(
                    "term_id" => $term["term_id"],
                    "name" => $term["name"],
                    "vendure_term_id" => $term["vendure_term_id"],
                    "lang" => $this->defaultLang,
                    "vendure_updated_at" => $term["vendure_updated_at"],
                    "translations" => [],
                );
            }

            if ($lang && $lang !== $this->defaultLang) {
                $wpFacets[$vendureFacetId]['translations'][$lang] = array(
                    "name" => $term["name"],
                    "term_id" => $term["term_id"],
                );
            }
        }

        return $wpFacets;
    }

    public function termsToDeleteQuery($taxonomy, $vendureType, $wpmlType)
    {
        global $wpdb;
        if ($this->useWpml) {
            return $wpdb->prepare(
                "SELECT tt.term_id
                FROM wp_term_taxonomy tt
                LEFT JOIN {$wpdb->prefix}icl_translations icl ON tt.term_id = icl.element_id AND icl.element_type = %s
                LEFT JOIN {$wpdb->prefix}termmeta tm ON tt.term_id = tm.term_id AND tm.meta_key = %s
                WHERE tt.taxonomy = %s
                AND (
                    tm.term_id IS NULL
                    OR tm.meta_value IS NULL
                    OR tm.meta_value = ''
                    OR icl.language_code IS NULL
                )",
                $wpmlType,
                $vendureType,
                $taxonomy
            );
        } else {
            return $wpdb->prepare(
                "SELECT tt.term_id
                FROM wp_term_taxonomy tt
                LEFT JOIN {$wpdb->prefix}termmeta tm ON tt.term_id = tm.term_id AND tm.meta_key = %s
                WHERE tt.taxonomy = %s
                AND (
                    tm.term_id IS NULL
                    OR tm.meta_value IS NULL
                    OR tm.meta_value = ''
                )",
                $vendureType,
                $taxonomy
            );
        }
    }

    public function deleteTermsWithMissingValues($taxonomy)
    {
        global $wpdb;
        $configHelper = new ConfigHelper();
        $isCollection = $configHelper->isCollection($taxonomy);
        $vendureType = $isCollection ? 'vendure_collection_id' : 'vendure_term_id';
        $wpmlType = 'tax_' . $taxonomy;

        $query = $this->termsToDeleteQuery($taxonomy, $vendureType, $wpmlType);

        return $wpdb->get_results($query, ARRAY_A);
    }

    public function getProductIds()
    {

        global $wpdb;

        if ($this->useWpml) {
            $query =
                "SELECT p.ID, pm.meta_value as vendure_id, t.language_code as lang
            FROM {$wpdb->prefix}posts p 
            LEFT JOIN {$wpdb->prefix}postmeta pm
                ON p.ID = pm.post_id
                AND pm.meta_key = 'vendure_id'
            LEFT JOIN {$wpdb->prefix}icl_translations t
            ON p.ID = t.element_id
            AND t.element_type = 'post_produkter'
            WHERE post_type ='produkter'";
        } else {
            $query = 
                "SELECT p.ID, pm.meta_value as vendure_id
                FROM {$wpdb->prefix}posts p 
                LEFT JOIN {$wpdb->prefix}postmeta pm
                    ON p.ID = pm.post_id
                    AND pm.meta_key = 'vendure_id'
                WHERE post_type ='produkter'";
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    public function getFacetids()
    {
        global $wpdb;
        $termmeta = $wpdb->prefix . 'termmeta';

        $configHelper = new ConfigHelper();
        $facetTaxonomies = $configHelper->getFacetTaxonomyPostTypes();
        $taxonomyPlaceholders = implode(', ', array_fill(0, count($facetTaxonomies), '%s'));

        if ($this->useWpml) {
            $query = $wpdb->prepare(
                "SELECT tt.term_id as ID, tt.taxonomy as taxonomy, tm.meta_value as vendure_id, tr.language_code as lang
                 FROM {$wpdb->prefix}term_taxonomy tt 
                 LEFT JOIN $termmeta tm ON tt.term_id = tm.term_id
                 AND tm.meta_key = 'vendure_term_id'
                 LEFT JOIN {$wpdb->prefix}icl_translations tr 
                 ON tt.term_taxonomy_id = tr.element_id
                 WHERE taxonomy IN ($taxonomyPlaceholders)",
                $facetTaxonomies
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT tt.term_id as ID, tt.taxonomy as taxonomy, tm.meta_value as vendure_id
                 FROM {$wpdb->prefix}term_taxonomy tt 
                 LEFT JOIN $termmeta tm ON tt.term_id = tm.term_id
                 AND tm.meta_key = 'vendure_term_id'
                 WHERE taxonomy IN ($taxonomyPlaceholders)",
                $facetTaxonomies
            );
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    public function getCollectionids()
    {
        global $wpdb;

        $termmeta = $wpdb->prefix . 'termmeta';
        $configHelper = new ConfigHelper();
        $collectionTaxonomy = $configHelper->getCollectionTaxonomyPostTypes();
        $taxonomyPlaceholders = implode(', ', array_fill(0, count($collectionTaxonomy), '%s'));

        if ($this->useWpml) {
            $query = $wpdb->prepare(
                "SELECT tt.term_id as ID, tt.taxonomy, tm.meta_value as vendure_id, tr.language_code as lang
                 FROM {$wpdb->prefix}term_taxonomy tt 
                 LEFT JOIN $termmeta tm ON tt.term_id = tm.term_id
                 AND tm.meta_key = 'vendure_collection_id'
                 LEFT JOIN {$wpdb->prefix}icl_translations tr 
                 ON tt.term_taxonomy_id = tr.element_id
                 WHERE taxonomy IN ($taxonomyPlaceholders)",
                $collectionTaxonomy
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT tt.term_id as ID, tt.taxonomy, tm.meta_value as vendure_id
                 FROM {$wpdb->prefix}term_taxonomy tt 
                 LEFT JOIN $termmeta tm ON tt.term_id = tm.term_id
                 AND tm.meta_key = 'vendure_collection_id'
                 WHERE taxonomy IN ($taxonomyPlaceholders)",
                $collectionTaxonomy
            );
        }

        return $wpdb->get_results($query, ARRAY_A);
    }
}

