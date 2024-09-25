<?php
namespace WeAreHausTech\Queries;

class Facet extends BaseQuery
{
    public function get($lang)
    {

        $config = require(HAUS_ECOM_PLUGIN_PATH . '/config.php');

        $customFields = $config['productSync']['facets']['customFieldsQuery'] ?? '';
          
        $this->query =
         "query {
                facets {
                    items {
                        id
                        name
                        code
                        values{
                            id
                            name
                            code
                            updatedAt
                            $customFields
                        }
                    }
                }
            }
        ";

        return $this->fetch($lang);
    }
}