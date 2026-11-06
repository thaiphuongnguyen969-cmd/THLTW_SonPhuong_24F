import { apiClient, getAdminPage, type AdminListParams, type AdminPage, type LaravelResource } from "@/lib/api/client";

export type AdminPost = {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  content: string;
  thumbnail: string | null;
  thumbnail_path?: string | null;
  status: "draft" | "published";
  author: { id: number; name: string } | null;
  published_at: string | null;
  created_at: string;
};

export type PostPayload = {
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  status: "draft" | "published";
  published_at: string;
  thumbnail?: File | null;
};

export async function listAdminPosts(token: string, params: AdminListParams & { status?: string }): Promise<AdminPage<AdminPost>> {
  return getAdminPage<AdminPost>("/admin/posts", token, params);
}

export async function getAdminPost(token: string, slug: string): Promise<AdminPost> {
  const response = await apiClient.get<LaravelResource<AdminPost>>(`/admin/posts/${encodeURIComponent(slug)}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.data;
}

function toFormData(payload: PostPayload) {
  const data = new FormData();
  data.append("title", payload.title);
  data.append("slug", payload.slug);
  data.append("excerpt", payload.excerpt);
  data.append("content", payload.content);
  data.append("status", payload.status);
  if (payload.published_at) data.append("published_at", payload.published_at);
  if (payload.thumbnail) data.append("thumbnail", payload.thumbnail);
  return data;
}

export async function createAdminPost(token: string, payload: PostPayload): Promise<AdminPost> {
  const response = await apiClient.post<LaravelResource<AdminPost>>("/admin/posts", toFormData(payload), {
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "multipart/form-data" },
  });
  return response.data.data;
}

export async function updateAdminPost(token: string, slug: string, payload: PostPayload): Promise<AdminPost> {
  const data = toFormData(payload);
  data.append("_method", "PATCH");
  const response = await apiClient.post<LaravelResource<AdminPost>>(`/admin/posts/${encodeURIComponent(slug)}`, data, {
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "multipart/form-data" },
  });
  return response.data.data;
}

export async function deleteAdminPost(token: string, slug: string): Promise<void> {
  await apiClient.delete(`/admin/posts/${encodeURIComponent(slug)}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
}

export type PublicPost = {
  title: string;
  slug: string;
  excerpt: string | null;
  content: string;
  thumbnail: string | null;
  author: string | null;
  published_at: string | null;
};

export async function listPublishedPosts(params: { page?: number; per_page?: number } = {}) {
  const response = await apiClient.get<{ data: PublicPost[]; meta: AdminPage<PublicPost> }>("/posts", { params });
  return { ...response.data.meta, data: response.data.data };
}

export async function getPublishedPost(slug: string): Promise<PublicPost> {
  const response = await apiClient.get<LaravelResource<PublicPost>>(`/posts/${encodeURIComponent(slug)}`);
  return response.data.data;
}
