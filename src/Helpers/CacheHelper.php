<?php

namespace WeAreHausTech\WpProductSync\Helpers;

class CacheHelper
{
    public static function clear($postId)
    {
        if (!isset($postId)) {
            return;
        }
        if (function_exists('rocket_clean_post')) {
            rocket_clean_post($postId);
        }

        if (defined('LSCWP_V')) {
            do_action('litespeed_purge_post', $postId);
        }
    }
}