<?php
namespace WeAreHausTech\WpProductSync\Queries;
use WeAreHausTech\WpProductSync\Helpers\ConfigHelper;
class Collection extends BaseQuery
{
    public function get($lang, $skip, $take, $parentIds = [])
    {

        $config = ConfigHelper::getConfig();
        $customFields = $config['collections']['customFieldsQuery'] ?? '';

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
                    position
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