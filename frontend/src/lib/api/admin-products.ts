import { apiClient, getAdminPage, type AdminListParams, type AdminPage, type LaravelResource } from "@/lib/api/client";
import type { AdminPayload, AdminRecord, AdminField } from "@/components/admin/AdminResourceManager";
import { listAdminBrands } from "@/lib/api/admin-brands";
import { listAdminCategories } from "@/lib/api/admin-categories";
import type { Product } from "@/types/product";

function toAdminRecord(product: Product): AdminRecord {
  return {
    id: product.id,
    slug: product.slug,
    image: product.image ?? product.images?.[0]?.image_url ?? "",
    name: product.name,
    category_id: product.category?.id ?? "",
    category_name: product.category?.name ?? "",
    brand_id: product.brand?.id ?? "",
    brand_name: product.brand?.name ?? "",
    description: product.description ?? "",
    price: product.price,
    sale_price: product.sale_price ?? "",
    quantity: product.quantity ?? 0,
    status: product.status,
  };
}

export async function listAdminProducts(token: string, params: AdminListParams): Promise<AdminPage<AdminRecord>> {
  const result = await getAdminPage<Product>("/admin/products", token, { ...params, status: "all" });
  return { ...result, data: result.data.map(toAdminRecord) };
}

export async function getAdminProductFieldOptions(token: string): Promise<Record<string, AdminField["options"]>> {
  const [categories, brands] = await Promise.all([
    listAdminCategories(token, { page: 1, per_page: 100 }),
    listAdminBrands(token, { page: 1, per_page: 100 }),
  ]);

  return {
    category_id: categories.data.map((category) => ({
      value: String(category.id),
      label: String(category.name),
    })),
    brand_id: [
      { value: "", label: "Không có thương hiệu" },
      ...brands.data.map((brand) => ({ value: String(brand.id), label: String(brand.name) })),
    ],
  };
}

export async function createAdminProduct(token: string, payload: AdminPayload) {
  const response = await apiClient.post<LaravelResource<Product>>("/products", payload, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return toAdminRecord(response.data.data);
}

export async function updateAdminProduct(token: string, record: AdminRecord, payload: AdminPayload) {
  const response = await apiClient.patch<LaravelResource<Product>>(
    `/products/${encodeURIComponent(String(record.slug))}`,
    payload,
    { headers: { Authorization: `Bearer ${token}` } },
  );
  return toAdminRecord(response.data.data);
}

export async function deleteAdminProduct(token: string, record: AdminRecord) {
  await apiClient.delete(`/products/${encodeURIComponent(String(record.slug))}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
}
