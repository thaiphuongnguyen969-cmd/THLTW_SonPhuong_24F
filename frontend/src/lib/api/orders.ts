import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Order } from "@/types/order";
import { apiFetch } from "@/lib/api/client";

export async function getOrders(token: string) {
  return apiFetch<ApiResponse<PaginatedResponse<Order>>>("/orders", {
    headers: { Authorization: `Bearer ${token}` },
  });
}
