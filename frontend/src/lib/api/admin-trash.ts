import { apiClient } from "@/lib/api/client";

export type TrashType = "category" | "brand" | "product" | "post" | "menu" | "banner";

export type TrashRecord = {
  type: TrashType;
  id: number;
  name: string;
  slug: string | null;
  status: string;
  deleted_at: string;
};

export type TrashPage = {
  success: boolean;
  message: string;
  data: TrashRecord[];
  links: { first: string | null; last: string | null; prev: string | null; next: string | null };
  meta: { current_page: number; last_page: number; per_page: number; total: number };
};

export async function listAdminTrash(
  token: string,
  params: { type?: TrashType | "all"; search?: string; page?: number; per_page?: number },
): Promise<TrashPage> {
  const response = await apiClient.get<TrashPage>("/admin/trash", {
    params: { ...params, type: params.type === "all" ? undefined : params.type },
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data;
}

export async function restoreAdminTrashRecord(token: string, record: TrashRecord): Promise<void> {
  await apiClient.post(`/admin/trash/${record.type}/${record.id}/restore`, undefined, {
    headers: { Authorization: `Bearer ${token}` },
  });
}

export async function permanentlyDeleteAdminTrashRecord(token: string, record: TrashRecord): Promise<void> {
  await apiClient.delete(`/admin/trash/${record.type}/${record.id}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
}
