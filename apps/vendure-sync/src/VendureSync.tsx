import './VendureSync.css'
import { Flex, Heading, Text, Card } from '@radix-ui/themes'


function VendureSync() {

  return (
    <Flex direction="column" gap="6" className="haus-storefront-container">
      <Flex direction="column" gap="2">
        <Heading size="8">Haus Storefront Settings</Heading>
        <Text>  Setting for Vendure Sync</Text>
      </Flex>
      <Card>
        <Flex direction="column" gap="2">
          <Heading size="5"> Mapping for Taxonomies</Heading>
          <Text size="2" color="gray">
            Define how Vendure data (collections or facets) should be mapped to WordPress taxonomies.
          </Text>
          <Flex direction="column" gap="2">
            <Text>Vendure API URL</Text>
          </Flex>


          <Heading size="5">Settings</Heading>
          <Flex direction="column" gap="2">
            <Text>Flush Links on updateL</Text>
          </Flex>
        </Flex>
      </Card >
    </Flex >
  )
}

export default VendureSync
