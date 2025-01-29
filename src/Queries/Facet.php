<?php
namespace WeAreHausTech\WpProductSync\Queries;
use WeAreHausTech\WpProductSync\Helpers\ConfigHelper;

class Facet extends BaseQuery
{
    public function get($lang)
    {

        $config = ConfigHelper::getConfig();

        $customFields = $config['facets']['customFieldsQuery'] ?? '';

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