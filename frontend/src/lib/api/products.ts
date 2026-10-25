import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Product } from "@/types/product";
import { apiClient } from "@/lib/api/client";

export async function getProducts(params: Record<string, string | number | undefined> = {}): Promise<PaginatedResponse<Product>> {
  const response = await apiClient.get<PaginatedResponse<Product>>("/products", { params });
  return response.data;
}

export async function getProductBySlug(slug: string): Promise<ApiResponse<Product>> {
  const response = await apiClient.get<ApiResponse<Product>>(`/products/${encodeURIComponent(slug)}`);
  return response.data;
}
