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
    label: 'Vendure Data Type',
    type: 'select',
    required: true,
    options: [
      { value: 'collection', label: 'Collection' },
      { value: 'facet', label: 'Facet' },
    ],
  },
  {
    key: 'vendureTaxonomyWp',
    label: 'Taxonomy in WordPress',
    type: 'text',
    placeholder: 'Enter the WordPress taxonomy name',
    required: true,
  },
  {
    key: 'vendureTaxonomyCollectionId',
    label: 'Root Collection ID',
    description: 'Specify the top-level Vendure collection ID whose child collections should be synced',
    type: 'text',
    placeholder: 'Enter collection ID',
    required: true,
    conditional: {
      field: 'vendureTaxonomyType',
      value: 'collection',
    },
  },
  {
    key: 'vendureTaxonomyFacetCode',
    label: 'Facet Code in Vendure',
    description: 'Enter the Facet Code from Vendure',
    type: 'text',
    placeholder: 'Enter facet code',
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
    label: 'Flush Links on update',
    description:
      'Enable this option to flush WordPress rewrite rules whenever a term or product is updated during the sync. This is necessary if you are using custom rewrite rules for taxonomies or products to ensure the URL structure remains correct.',
    type: 'switch',
  },
  {
    key: 'softDelete',
    label: 'Enable Soft Delete for Terms',
    description:
      'When enabled, terms will not be deleted during the sync. Instead, they will be marked as "soft deleted," and their pages will no longer be accessible.',
    type: 'switch',
  },
  {
    key: 'taxonomySyncDescription',
    label: 'Sync description for taxonomies',
    description: 'When enabled, taxonomy descriptions will be synced from Vendure to WordPress.',
    type: 'switch',
  },
]
