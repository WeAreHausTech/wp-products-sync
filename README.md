# WP Product Sync

WP Product Sync allows for easy synchronization of product data between your WordPress site and Vendure

## Publish package
  1. make sure release notes are updated
`git tag <version number>` // including v and workflow will trigger a new release
`git push --tags`

## Commands

Sync products and taxonomies:
`wp sync-products sync`

Delete all products:
`wp sync-products delete`

Remove lock:
`wp sync-products removeLock`

## Installation Guide

Follow these steps to install and set up WP Product Sync in your project.

### Step 1: Install the Package

Run the following command in your project directory to install the package via Composer:
`composer require WeAreHausTech/wp-product-sync`

### Step 2: Initialize the Sync

Add follow code to ecom-elementor-widgets.php

```
\HausTech\BaseSyncProducts::init();
```

### Step 3: Add Configuration File for syncing customized data

Create file config.php taxonomies to sync or customfields to products.
example:

```
<?php
return [
  'productSync' => [
    "taxonomies" => [
      "collection" => [
        "wp" => "produkter-kategorier",
        "vendure" => "category",
        "type" => "collection",
        "rootCollectionId" => "2"
      ]
    ],
    'products' => [
      'customFieldsQuery' => '
              customFields {
                  specificCustomField
              }
          '
    ],
    "collections" => [
      "customFieldsQuery" => "
           customFields {
              specificCustomField
           }
          "
    ],
    "facets" => [
      "customFieldsQuery" => "
           customFields {
            specificCustomField
           }
          "
    ],
  ], 
   'settings' => [
        'flushLinks' => true
    ]
];
```

#### Settings

- **flushLinks**
  - **Description**: Activates flush_rewrite_rules when product/taxonomy is updated/created
  - **Possible values**: true/false
  - **Required/Optional**: Optional (use if custom url structure)
  - **Example**:
    ```bash
    'flushLinks' => true
    ```