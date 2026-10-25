import { apiClient } from "@/lib/api/client";
import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Order } from "@/types/order";

const auth = (token: string) => ({ headers: { Authorization: `Bearer ${token}` } });

function unwrap<T>(response: ApiResponse<T>): T {
  if (!response.success || response.data === undefined) {
    throw new Error(response.message || "API không trả về dữ liệu đơn hàng hợp lệ.");
  }
  return response.data;
}

export async function getOrders(token: string, params: { page?: number; per_page?: number } = {}): Promise<PaginatedResponse<Order>> {
  const response = await apiClient.get<PaginatedResponse<Order>>("/orders", { ...auth(token), params });
  return response.data;
}

export async function getOrder(orderId: number, token: string): Promise<Order> {
  const response = await apiClient.get<ApiResponse<Order>>(`/orders/${orderId}`, auth(token));
  return unwrap(response.data);
}

export async function createOrder(payload: { address_id: number; note?: string | null }, token: string): Promise<Order> {
  const response = await apiClient.post<ApiResponse<Order>>("/orders", payload, auth(token));
  return unwrap(response.data);
}

export async function cancelOrder(orderId: number, token: string, reason?: string): Promise<Order> {
  const response = await apiClient.post<ApiResponse<Order>>(
    `/orders/${orderId}/cancel`,
    reason ? { reason } : {},
    auth(token),
  );
  return unwrap(response.data);
}
