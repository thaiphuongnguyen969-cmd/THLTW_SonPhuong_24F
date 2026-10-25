import { apiClient, getAdminPage, type AdminListParams, type AdminPage, type LaravelResource } from "@/lib/api/client";
import type { AdminPayload, AdminRecord } from "@/components/admin/AdminResourceManager";
import type { Brand } from "@/types/brand";

function toAdminRecord(brand: Brand): AdminRecord {
  return {
    id: brand.id,
    name: brand.name,
    slug: brand.slug,
    logo: brand.logo ?? "",
    description: brand.description ?? "",
    status: brand.status,
  };
}

export async function listAdminBrands(token: string, params: AdminListParams): Promise<AdminPage<AdminRecord>> {
  const result = await getAdminPage<Brand>("/admin/brands", token, { ...params, status: "all" });
  return { ...result, data: result.data.map(toAdminRecord) };
}

export async function createAdminBrand(token: string, payload: AdminPayload) {
  const response = await apiClient.post<LaravelResource<Brand>>("/brands", payload, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return toAdminRecord(response.data.data);
}

export async function updateAdminBrand(token: string, record: AdminRecord, payload: AdminPayload) {
  const response = await apiClient.patch<LaravelResource<Brand>>(
    `/brands/${encodeURIComponent(String(record.slug))}`,
    payload,
    { headers: { Authorization: `Bearer ${token}` } },
  );
  return toAdminRecord(response.data.data);
}

export async function deleteAdminBrand(token: string, record: AdminRecord) {
  await apiClient.delete(`/brands/${encodeURIComponent(String(record.slug))}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
}
