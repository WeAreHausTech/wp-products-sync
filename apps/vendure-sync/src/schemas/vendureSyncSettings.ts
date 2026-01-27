import { z } from "zod";
import { flattenZodError } from "../lib/validation";

const TaxonomyMappingSchema = z.object({
  vendureTaxonomyType: z.enum(["collection", "facet"], {
    message: "Vendure data type is required",
  }),
  vendureTaxonomyWp: z
    .string()
    .min(1, { message: "WordPress taxonomy is required" }),
  vendureTaxonomyCollectionId: z.string().optional(),
  vendureTaxonomyFacetCode: z.string().optional(),
});

export const VendureSyncSettingsSchema = z
  .object({
    taxonomies: z.array(TaxonomyMappingSchema).default([]),
    settings: z
      .object({
        flushLinks: z.boolean().default(false),
        softDelete: z.boolean().default(false),
        taxonomySyncDescription: z.boolean().default(false),
      })
      .default({
        flushLinks: false,
        softDelete: false,
        taxonomySyncDescription: false,
      }),
  })
  .refine(
    (data) => {
      return data.taxonomies.every((tax) => {
        if (tax.vendureTaxonomyType === "collection") {
          return !!tax.vendureTaxonomyCollectionId;
        }
        if (tax.vendureTaxonomyType === "facet") {
          return !!tax.vendureTaxonomyFacetCode;
        }
        return true;
      });
    },
    {
      message:
        "Collection ID is required for collection type, Facet Code is required for facet type",
      path: ["taxonomies"],
    },
  );

export type VendureSyncSettings = z.infer<typeof VendureSyncSettingsSchema>;

export type VendureSyncSettingsErrors = Record<string, string>;

export function validateVendureSyncSettings(
  input: unknown,
):
  | { success: true; data: VendureSyncSettings }
  | { success: false; errors: VendureSyncSettingsErrors } {
  const result = VendureSyncSettingsSchema.safeParse(input);
  if (result.success) {
    return { success: true, data: result.data };
  }
  return { success: false, errors: flattenZodError(result.error) };
}
