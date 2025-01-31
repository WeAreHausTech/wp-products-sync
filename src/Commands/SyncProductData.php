<?php

namespace WeAreHausTech\WpProductSync\Commands;
/*
 * WP-CLI Command
 * $ wp sync-products sync
 */

use WeAreHausTech\WpProductSync\Classes\Products;
use WeAreHausTech\WpProductSync\Classes\Taxonomies;
use WeAreHausTech\WpProductSync\Classes\Relations;
use WeAreHausTech\WpProductSync\Helpers\LockHelper;
use WeAreHausTech\WpProductSync\Helpers\WpHelper;
use WeAreHausTech\WpProductSync\Helpers\VendureHelper;
class SyncProductData extends \WP_CLI_Command
{

    public function sync()
    {
        LockHelper::abortIfAlreadyRunning();
        LockHelper::setLock();
        WpHelper::log('Sync started');

        try {
            $taxonomiesInstance = new Taxonomies();
            $productsInstance = new Products();
            $wpHelper = new WpHelper();
            $vendureHelper = new VendureHelper();

            // sync taxonomies
            $taxonomiesInstance->syncTaxonomies();

            $vendureProducts = $vendureHelper->getAllProductsFromVendure();

            if (!isset($vendureProducts)) {
                \WP_CLI::error('No products found in vendure');
            }

            $wpProducts = $wpHelper->getAllProductsFromWp();

            // sync products
            $productsInstance->syncProductsData($vendureProducts, $wpProducts);

            // add taxonomies to products
            $relationsInstance = new Relations($productsInstance);
            $relationsInstance->syncRelationships($vendureProducts);

            // Flush rewrite rules if needed
            $wpHelper->flashRewriteRulesIfAnythingIsUpdated($productsInstance, $taxonomiesInstance);

            $productsSummary = sprintf(
                'Products: Created: %d Updated: %d Deleted: %d',
                $productsInstance->created,
                $productsInstance->updated,
                $productsInstance->deleted
            );

            $taxonomiesSummary = sprintf(
                'Taxonomies: Created: %d Updated: %d Deleted: %d',
                $taxonomiesInstance->createdTaxonomies,
                $taxonomiesInstance->updatedTaxonimies,
                $taxonomiesInstance->deletedTaxonomies
            );
            WpHelper::log( $productsSummary );
            WpHelper::log( $taxonomiesSummary );
            \WP_CLI::success("\n" . $productsSummary . "\n" . $taxonomiesSummary);
        } catch (Exception $e) {
            WpHelper::log('An error occurred when syncing products', );
            \WP_CLI::error("An error occurred: " . $e->getMessage());
        } finally {
            // Ensure the lock is cleared
            WpHelper::log('Sync completed', );
            LockHelper::removeLock();
        }

        update_option('haus-vendure-last-sync', date('Y-m-d H:i:s'));
    }
}

