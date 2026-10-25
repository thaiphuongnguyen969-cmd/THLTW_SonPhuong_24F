import { apiClient, getAdminPage, type AdminListParams, type AdminPage, type LaravelResource } from "@/lib/api/client";

export type AdminMenu = {
  id: number;
  name: string;
  link_type: "internal" | "external";
  url: string;
  sort_order: number;
  status: "active" | "inactive";
  created_at: string;
  updated_at: string;
};

export type AdminMenuInput = Pick<AdminMenu, "name" | "link_type" | "url" | "sort_order" | "status">;

export function listAdminMenus(token: string, params: AdminListParams): Promise<AdminPage<AdminMenu>> {
  return getAdminPage<AdminMenu>("/admin/menus", token, params);
}

export async function getAdminMenu(token: string, id: number): Promise<AdminMenu> {
  const response = await apiClient.get<LaravelResource<AdminMenu>>(`/admin/menus/${id}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.data;
}

export async function createAdminMenu(token: string, input: AdminMenuInput): Promise<AdminMenu> {
  const response = await apiClient.post<LaravelResource<AdminMenu>>("/admin/menus", input, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.data;
}

export async function updateAdminMenu(token: string, id: number, input: Partial<AdminMenuInput>): Promise<AdminMenu> {
  const response = await apiClient.patch<LaravelResource<AdminMenu>>(`/admin/menus/${id}`, input, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.data;
}

export async function deleteAdminMenu(token: string, id: number): Promise<void> {
  await apiClient.delete(`/admin/menus/${id}`, { headers: { Authorization: `Bearer ${token}` } });
}
