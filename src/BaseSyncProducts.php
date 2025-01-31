<?php

namespace WeAreHausTech\WpProductSync;
use WeAreHausTech\WpProductSync\Helpers\ConfigHelper;

class BaseSyncProducts
{


    public static function init()
    {
        add_action('init', function () {
            if (defined('WP_CLI') && WP_CLI) {
                \WP_CLI::add_command('sync-products', \WeAreHausTech\WpProductSync\Commands\SyncProductData::class);
                \WP_CLI::add_command('sync-products', \WeAreHausTech\WpProductSync\Commands\DeleteAllPosts::class);
                \WP_CLI::add_command('sync-products', \WeAreHausTech\WpProductSync\Commands\RemoveLock::class);
            }
        });

        \WeAreHausTech\WpProductSync\Admin\AdminUI::init();
    }
}