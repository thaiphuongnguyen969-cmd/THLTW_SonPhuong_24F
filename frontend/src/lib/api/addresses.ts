import { apiClient } from "@/lib/api/client";
import type { ApiResponse } from "@/lib/types";
import type { Address } from "@/types/address";

export type AddressPayload = Omit<Address, "id" | "is_default" | "created_at" | "updated_at">;
export type AddressPatch = Partial<AddressPayload>;

const auth = (token: string) => ({ headers: { Authorization: `Bearer ${token}` } });

function unwrap<T>(response: ApiResponse<T>): T {
  if (!response.success || response.data === undefined) {
    throw new Error(response.message || "API không trả về dữ liệu địa chỉ hợp lệ.");
  }
  return response.data;
}

export async function getAddresses(token: string): Promise<Address[]> {
  const response = await apiClient.get<ApiResponse<Address[]>>("/addresses", auth(token));
  return unwrap(response.data);
}

export async function getAddress(addressId: number, token: string): Promise<Address> {
  const response = await apiClient.get<ApiResponse<Address>>(`/addresses/${addressId}`, auth(token));
  return unwrap(response.data);
}

export async function createAddress(payload: AddressPayload, token: string): Promise<Address> {
  const response = await apiClient.post<ApiResponse<Address>>("/addresses", payload, auth(token));
  return unwrap(response.data);
}

export async function updateAddress(addressId: number, payload: AddressPayload, token: string): Promise<Address> {
  const response = await apiClient.put<ApiResponse<Address>>(`/addresses/${addressId}`, payload, auth(token));
  return unwrap(response.data);
}

export async function patchAddress(addressId: number, payload: AddressPatch, token: string): Promise<Address> {
  const response = await apiClient.patch<ApiResponse<Address>>(`/addresses/${addressId}`, payload, auth(token));
  return unwrap(response.data);
}

export async function setDefaultAddress(addressId: number, token: string): Promise<Address> {
  const response = await apiClient.patch<ApiResponse<Address>>(`/addresses/${addressId}/default`, {}, auth(token));
  return unwrap(response.data);
}

export async function deleteAddress(addressId: number, token: string): Promise<void> {
  const response = await apiClient.delete<ApiResponse<never>>(`/addresses/${addressId}`, auth(token));
  if (!response.data.success) throw new Error(response.data.message || "Không thể xóa địa chỉ.");
}
