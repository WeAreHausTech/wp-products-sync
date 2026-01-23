import { z } from 'zod'

const BaseResponseSchema = z.object({
  success: z.boolean(),
  message: z.string().optional(),
})

const VendureSyncSettingsDataSchema = z.object({
  taxonomies: z
    .array(
      z.object({
        vendureTaxonomyType: z.enum(['collection', 'facet']),
        vendureTaxonomyWp: z.string(),
        vendureTaxonomyCollectionId: z.string().optional(),
        vendureTaxonomyFacetCode: z.string().optional(),
      }),
    )
    .optional()
    .default([]),
  settings: z
    .object({
      flushLinks: z.boolean().optional().default(false),
      softDelete: z.boolean().optional().default(false),
      taxonomySyncDescription: z.boolean().optional().default(false),
    })
    .optional()
    .default({
      flushLinks: false,
      softDelete: false,
      taxonomySyncDescription: false,
    }),
})

export const VendureSyncSettingsResponseSchema = BaseResponseSchema.extend({
  data: VendureSyncSettingsDataSchema.optional(),
})
