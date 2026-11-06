import { apiClient, getAdminPage, type AdminListParams, type AdminPage, type LaravelResource } from "@/lib/api/client";
import type { AdminField, AdminPayload, AdminRecord } from "@/components/admin/AdminResourceManager";
import type { Category } from "@/types/category";

function toAdminRecord(category: Category): AdminRecord {
  return {
    id: category.id,
    name: category.name,
    slug: category.slug,
    parent_id: category.parent?.id ?? category.parent_id ?? null,
    parent_name: category.parent?.name ?? "",
    description: category.description ?? "",
    status: category.status,
  };
}

export async function listAdminCategories(token: string, params: AdminListParams): Promise<AdminPage<AdminRecord>> {
  const result = await getAdminPage<Category>("/admin/categories", token, { ...params, status: "all" });
  return { ...result, data: result.data.map(toAdminRecord) };
}

export async function getAdminCategoryOptions(token: string): Promise<Record<string, AdminField["options"]>> {
  const categories = await listAdminCategories(token, { page: 1, per_page: 100 });
  return {
    parent_id: [
      { value: "", label: "Không có danh mục cha" },
      ...categories.data.map((category) => ({
        value: String(category.id),
        label: String(category.name),
      })),
    ],
  };
}

export async function createAdminCategory(token: string, payload: AdminPayload) {
  const response = await apiClient.post<LaravelResource<Category>>("/categories", payload, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return toAdminRecord(response.data.data);
}

export async function updateAdminCategory(token: string, record: AdminRecord, payload: AdminPayload) {
  const response = await apiClient.patch<LaravelResource<Category>>(
    `/categories/${encodeURIComponent(String(record.slug))}`,
    payload,
    { headers: { Authorization: `Bearer ${token}` } },
  );
  return toAdminRecord(response.data.data);
}

export async function deleteAdminCategory(token: string, record: AdminRecord) {
  await apiClient.delete(`/categories/${encodeURIComponent(String(record.slug))}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
}
