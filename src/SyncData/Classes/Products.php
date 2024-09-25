<?php
namespace WeAreHausTech\SyncData\Classes;

use WeAreHausTech\SyncData\Helpers\CacheHelper;
use WeAreHausTech\SyncData\Helpers\WpmlHelper;
use WeAreHausTech\SyncData\Helpers\WpHelper;

class Products
{
    public $created = 0;
    public $updated = 0;
    public $deleted = 0;
    public $defaultLang = '';
    public $useWpml = false;


    public function __construct()
    {
        $wpmlHelper = new WpmlHelper();
        $this->useWpml = $wpmlHelper->hasWpml();
        $this->defaultLang = $wpmlHelper->getDefaultLanguage();
    }
    public function syncProductsData($vendureProducts, $wpProducts)
    {
        //Exists in WP, not in Vendure
        $delete = array_diff_key($wpProducts, $vendureProducts);

        array_walk($delete, function ($product) {
            $shouldBeExcluded = "1";
            if ($product['exclude_from_sync'] === $shouldBeExcluded) {
                return;
            }

            $this->deleteProduct($product['id']);

            foreach ($product['translations'] as $lang => $translation) {
                if ($translation['id']) {
                    $this->deleteProduct($translation['id']);
                }
            }
        });

        //Exists in Vendure, not in WP
        $create = array_diff_key($vendureProducts, $wpProducts);

        array_walk($create, function ($product) {
            $this->createProduct($product);
        });

        //Exists in Vendure and in  WP
        $update = array_intersect_key($vendureProducts, $wpProducts);

        foreach ($update as $vendureId => $vendureProduct) {
            $this->updateProduct($wpProducts[$vendureId], $vendureProduct);
        }

    }

    public function insertPost($ProductName, $description, $slug, $vendureId, $updatedAt, $customFields = null)
    {
        $metaInput = [
            'vendure_id' => $vendureId,
            'vendure_updated_at' => $updatedAt,
        ];

        $customFields =  $this->getCustomFields($customFields);
        $metaInput = array_merge($metaInput, $customFields);

        $postId = wp_insert_post([
            'post_title' => $ProductName,
            'post_status' => 'publish',
            'post_type' => 'produkter',
            'post_name' => $slug,
            'post_content' => $description,
            'meta_input' => $metaInput,

        ]);

        WpHelper::log(['Creating product', $vendureId, $ProductName, $slug]);
        CacheHelper::clear($postId);

        return $postId;
    }

    public function getCustomFields($customFields){

        $data = [];

        if(!$customFields){
            return $data;
        }

        forEach($customFields as $key => $value){
            if (is_array($value)) {
                $data[$key] = $value['source'];
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public function createProduct($product)
    {
        $customFields = isset($product['customFields']) ? $product['customFields'] : null;

        $orignal = $this->insertPost($product['name'], $product['description'], $product['slug'], $product['id'], $product['updatedAt'], $customFields);

        if (!$this->useWpml) {
            $this->created++;
            return;
        }

        foreach ($product['translations'] as $lang => $translation) {
            $customFieldsTranslation = isset($translation['customFields']) ? $translation['customFields'] : null;
            $translations[$lang] = $this->insertPost($translation['name'], $translation['description'], $translation['slug'], $product['id'], $product['updatedAt'], $customFieldsTranslation);
        }

        $wpmlHelper = new WpmlHelper();
        $wpmlHelper->setLanguageDetails($orignal, $translations, 'post_produkter');
        $this->created++;
    }

    public function deleteProduct($postId)
    {
        wp_delete_post($postId, true);

        WpHelper::log(['Deleting product', $postId]);
        $this->deleted++;
    }

    public function updateProduct($wpProduct, $vendureProduct)
    {

        $update = $this->isUpdatedInVendure($wpProduct, $vendureProduct);

        if ($update === []) {
            return;
        }

        foreach ($update as $lang) {
            if ($lang === $this->defaultLang) {
                $customFields = isset($vendureProduct['customFields']) ? $vendureProduct['customFields'] : null;
                $this->updatePost($wpProduct['id'], $vendureProduct['name'], $vendureProduct['description'], $vendureProduct['slug'], $vendureProduct['id'], $vendureProduct['updatedAt'], $customFields);
                continue;
            }

            $langExistsInWp = $wpProduct['translations'][$lang];

            if ($langExistsInWp && $langExistsInWp['id']) {
                $translatedPostId = $wpProduct['translations'][$lang]['id'];
                $translatedSlug = $vendureProduct['translations'][$lang]['slug'];
                $translatedName = $vendureProduct['translations'][$lang]['name'];
                $translatedDescription = $vendureProduct['translations'][$lang]['description'];
                $customFields = isset($vendureProduct['translations'][$lang]['customFields']) ? $vendureProduct['translations'][$lang]['customFields'] : null;

                $this->updatePost($translatedPostId, $translatedName, $translatedDescription, $translatedSlug, $vendureProduct['id'], $vendureProduct['updatedAt'], $customFields);
            } else {
                $this->createTranslatedPost($vendureProduct, $wpProduct['id'], $lang);
            }
        }
    }

    public function createTranslatedPost($vendureProduct, $originalId, $lang)
    {
        $customFields = isset($vendureProduct['translations'][$lang]['customFields']) ? $vendureProduct['translations'][$lang]['customFields'] : null;
        $newPost[$lang] = $this->insertPost($vendureProduct['translations'][$lang]['name'], $vendureProduct['translations'][$lang]['description'], $vendureProduct['translations'][$lang]['slug'], $vendureProduct['id'], $vendureProduct['updatedAt'], $customFields);
        $wpmlHelper = new WpmlHelper();
        $wpmlHelper->setLanguageDetails($originalId, $newPost, 'post_produkter');

        WpHelper::log(['Create product translation', $vendureProduct['translations'][$lang]['name'], $vendureProduct['translations'][$lang]['slug']]);
        $this->created++;

    }

    public function updatePost($postId, $postTitle, $postContent, $postName, $vendureId, $updatedAt, $customFields = null)
    {

        $metaInput = [
            'vendure_updated_at' => $updatedAt,
        ];

        $customFields =  $this->getCustomFields($customFields);
        $metaInput = array_merge($metaInput, $customFields);

        wp_update_post([
            'ID' => $postId,
            'post_title' => $postTitle,
            'post_name' => $postName, 
            'post_content' => $postContent,
            'meta_input' => $metaInput
        ]);

        WpHelper::log(['Updating product', $postTitle, $postName, $vendureId]);
        CacheHelper::clear($postId);

        $this->updated++;
    }

    public function isUpdatedInVendure($wpProduct, $vendureProduct)
    {
        $updateLang = [];

        if ($vendureProduct['updatedAt'] === $wpProduct['vendure_updated_at']) {
            return $updateLang;
        }

        $updateLang[] = $this->defaultLang;

        if (!$this->useWpml) {
            return $updateLang;
        }

        foreach ($wpProduct['translations'] as $lang => $translation) {
            $updateLang[] = $lang;
        }

        return $updateLang;
    }
}