import { useCallback, useEffect, useState } from 'react'
import { Button, Card, Flex, Heading, Separator, Switch, Text, TextField, Select } from '@radix-ui/themes'
import { Cross2Icon, PlusIcon } from '@radix-ui/react-icons'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { loadVendureSyncSettings, saveVendureSyncSettings } from './api/endpoints'
import { useToast } from './contexts/ToastContext'
import { validateVendureSyncSettings } from './schemas/vendureSyncSettings'
import type { VendureSyncSettingsErrors } from './schemas/vendureSyncSettings'
import { LoadingState } from './components/LoadingState'
import { taxonomyFields, settingsFields } from './config/formFields'

type TaxonomyItem = {
  vendureTaxonomyType: 'collection' | 'facet'
  vendureTaxonomyWp: string
  vendureTaxonomyCollectionId?: string
  vendureTaxonomyFacetCode?: string
}

export default function VendureSync() {
  const [taxonomies, setTaxonomies] = useState<TaxonomyItem[]>([])
  const [settings, setSettings] = useState({
    flushLinks: false,
    softDelete: false,
    taxonomySyncDescription: false,
  })
  const [errors, setErrors] = useState<VendureSyncSettingsErrors>({})

  const queryClient = useQueryClient()
  const { pushToast } = useToast()

  const vendureSyncSettingsQuery = useQuery({
    queryKey: ['vendureSyncSettings'],
    queryFn: loadVendureSyncSettings,
  })

  const saveMutation = useMutation({
    mutationFn: saveVendureSyncSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vendureSyncSettings'] })
    },
  })

  useEffect(() => {
    const data = vendureSyncSettingsQuery.data
    if (data) {
      setTaxonomies(data.taxonomies || [])
      setSettings({
        flushLinks: Boolean(data.settings?.flushLinks),
        softDelete: Boolean(data.settings?.softDelete),
        taxonomySyncDescription: Boolean(data.settings?.taxonomySyncDescription),
      })
    }
  }, [vendureSyncSettingsQuery.data])

  const clearError = useCallback((key: string) => {
    if (!(key in errors)) return
    setErrors((prev) => {
      const { [key]: _removed, ...rest } = prev
      return rest
    })
  }, [errors])

  const handleAddTaxonomy = useCallback(() => {
    setTaxonomies((prev) => [
      ...prev,
      { vendureTaxonomyType: 'collection', vendureTaxonomyWp: '' },
    ])
  }, [])

  const handleRemoveTaxonomy = useCallback((index: number) => {
    setTaxonomies((prev) => prev.filter((_, i) => i !== index))
  }, [])

  const handleTaxonomyChange = useCallback(
    (index: number, field: string, value: string | 'collection' | 'facet') => {
      setTaxonomies((prev) => {
        const updated = prev.map((t, i) => {
          if (i !== index) return t
          if (field === 'vendureTaxonomyType') {
            // Reset type-specific fields when type changes
            return {
              ...t,
              vendureTaxonomyType: value as 'collection' | 'facet',
              vendureTaxonomyCollectionId: value === 'collection' ? t.vendureTaxonomyCollectionId : undefined,
              vendureTaxonomyFacetCode: value === 'facet' ? t.vendureTaxonomyFacetCode : undefined,
            }
          }
          return { ...t, [field]: value }
        })
        return updated
      })
      clearError(`taxonomies.${index}.${field}`)
    },
    [clearError],
  )

  const handleSettingChange = useCallback((key: keyof typeof settings, checked: boolean) => {
    setSettings((prev) => ({ ...prev, [key]: checked }))
  }, [])

  const handleSave = useCallback(async () => {
    const validation = validateVendureSyncSettings({
      taxonomies,
      settings,
    })

    if (!validation.success) {
      setErrors(validation.errors)
      const errorCount = Object.keys(validation.errors).length
      pushToast({
        variant: 'error',
        title: `Please fix ${errorCount} error${errorCount > 1 ? 's' : ''} before saving.`,
      })
      return
    }

    const result = await saveMutation.mutateAsync(validation.data)

    if (result.success) {
      pushToast({
        variant: 'success',
        title: result.message || 'Vendure sync settings saved successfully',
      })
      setErrors({})
    } else {
      pushToast({
        variant: 'error',
        title: result.message || 'Failed to save vendure sync settings',
      })
    }
  }, [taxonomies, settings, saveMutation, pushToast])

  if (vendureSyncSettingsQuery.isPending) {
    return <LoadingState />
  }

  return (
    <Flex direction="column" gap="6" className="vendure-sync-container" width="100%">
      <Flex direction="column" gap="2">
        <Heading size="8">Vendure Product Sync Settings</Heading>
      </Flex>

      <Flex direction="column" gap="6">
        {/* Taxonomies Section */}
        <Card>
          <Flex direction="column" gap="5">
            <Flex direction="column" gap="2">
              <Heading size="5">Taxonomy Mappings</Heading>
              <Text size="2" color="gray">
                Map Vendure collections or facets to WordPress taxonomies. Each mapping creates/updates terms during synchronization.
              </Text>
            </Flex>
            <Separator />

            {taxonomies.length === 0 ? (
              <Text size="2" color="gray">
                No mappings configured. Click "Add Mapping" to get started.
              </Text>
            ) : (
              <Flex direction="column" gap="4">
                {taxonomies.map((taxonomy, index) => (
                  <Card key={index} style={{ padding: '16px' }} >
                    <Flex direction="column" gap="3" style={{
                      padding: '16px',
                      borderRadius: 'var(--radius-3)',
                      border: '1px solid var(--gray-a5)',
                      background: 'var(--gray-a2)',
                    }}>
                      <Flex align="center" justify="between">
                        <Text size="2" weight="medium">
                          Mapping #{index + 1}
                        </Text>
                        <Button
                          size="1"
                          color="red"
                          variant="ghost"
                          onClick={() => handleRemoveTaxonomy(index)}
                        >
                          <Cross2Icon />
                        </Button>
                      </Flex>

                      <Flex direction="column" gap="3">
                        {taxonomyFields.map((field) => {
                          // Check if field should be shown based on conditional
                          if (
                            field.conditional &&
                            taxonomy[field.conditional.field] !== field.conditional.value
                          ) {
                            return null
                          }

                          const fieldValue = taxonomy[field.key as keyof TaxonomyItem] as string | undefined
                          const errorKey = `taxonomies.${index}.${field.key}`
                          const hasError = errorKey in errors

                          return (
                            <Flex key={field.key} direction="column" gap="1">
                              <Text size="2" weight="medium">
                                {field.label}
                                {field.required && <Text color="red"> *</Text>}
                              </Text>
                              {field.description && (
                                <Text size="1" color="gray">
                                  {field.description}
                                </Text>
                              )}
                              {field.type === 'select' && field.options ? (
                                <Select.Root
                                  value={fieldValue || ''}
                                  onValueChange={(value) =>
                                    handleTaxonomyChange(index, field.key, value)
                                  }
                                >
                                  <Select.Trigger />
                                  <Select.Content>
                                    {field.options.map((option) => (
                                      <Select.Item key={option.value} value={option.value}>
                                        {option.label}
                                      </Select.Item>
                                    ))}
                                  </Select.Content>
                                </Select.Root>
                              ) : (
                                <TextField.Root
                                  placeholder={field.placeholder}
                                  value={fieldValue || ''}
                                  onChange={(e) => handleTaxonomyChange(index, field.key, e.target.value)}
                                  size="2"
                                  color={hasError ? 'red' : undefined}
                                />
                              )}
                              {hasError && (
                                <Text size="1" color="red">
                                  {errors[errorKey]}
                                </Text>
                              )}
                            </Flex>
                          )
                        })}
                      </Flex>
                    </Flex>
                  </Card>
                ))}
              </Flex>
            )}

            <Flex justify="end">
              <Button size="2" onClick={handleAddTaxonomy} style={{ width: 'fit-content' }}>
                <PlusIcon /> Add Mapping
              </Button>
            </Flex>
          </Flex>
        </Card>

        {/* Settings Section */}
        <Card>
          <Flex direction="column" gap="5">
            <Heading size="5">Synchronization options</Heading>
            <Separator />
            <Flex direction="column" gap="4">
              {settingsFields.map((field) => {
                const fieldValue = settings[field.key as keyof typeof settings] as boolean
                return (
                  <Flex key={field.key} align="center" justify="between" gap="4">
                    <Flex direction="column" gap="1">
                      <Text size="2" weight="medium">
                        {field.label}
                      </Text>
                      {field.description && (
                        <Text size="1" color="gray">
                          {field.description}
                        </Text>
                      )}
                    </Flex>
                    {field.type === 'switch' && (
                      <Switch
                        checked={fieldValue}
                        onCheckedChange={(checked) =>
                          handleSettingChange(field.key as keyof typeof settings, Boolean(checked))
                        }
                      />
                    )}
                  </Flex>
                )
              })}
            </Flex>
          </Flex>
        </Card>
      </Flex >

      <Flex justify="start" gap="3">
        <Button
          size="3"
          onClick={handleSave}
          disabled={saveMutation.isPending}
          style={{ minWidth: '120px' }}
        >
          {saveMutation.isPending ? 'Saving...' : 'Save changes'}
        </Button>
      </Flex>
    </Flex >
  )
}
