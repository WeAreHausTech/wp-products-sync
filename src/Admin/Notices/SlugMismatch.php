<?php

namespace WeAreHausTech\WpProductSync\Admin\Notices;
use WeAreHausTech\WpProductSync\Admin\AdminSettingsUI;

class SlugMismatch
{
    public static function showSlugMismatchNotice()
    {
        $mismatchedPosts = self::getMisMatchedSlugPosts();
        $mismatchedTerms = self::getMisMatchedSlugTerms();

        if (empty($mismatchedPosts) && empty($mismatchedTerms)) {
            return;
        }

        $postList = !empty($mismatchedPosts) ? '<span style="margin-left: 20px;">Products<span/><ul style="margin-left: 20px;">' . implode('', $mismatchedPosts) . '</ul>' : '';
        $termList = !empty($mismatchedTerms) ? '<span style="margin-left: 20px;">Taxonomies<span/><ul style="margin-left: 20px;">' . implode('', $mismatchedTerms) . '</ul>' : '';

        $allList = $postList . $termList;
        $message = "Products/taxonomies detected where the slug does not match between WordPress and Vendure. This may cause issues with links or product visibility. Please check the following items:";
        AdminSettingsUI::renderAdminNotice('error', 'Slug Mismatch Detected', $message, $allList, false);
    }

    private static function getMisMatchedSlugPosts()
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
        ];

        $mismatchedPosts = get_posts($args);

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

    private static function getMisMatchedSlugTerms()
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