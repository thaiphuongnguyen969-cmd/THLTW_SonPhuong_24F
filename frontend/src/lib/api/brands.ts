import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Brand } from "@/types/brand";
import { apiClient, getAllPublicPages } from "@/lib/api/client";

export function getAllBrands(): Promise<Brand[]> {
  return getAllPublicPages<Brand>("/brands");
}

export async function getBrands(params: Record<string, string | number | undefined> = {}) {
  const response = await apiClient.get<PaginatedResponse<Brand>>("/brands", { params });
  return response.data;
}

export async function getBrandBySlug(slug: string) {
  return apiClient.get<ApiResponse<Brand>>(`/brands/${encodeURIComponent(slug)}`).then((response) => response.data);
}
