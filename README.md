# WP Product Sync

WP Product Sync allows for easy synchronization of product data between your WordPress site and Vendure

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
`composer require wearehaustech/wp-product-sync`

### Step 2: Initialize the Sync

Add follow code to ecom-elementor-widgets.php

```
\WeAreHausTech\BaseSyncProducts::init();
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
  ]
];
```
