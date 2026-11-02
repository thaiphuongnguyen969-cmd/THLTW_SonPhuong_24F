import { apiClient, getAdminPage, type AdminListParams, type AdminPage, type LaravelResource } from "@/lib/api/client";
import type { Banner } from "@/types/banner";

export type AdminBanner = Banner & {
  image_path: string;
  status: "active" | "inactive";
  created_at: string;
  updated_at: string;
};

export type AdminBannerInput = {
  title: string;
  description: string | null;
  link_url: string | null;
  button_text: string | null;
  status: "active" | "inactive";
  sort_order: number;
  image?: File | null;
};

export async function getPublicBanners(): Promise<Banner[]> {
  const response = await apiClient.get<{ data: Banner[] }>("/banners");
  return response.data.data;
}

export function listAdminBanners(
  token: string,
  params: AdminListParams & { status?: "all" | "active" | "inactive" },
): Promise<AdminPage<AdminBanner>> {
  return getAdminPage<AdminBanner>("/admin/banners", token, params);
}

function toFormData(payload: AdminBannerInput): FormData {
  const data = new FormData();
  data.append("title", payload.title);
  data.append("description", payload.description ?? "");
  data.append("link_url", payload.link_url ?? "");
  data.append("button_text", payload.button_text ?? "");
  data.append("status", payload.status);
  data.append("sort_order", String(payload.sort_order));
  if (payload.image) data.append("image", payload.image);
  return data;
}

export async function createAdminBanner(token: string, payload: AdminBannerInput): Promise<AdminBanner> {
  const response = await apiClient.post<LaravelResource<AdminBanner>>("/admin/banners", toFormData(payload), {
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "multipart/form-data" },
  });
  return response.data.data;
}

export async function updateAdminBanner(
  token: string,
  id: number,
  payload: Partial<AdminBannerInput>,
): Promise<AdminBanner> {
  if (payload.image) {
    const data = toFormData({
      title: payload.title ?? "",
      description: payload.description ?? null,
      link_url: payload.link_url ?? null,
      button_text: payload.button_text ?? null,
      status: payload.status ?? "active",
      sort_order: payload.sort_order ?? 0,
      image: payload.image,
    });
    data.append("_method", "PATCH");
    const response = await apiClient.post<LaravelResource<AdminBanner>>(`/admin/banners/${id}`, data, {
      headers: { Authorization: `Bearer ${token}`, "Content-Type": "multipart/form-data" },
    });
    return response.data.data;
  }

  const response = await apiClient.patch<LaravelResource<AdminBanner>>(`/admin/banners/${id}`, payload, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.data;
}

export async function deleteAdminBanner(token: string, id: number): Promise<void> {
  await apiClient.delete(`/admin/banners/${id}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
}
