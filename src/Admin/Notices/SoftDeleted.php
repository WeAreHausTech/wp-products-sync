<?php

namespace WeAreHausTech\WpProductSync\Admin\Notices;
use WeAreHausTech\WpProductSync\Admin\AdminSettingsUI;

class SoftDeleted
{
    public static function showSoftDeletedNotice()
    {
        global $pagenow;

        if ($pagenow !== 'edit-tags.php' || !isset($_GET['taxonomy'])) {
            return;
        }

        $taxonomy = $_GET['taxonomy'];
        $softDeletedCount = self::getSoftDeletedCount($taxonomy);

        if ($softDeletedCount > 0) {
            $message = 'There are currently <strong>' . $softDeletedCount . ' deleted or inactivated terms</strong> in this taxonomy. These terms are hidden from the frontend but remain in the database for future reference';
            AdminSettingsUI::renderAdminNotice('warning', 'Soft Deleted Terms Detected', $message, '', true);
        }
    }


    public static function preventSoftDeletedTermAccess()
    {
        if (is_tax()) {
            $term = get_queried_object();

            if ($term && !is_wp_error($term)) {
                $isSoftDeleted = get_term_meta($term->term_id, 'vendure_soft_deleted', true);

                if ($isSoftDeleted) {
                    wp_redirect(home_url());
                }
            }
        }
    }

    public static function getSoftDeletedCount($taxonomy)
    {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'meta_query' => [
                [
                    'key' => 'vendure_soft_deleted',
                    'value' => '1',
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'hide_empty' => false,
        ]);

        return is_array($terms) ? count($terms) : 0;
    }

    public static function getSoftDeletedTemplate($term_id)
    {
        $softDeleted = get_term_meta($term_id, 'vendure_soft_deleted', true);

        return $softDeleted ? '<span style="color: red; font-weight: bold;">Deleted or Inactivated</span>' : 'Active';
    }
}