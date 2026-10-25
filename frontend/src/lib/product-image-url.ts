import { API_BASE_URL } from "@/lib/api/client";

export function getProductImageUrl(imagePath: string | null | undefined): string | null {
  const value = imagePath?.trim();
  if (!value) return null;

  if (/^https?:\/\//i.test(value)) return value;

  const apiOrigin = new URL(API_BASE_URL).origin;
  if (value.startsWith("/storage/")) return new URL(value, apiOrigin).toString();

  const normalizedPath = value.replaceAll("\\", "/").replace(/^\/+/, "");
  const encodedPath = normalizedPath.split("/").map(encodeURIComponent).join("/");
  return new URL(`/storage/${encodedPath}`, apiOrigin).toString();
}
