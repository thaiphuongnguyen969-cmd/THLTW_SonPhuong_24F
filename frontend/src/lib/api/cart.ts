import type { ApiResponse } from "@/lib/types";
import type { Cart } from "@/types/cart";
import { apiFetch } from "@/lib/api/client";

export async function getCart(token?: string) {
  return apiFetch<ApiResponse<Cart>>("/cart", {
    headers: token ? { Authorization: `Bearer ${token}` } : undefined,
  });
}

export async function addCartItem(payload: { product_variant_id: number; quantity: number }, token: string) {
  return apiFetch<ApiResponse<Cart>>("/cart/items", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  });
}
