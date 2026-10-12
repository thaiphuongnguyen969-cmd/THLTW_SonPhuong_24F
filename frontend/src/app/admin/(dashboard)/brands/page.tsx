import { AdminResourceManager } from "@/components/admin/AdminResourceManager";
import type { AdminColumn, AdminField, AdminRecord } from "@/components/admin/AdminResourceManager";
import { mockBrands } from "@/lib/mock-data";

const rows: AdminRecord[] = mockBrands.map((brand) => ({
  id: brand.id,
  name: brand.name,
  slug: brand.slug,
  logo: brand.logo ?? "",
  description: brand.description ?? "",
  status: brand.status,
}));

const columns: AdminColumn[] = [
  { key: "name", label: "Thương hiệu" },
  { key: "slug", label: "Đường dẫn" },
  { key: "logo", label: "Logo / ký hiệu" },
  { key: "description", label: "Mô tả" },
  { key: "status", label: "Trạng thái", kind: "status" },
];

const fields: AdminField[] = [
  { name: "name", label: "Tên thương hiệu", required: true },
  { name: "slug", label: "Đường dẫn", required: true },
  { name: "logo", label: "Logo hoặc ký hiệu" },
  { name: "description", label: "Mô tả", type: "textarea" },
  { name: "status", label: "Trạng thái", type: "select", required: true, options: [{ value: "active", label: "Đang hoạt động" }, { value: "inactive", label: "Ngừng hoạt động" }] },
];

export default function AdminBrandsPage() {
  return <AdminResourceManager title="Quản lý thương hiệu" description="Các trường hiển thị dựa trên kiểu Brand hiện có." initialRows={rows} columns={columns} fields={fields} emptyMessage="Chưa có thương hiệu trong danh sách demo." />;
}
