<?php

namespace WeAreHausTech\WpProductSync\Admin;

use WeAreHausTech\WpProductSync\Helpers\ConfigHelper;

class AdminUI
{
    private static $vendureColumns = [

        'vendure_soft_deleted' => 'Status in Vendure',
    ];

    public static function init(): void
    {
        add_action('admin_init', [__CLASS__, 'addAdminColumns']);
        add_action('admin_notices', [__CLASS__, 'showSoftDeletedNotice']);
        add_action('template_redirect', [__CLASS__, 'preventSoftDeletedTermAccess']);

    }

    public static function showSoftDeletedNotice()
    {
        global $pagenow;

        if ($pagenow !== 'edit-tags.php' || !isset($_GET['taxonomy'])) {
            return;
        }

        $taxonomy = $_GET['taxonomy'];
        $softDeletedCount = self::getSoftDeletedCount($taxonomy);

        if ($softDeletedCount > 0) {
            $message = <<<HTML
            <div class="notice notice-error is-dismissible">
                <p style="font-weight: 700;">Ecom components</p>
                <p>There are currently <strong>{$softDeletedCount} deleted or inactivated terms</strong> in this taxonomy. These terms are hidden from the frontend but remain in the database for future reference.</p>
            </div>
            HTML;

            echo $message;
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

    public static function addAdminColumns()
    {
        $configHelper = new ConfigHelper();
        $taxonomies = $configHelper->getTaxonomiesFromConfig();
        if (empty((array) $taxonomies)) {
            return;
        }

        foreach ($taxonomies as $taxonomy) {
            add_action("{$taxonomy['wp']}_add_form_fields", [__CLASS__, 'addVendureIdField']);
            add_action("{$taxonomy['wp']}_edit_form_fields", [__CLASS__, 'editVendureIdField'], 10, 2);
            add_action("created_{$taxonomy['wp']}", [__CLASS__, 'saveVendureIdField'], 10, 2);
            add_action("edited_{$taxonomy['wp']}", [__CLASS__, 'saveVendureIdField'], 10, 2);
            add_filter('manage_edit-' . $taxonomy['wp'] . '_columns', [__CLASS__, 'addVendureColumns']);
            add_filter('manage_' . $taxonomy['wp'] . '_custom_column', [__CLASS__, 'addVendureColumnValues'], 10, 3);
        }
    }

    public static function getVendureIdKey($taxonomy)
    {
        $configHelper = new ConfigHelper();
        $taxonomies = $configHelper->getTaxonomiesFromConfig();

        foreach ($taxonomies as $tax) {
            if ($tax['wp'] === $taxonomy) {
                return $tax['type'] === 'collection' ? 'vendure_collection_id' : 'vendure_term_id';
            }
        }
        return null;
    }
    public static function addVendureIdField()
    {
        $taxonomy = $_GET['taxonomy'];
        $fieldKey = self::getVendureIdKey($taxonomy);

        if (!$fieldKey)
            return;

        ?>
        <div class="form-field">
            <label for="<?php echo esc_attr($fieldKey); ?>">Vendure id</label>
            <input type="text" name="<?php echo esc_attr($fieldKey); ?>" id="<?php echo esc_attr($fieldKey); ?>" value="">
        </div>
        <?php
    }

    public static function editVendureIdField($term)
    {
        $taxonomy = $_GET['taxonomy'];
        $fieldKey = self::getVendureIdKey($taxonomy);
        if (!$fieldKey)
            return;

        $fieldValue = get_term_meta($term->term_id, $fieldKey, true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="<?php echo esc_attr($fieldKey); ?>">Vendure id</label>
            </th>
            <td>
                <input type="text" name="<?php echo esc_attr($fieldKey); ?>" id="<?php echo esc_attr($fieldKey); ?>"
                    value="<?php echo esc_attr($fieldValue); ?>">
            </td>
        </tr>
        <?php
    }

    public static function saveVendureIdField($term_id)
    {
        $taxonomy = get_term($term_id)->taxonomy;
        $fieldKey = self::getVendureIdKey($taxonomy);
        if (!$fieldKey)
            return;

        if (isset($_POST[$fieldKey])) {
            update_term_meta($term_id, $fieldKey, sanitize_text_field($_POST[$fieldKey]));
        }
    }

    public static function addVendureColumns($columns)
    {
        foreach (self::$vendureColumns as $key => $value) {
            $columns[$key] = $value;
        }
        return $columns;
    }

    public static function addVendureColumnValues($content, $column_name, $term_id)
    {
        if (!array_key_exists($column_name, self::$vendureColumns)) {
            return $content;
        }

        switch ($column_name) {
            case 'vendure_soft_deleted':
                return self::getSoftDeletedTemplate($term_id);
            default:
                return get_term_meta($term_id, $column_name, true);
        }
    }

    public static function getSoftDeletedTemplate($term_id)
    {
        $softDeleted = get_term_meta($term_id, 'vendure_soft_deleted', true);

        return $softDeleted ? '<span style="color: red; font-weight: bold;">Deleted or Inactivated</span>' : 'Active';
    }
}
