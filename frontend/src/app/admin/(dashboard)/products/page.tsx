import { AdminResourceManager } from "@/components/admin/AdminResourceManager";
import type { AdminColumn, AdminField, AdminRecord } from "@/components/admin/AdminResourceManager";
import { mockBrands, mockCategories, mockProducts } from "@/lib/mock-data";

const categoryLabels: Record<string, string> = {
  "New Arrivals": "Hàng mới",
  Tailoring: "Đồ may đo",
  Accessories: "Phụ kiện",
  Essentials: "Đồ thiết yếu",
};

const rows: AdminRecord[] = mockProducts.map((product) => ({
  id: product.id,
  image: product.image ?? product.images?.[0]?.image_url ?? "",
  name: product.name,
  sku: product.variants?.[0]?.sku ?? "Chưa có mã",
  category: categoryLabels[product.category?.name ?? ""] ?? "Chưa phân loại",
  brand: product.brand?.name ?? "Chưa có thương hiệu",
  price: String(product.sale_price ?? product.price),
  status: product.status,
}));

const columns: AdminColumn[] = [
  { key: "image", label: "Ảnh", kind: "image" },
  { key: "name", label: "Sản phẩm" },
  { key: "id", label: "ID" },
  { key: "sku", label: "Mã sản phẩm" },
  { key: "category", label: "Danh mục" },
  { key: "brand", label: "Thương hiệu" },
  { key: "price", label: "Giá", kind: "price" },
  { key: "status", label: "Trạng thái", kind: "status" },
];

const fields: AdminField[] = [
  { name: "name", label: "Tên sản phẩm", required: true },
  { name: "sku", label: "Mã sản phẩm" },
  { name: "category", label: "Danh mục", type: "select", options: mockCategories.map((item) => ({ value: categoryLabels[item.name] ?? item.name, label: categoryLabels[item.name] ?? item.name })) },
  { name: "brand", label: "Thương hiệu", type: "select", options: mockBrands.map((item) => ({ value: item.name, label: item.name })) },
  { name: "price", label: "Giá (đơn vị chưa xác minh)", type: "number", required: true },
  { name: "image", label: "Đường dẫn hình ảnh" },
  { name: "status", label: "Trạng thái", type: "select", required: true, options: [{ value: "active", label: "Đang hoạt động" }, { value: "inactive", label: "Ngừng hoạt động" }] },
];

export default function AdminProductsPage() {
  return <AdminResourceManager title="Quản lý sản phẩm" description="Danh sách sản phẩm mẫu; giá chưa xác định đơn vị tiền." initialRows={rows} columns={columns} fields={fields} emptyMessage="Chưa có sản phẩm trong danh sách demo." />;
}
