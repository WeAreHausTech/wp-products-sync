import {
  fetchJson,
  getVendureSyncSettingsApiUrl,
  getVendureSyncConfig,
} from "./client";
import { VendureSyncSettingsResponseSchema } from "./schemas";
import type { VendureSyncSettings } from "../schemas/vendureSyncSettings";

export async function loadVendureSyncSettings(): Promise<VendureSyncSettings | null> {
  const config = getVendureSyncConfig();
  if (!config) return null;

  try {
    const apiUrl = getVendureSyncSettingsApiUrl();
    const result = await fetchJson(
      apiUrl,
      {
        method: "GET",
        headers: {
          "X-WP-Nonce": config.nonce,
          "Content-Type": "application/json",
        },
      },
      VendureSyncSettingsResponseSchema,
      "Failed to load vendure sync settings: invalid response shape",
    );

    if (result.success && result.data) {
      return result.data;
    }

    return null;
  } catch (error) {
    console.error("Error loading vendure sync settings:", error);
    return null;
  }
}

export async function saveVendureSyncSettings(
  settings: VendureSyncSettings,
): Promise<{ success: boolean; message: string }> {
  const config = getVendureSyncConfig();
  if (!config) {
    return {
      success: false,
      message: "WordPress API not available.",
    };
  }

  try {
    const apiUrl = getVendureSyncSettingsApiUrl();
    const result = await fetchJson(
      apiUrl,
      {
        method: "POST",
        headers: {
          "X-WP-Nonce": config.nonce,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ settings }),
      },
      VendureSyncSettingsResponseSchema,
      "Failed to save vendure sync settings: invalid response shape",
    );

    if (result.success) {
      return {
        success: true,
        message: result.message || "Vendure sync settings saved successfully",
      };
    }

    throw new Error(result.message || "Failed to save vendure sync settings");
  } catch (error) {
    console.error("Error saving vendure sync settings:", error);
    return {
      success: false,
      message:
        error instanceof Error
          ? error.message
          : "Failed to save vendure sync settings",
    };
  }
}
