import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Category } from "@/types/category";
import { apiClient, getAllPublicPages } from "@/lib/api/client";

export function getAllCategories(): Promise<Category[]> {
  return getAllPublicPages<Category>("/categories");
}

export async function getCategories(params: Record<string, string | number | undefined> = {}) {
  const response = await apiClient.get<PaginatedResponse<Category>>("/categories", { params });
  return response.data;
}

export async function getCategoryBySlug(slug: string) {
  return apiClient.get<ApiResponse<Category>>(`/categories/${encodeURIComponent(slug)}`).then((response) => response.data);
}
