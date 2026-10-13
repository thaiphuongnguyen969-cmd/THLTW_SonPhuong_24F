import type { ApiResponse, PaginatedResponse } from "@/lib/types";
import type { Address } from "@/types/address";
import { apiFetch } from "@/lib/api/client";

export async function getAddresses(token: string) {
  return apiFetch<ApiResponse<PaginatedResponse<Address>>>("/addresses", {
    headers: { Authorization: `Bearer ${token}` },
  });
}
