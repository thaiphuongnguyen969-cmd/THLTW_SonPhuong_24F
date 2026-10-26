import {
  apiClient,
  getAdminPage,
  type AdminListParams,
  type AdminPage,
  type LaravelResource,
} from "@/lib/api/client";

export type AdminProductVariant = {
  id: number;
  size_id: number;
  color_id: number;
  sku: string;
  price: string | null;
  sale_price: string | null;
  original_price: string;
  effective_price: string;
  is_promotion: boolean;
  discount_percentage: number;
  stock: number;
  status: "active" | "inactive";
  size: { id: number; name: string };
  color: { id: number; name: string; hex_code?: string | null };
};

export type VariantOptions = {
  sizes: Array<{ id: number; name: string }>;
  colors: Array<{ id: number; name: string; hex_code?: string | null }>;
};

export type AdminVariantPayload = {
  sku: string;
  size_id: number;
  color_id: number;
  price: number | null;
  sale_price: number | null;
  stock: number;
  status: "active" | "inactive";
  expected_stock?: number;
};

function variantsPath(productSlug: string) {
  return "/admin/products/" + encodeURIComponent(productSlug) + "/variants";
}

export function listAdminProductVariants(
  token: string,
  productSlug: string,
  params: AdminListParams,
): Promise<AdminPage<AdminProductVariant>> {
  return getAdminPage<AdminProductVariant>(variantsPath(productSlug), token, params);
}

export async function getAdminVariantOptions(token: string): Promise<VariantOptions> {
  const response = await apiClient.get<LaravelResource<VariantOptions>>("/admin/variant-options", {
    headers: { Authorization: "Bearer " + token },
  });
  return response.data.data;
}

export async function createAdminProductVariant(
  token: string,
  productSlug: string,
  payload: AdminVariantPayload,
): Promise<AdminProductVariant> {
  const response = await apiClient.post<LaravelResource<AdminProductVariant>>(
    variantsPath(productSlug),
    payload,
    { headers: { Authorization: "Bearer " + token } },
  );
  return response.data.data;
}

export async function updateAdminProductVariant(
  token: string,
  productSlug: string,
  variantId: number,
  payload: AdminVariantPayload,
): Promise<AdminProductVariant> {
  const response = await apiClient.patch<LaravelResource<AdminProductVariant>>(
    variantsPath(productSlug) + "/" + variantId,
    payload,
    { headers: { Authorization: "Bearer " + token } },
  );
  return response.data.data;
}
