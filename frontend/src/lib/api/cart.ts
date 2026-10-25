import { apiClient } from "@/lib/api/client";
import type { ApiResponse } from "@/lib/types";
import type { Cart, CartItem } from "@/types/cart";

function dataOrThrow<T>(response: ApiResponse<T>): T {
  if (!response.success || response.data === undefined) {
    throw new Error(response.message || "API không trả về dữ liệu giỏ hàng hợp lệ.");
  }
  return response.data;
}

function auth(token: string) {
  return { headers: { Authorization: `Bearer ${token}` } };
}

export async function getCart(token: string): Promise<Cart> {
  const response = await apiClient.get<ApiResponse<Cart>>("/cart", auth(token));
  return dataOrThrow(response.data);
}

export async function addCartItem(
  payload: { product_variant_id: number; quantity: number },
  token: string,
): Promise<CartItem> {
  const response = await apiClient.post<ApiResponse<CartItem>>("/cart/items", payload, auth(token));
  return dataOrThrow(response.data);
}

export async function updateCartItem(cartItemId: number, quantity: number, token: string): Promise<CartItem> {
  const response = await apiClient.patch<ApiResponse<CartItem>>(`/cart/items/${cartItemId}`, { quantity }, auth(token));
  return dataOrThrow(response.data);
}

export async function removeCartItem(cartItemId: number, token: string): Promise<void> {
  const response = await apiClient.delete<ApiResponse<never>>(`/cart/items/${cartItemId}`, auth(token));
  if (!response.data.success) throw new Error(response.data.message || "Không thể xóa sản phẩm khỏi giỏ hàng.");
}

export async function clearCart(token: string): Promise<void> {
  const response = await apiClient.delete<ApiResponse<never>>("/cart", auth(token));
  if (!response.data.success) throw new Error(response.data.message || "Không thể xóa giỏ hàng.");
}
