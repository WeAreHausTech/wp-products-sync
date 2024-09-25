<?php
namespace WeAreHausTech\Queries;
class Collection extends BaseQuery
{
    public function get($lang, $skip, $take, $parentIds = [])
    {

        $config = require(HAUS_ECOM_PLUGIN_PATH . '/config.php');
        $customFields = $config['productSync']['collections']['customFieldsQuery'] ?? '';

        $encodedParentIds = json_encode($parentIds);

        $options = "(options: {
            take: $take,
            skip: $skip, 
            filter: { 
                parentId: 
                {in: $encodedParentIds} 
            }
        })";

        $this->query = 
           "query{
                collections $options{
                    totalItems
                    items {
                    id
                    name
                    slug
                    description
                    assets {
                        source
                    }           
                    parentId
                    updatedAt
                    $customFields
                    }
                }
            }";

        return $this->fetch($lang);
    }
}