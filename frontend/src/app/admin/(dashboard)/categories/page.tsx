import { AdminResourceManager } from "@/components/admin/AdminResourceManager";
import type { AdminColumn, AdminField, AdminRecord } from "@/components/admin/AdminResourceManager";
import { mockCategories } from "@/lib/mock-data";

const categoryLabels: Record<string, string> = {
  "New Arrivals": "Hàng mới",
  Tailoring: "Đồ may đo",
  Essentials: "Đồ thiết yếu",
  Accessories: "Phụ kiện",
};

const rows: AdminRecord[] = mockCategories.map((category) => ({
  id: category.id,
  name: categoryLabels[category.name] ?? category.name,
  slug: category.slug,
  parent_id: category.parent_id ?? "",
  description: category.description ?? "",
  status: category.status,
}));

const columns: AdminColumn[] = [
  { key: "name", label: "Danh mục" },
  { key: "slug", label: "Đường dẫn" },
  { key: "parent_id", label: "Danh mục cha (ID)" },
  { key: "description", label: "Mô tả" },
  { key: "status", label: "Trạng thái", kind: "status" },
];

const fields: AdminField[] = [
  { name: "name", label: "Tên danh mục", required: true },
  { name: "slug", label: "Đường dẫn", required: true },
  { name: "parent_id", label: "ID danh mục cha (nếu có)", type: "number" },
  { name: "description", label: "Mô tả", type: "textarea" },
  { name: "status", label: "Trạng thái", type: "select", required: true, options: [{ value: "active", label: "Đang hoạt động" }, { value: "inactive", label: "Ngừng hoạt động" }] },
];

export default function AdminCategoriesPage() {
  return <AdminResourceManager title="Quản lý danh mục" description="Các trường hiển thị dựa trên kiểu Category hiện có." initialRows={rows} columns={columns} fields={fields} emptyMessage="Chưa có danh mục trong danh sách demo." />;
}
