import { apiClient, type AdminListParams, type AdminPage, type LaravelResource } from "@/lib/api/client";

export type AdminUser = {
  id: number;
  name: string;
  email: string;
  role: "admin" | "customer" | string;
  status: string;
  phone?: string | null;
  address?: string | null;
  created_at?: string | null;
};

export async function listAdminUsers(token: string, params: AdminListParams): Promise<AdminPage<AdminUser>> {
  const response = await apiClient.get<{ data: AdminUser[]; meta: AdminPage<AdminUser> }>("/users", {
    params,
    headers: { Authorization: `Bearer ${token}` },
  });
  return { ...response.data.meta, data: response.data.data };
}

export async function updateAdminUser(token: string, user: AdminUser, payload: Partial<Pick<AdminUser, "name" | "email" | "phone" | "address">>) {
  const response = await apiClient.patch<LaravelResource<AdminUser>>(`/users/${user.id}`, payload, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.data;
}

export async function setAdminUserStatus(token: string, user: AdminUser, status: "active" | "blocked") {
  const response = await apiClient.patch<LaravelResource<AdminUser>>(`/users/${user.id}/status`, { status }, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.data;
}

export async function createAdminUser(token: string, payload: { name: string; email: string; password: string; phone?: string | null; address?: string | null }) {
  const response = await apiClient.post<LaravelResource<AdminUser>>("/users", payload, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.data;
}
