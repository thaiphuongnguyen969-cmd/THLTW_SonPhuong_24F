import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Category } from "@/types/category";
import { apiFetch } from "@/lib/api/client";

export async function getCategories(params: Record<string, string | number | undefined> = {}) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === "") return;
    query.set(key, String(value));
  });

  const url = query.size > 0 ? `/categories?${query.toString()}` : "/categories";
  return apiFetch<ApiResponse<PaginatedResponse<Category>>>(url);
}

export async function getCategoryBySlug(slug: string) {
  return apiFetch<ApiResponse<Category>>(`/categories/${slug}`);
}
