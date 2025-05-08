<?php

namespace WeAreHausTech\WpProductSync\Classes;

use WeAreHausTech\WpProductSync\Helpers\WpmlHelper;
use WeAreHausTech\WpProductSync\Helpers\WpHelper;
use WeAreHausTech\WpProductSync\Helpers\VendureHelper;
use WeAreHausTech\WpProductSync\Helpers\ConfigHelper;

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
            WpHelper::log(['No taxonomies found in settings']);
            return;
        }

        $vendureHelper = new VendureHelper();
        $facets = $vendureHelper->getfacets();
        foreach ($taxonomies as $taxonomyInfo) {
            // Delete taxonomies with no vendure id and taxonomies with no translation language
            $termsToDelete = $wpHelper->deleteTermsWithMissingValues($taxonomyInfo['wp']);

            if (isset($termsToDelete) && count($termsToDelete) > 0) {
                foreach ($termsToDelete as $term) {
                    $this->deleteTerm($term['term_id'], $taxonomyInfo['wp']);
                }
            }

            if ($taxonomyInfo['type'] === 'collection') {
                $venudreDefaultRootCollection = "1";
                $rootCollection = $taxonomyInfo['rootCollectionId'] ?? $venudreDefaultRootCollection;
                $vendureValues = $vendureHelper->getCollectionsFromVendure($rootCollection);
                $wpTerms = $wpHelper->getAllCollectionsFromWp($taxonomyInfo['wp']);
                $this->syncAttributes($taxonomyInfo['wp'], $vendureValues, $wpTerms, $rootCollection);
            }

            if ($taxonomyInfo['type'] === 'facet') {
                $vendureValues = $facets[$taxonomyInfo['vendure']];
                $wpTerms = $wpHelper->getAllTermsFromWp($taxonomyInfo['wp']);
                $this->syncAttributes($taxonomyInfo['wp'], $vendureValues, $wpTerms);
            }
        }
    }

    public function wpTermsThatAreSoftdeleted($wpTerms)
    {
        return array_filter($wpTerms, function ($term) {
            return $term['vendure_soft_deleted'] === "1";
        });
    }

    public function syncAttributes($taxonomy, $vendureTerms, $wpTerms, $rootCollection = null)
    {
        // Is soft deleted in WP, exists in Vendure
        $restore = array_intersect_key($this->wpTermsThatAreSoftdeleted($wpTerms), $vendureTerms);

        array_walk($restore, function ($term) use ($taxonomy) {
            $this->restoreTerm($term, $taxonomy);
        });

        //Exists in WP, not in Vendure
        $delete = array_diff_key($wpTerms, $vendureTerms);

        array_walk($delete, function ($term) use ($taxonomy) {
            if ($term['vendure_soft_deleted'] === "1") {
                return;
            }
            $this->deleteTranslation($taxonomy, $term);
            $this->deleteTerm($term['term_id'], $taxonomy);
        });

        //Exists in Vendure, not in WP
        $create = array_diff_key($vendureTerms, $wpTerms);

        array_walk($create, callback: function ($term) use ($taxonomy) {
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
                $wpTerm = isset($wpTerms[$vendureId]) ? $wpTerms[$vendureId] : null;

                if (empty($wpTerm) || $vendureTerm['updatedAt'] !== $wpTerm['vendure_updated_at']) {
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
    }

    public function updateTerm($wpTerm, $vendureTerm, $taxonomy)
    {
        $update = $this->isUpdatedInVendure($wpTerm, $vendureTerm);

        if ($update === []) {
            return;
        }

        foreach ($update as $lang) {
            $vendureSlug = $this->getVendureTermSlug($vendureTerm);
            $customFields = isset($vendureTerm['customFields']) ? $vendureTerm['customFields'] : null;
            $position = isset($vendureTerm['position']) ? $vendureTerm['position'] : null;
            $description = isset($vendureTerm['description']) ? $vendureTerm['description'] : '';

            if ($lang === $this->defaultLang) {
                $termImage = $vendureTerm['assets'] ? $vendureTerm['assets'][0]['source'] : null;
                $this->updateTaxonomy($wpTerm['term_id'] ?? null, $taxonomy, $vendureTerm['name'], $vendureSlug, $vendureTerm['updatedAt'], $customFields, $description, $termImage, $position);
                continue;
            }

            if (isset($wpTerm['translations'][$lang]['term_id'])) {
                $translatedTermId = (int) $wpTerm['translations'][$lang]['term_id'];
                $translatedName = $vendureTerm['translations'][$lang]['name'];
                $name = $vendureTerm['translations'][$lang]['name'];
                $data = $vendureTerm['translations'][$lang];
                $translatedDescription = $vendureTerm['translations'][$lang]['description'] ?? '';
                $termImage = $vendureTerm['translations'][$lang]['assets'] ? $vendureTerm['translations'][$lang]['assets'][0]['source'] : null;
                $translatedSlug = $this->getSlugForTranslations($name, $data, $lang);
                $this->updateTaxonomy($translatedTermId, $taxonomy, $translatedName, $translatedSlug, $vendureTerm['updatedAt'], $vendureTerm['translations'][$lang]['customFields'] ?? null, $translatedDescription, $termImage, $position);
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

    public function updateTaxonomy($termID, $taxonomy, $name, $slug, $updatedAt, $customFields = null, $description = '', $termImage = null, $position = null)
    {
        $args = array(
            'name' => $name,
            'slug' => $slug,

        );

        if (ConfigHelper::getSettingByKey('taxonomySyncDescription')) {
            $args['description'] = $description;
        }

        $updatedTerm = wp_update_term($termID, $taxonomy, $args);

        if (is_wp_error($updatedTerm)) {
            if ($updatedTerm->get_error_code() === 'duplicate_term_slug') {
                $updatedTerm = $this->handleTermExistOnUpdateError($termID, $slug, $taxonomy, $args);
            }

            if (is_wp_error($updatedTerm)) {
                WpHelper::log(['Error updating term with vendureid:', $termID, $updatedTerm->get_error_code()]);
                return;
            }
        }

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

        if ($position !== null) {
            update_term_meta($termID, 'vendure_term_position', $position);
        }

        $this->handleSlugMismatch($termID, $slug);

        WpHelper::log(['Updating taxonomy', $taxonomy, $name, $slug]);

        $this->updatedTaxonimies++;
    }
    public function createTranslatedTerm($vendureTerm, $taxonomy, $originalId, $lang, $vendureType)
    {
        $name = $vendureTerm['translations'][$lang]['name'];
        $slug = $this->getSlugForTranslations($vendureTerm['name'], $vendureTerm['translations'][$lang], $lang);
        $termImage = $vendureTerm['translations'][$lang]['assets'] ? $vendureTerm['translations'][$lang]['assets'][0]['source'] : null;
        $description = $vendureTerm['translations'][$lang]['description'] ?? '';
        $customFields = $vendureTerm['translations'][$lang]['customFields'] ? $this->getCustomFields($vendureTerm['translations'][$lang]['customFields']) : null;
        $position = isset($vendureTerm['position']) ? $vendureTerm['position'] : null;

        $term = $this->insertTerm($vendureTerm['id'], $name, $slug, $taxonomy, $vendureType, $vendureTerm['updatedAt'], $customFields, $description, $termImage, $position);

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

    public function restoreTerm($term, $taxonomy)
    {
        $wpHelper = new WpHelper();
        $wpHelper->setSoftDeletedStatus($term['term_id'], false, taxonomy: $taxonomy);

        if (!isset($term['translations'])) {
            return;
        }

        foreach ($term['translations'] as $lang => $translation) {
            if ($translation && $translation['term_id']) {

                $wpHelper->setSoftDeletedStatus($translation['term_id'], false, $taxonomy);
            }
        }
    }

    public function deleteTerm($id, $taxonomy)
    {
        $softDelete = ConfigHelper::getSettingByKey('softDelete');
        $wpHelper = new WpHelper();
        if ($softDelete) {
            $wpHelper->setSoftDeletedStatus($id, true, $taxonomy);
            $this->deletedTaxonomies++;
            return;
        }

        $wpHelper->hardDeleteTerm($id, $taxonomy);
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
        $termImage = isset($value['assets'][0]['source']) ? $value['assets'][0]['source'] : null;
        $position = isset($value['position']) ? $value['position'] : null;
        $description = isset($value['description']) ? $value['description'] : '';

        $term = $this->insertTerm($value['id'], $value['name'], $slug, $taxonomy, $vendureType, $value['updatedAt'], $customFields, $description, $termImage, $position);

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

            $termImage = isset($translation['assets'][0]['source']) ? $translation['assets'][0]['source'] : null;
            $position = isset($value['position']) ? $value['position'] : null;
            $term = $this->insertTerm($value['id'], $translation['name'], $slug, $taxonomy, $vendureType, $value['updatedAt'], $customFields, $description, $termImage, $position);
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

    public function insertTerm($vendureId, $name, $slug, $taxonomy, $vendureType, $updatedAt, $customFields = null, $description = '', $termImage = null, $position = null)
    {
        $args = ['slug' => $slug];

        if (ConfigHelper::getSettingByKey('taxonomySyncDescription')) {
            $args['description'] = $description;
        }

        $term = wp_insert_term($name, $taxonomy, $args);


        if (is_wp_error($term)) {
            if ($term->get_error_code() === 'term_exists') {
                $term = $this->handleTermExistOnInsertError($term, $name, $slug, $taxonomy, $args);
            }

            if (is_wp_error($term)) {
                WpHelper::log(['Error creating term with vendureid:', $vendureId, $term->get_error_code()]);
                return;
            }
        }

        add_term_meta($term['term_id'], $vendureType, $vendureId, true);
        add_term_meta($term['term_id'], 'vendure_updated_at', $updatedAt, true);

        if ($position !== null) {
            add_term_meta($term['term_id'], 'vendure_term_position', $position, true);
        }

        if ($termImage) {
            add_term_meta($term['term_id'], 'vendure_term_image', $termImage, true);
        }

        if ($customFields) {
            foreach ($customFields as $key => $value) {
                add_term_meta($term['term_id'], $key, $value, true);
            }
        }

        $this->handleSlugMismatch($term['term_id'], $slug);

        return $term['term_id'];
    }

    private function handleTermExistErrorRetry(callable $callback, $slug, $taxonomy, $args)
    {
        $retryErrorCodes = [
            'term_exists',
            'duplicate_term_slug'
        ];
        for ($suffix = 2; ; $suffix++) {
            $args['slug'] = "{$slug}-{$suffix}";
            $result = $callback($taxonomy, $args);

            if (!is_wp_error($result) || !in_array($result->get_error_code(), $retryErrorCodes, true)) {
                return $result;
            }
        }
    }

    private function handleTermExistOnInsertError($term, $name, $slug, $taxonomy, $args)
    {
        return $this->handleTermExistErrorRetry(
            fn($taxonomy, $args) => wp_insert_term($name, $taxonomy, $args),
            $slug,
            $taxonomy,
            $args
        );
    }

    private function handleTermExistOnUpdateError($termID, $slug, $taxonomy, $args)
    {
        return $this->handleTermExistErrorRetry(
            fn($taxonomy, $args) => wp_update_term($termID, $taxonomy, $args),
            $slug,
            $taxonomy,
            $args
        );
    }

    public function handleSlugMismatch($termId, $expectedSlug)
    {
        $currentSlug = get_term_field('slug', $termId);

        if ($currentSlug === $expectedSlug) {
            delete_term_meta($termId, 'vendure_slug_mismatch');
            return;
        }

        WpHelper::log([
            'Slug mismatch taxonomy',
            'Vendure slug' => $expectedSlug,
            'Created WP slug' => $currentSlug,
            'Term ID' => $termId,
        ]);

        update_term_meta($termId, 'vendure_slug_mismatch', true);
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

        delete_option($taxonomy . '_children');
    }
}