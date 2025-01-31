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

            acf_add_local_field_group(array(
                'key' => 'group_6798ee15c123f',
                'title' => 'Vendure sync settings',
                'fields' => array(
                    array(
                        'key' => 'field_6799d65c2d13f',
                        'label' => 'Mappings for Taxonomies',
                        'name' => '',
                        'aria-label' => '',
                        'type' => 'tab',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'placement' => 'left',
                        'endpoint' => 0,
                        'selected' => 0,
                        'wpml_cf_preferences' => 1,
                    ),
                    array(
                        'key' => 'field_6798ee153d31c',
                        'label' => 'Sync Mappings for taxonomies',
                        'name' => 'vendure-taxonomies',
                        'aria-label' => '',
                        'type' => 'repeater',
                        'instructions' => 'Use this section to define how Vendure data (collections or facets) should be mapped to WordPress taxonomies.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'layout' => 'table',
                        'pagination' => 0,
                        'min' => 0,
                        'max' => 0,
                        'collapsed' => '',
                        'button_label' => 'Lägg till rad',
                        'rows_per_page' => 20,
                        'wpml_cf_preferences' => 1,
                        'sub_fields' => array(
                            array(
                                'key' => 'field_6798f197010c6',
                                'label' => 'Vendure Data Type',
                                'name' => 'vendure-taxonomy-type',
                                'aria-label' => '',
                                'type' => 'select',
                                'instructions' => 'Select whether to sync a "Collection" or "Facet" from Vendure.',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'choices' => array(
                                    'collection' => 'Collection',
                                    'facet' => 'Facet',
                                ),
                                'default_value' => false,
                                'return_format' => 'value',
                                'multiple' => 0,
                                'allow_null' => 0,
                                'ui' => 0,
                                'ajax' => 0,
                                'placeholder' => '',
                                'parent_repeater' => 'field_6798ee153d31c',
                                'wpml_cf_preferences' => 1,
                            ),
                            array(
                                'key' => 'field_6798ee363d31d',
                                'label' => 'Taxonomy in wp',
                                'name' => 'vendure-taxonomy-wp',
                                'aria-label' => '',
                                'type' => 'text',
                                'instructions' => 'Enter the WordPress taxonomy where the Vendure data should be stored',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'maxlength' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'parent_repeater' => 'field_6798ee153d31c',
                                'wpml_cf_preferences' => 1,
                            ),
                            array(
                                'key' => 'field_6798f1c3010c7',
                                'label' => 'Root collection id',
                                'name' => 'vendure-taxonomy-collectionId',
                                'aria-label' => '',
                                'type' => 'text',
                                'instructions' => 'Specify the top-level Vendure collection ID whose child collections should be synced',
                                'required' => 0,
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field' => 'field_6798f197010c6',
                                            'operator' => '==',
                                            'value' => 'collection',
                                        ),
                                    ),
                                ),
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'maxlength' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'parent_repeater' => 'field_6798ee153d31c',
                                'wpml_cf_preferences' => 1,
                            ),
                            array(
                                'key' => 'field_6798f271010c8',
                                'label' => 'Facet code in vendure',
                                'name' => 'vendure-taxonomy-facetCode',
                                'aria-label' => '',
                                'type' => 'text',
                                'instructions' => 'Enter the Facet Code from Vendure.',
                                'required' => 0,
                                'conditional_logic' => array(
                                    array(
                                        array(
                                            'field' => 'field_6798f197010c6',
                                            'operator' => '==',
                                            'value' => 'facet',
                                        ),
                                    ),
                                ),
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'maxlength' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'parent_repeater' => 'field_6798ee153d31c',
                                'wpml_cf_preferences' => 1,
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_6799d686a2c5b',
                        'label' => 'Settings',
                        'name' => '',
                        'aria-label' => '',
                        'type' => 'tab',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'placement' => 'left',
                        'endpoint' => 0,
                        'selected' => 0,
                        'wpml_cf_preferences' => 1,
                    ),
                    array(
                        'key' => 'field_6799d41d3b55a',
                        'label' => 'Settings',
                        'name' => 'vendure-settings',
                        'aria-label' => '',
                        'type' => 'group',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'layout' => 'block',
                        'wpml_cf_preferences' => 1,
                        'sub_fields' => array(
                            array(
                                'key' => 'field_6798f7d9aed5c',
                                'label' => 'Flush Links on update',
                                'name' => 'vendure-settings-flushlinks',
                                'aria-label' => '',
                                'type' => 'true_false',
                                'instructions' => 'Enable this option to flush WordPress rewrite rules whenever a term or product is updated during the sync. This is necessary if you are using custom rewrite rules for taxonomies or products to ensure the URL structure remains correct.',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '50',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'message' => '',
                                'default_value' => 0,
                                'ui' => 0,
                                'ui_on_text' => '',
                                'ui_off_text' => '',
                                'wpml_cf_preferences' => 1,
                            ),
                            array(
                                'key' => 'field_6798f90a421c3',
                                'label' => 'Enable Soft Delete for Terms',
                                'name' => 'vendure-settings-softDelete',
                                'aria-label' => '',
                                'type' => 'true_false',
                                'instructions' => 'When enabled, terms will not be deleted during the sync. Instead, they will be marked as "soft deleted," and their pages will no longer be accessible.',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '50',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'message' => '',
                                'default_value' => 1,
                                'ui' => 0,
                                'ui_on_text' => '',
                                'ui_off_text' => '',
                                'wpml_cf_preferences' => 1,
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'vendure-sync',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 0,
                'wpml_cf_preferences' => 1,
            ));
        });

        add_action('acf/init', function () {
            acf_add_options_page(array(
                'page_title' => 'Vendure sync',
                'menu_slug' => 'vendure-sync',
                'parent_slug' => 'options-general.php',
                'redirect' => false,
            ));
        });



    }

}
