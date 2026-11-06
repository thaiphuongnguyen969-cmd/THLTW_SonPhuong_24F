"use client";

import { AdminResourceManager } from "@/components/admin/AdminResourceManager";
import type { AdminColumn, AdminField } from "@/components/admin/AdminResourceManager";
import { createAdminProduct, deleteAdminProduct, getAdminProductFieldOptions, listAdminProducts, updateAdminProduct } from "@/lib/api/admin-products";

const columns: AdminColumn[] = [
  { key: "image", label: "Ảnh", kind: "image" },
  { key: "name", label: "Sản phẩm" },
  { key: "id", label: "ID" },
  { key: "category_name", label: "Danh mục" },
  { key: "brand_name", label: "Thương hiệu" },
  { key: "price", label: "Giá", kind: "price" },
  { key: "status", label: "Trạng thái", kind: "status" },
];
const fields: AdminField[] = [
  { name: "name", label: "Tên sản phẩm", required: true },
  { name: "category_id", label: "Danh mục", type: "select", required: true },
  { name: "brand_id", label: "Thương hiệu", type: "select" },
  { name: "price", label: "Giá", type: "number", required: true },
  { name: "sale_price", label: "Giá khuyến mãi", type: "number" },
  { name: "quantity", label: "Số lượng", type: "number" },
  { name: "image", label: "Đường dẫn hình ảnh" },
  { name: "description", label: "Mô tả", type: "textarea" },
  { name: "status", label: "Trạng thái", type: "select", required: true, options: [{ value: "active", label: "Đang hoạt động" }, { value: "inactive", label: "Ngừng hoạt động" }] },
];

export default function AdminProductsPage() {
  return <AdminResourceManager title="Quản lý sản phẩm" description="Danh sách sản phẩm từ API Laravel. Quản lý giá và biến thể riêng cho từng sản phẩm." resource={{ list: listAdminProducts, create: createAdminProduct, update: updateAdminProduct, remove: deleteAdminProduct, loadFieldOptions: getAdminProductFieldOptions }} columns={columns} fields={fields} emptyMessage="Chưa có sản phẩm." trashHref="/admin/trash?type=product" rowAction={{ label: "Biến thể", href: (record) => "/admin/products/" + encodeURIComponent(String(record.slug)) + "/variants" }} />;
}
