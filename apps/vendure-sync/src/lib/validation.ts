import type { ZodError } from 'zod'

/**
 * Convert a ZodError into a flat Record of first errors.
 *
 * @param error - The ZodError to flatten and convert
 * @returns A Record mapping dot-paths to their first error message
 */
export function flattenZodError(error: ZodError): Record<string, string> {
  const result: Record<string, string> = {}

  for (const issue of error.issues) {
    const key = issue.path.join('.')
    if (!key) continue
    // Keep the first error per field path
    if (!(key in result)) {
      result[key] = issue.message
    }
  }

  return result
}
