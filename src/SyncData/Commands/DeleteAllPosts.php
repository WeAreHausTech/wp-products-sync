<?php

/*
 * WP-CLI Command
 * $ wp sync-products delete
 */

use WeAreHausTech\SyncData\Classes\Products;
use WeAreHausTech\SyncData\Classes\Taxonomies;
use WeAreHausTech\SyncData\Classes\Relations;
use WeAreHausTech\SyncData\Helpers\WpHelper;
use WeAreHausTech\SyncData\Helpers\VendureHelper;

class DeleteAllPosts extends WP_CLI_Command
{

    public function delete()
    {
        $taxonomiesInstance = new Taxonomies();
        $wpHelper = new WpHelper();

        global $wpdb;
        $query = 
            "SELECT p.ID
             FROM {$wpdb->prefix}posts p
             WHERE p.post_type = 'produkter'";

        $productsToDelete = $wpdb->get_results($query, ARRAY_A);

        if (empty($productsToDelete)) {
            return;
        }

        $productsToExclude = $wpHelper->getProductsToExclude();
        $filteredProductsToDelete = array_filter($productsToDelete, function ($product) use ($productsToExclude) {
            return !in_array(intval($product['ID']), $productsToExclude);
        });

        $productsInstance = new Products();
        foreach ($filteredProductsToDelete as $product) {
            $productsInstance->deleteProduct($product['ID']);
        }

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
    }
}

WP_CLI::add_command('sync-products', 'deleteAllPosts');