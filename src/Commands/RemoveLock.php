<?php

namespace WeAreHausTech\WpProductSync\Commands;
/*
 * WP-CLI Command
 * $ wp sync-products removeLock
 */

 use WeAreHausTech\WpProductSync\Helpers\LockHelper;

class RemoveLock extends  \WP_CLI_Command
{

    public function removeLock()
    {
        LockHelper::removeLock();

        \WP_CLI::success('Lock removed');
    }
}
