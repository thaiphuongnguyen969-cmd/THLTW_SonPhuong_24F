"use client";

import { AdminResourceManager } from "@/components/admin/AdminResourceManager";
import type { AdminColumn, AdminField } from "@/components/admin/AdminResourceManager";
import { createAdminCategory, deleteAdminCategory, getAdminCategoryOptions, listAdminCategories, updateAdminCategory } from "@/lib/api/admin-categories";

const columns: AdminColumn[] = [
  { key: "name", label: "Danh mục" }, { key: "slug", label: "Đường dẫn" },
  { key: "parent_name", label: "Danh mục cha" }, { key: "description", label: "Mô tả" },
  { key: "status", label: "Trạng thái", kind: "status" },
];
const fields: AdminField[] = [
  { name: "name", label: "Tên danh mục", required: true },
  { name: "slug", label: "Đường dẫn" },
  { name: "parent_id", label: "Danh mục cha", type: "select" },
  { name: "description", label: "Mô tả", type: "textarea" },
  { name: "status", label: "Trạng thái", type: "select", required: true, options: [{ value: "active", label: "Đang hoạt động" }, { value: "inactive", label: "Ngừng hoạt động" }] },
];

export default function AdminCategoriesPage() {
  return <AdminResourceManager title="Quản lý danh mục" description="Danh sách danh mục từ API Laravel." resource={{ list: listAdminCategories, create: createAdminCategory, update: updateAdminCategory, remove: deleteAdminCategory, loadFieldOptions: getAdminCategoryOptions }} columns={columns} fields={fields} emptyMessage="Chưa có danh mục." trashHref="/admin/trash?type=category" />;
}
