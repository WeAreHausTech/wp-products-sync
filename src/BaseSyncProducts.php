<?php

namespace WeAreHausTech;

class BaseSyncProducts
{
    public static function init()
    {
        add_action('init', function () {
            if (defined('WP_CLI') && WP_CLI) {
                require_once 'SyncData/Commands/SyncProductData.php';
                require_once 'SyncData/Commands/DeleteAllPosts.php';
                require_once 'SyncData/Commands/RemoveLock.php';
            }
        });
    }
}