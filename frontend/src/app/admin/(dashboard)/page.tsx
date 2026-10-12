import Link from "next/link";
import { adminDemoRecords, adminStatusLabels } from "@/lib/admin-demo-data";

const overview = [
  { label: "Sản phẩm mẫu", count: adminDemoRecords.products.length, href: "/admin/products" },
  { label: "Danh mục mẫu", count: adminDemoRecords.categories.length, href: "/admin/categories" },
  { label: "Thương hiệu mẫu", count: adminDemoRecords.brands.length, href: "/admin/brands" },
  { label: "Đơn hàng mẫu", count: adminDemoRecords.orders.length, href: "/admin/orders" },
  { label: "Khách hàng mẫu", count: adminDemoRecords.customers.length, href: "/admin/customers" },
];

export default function AdminDashboardPage() {
  return (
    <div className="space-y-6">
      <div><h1 className="text-2xl font-semibold">Tổng quan quản trị</h1><p className="mt-1 text-sm text-zinc-500">Các mục dưới đây chỉ đếm dữ liệu mẫu có trong giao diện.</p></div>
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        {overview.map((item) => <Link key={item.href} href={item.href} className="rounded-2xl border border-zinc-200 bg-white p-5 hover:border-zinc-400"><p className="text-sm text-zinc-500">{item.label}</p><p className="mt-3 text-3xl font-semibold">{item.count}</p><p className="mt-1 text-xs text-zinc-400">bản ghi demo</p></Link>)}
      </div>
      <div className="grid gap-6 xl:grid-cols-[1fr_320px]">
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-6">
          <div className="mb-5 flex flex-wrap items-center justify-between gap-2"><div><h2 className="text-lg font-semibold">Đơn hàng mẫu gần đây</h2><p className="mt-1 text-sm text-zinc-500">Không phải dữ liệu đơn hàng thực.</p></div><Link href="/admin/orders" className="text-sm underline">Mở quản lý đơn hàng</Link></div>
          <div className="overflow-x-auto"><table className="min-w-full text-left text-sm"><thead className="text-xs text-zinc-500"><tr><th className="px-3 py-3">Mã đơn</th><th className="px-3 py-3">Khách hàng</th><th className="px-3 py-3">Trạng thái</th><th className="px-3 py-3">Giá trị mẫu</th></tr></thead><tbody className="divide-y divide-zinc-100">{adminDemoRecords.orders.map((order) => <tr key={order.id}><td className="px-3 py-3">#{order.id}</td><td className="px-3 py-3">{order.customer}</td><td className="px-3 py-3">{adminStatusLabels[order.status]}</td><td className="px-3 py-3">{order.total} <span className="text-xs text-zinc-400">(đơn vị chưa xác minh)</span></td></tr>)}</tbody></table></div>
        </section>
        <section className="rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6"><h2 className="font-semibold text-amber-950">Chưa có số liệu vận hành</h2><p className="mt-2 text-sm leading-6 text-amber-900">Doanh thu, tỷ lệ chuyển đổi và biểu đồ chưa được kết nối nguồn dữ liệu đáng tin cậy nên không hiển thị giá trị giả.</p></section>
      </div>
    </div>
  );
}
