<?php

namespace WeAreHausTech\SyncData\Helpers;

class CacheHelper
{
    public static function clear($postId)
    {
        if ($postId && function_exists('rocket_clean_post')) {
            rocket_clean_post($postId);
        }
    }
}