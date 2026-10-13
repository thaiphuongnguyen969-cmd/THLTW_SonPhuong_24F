import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Brand } from "@/types/brand";
import { apiFetch } from "@/lib/api/client";

export async function getBrands(params: Record<string, string | number | undefined> = {}) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === "") return;
    query.set(key, String(value));
  });

  const url = query.size > 0 ? `/brands?${query.toString()}` : "/brands";
  return apiFetch<ApiResponse<PaginatedResponse<Brand>>>(url);
}

export async function getBrandBySlug(slug: string) {
  return apiFetch<ApiResponse<Brand>>(`/brands/${slug}`);
}
