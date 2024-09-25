<?php

/*
 * WP-CLI Command
 * $ wp sync-products sync
 */

use WeAreHausTech\SyncData\Classes\Products;
use WeAreHausTech\SyncData\Classes\Taxonomies;
use WeAreHausTech\SyncData\Classes\Relations;
use WeAreHausTech\SyncData\Helpers\LockHelper;
use WeAreHausTech\SyncData\Helpers\WpHelper;
use WeAreHausTech\SyncData\Helpers\VendureHelper;

class SyncProductData extends WP_CLI_Command
{
    public function sync()
    {
        LockHelper::abortIfAlreadyRunning();
        LockHelper::setLock();

        try {
            $taxonomiesInstance = new Taxonomies();
            $productsInstance = new Products();
            $wpHelper = new WpHelper();
            $vendureHelper = new VendureHelper();

            // sync taxonomies
            $taxonomiesInstance->syncTaxonomies();

            $vendureProducts = $vendureHelper->getAllProductsFromVendure();

            if (!isset($vendureProducts)) {
                WP_CLI::error('No products found in vendure');
            }

            $wpProducts = $wpHelper->getAllProductsFromWp();

            // sync products
            $productsInstance->syncProductsData($vendureProducts, $wpProducts);

            // add taxonomies to products
            (new Relations)->syncRelationships($vendureProducts);

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

            WP_CLI::success("\n" . $productsSummary . "\n" . $taxonomiesSummary);
        } catch ( Exception $e ) {
            WP_CLI::error( "An error occurred: " . $e->getMessage() );
        } finally {
            // Ensure the lock is cleared
            LockHelper::removeLock();
        }

        update_option('haus-vendure-last-sync', date('Y-m-d H:i:s'));
    }
}

WP_CLI::add_command('sync-products', 'syncProductData');
