<?php

namespace WeAreHausTech\WpProductSync\Classes;

use WeAreHausTech\WpProductSync\Helpers\WpHelper;
use WeAreHausTech\WpProductSync\Helpers\WpmlHelper;
use WeAreHausTech\WpProductSync\Helpers\ConfigHelper;
use WeAreHausTech\WpProductSync\Classes\Products;

class Relations
{
    public $defaultLang = '';
    public $useWpml = false;
    public $collectionTaxonomy = '';
    public $syncCollections = false;
    public $syncFacets = false;
    public $updatedOrCreatedProductIds = [];
    public $facetTypes = [];

    public function __construct(Products $products)
    {
        $wpmlHelper = new WpmlHelper();
        $configHelper = new ConfigHelper();
        $this->useWpml = $wpmlHelper->hasWpml();
        $this->defaultLang = $wpmlHelper->getDefaultLanguage();
        $this->collectionTaxonomy = $configHelper->getCollectionTaxonomyType();
        $this->syncFacets = $configHelper->hasFacets();
        $this->syncCollections = $configHelper->hasCollection();
        $this->updatedOrCreatedProductIds = $products->updatedOrCreatedProductIds;
        $this->facetTypes = $configHelper->getFacetTypesInWP();
    }

    public function syncRelationships($vendureProducts)
    {
        $products = $this->getProductIds();
        $this->syncFacets && $facets = $this->getFacetids();
        $this->syncCollections && $collections = $this->getCollectionids();

        if (!$this->syncFacets && !$this->syncCollections) {
            return;
        }
        foreach ($vendureProducts as $vendureId => $vendureProduct) {
            if (!in_array($vendureId, $this->updatedOrCreatedProductIds)) {
                continue;
            }

            $wpProduct = $products[$vendureId];
            $this->syncFacets && $this->assignFacetValues($wpProduct, $vendureProduct['facetValueIds'], $facets);
            $this->syncCollections && $this->assignCollectionValues($wpProduct, $vendureProduct['collectionIds'], $collections);
        }
    }

    public function getProductIds()
    {
        $wpHelper = new WpHelper();

        $productData = $wpHelper->getProductIds();

        return $this->combineVendureIds($productData);
    }

    public function getFacetids()
    {
        $wpHelper = new WpHelper();

        $termsData = $wpHelper->getFacetids();

        return $this->combineVendureIds($termsData);
    }

    public function getCollectionids()
    {
        $wpHelper = new WpHelper();

        $termsData = $wpHelper->getCollectionids();

        return $this->combineVendureIds($termsData);
    }

    public function combineVendureIds($incomingData)
    {
        $seen = [];
        $returnData = [];

        foreach ($incomingData as $data) {
            $id = $data['vendure_id'];

            if (!in_array($id, $seen)) {
                $seen[] = $id;
                $returnData[$id] = [
                    'vendure_id' => $id,
                    'taxonomy' => isset($data['taxonomy']) ? $data['taxonomy'] : null,
                    'ids' => []
                ];
            }

            if ($this->useWpml) {
                $returnData[$id]['ids'][$data['lang']] = $data['ID'];
            } else {
                $returnData[$id]['ids']['sv'] = $data['ID'];
            }
        }

        return $returnData;
    }

    public function assignCollectionValues($wpProduct, $collectionIds, $collections)
    {
        $this->removeAllTermsOfType($wpProduct, $this->collectionTaxonomy);
        $collectionsData = array();
        foreach ($collectionIds as $collectionValueId) {
            if (!isset($collections[$collectionValueId])) {
                continue;
            }

            foreach ($wpProduct['ids'] as $lang => $wpProductId) {
                if (!isset($collections[$collectionValueId]["ids"][$lang])) {
                    continue;
                }

                $collectionsData[$wpProductId][] = (int) $collections[$collectionValueId]["ids"][$lang];
            }
        }

        if (!isset($this->collectionTaxonomy)) {
            return;
        }

        foreach ($collectionsData as $wpProductId => $collectionData) {
            wp_set_object_terms($wpProductId, $collectionData, $this->collectionTaxonomy);
        }
    }

    public function removeAllTermsOfType($wpProduct, $taxonomyType)
    {
        foreach ($wpProduct['ids'] as $lang => $wpProductId) {
            $terms = wp_get_object_terms($wpProductId, $taxonomyType);


            if (!empty($terms) && !is_wp_error($terms)) {
                $termIds = wp_list_pluck($terms, 'term_id');

                if (!empty($termIds)) {
                    wp_remove_object_terms($wpProductId, $termIds, $taxonomyType);
                }
            }
        }
    }

    public function assignFacetValues($wpProduct, $facetValuesIds, $facets)
    {
        foreach ($this->facetTypes as $facetType) {
            $this->removeAllTermsOfType($wpProduct, $facetType);
        }

        foreach ($facetValuesIds as $facetValueId) {
            if (!isset($facets[$facetValueId])) {
                continue;
            }

            foreach ($wpProduct['ids'] as $lang => $wpProductId) {
                if (!isset($facets[$facetValueId]["ids"][$lang])) {
                    continue;
                }
                $wpTermId = $facets[$facetValueId]["ids"][$lang];
                $taxonomy = $facets[$facetValueId]["taxonomy"];

                wp_set_object_terms($wpProductId, (int) $wpTermId, $taxonomy);
            }
        }
    }
}
