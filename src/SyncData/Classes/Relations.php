<?php

namespace WeAreHausTech\SyncData\Classes;

use WeAreHausTech\SyncData\Helpers\WpHelper;
use WeAreHausTech\SyncData\Helpers\WpmlHelper;
use WeAreHausTech\SyncData\Helpers\ConfigHelper;

class Relations
{
    public $defaultLang = '';
    public $useWpml = false;
    public $collectionTaxonomy = '';
    public $syncCollections = false;
    public $syncFacets = false;

    public function __construct()
    {
        $wpmlHelper = new WpmlHelper();
        $configHelper = new ConfigHelper();
        $this->useWpml = $wpmlHelper->hasWpml();
        $this->defaultLang = $wpmlHelper->getDefaultLanguage();
        $this->collectionTaxonomy = $configHelper->getCollectionTaxonomyType();
        $this->syncFacets = $configHelper->hasFacets();
        $this->syncCollections = $configHelper->hasCollection();
    }
    
    public function syncRelationships($vendureProducts)
    {
        $products = $this->getProductIds();
        $this->syncFacets && $facets = $this->getFacetids();
        $this->syncCollections && $collections = $this->getCollectionids();

        if ( !$this->syncFacets && !$this->syncCollections) {
            return;
        }

        foreach ($vendureProducts as $vendureId => $vendureProduct) {
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

        if (!isset($this->collectionTaxonomy)){
            return;
        }

        foreach ($collectionsData as $wpProductId => $collectionData) {
            wp_set_object_terms($wpProductId, $collectionData, $this->collectionTaxonomy);
        }
    }

    public function assignFacetValues($wpProduct, $facetValuesIds, $facets)
    {
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
