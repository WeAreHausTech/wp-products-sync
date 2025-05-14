<?php

namespace WeAreHausTech\WpProductSync\Admin\Notices;
use WeAreHausTech\WpProductSync\Admin\AdminSettingsUI;

class SlugMismatch
{
    public static function showSlugMismatchNotice()
    {
        $mismatchedPosts = self::getMismatchedSlugPosts();
        $mismatchedTerms = self::getMismatchedSlugTerms();

        if (empty($mismatchedPosts) && empty($mismatchedTerms)) {
            return;
        }

        $amountOfMismatchedPosts = self::getAmountOfMismatchedSlugPost();

        $postList = '';
        if (!empty($mismatchedPosts)) {
            $postLabel = 'Products (' . count($mismatchedPosts);
            if ($amountOfMismatchedPosts > count($mismatchedPosts)) {
                $postLabel .= ' of ' . $amountOfMismatchedPosts;
            }
            $postLabel .= ')';
            $postList = '<span style="margin-left: 20px;">' . $postLabel . '</span><ul style="margin-left: 20px;">' . implode('', $mismatchedPosts) . '</ul>';
        }
        $termList = !empty($mismatchedTerms) ? '<span style="margin-left: 20px;">Taxonomies<span/><ul style="margin-left: 20px;">' . implode('', $mismatchedTerms) . '</ul>' : '';

        $allList = $postList . $termList;
        $message = "Products/taxonomies detected where the slug does not match between WordPress and Vendure. This may cause issues with links or product visibility. Please check the following items:";
        AdminSettingsUI::renderAdminNotice('error', 'Slug Mismatch Detected', $message, $allList, false);
    }


    private static function getAmountOfMismatchedSlugPost()
    {
        $args = [
            'post_type' => 'produkter',
            'meta_query' => [
                [
                    'key' => 'vendure_slug_mismatch',
                    'value' => '1',
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'posts_per_page' => -1
        ];

        $mismatchedPosts = get_posts($args);
        $totalCount = count($mismatchedPosts);
        return $totalCount;
    }

    private static function getMismatchedSlugPosts()
    {
        $args = [
            'post_type' => 'produkter',
            'meta_query' => [
                [
                    'key' => 'vendure_slug_mismatch',
                    'value' => '1',
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'posts_per_page' => 50
        ];

        $mismatchedPosts = get_posts($args);
        $countArgs = $args;
        $countArgs['posts_per_page'] = -1;

        $totalCount = count(get_posts($countArgs));

        if (empty($mismatchedPosts)) {
            return [];
        }

        return array_map(function ($postId) {
            $editLink = get_edit_post_link($postId);
            $title = get_the_title($postId);
            $vendureId = get_post_meta($postId, 'vendure_id', true);
            return "<li><a href='{$editLink}' target='_blank'>{$title} (Vendure id: {$vendureId})</a></li>";
        }, $mismatchedPosts);
    }

    private static function getMismatchedSlugTerms()
    {
        $args = [
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'vendure_slug_mismatch',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => 'vendure_soft_deleted',
                    'value' => '1',
                    'compare' => '!=',
                ],
            ],
            'fields' => 'ids',
            'hide_empty' => false,
        ];

        $mismatchedTerms = get_terms($args);

        return array_map(function ($termId) {
            $editLink = get_edit_term_link($termId);
            $name = get_term($termId)->name;
            $vendureId = get_term_meta($termId, 'vendure_collection_id', true) ?? get_term_meta($termId, 'vendure_term_id', true);
            return "<li><a href='{$editLink}' target='_blank'>{$name} (Vendure id: {$vendureId})</a></li>";
        }, $mismatchedTerms);
    }
}