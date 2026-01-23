export type FieldType = 'text' | 'select' | 'switch'

export interface FormField {
  key: string
  label: string
  description?: string
  type: FieldType
  placeholder?: string
  options?: Array<{ value: string; label: string }>
  required?: boolean
  conditional?: {
    field: string
    value: unknown
  }
}

export interface TaxonomyField extends FormField {
  conditional?: {
    field: 'vendureTaxonomyType'
    value: 'collection' | 'facet'
  }
}

export const taxonomyFields: TaxonomyField[] = [
  {
    key: 'vendureTaxonomyType',
    label: 'Data Type',
    description: 'Sync collections or facets from Vendure.',
    type: 'select',
    required: true,
    options: [
      { value: 'collection', label: 'Collection' },
      { value: 'facet', label: 'Facet' },
    ],
  },
  {
    key: 'vendureTaxonomyWp',
    label: 'WordPress Taxonomy Name',
    type: 'text',
    placeholder: 'e.g., product-category',
    required: true,
  },
  {
    key: 'vendureTaxonomyCollectionId',
    label: 'Collection ID',
    description: 'Main collection ID from Vendure. All sub-collections will sync.',
    type: 'text',
    placeholder: 'e.g., 1, 2, 3',
    required: true,
    conditional: {
      field: 'vendureTaxonomyType',
      value: 'collection',
    },
  },
  {
    key: 'vendureTaxonomyFacetCode',
    label: 'Facet Code',
    description: 'The facet code from Vendure (e.g., size, color, material).',
    type: 'text',
    placeholder: 'e.g., size, color',
    required: true,
    conditional: {
      field: 'vendureTaxonomyType',
      value: 'facet',
    },
  },
]

export const settingsFields: FormField[] = [
  {
    key: 'flushLinks',
    label: 'Update URLs when syncing',
    description: 'Refresh permalinks after syncing. Enable if you have custom URL structures.',
    type: 'switch',
  },
  {
    key: 'softDelete',
    label: 'Keep deleted items in WordPress',
    description: 'Hide removed items instead of deleting them. Useful for keeping historical data.',
    type: 'switch',
  },
  {
    key: 'taxonomySyncDescription',
    label: 'Sync descriptions',
    description: 'Sync descriptions from Vendure collections to WordPress.',
    type: 'switch',
  },
]
