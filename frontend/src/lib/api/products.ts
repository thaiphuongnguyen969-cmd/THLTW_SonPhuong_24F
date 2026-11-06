import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Product } from "@/types/product";
import { apiClient } from "@/lib/api/client";

export async function getProducts(params: Record<string, string | number | boolean | undefined> = {}): Promise<PaginatedResponse<Product>> {
  const response = await apiClient.get<PaginatedResponse<Product>>("/products", { params });
  return response.data;
}

export async function getProductBySlug(slug: string): Promise<ApiResponse<Product>> {
  const response = await apiClient.get<ApiResponse<Product>>(`/products/${encodeURIComponent(slug)}`);
  return response.data;
}

export async function getHotProducts(): Promise<Product[]> {
  const response = await apiClient.get<ApiResponse<Product[]>>("/products/hot");
  return response.data.data ?? [];
}

export async function getRelatedProducts(slug: string): Promise<Product[]> {
  const response = await apiClient.get<ApiResponse<Product[]>>(
    `/products/${encodeURIComponent(slug)}/related`,
  );
  return response.data.data ?? [];
}
