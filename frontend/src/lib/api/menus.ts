import { apiClient } from "@/lib/api/client";
import type { ApiResponse } from "@/lib/types";
import type { Menu } from "@/types/menu";

export async function getMenus(signal?: AbortSignal): Promise<Menu[]> {
  const response = await apiClient.get<ApiResponse<Menu[]>>("/menus", { signal });
  const payload = response.data;

  if (!payload.success || !Array.isArray(payload.data)) {
    throw new Error(payload.message || "API không trả về danh sách menu hợp lệ.");
  }

  return payload.data;
}
