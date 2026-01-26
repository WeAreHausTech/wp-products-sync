export type FieldType = "text" | "select" | "switch";

export interface FormField {
  key: string;
  label: string;
  description?: string;
  type: FieldType;
  placeholder?: string;
  options?: Array<{ value: string; label: string }>;
  required?: boolean;
  conditional?: {
    field: string;
    value: unknown;
  };
}

export interface TaxonomyField extends FormField {
  conditional?: {
    field: "vendureTaxonomyType";
    value: "collection" | "facet";
  };
}

export const taxonomyFields: TaxonomyField[] = [
  {
    key: "vendureTaxonomyType",
    label: "Vendure source",
    description: "Choose what to sync from Vendure (Collections or Facets).",
    type: "select",
    required: true,
    options: [
      { value: "collection", label: "Collection" },
      { value: "facet", label: "Facet" },
    ],
  },
  {
    key: "vendureTaxonomyWp",
    label: "WordPress taxonomy (slug)",
    type: "text",
    description: "Enter the taxonomy slug (e.g. product_category)",
    placeholder: "e.g., product-category",
    required: true,
  },
  {
    key: "vendureTaxonomyCollectionId",
    label: "Collection ID",
    description:
      "Enter the parent collection ID from Vendure. All child collections will be synchronized.",
    type: "text",
    placeholder: "e.g., 1, 2, 3",
    required: true,
    conditional: {
      field: "vendureTaxonomyType",
      value: "collection",
    },
  },
  {
    key: "vendureTaxonomyFacetCode",
    label: "Facet Code",
    description: "The facet code from Vendure (e.g., size, color, material).",
    type: "text",
    placeholder: "e.g., size, color",
    required: true,
    conditional: {
      field: "vendureTaxonomyType",
      value: "facet",
    },
  },
];

export const settingsFields: FormField[] = [
  {
    key: "flushLinks",
    label: "Flush permalinks after sync",
    description:
      "Refreshes permalinks only for products and taxonomies updated during sync. Enable if you have custom URL structures.",
    type: "switch",
  },
  {
    key: "softDelete",
    label: "Keep removed items (don’t delete)",
    description:
      "Hide removed items instead of deleting them. Useful for keeping historical data.",
    type: "switch",
  },
  {
    key: "taxonomySyncDescription",
    label: "Sync collection descriptions",
    description: "Sync collection descriptions from Vendure to WordPress.",
    type: "switch",
  },
];
