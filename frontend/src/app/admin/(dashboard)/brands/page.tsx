"use client";

import { AdminResourceManager } from "@/components/admin/AdminResourceManager";
import type { AdminColumn, AdminField } from "@/components/admin/AdminResourceManager";
import { createAdminBrand, deleteAdminBrand, listAdminBrands, updateAdminBrand } from "@/lib/api/admin-brands";

const columns: AdminColumn[] = [
  { key: "name", label: "Thương hiệu" }, { key: "slug", label: "Đường dẫn" },
  { key: "logo", label: "Logo / ký hiệu" }, { key: "description", label: "Mô tả" },
  { key: "status", label: "Trạng thái", kind: "status" },
];
const fields: AdminField[] = [
  { name: "name", label: "Tên thương hiệu", required: true }, { name: "slug", label: "Đường dẫn" },
  { name: "logo", label: "Logo hoặc ký hiệu" }, { name: "description", label: "Mô tả", type: "textarea" },
  { name: "status", label: "Trạng thái", type: "select", required: true, options: [{ value: "active", label: "Đang hoạt động" }, { value: "inactive", label: "Ngừng hoạt động" }] },
];

export default function AdminBrandsPage() {
  return <AdminResourceManager title="Quản lý thương hiệu" description="Danh sách thương hiệu từ API Laravel." resource={{ list: listAdminBrands, create: createAdminBrand, update: updateAdminBrand, remove: deleteAdminBrand }} columns={columns} fields={fields} emptyMessage="Chưa có thương hiệu." trashHref="/admin/trash?type=brand" />;
}
