import { mockBrands, mockCategories, mockProducts } from "@/lib/mock-data";

export const demoCustomers = [
  { id: 1, name: "Khách hàng mẫu 01", email: "khachhang01@example.test", phone: "••••••••01" },
  { id: 2, name: "Khách hàng mẫu 02", email: "khachhang02@example.test", phone: "••••••••02" },
  { id: 3, name: "Khách hàng mẫu 03", email: "khachhang03@example.test", phone: "••••••••03" },
];

export const demoOrders = [
  { id: 1042, customer: demoCustomers[0].name, date: "2026-10-08", total: "283", status: "pending", items: ["Cropped Wool Blazer × 1"] },
  { id: 1043, customer: demoCustomers[1].name, date: "2026-10-09", total: "142", status: "processing", items: ["Classic Denim Layer × 1"] },
  { id: 1044, customer: demoCustomers[2].name, date: "2026-10-10", total: "216", status: "shipped", items: ["Minimal Leather Tote × 1"] },
];

export const adminDemoRecords = {
  products: mockProducts,
  categories: mockCategories,
  brands: mockBrands,
  orders: demoOrders,
  customers: demoCustomers,
};

export const adminStatusLabels: Record<string, string> = {
  active: "Đang hoạt động",
  inactive: "Ngừng hoạt động",
  pending: "Chờ xử lý",
  processing: "Đang xử lý",
  shipped: "Đang giao",
  delivered: "Đã giao",
  cancelled: "Đã hủy",
};
