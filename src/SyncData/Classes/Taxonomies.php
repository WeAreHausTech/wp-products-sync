<?php

namespace WeAreHausTech\SyncData\Classes;

use WeAreHausTech\SyncData\Helpers\WpmlHelper;
use WeAreHausTech\SyncData\Helpers\WpHelper;
use WeAreHausTech\SyncData\Helpers\VendureHelper;
use WeAreHausTech\SyncData\Helpers\ConfigHelper;

class Taxonomies
{
    public $createdTaxonomies = 0;
    public $updatedTaxonimies = 0;
    public $deletedTaxonomies = 0;
    public $defaultLang = '';

    public $useWpml = false;

    public function __construct()
    {
        $wpmlHelper = new WpmlHelper();
        $this->useWpml = $wpmlHelper->hasWpml();
        $this->defaultLang = $wpmlHelper->getDefaultLanguage();
    }

    public function syncTaxonomies()
    {
        $wpHelper = new WpHelper();
        $configHelper = new ConfigHelper();
        $taxonomies = $configHelper->getTaxonomiesFromConfig();

        if (!isset($taxonomies) || empty((array) $taxonomies)) {
            WpHelper::log(['No taxonomies found in config.json']);
            return;
        }

        $vendureHelper = new VendureHelper();
        $facets = $vendureHelper->getfacets();
        foreach ($taxonomies as $taxonomyType => $taxonomyInfo) {
            // Delete taxonomies with no vendure id and taxonomies with no translation language
            $termsToDelete = $wpHelper->deleteTermsWithMissingValues($taxonomyInfo['wp']);

            if (isset($termsToDelete) && count($termsToDelete) > 0) {
                foreach ($termsToDelete as $term) {
                    $this->deleteTerm($term['term_id'], $taxonomyInfo['wp']);
                }
            }

            if ($taxonomyType === 'collection') {
                $venudreDefaultRootCollection =  "1";
                $rootCollection = $taxonomyInfo['rootCollectionId'] ?? $venudreDefaultRootCollection;
                $vendureValues = $vendureHelper->getCollectionsFromVendure($rootCollection);
                $wpTerms = $wpHelper->getAllCollectionsFromWp($taxonomyInfo['wp']);
                $this->findMissMatchedTaxonomies($taxonomyInfo['wp'], $vendureValues, $wpTerms);
                $this->syncAttributes($taxonomyInfo['wp'], $vendureValues, $wpTerms, $rootCollection);
                continue;
            } else {
                $vendureValues = $facets[$taxonomyInfo['vendure']];
                $wpTerms = $wpHelper->getAllTermsFromWp($taxonomyInfo['wp']);
                $this->findMissMatchedTaxonomies($taxonomyInfo['wp'], $vendureValues, $wpTerms);
                $this->syncAttributes($taxonomyInfo['wp'], $vendureValues, $wpTerms);
            }
        }
    }

    public function findMissMatchedTaxonomies($taxonomy, $vendureTerms, $wpTerms)
    {
        foreach ($vendureTerms as $vendureId => $vendureTerm) {
            foreach ($wpTerms as $wpId => $wpTerm) {
                if ($wpTerm['name'] === null || ($wpId === $vendureId && html_entity_decode($wpTerm['name']) !== $vendureTerm['name'])) {
                    $this->deleteTranslation($taxonomy, $wpTerm);
                    $this->deleteTerm($wpTerm['term_id'] ?? null, $taxonomy);
                    WpHelper::log(['Deleted taxonomy missmatch', $taxonomy, $vendureTerm['name']]);
                }
            }
        }
    }

    public function syncAttributes($taxonomy, $vendureTerms, $wpTerms, $rootCollection = null)
    {

        //Exists in WP, not in Vendure
        $delete = array_diff_key($wpTerms, $vendureTerms);

        array_walk($delete, function ($term) use ($taxonomy) {
            $this->deleteTranslation($taxonomy, $term);
            $this->deleteTerm($term['term_id'], $taxonomy);
        });

        //Exists in Vendure, not in WP
        $create = array_diff_key($vendureTerms, $wpTerms);

        array_walk($create, function ($term) use ($taxonomy) {
            $this->addNewTerm($term, $taxonomy);
        });

        //Exists in Vendure and in  WP 
        $update = array_intersect_key($vendureTerms, $wpTerms);

        foreach ($update as $vendureId => $vendureTerm) {
            $this->updateTerm($wpTerms[$vendureId], $vendureTerm, $taxonomy);
        }

        // Add parents for collections, needs to be done after all terms are created
        $configHelper = new ConfigHelper();
        $isCollection = $configHelper->isCollection($taxonomy);

        if ($isCollection) {
            foreach ($vendureTerms as $vendureId => $vendureTerm) {
                $wpTerm = $wpTerms[$vendureId];
                if ($vendureTerm['updatedAt'] !== $wpTerm['vendure_updated_at']) {
                    $this->syncCollectionParents($vendureId, $vendureTerm['parentId'], $taxonomy, $rootCollection);
                }
            }
        }
    }

    public function getVendureTermSlug($vendureTerm)
    {
        if (isset($vendureTerm['slug'])) {
            return $vendureTerm['slug'];
        } else {
            return sanitize_title($vendureTerm['name']);
        }
        return null;
    }

    public function updateTerm($wpTerm, $vendureTerm, $taxonomy)
    {
        $update = $this->isUpdatedInVendure($wpTerm, $vendureTerm);

        if ($update === []) {
            return;
        }

        foreach ($update as $lang) {
            $vendureSlug = $this->getVendureTermSlug($vendureTerm);

            if ($lang === $this->defaultLang) {
                $termImage = $vendureTerm['assets'] ? $vendureTerm['assets'][0]['source'] : null;
                $this->updateTaxonomy($wpTerm['term_id'] ?? null, $taxonomy, $vendureTerm['name'], $vendureSlug, $vendureTerm['updatedAt'], $vendureTerm['customFields'] ?? null, $vendureTerm['description'] ?? '', $termImage);
                continue;
            }

            if (isset($wpTerm['translations'][$lang]['term_id'])) {
                $translatedTermId = (int) $wpTerm['translations'][$lang]['term_id'];
                $translatedName = $vendureTerm['translations'][$lang]['name'];
                $translatedDescription = $vendureTerm['translations'][$lang]['description'] ?? '';
                $name = $vendureTerm['translations'][$lang]['name'];
                $data = $vendureTerm['translations'][$lang];
                $termImage = $vendureTerm['translations'][$lang]['assets'] ? $vendureTerm['translations'][$lang]['assets'][0]['source'] : null;

                $translatedSlug = $this->getSlugForTranslations($name, $data, $lang);
                $this->updateTaxonomy($translatedTermId, $taxonomy, $translatedName, $translatedSlug, $vendureTerm['updatedAt'], $vendureTerm['translations'][$lang]['customFields'] ?? null, $translatedDescription, $termImage);
            } else {
                $configHelper = new ConfigHelper();
                $isCollection = $configHelper->isCollection($taxonomy);
                $vendureType = $isCollection ? 'vendure_collection_id' : 'vendure_term_id';
                $this->createTranslatedTerm($vendureTerm, $taxonomy, $wpTerm['term_id'], $lang, $vendureType);
            }
        }
    }

    public function getCustomFields($customFields)
    {

        $data = [];

        if (!$customFields) {
            return $data;
        }

        foreach ($customFields as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $value['source'];
            } else if ($value === false) {
                // set to 0 if false to avoid empty string
                $data[$key] = 0;
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public function updateTaxonomy($termID, $taxonomy, $name, $slug, $updatedAt, $customFields = null, $description = '', $termImage = null)
    {
        $args = array(
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
        );

        wp_update_term($termID, $taxonomy, $args);

        $meta = array(
            'vendure_updated_at' => $updatedAt,
        );
        $customFields = $this->getCustomFields($customFields);
        $termMeta = array_merge($meta, $customFields);

        foreach ($termMeta as $key => $value) {
            update_term_meta($termID, $key, $value);
        }

        if ($termImage) {
            update_term_meta($termID, 'vendure_term_image', $termImage);
        }

        WpHelper::log(['Updating taxonomy', $taxonomy, $name, $slug]);

        $this->updatedTaxonimies++;
    }
    public function createTranslatedTerm($vendureTerm, $taxonomy, $originalId, $lang, $vendureType)
    {
        $name = $vendureTerm['translations'][$lang]['name'];
        $slug = $this->getSlugForTranslations($vendureTerm['name'], $vendureTerm['translations'][$lang], $lang);
        $description = $vendureTerm['translations'][$lang]['description'] ?? '';
        $termImage = $vendureTerm['translations'][$lang]['assets'] ? $vendureTerm['translations'][$lang]['assets'][0]['source'] : null;

        $customFields = $vendureTerm['translations'][$lang]['customFields'] ? $this->getCustomFields($vendureTerm['translations'][$lang]['customFields']) : null;

        $term = $this->insertTerm($vendureTerm['id'], $name, $slug, $taxonomy, $vendureType, $vendureTerm['updatedAt'], $customFields, $description, $termImage);

        $translations[$lang] = $term;
        $wmplType = 'tax_' . $taxonomy;
        $originalIdInt = (int) $originalId;

        $wpmlHelper = new WpmlHelper();

        $wpmlHelper->setLanguageDetails($originalIdInt, $translations, $wmplType);

        WpHelper::log(['Creating translated term', $lang, $taxonomy, $name, $slug]);

        $this->createdTaxonomies++;
    }


    public function isUpdatedInVendure($wpTerm, $vendureTerm)
    {
        $updateLang = [];

        if ($vendureTerm['updatedAt'] === $wpTerm['vendure_updated_at']) {
            return $updateLang;
        }

        $updateLang[] = $this->defaultLang;

        if (!$this->useWpml) {
            return $updateLang;
        }

        foreach ($wpTerm['translations'] as $lang => $translation) {
            $updateLang[] = $lang;
        }

        return $updateLang;
    }

    public function deleteTerm($id, $taxonomy)
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->termmeta WHERE term_id = %d",
                $id
            )
        );

        $wpdb->delete(
            $wpdb->term_taxonomy,
            array('term_id' => $id),
            array('%d')
        );

        $wpdb->delete(
            $wpdb->terms,
            array('term_id' => $id),
            array('%d')
        );

        WpHelper::log(['Deleting taxonomy', $taxonomy, $id]);

        $this->deletedTaxonomies++;
    }

    public function deleteTranslation($taxonomy, $term)
    {
        foreach ($term['translations'] as $lang => $translation) {
            if ($translation && $translation['term_id']) {
                $this->deleteTerm($translation['term_id'], $taxonomy);
            }
        }
    }

    public function addNewTerm($value, $taxonomy)
    {
        $configHelper = new ConfigHelper();
        $isCollection = $configHelper->isCollection($taxonomy);
        $vendureType = $isCollection ? 'vendure_collection_id' : 'vendure_term_id';
        $wmplType = 'tax_' . $taxonomy;

        $original = $this->addNewTermOriginal($value, $taxonomy, $vendureType);

        if (!$this->useWpml) {
            $this->createdTaxonomies++;
            return;
        }

        $translations = $this->addNewTermTranslations($value, $taxonomy, $vendureType);

        if ($original && $translations) {
            $wpmlHelper = new WpmlHelper();
            $wpmlHelper->setLanguageDetails($original, $translations, $wmplType);
        }

        $this->createdTaxonomies++;
    }

    public function addNewTermOriginal($value, $taxonomy, $vendureType)
    {
        $slug = isset($value['slug']) ? $value['slug'] : sanitize_title($value['name']);

        $customFields = isset($value['customFields']) ? $this->getCustomFields($value['customFields']) : null;
        $description = $value['description'] ?? '';
        $termImage = $value['assets'] ? $value['assets'][0]['source'] : null;

        $term = $this->insertTerm($value['id'], $value['name'], $slug, $taxonomy, $vendureType, $value['updatedAt'], $customFields, $description, $termImage);

        WpHelper::log(['Creating taxonomy', $taxonomy, $value['name'], $slug]);

        return $term;
    }

    public function addNewTermTranslations($value, $taxonomy, $vendureType)
    {
        $translations = [];

        foreach ($value['translations'] as $lang => $translation) {

            $slug = $this->getSlugForTranslations($value['name'], $translation, $lang);

            $customFields = $translation['customFields'] ? $this->getCustomFields($translation['customFields']) : null;
            $description = $translation['description'] ?? '';

            $termImage = $translation['assets'] ? $translation['assets'][0]['source'] : null;

            $term = $this->insertTerm($value['id'], $translation['name'], $slug, $taxonomy, $vendureType, $value['updatedAt'], $customFields, $description, $termImage);
            $translations[$lang] = $term;
        }

        WpHelper::log(['Creating taxonomy translation', $lang, $taxonomy, $value['name'], $slug]);
        return $translations;
    }

    public function getSlugForTranslations($defaultName, $translation, $lang)
    {
        //slug only exists for collections. Slugs for facets are generated from name, if name is same for different languages it will add langcode to make it unique. 
        if (isset($translation['slug'])) {
            return $translation['slug'];
        } else {
            if ($defaultName === $translation['name']) {
                return sanitize_title($translation['name']) . '-' . $lang;
            } else {
                return sanitize_title($translation['name']);
            }
        }
    }

    public function insertTerm($vendureId, $name, $slug, $taxonomy, $vendureType, $updatedAt, $customFields = null, $description = '', $termImage = null)
    {
        $term = wp_insert_term($name, $taxonomy, ['slug' => $slug, 'description' => $description]);

        if (is_wp_error($term)) {
            //TODO log error somewhere
            error_log($term->get_error_message());
            return;
        }

        add_term_meta($term['term_id'], $vendureType, $vendureId, true);
        add_term_meta($term['term_id'], 'vendure_updated_at', $updatedAt, true);

        if ($termImage) {
            add_term_meta($term['term_id'], 'vendure_term_image', $termImage, true);
        }

        if ($customFields) {
            foreach ($customFields as $key => $value) {
                add_term_meta($term['term_id'], $key, $value, true);
            }
        }

        return $term['term_id'];
    }

    public function getTermIdByVendureId($vendureId)
    {
        global $wpdb;

        $query =
            "SELECT term_id 
            FROM {$wpdb->prefix}termmeta
            WHERE meta_key = 'vendure_collection_id' 
            AND meta_value = $vendureId";

        return $wpdb->get_col($query);
    }

    public function getParentTermId($vendureParentId, $taxonomy, $rootCollection)
    {
        global $wpdb;

        // Vendures default parentId is 1 if root collection

        if ($vendureParentId === $rootCollection) {
            return 0;
        } else {
            global $wpdb;
            $query =
                "SELECT term_id 
                FROM {$wpdb->prefix}termmeta
                WHERE meta_key = 'vendure_collection_id' 
                AND meta_value = $vendureParentId";

            $ids = $wpdb->get_col($query);
            $terms = [];

            foreach ($ids as $id) {
                $wpmlHelper = new WpmlHelper();
                $lang = $wpmlHelper->getTermLanguage($id, $taxonomy);
                $terms[$lang] = $id;
            }

            return $terms;
        }
    }

    public function syncCollectionParents($vendureId, $vendureParentId, $taxonomy, $rootCollection)
    {
        $collectionTermIds = $this->getTermIdByVendureId($vendureId);
        $collectionParentIds = $this->getParentTermId($vendureParentId, $taxonomy, $rootCollection);
        $parentData = [];

        foreach ($collectionTermIds as $id) {
            $wpmlHelper = new WpmlHelper();
            $lang = $wpmlHelper->getTermLanguage($id, $taxonomy);
            $parentId = $collectionParentIds === 0 ? 0 : $collectionParentIds[$lang];
            $parentData[$lang] = [
                'id' => $id,
                'parentId' => $parentId
            ];
        }

        foreach ($parentData as $id) {
            wp_update_term((int) $id['id'], $taxonomy, ['parent' => (int) $id['parentId']]);
        }
    }
}