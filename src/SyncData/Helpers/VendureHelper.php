<?php

namespace WeAreHausTech\SyncData\Helpers;

use WeAreHausTech\SyncData\Helpers\WpmlHelper;

class VendureHelper
{
    public $defaultLang = '';
    public $useWpml = false;

    public function __construct()
    {
        $wpmlHelper = new WpmlHelper();
        $this->useWpml = $wpmlHelper->hasWpml();
        $this->defaultLang = $wpmlHelper->getDefaultLanguage();
    }

    public function getAllProductsFromVendure()
    {
        $wpmlHelper = new WpmlHelper();
        $avalibleTranslations = $wpmlHelper->getAvalibleTranslations();

        $products = $this->getProductsByLang($this->defaultLang);
        $translations = [];

        foreach ($avalibleTranslations as $lang) {
            if ($lang === $this->defaultLang) {
                continue;
            }
            $translations[$lang] = $this->getProductsByLang($lang);
        }

        foreach ($translations as $lang => $translation) {
            foreach ($translation as $key => $value) {
                $products[$key]['translations'][$lang] = [
                    'name' => $value['name'],
                    'slug' => $value['slug'],
                    'description' => $value['description'],
                ];
            }
        }

        return $products;
    }

    public function getProductsByLang($lang)
    {
        $batchSize = 100;
        $skip = 0;

        $allProducts = [];

        do {
            $products = (new \WeAreHausTech\Queries\Product)->get($lang, $skip, $batchSize);
    
            $totalItems = $products['data']['products']['totalItems'];
    
            if (!empty($products['data']['products']['items'])) {
                $allProducts = array_merge($allProducts, $products['data']['products']['items']);
            }

            $skip = $skip + $batchSize;
        } while (count($allProducts) < $totalItems);

        foreach ($allProducts as $key => $item) {
            $allProducts[$key]['facetValueIds'] = array_column($item['facetValues'], 'id');
            $allProducts[$key]['collectionIds'] = array_column($item['collections'], 'id');
        }

        $unique = $this->getFirstUniqueTranslation($allProducts);

        return array_combine(array_column($unique, 'id'), $unique);
    }

    public function getFirstUniqueTranslation($translations)
    {
        $seenProductIds = array();
        $products = [];

        foreach ($translations as $translation) {
            $productId = $translation['id'];

            if (!in_array($productId, $seenProductIds)) {
                $seenProductIds[] = $productId;
                $products[] = $translation;
            }
        }

        return $products;
    }

    public function getCollectionsFromVendure($rootCollection)
    {
        $wpmlHelper = new WpmlHelper();
        $avalibleTranslations = $wpmlHelper->getAvalibleTranslations();
        $translations = [];
        $collections = $this->getAllCollectionsByParentIds($this->defaultLang, [$rootCollection]);

        foreach ($avalibleTranslations as $lang) {
            if ($lang === $this->defaultLang) {
                continue;
            }
            $translations[$lang] = $this->getAllCollectionsByParentIds($lang, [$rootCollection]);
        }
        //TO remove when vendure bug is fixed
        foreach ($collections as $coll) {
            if (!$coll['name']) {
                $collections[$coll['id']]['name'] = 'Saknar namn ' . rand();
                $collections[$coll['id']]['slug'] = 'saknar-namn-' . rand();
            }
        }

        foreach ($translations as $lang => $translation) {
            foreach ($translation as $key => $value) {

                $collections[$key]['translations'][$lang] = [
                    //TO remove custom name when vendure bug is fixed
                    "name" => $value['name'] ? $value['name'] : 'Missing name ' . rand(),
                    "slug" => $value['slug'] ? $value['slug'] . '-' . $lang : 'missing-name-' . rand() . '-' . $lang,
                    "customFields" => $value['customFields'] ?? null,
                ];
            }
        }
        return $collections;
    }

    public function getAllCollectionsByParentIds(string $lang, array $ids = []): array
    {
        $collections = [];
        $parentIds = $ids;
        $take = 100;

        while (!empty($parentIds)) {
            $skip = 0;
            $result = [];

            do {
                $queryResult = (new \WeAreHausTech\Queries\Collection)->get($lang, $skip, $take, $parentIds);
                $items = $queryResult['data']['collections']['items'];

                if (!empty($items)) {
                    $result = array_merge($result, $items);
                    $skip += $take;
                }

            } while (!empty($items) && count($items) === $take);

            if (!empty($result)) {
                $collections = array_merge($collections, $result);
                $parentIds = $this->extractParentIds($result);
            } else {
                $parentIds = [];
            }
        }

        return array_combine(array_column($collections, 'id'), $collections);
    }


    public function extractParentIds(array $collections): array
    {
        $ids = [];

        foreach ($collections as $collection) {
            if (isset($collection['id'])) {
                $ids[] = $collection['id'];
            }
        }

        return array_unique($ids);
    }

    public function getVendureCollectionData($lang, $data = [], $skip = 0, $take = 100)
    {
        $collections = (new \WeAreHausTech\Queries\Collection)->get($lang, $skip, $take);

        if (!isset($collections['data']['collections']['items'])) {
            return [];
        }

        $items = $collections['data']['collections']['items'];
        $totalItems = $collections['data']['collections']['totalItems'];

        $data = array_merge($data, $items);

        if (count($data) === $totalItems) {
            return array_combine(array_column($data, 'id'), $data);
        } else {
            return $this->getVendureCollectionData($lang, $data, $skip + 100, $take);
        }
    }
    public function getfacets()
    {
        $wpmlHelper = new WpmlHelper();
        $avalibleTranslations = $wpmlHelper->getAvalibleTranslations();
        $translations = [];
        $facets = $this->getFacetsFromVendure($this->defaultLang);

        foreach ($avalibleTranslations as $lang) {
            if ($lang === $this->defaultLang) {
                continue;
            }
            $translations[$lang] = $this->getFacetsFromVendure($lang);
        }

        foreach ($translations as $lang => $translation) {
            foreach ($translation as $key => $facet) {
                foreach ($facet as $facetValues) {
                    $facets[$key][$facetValues['id']]['translations'][$lang] = [
                        "name" => $facetValues['name'],
                        "customFields" => $facetValues['customFields'] ?? "",
                    ];
                }
            }
        }

        return $facets;
    }

    public function getFacetsFromVendure($lang)
    {
        $facets = (new \WeAreHausTech\Queries\Facet)->get($lang);

        if (!isset($facets['data']['facets']['items'])) {
            return [];
        }

        $items = $facets['data']['facets']['items'];
        $sorted = [];

        foreach ($items as $facetType) {
            $sorted[$facetType['code']] = array_combine(array_column($facetType['values'], 'id'), $facetType['values']);
        }

        return $sorted;
    }
}