import { apiClient, type AdminListParams, type AdminPage, type LaravelCollection, type LaravelResource } from "@/lib/api/client";
import type { Order } from "@/types/order";

export type AdminOrder = Order & {
  customer: { id: number; name: string; email: string; phone: string | null } | null;
  allowed_status_transitions: string[];
};

export async function listAdminOrders(
  token: string,
  params: AdminListParams & { status?: string },
): Promise<AdminPage<AdminOrder>> {
  const response = await apiClient.get<LaravelCollection<AdminOrder>>("/admin/orders", {
    params,
    headers: { Authorization: `Bearer ${token}` },
  });
  const meta = response.data.meta;
  if (!response.data.success || !meta) {
    throw new Error(response.data.message || "API không trả về thông tin phân trang hợp lệ.");
  }
  return {
    data: response.data.data,
    current_page: meta.current_page,
    last_page: meta.last_page,
    per_page: meta.per_page,
    total: meta.total,
  };
}

export async function getAdminOrder(token: string, id: number): Promise<AdminOrder> {
  const response = await apiClient.get<LaravelResource<AdminOrder>>(`/admin/orders/${id}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!response.data.success || !response.data.data) {
    throw new Error(response.data.message || "API không trả về chi tiết đơn hàng.");
  }
  return response.data.data;
}

export async function updateAdminOrderStatus(
  token: string,
  id: number,
  payload: { status: string; expected_status: string; reason?: string },
): Promise<AdminOrder> {
  const response = await apiClient.patch<LaravelResource<AdminOrder>>(`/admin/orders/${id}/status`, payload, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!response.data.success || !response.data.data) {
    throw new Error(response.data.message || "API không trả về đơn hàng đã cập nhật.");
  }
  return response.data.data;
}
