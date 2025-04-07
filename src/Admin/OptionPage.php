<?php

namespace WeAreHausTech\WpProductSync\Admin;

class OptionPage
{
    public static function init(): void
    {
        add_action('acf/include_fields', function () {
            if (!function_exists('acf_add_local_field_group')) {
                return;
            }

            acf_add_local_field_group([
                'key'                   => 'group_6798ee15c123f',
                'title'                 => 'Vendure sync settings',
                'fields'                => [
                    [
                        'key'               => 'field_6799d65c2d13f',
                        'label'             => 'Mappings for Taxonomies',
                        'name'              => '',
                        'aria-label'        => '',
                        'type'              => 'tab',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => 0,
                        'wrapper'           => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'placement'         => 'left',
                        'endpoint'          => 0,
                        'selected'          => 0,
                        'wpml_cf_preferences' => 1,
                    ],
                    [
                        'key'                   => 'field_vendure-taxonomies-facet',
                        'label'                 => 'Facets',
                        'name'                  => 'vendure-taxonomies-facet',
                        'aria-label'            => '',
                        'type'                  => 'repeater',
                        'instructions'          => 'Use this section to define how Vendure data facets should be mapped to WordPress taxonomies.',
                        'required'              => 0,
                        'conditional_logic'     => 0,
                        'wrapper'               => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'layout'                => 'table',
                        'pagination'            => 0,
                        'min'                   => 0,
                        'max'                   => 0,
                        'collapsed'             => '',
                        'button_label'          => 'Lägg till rad',
                        'rows_per_page'         => 20,
                        'wpml_cf_preferences'   => 1,
                        'sub_fields'            => [
                            [
                                'key'                   => 'field_vendure-taxonomies-facet-taxonomy',
                                'label'                 => 'Taxonomy in wp',
                                'name'                  => 'vendure-taxonomy-wp',
                                'aria-label'            => '',
                                'type'                  => 'text',
                                'instructions'          => '', 
                                'parent_repeater'       => 'field_vendure-taxonomies-facet',
                                'wpml_cf_preferences'   => 1,
                            ],
                            [
                                'key'                   => 'field_vendure-taxonomies-facet-facetCode',
                                'label'                 => 'Factet code in Vendure',
                                'name'                  => 'vendure-taxonomy-facetCode',
                                'aria-label'            => '',
                                'type'                  => 'text',
                                'instructions'          => '',
                                'required'              => 0,
                                'parent_repeater'       => 'field_vendure-taxonomies-facet',
                                'wpml_cf_preferences'   => 1,
                            ],
                        ],
                    ],
                    [
                        'key'                   => 'field_vendure-taxonomies-collection',
                        'label'                 => 'Collections',
                        'name'                  => 'vendure-taxonomies-collection',
                        'aria-label'            => '',
                        'type'                  => 'repeater',
                        'instructions'          => 'Use this section to define how Vendure data collections should be mapped to WordPress taxonomies. Specify the top-level Vendure collection ID whose child collections should be synced',
                        'required'              => 0,
                        'conditional_logic'     => 0,
                        'layout'                => 'table',
                        'collapsed'             => '',
                        'button_label'          => 'Lägg till rad',
                        'rows_per_page'         => 20,
                        'wpml_cf_preferences'   => 1,
                        'sub_fields'            => [
                            [
                                'key'                   => 'field_vendure-taxonomies-collection-taxonomy',
                                'label'                 => 'Taxonomy in wp',
                                'name'                  => 'vendure-taxonomy-wp',
                                'aria-label'            => '',
                                'type'                  => 'text',
                                'instructions'          => '',
                                'parent_repeater'       => 'field_vendure-taxonomies-collection',
                                'wpml_cf_preferences'   => 1,
                            ],
                            [
                                'key'                   => 'field_vendure-taxonomies-collection-collectionId',
                                'label'                 => 'Root collection id',
                                'name'                  => 'vendure-taxonomy-collectionId',
                                'aria-label'            => '',
                                'type'                  => 'text',
                                'instructions'          => '',
                                'required'              => 0,
                                'parent_repeater'       => 'field_vendure-taxonomies-collection',
                                'wpml_cf_preferences'   => 1,
                            ],
                        ],
                    ],
                    [
                        'key'                   => 'field_6799d686a2c5b',
                        'label'                 => 'Settings',
                        'name'                  => '',
                        'aria-label'            => '',
                        'type'                  => 'tab',
                        'instructions'          => '',
                        'placement'             => 'left',
                        'wpml_cf_preferences'   => 1,
                    ],
                    [
                        'key'                   => 'field_6799d41d3b55a',
                        'label'                 => 'Settings',
                        'name'                  => 'vendure-settings',
                        'aria-label'            => '',
                        'type'                  => 'group',
                        'instructions'          => '',
                        'layout'                => 'block',
                        'wpml_cf_preferences'   => 1,
                        'sub_fields'            => [
                            [
                                'key'                   => 'field_vendure-settings-flushlinks',
                                'label'                 => 'Flush Links on update',
                                'name'                  => 'vendure-settings-flushlinks',
                                'aria-label'            => '',
                                'type'                  => 'true_false',
                                'instructions'          => 'Enable this option to flush WordPress rewrite rules whenever a term or product is updated during the sync. This is necessary if you are using custom rewrite rules for taxonomies or products to ensure the URL structure remains correct.',
                                'required'              => 0,
                                'wpml_cf_preferences'   => 1,                            ],
                            [
                                'key'                   => 'field_vendure-settings-softDelete',
                                'label'                 => 'Enable Soft Delete for Terms',
                                'name'                  => 'vendure-settings-softDelete',
                                'aria-label'            => '',
                                'type'                  => 'true_false',
                                'instructions'          => 'When enabled, terms will not be deleted during the sync. Instead, they will be marked as "soft deleted," and their pages will no longer be accessible.',
                                'message'               => '',
                                'default_value'         => 1,
                                'wpml_cf_preferences'   => 1,
                            ],
                            [
                                'key'                   => 'field_vendure-settings-taxonomySyncDescription',
                                'label'                 => 'Sync description for taxonomies',
                                'name'                  => 'vendure-settings-taxonomySyncDescription',
                                'aria-label'            => '',
                                'type'                  => 'true_false',
                                'instructions'          => 'When enabled, terms will not be deleted during the sync. Instead, they will be marked as "soft deleted," and their pages will no longer be accessible.',
                                'message'               => '',
                                'default_value'         => 1,
                                'wpml_cf_preferences'   => 1,
                            ],
                        ],
                    ],
                ],
                'location'              => [
                    [
                        [
                            'param'    => 'options_page',
                            'operator' => '==',
                            'value'    => 'vendure-sync',
                        ],
                    ],
                ],
                'menu_order'            => 0,
                'position'              => 'normal',
                'style'                 => 'default',
                'label_placement'       => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen'        => '',
                'active'                => true,
                'description'           => '',
                'show_in_rest'          => 0,
                'wpml_cf_preferences'   => 1,
            ]);
        });

        add_action('acf/init', function () {
            acf_add_options_page([
                'page_title'  => 'Vendure sync',
                'menu_slug'   => 'vendure-sync',
                'parent_slug' => 'options-general.php',
                'redirect'    => false,
            ]);
        });
    }
}
