<?php

/*
 * WP-CLI Command
 * $ wp sync-products removeLock
 */

 use WeAreHausTech\SyncData\Helpers\LockHelper;

class RemoveLock extends WP_CLI_Command
{

    public function removeLock()
    {
        LockHelper::removeLock();

        WP_CLI::success('Lock removed');
    }
}

WP_CLI::add_command('sync-products', 'removeLock');