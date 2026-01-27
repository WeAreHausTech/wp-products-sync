import type { z } from 'zod'

declare global {
  interface Window {
    vendureSync?: {
      apiUrl: string
      nonce: string
    }
  }
}

export function getVendureSyncConfig(): { apiUrl: string; nonce: string } | null {
  if (!window.vendureSync) {
    console.warn('WordPress API data not available')
    return null
  }
  return window.vendureSync
}

export function getVendureSyncSettingsApiUrl(): string {
  const config = getVendureSyncConfig()
  if (!config) {
    throw new Error('Vendure Sync API configuration not available')
  }
  return config.apiUrl
}

export async function fetchJson<TSchema extends z.ZodTypeAny>(
  url: string,
  init: RequestInit,
  schema: TSchema,
  invalidShapeMessage: string,
): Promise<z.infer<TSchema>> {
  const response = await fetch(url, init)

  if (!response.ok) {
    // Try to surface WP REST errors (often JSON with `message`)
    const errorData = await response.json().catch(() => ({}))
    const msg =
      typeof (errorData as { message?: unknown })?.message === 'string'
        ? (errorData as { message: string }).message
        : response.statusText
    throw new Error(msg)
  }

  const raw = await response.json()
  const parsed = schema.safeParse(raw)
  if (!parsed.success) {
    throw new Error(invalidShapeMessage)
  }
  return parsed.data
}
