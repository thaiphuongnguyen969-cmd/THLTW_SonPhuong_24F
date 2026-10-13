import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Product } from "@/types/product";
import { apiFetch } from "@/lib/api/client";

export async function getProducts(params: Record<string, string | number | undefined> = {}) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === "") return;
    query.set(key, String(value));
  });

  const url = query.size > 0 ? `/products?${query.toString()}` : "/products";
  return apiFetch<ApiResponse<PaginatedResponse<Product>>>(url);
}

export async function getProductById(id: number | string) {
  return apiFetch<ApiResponse<Product>>(`/products/${id}`);
}
