import { Flex, Text } from '@radix-ui/themes'

export function LoadingState() {
  return (
    <Flex
      direction="column"
      align="center"
      justify="center"
      style={{ minHeight: '100vh', padding: '24px' }}
    >
      <Text size="4">Loading settings...</Text>
    </Flex>
  )
}