<?php
namespace WeAreHausTech\Queries;

// For productsSync only

class Product extends BaseQuery
{
  public function get($lang, $skip = 0, $take = 100)
  {

    $config = require(HAUS_ECOM_PLUGIN_PATH . '/config.php');

    $customFields = $config['productSync']['products']['customFieldsQuery'] ?? '';

    $options = "(options: {
            take: $take,
            skip: $skip
      })";


    $this->query =
      "query {
            products $options {
              items {
                id
                updatedAt
                name
                description
                slug
                facetValues {
                  id
                }
                collections{
                  id
                }
                  $customFields
              }
              totalItems
            }
          }";

    return $this->fetch($lang);
  }
}
