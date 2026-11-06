import axios, { type AxiosError } from "axios";
import type { PaginatedResponse } from "@/lib/types";

export const API_BASE_URL = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api"
).replace(/\/+$/, "");

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

// Keep the existing fetch-based storefront services compatible while Admin CRUD uses Axios.
export async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
  const url = `${API_BASE_URL}${path.startsWith("/") ? path : `/${path}`}`;
  const response = await fetch(url, {
    ...init,
    headers: { Accept: "application/json", ...(init.headers ?? {}) },
    cache: "no-store",
  });
  if (!response.ok) {
    const body = await response.text();
    throw new Error(body || `Request failed with status ${response.status}`);
  }
  return response.json() as Promise<T>;
}

export type LaravelCollection<T> = {
  data: T[];
  success: boolean;
  message?: string;
  links?: Record<string, string | null>;
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export type AdminListParams = {
  page: number;
  per_page: number;
  search?: string;
};

export type AdminPage<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type LaravelResource<T> = {
  data: T;
  success: boolean;
  message?: string;
};

export async function getAllPages<T>(
  path: string,
  token: string,
  params: Record<string, string | number> = {},
): Promise<T[]> {
  const rows: T[] = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await apiClient.get<LaravelCollection<T>>(path, {
      params: { ...params, per_page: 100, page },
      headers: { Authorization: `Bearer ${token}` },
    });

    if (!response.data.success) {
      throw new Error(response.data.message || "API không thể tải dữ liệu.");
    }

    rows.push(...response.data.data);
    lastPage = response.data.meta?.last_page ?? 1;
    page += 1;
  } while (page <= lastPage);

  return rows;
}

export async function getAdminPage<T>(
  path: string,
  token: string,
  params: AdminListParams & Record<string, string | number | undefined>,
): Promise<AdminPage<T>> {
  const response = await apiClient.get<LaravelCollection<T>>(path, {
    params,
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.data.success || !response.data.meta) {
    throw new Error(response.data.message || "API không trả về thông tin phân trang.");
  }

  return {
    data: response.data.data,
    current_page: response.data.meta.current_page,
    last_page: response.data.meta.last_page,
    per_page: response.data.meta.per_page,
    total: response.data.meta.total,
  };
}

export async function getAllPublicPages<T>(path: string): Promise<T[]> {
  const rows: T[] = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await apiClient.get<PaginatedResponse<T>>(path, {
      params: { page, per_page: 100 },
    });
    if (!response.data.success || !response.data.meta) {
      throw new Error(response.data.message || "API không trả về dữ liệu phân trang hợp lệ.");
    }

    rows.push(...response.data.data);
    lastPage = response.data.meta.last_page;
    page += 1;
  } while (page <= lastPage);

  return rows;
}

export function getApiErrorMessage(error: unknown, fallback: string): string {
  if (!isAxiosError(error)) {
    return error instanceof Error ? error.message : fallback;
  }

  if (!error.response) {
    return "Không thể kết nối đến máy chủ. Kiểm tra API đang chạy và cấu hình NEXT_PUBLIC_API_URL.";
  }

  const data = error.response.data as {
    message?: string;
    errors?: Record<string, string[]>;
  } | undefined;
  const validationMessages = Object.values(data?.errors ?? {}).flat();
  return [data?.message, ...validationMessages].filter(Boolean).join(" ")
    || `Yêu cầu thất bại (HTTP ${error.response.status}).`;
}

function isAxiosError(error: unknown): error is AxiosError {
  return axios.isAxiosError(error);
}

export function isApiNotFound(error: unknown): boolean {
  return axios.isAxiosError(error) && error.response?.status === 404;
}
