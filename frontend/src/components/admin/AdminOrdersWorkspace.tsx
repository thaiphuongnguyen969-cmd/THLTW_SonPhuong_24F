"use client";

import { useMemo, useState } from "react";
import { adminStatusLabels, demoOrders } from "@/lib/admin-demo-data";

type DemoOrder = (typeof demoOrders)[number];

export function AdminOrdersWorkspace() {
  const [orders, setOrders] = useState<DemoOrder[]>(demoOrders);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("");
  const [selected, setSelected] = useState<DemoOrder | null>(null);
  const [notice, setNotice] = useState("");
  const visible = useMemo(() => orders.filter((order) =>
    (!status || order.status === status)
    && `${order.id} ${order.customer}`.toLocaleLowerCase("vi").includes(query.trim().toLocaleLowerCase("vi")),
  ), [orders, query, status]);

  function updateStatus(id: number, nextStatus: string) {
    setOrders((current) => current.map((order) => order.id === id ? { ...order, status: nextStatus } : order));
    setNotice("Trạng thái chỉ được thay đổi trong dữ liệu demo trên trang; không cập nhật đơn hàng thật.");
    setSelected((current) => current?.id === id ? { ...current, status: nextStatus } : current);
  }

  return (
    <section className="space-y-5">
      <div><h1 className="text-2xl font-semibold">Quản lý đơn hàng</h1><p className="mt-1 text-sm text-zinc-500">Danh sách và thao tác minh họa bằng dữ liệu mẫu.</p></div>
      <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Không xác nhận thanh toán hoặc giao hàng thật. Đơn vị giá mẫu chưa được xác minh.</p>
      {notice && <p role="status" className="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
      <div className="grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 sm:grid-cols-2">
        <label className="text-sm font-medium">Tìm mã đơn hoặc khách hàng<input value={query} onChange={(event) => setQuery(event.target.value)} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-2.5 font-normal" /></label>
        <label className="text-sm font-medium">Lọc trạng thái<select value={status} onChange={(event) => setStatus(event.target.value)} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 font-normal"><option value="">Tất cả trạng thái</option>{Object.entries(adminStatusLabels).filter(([key]) => ["pending", "processing", "shipped", "delivered", "cancelled"].includes(key)).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
      </div>
      <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table className="min-w-full text-left text-sm"><thead className="bg-stone-100 text-xs uppercase tracking-wide text-zinc-500"><tr><th className="px-4 py-3">Mã đơn</th><th className="px-4 py-3">Khách hàng</th><th className="px-4 py-3">Ngày đặt (mẫu)</th><th className="px-4 py-3">Tổng tiền mẫu</th><th className="px-4 py-3">Trạng thái</th><th className="px-4 py-3">Thao tác</th></tr></thead>
          <tbody className="divide-y divide-zinc-100">{visible.map((order) => <tr key={order.id}><td className="px-4 py-3">#{order.id}</td><td className="px-4 py-3">{order.customer}</td><td className="px-4 py-3">{order.date}</td><td className="px-4 py-3">{order.total} <span className="text-xs text-zinc-400">(đơn vị chưa rõ)</span></td><td className="px-4 py-3">{adminStatusLabels[order.status]}</td><td className="whitespace-nowrap px-4 py-3"><button type="button" onClick={() => setSelected(order)} className="mr-3 underline">Chi tiết</button><select aria-label={`Đổi trạng thái đơn ${order.id} (demo)`} value={order.status} onChange={(event) => updateStatus(order.id, event.target.value)} className="rounded-lg border border-zinc-300 bg-white px-2 py-1"><option value="pending">Chờ xử lý</option><option value="processing">Đang xử lý</option><option value="shipped">Đang giao</option><option value="delivered">Đã giao</option><option value="cancelled">Đã hủy</option></select></td></tr>)}
          {visible.length === 0 && <tr><td colSpan={6} className="px-4 py-12 text-center text-zinc-500">Không có đơn hàng phù hợp trong dữ liệu demo.</td></tr>}</tbody>
        </table>
      </div>
      {selected && <div className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) setSelected(null); }}><section role="dialog" aria-modal="true" aria-labelledby="order-detail-title" className="w-full max-w-lg rounded-t-2xl bg-white p-6 sm:rounded-2xl"><div className="flex justify-between"><h2 id="order-detail-title" className="text-xl font-semibold">Chi tiết đơn mẫu #{selected.id}</h2><button type="button" onClick={() => setSelected(null)} className="text-sm underline">Đóng</button></div><dl className="mt-5 space-y-3 text-sm"><div className="flex justify-between gap-3"><dt className="text-zinc-500">Khách hàng</dt><dd>{selected.customer}</dd></div><div className="flex justify-between gap-3"><dt className="text-zinc-500">Ngày tạo mẫu</dt><dd>{selected.date}</dd></div><div className="flex justify-between gap-3"><dt className="text-zinc-500">Trạng thái demo</dt><dd>{adminStatusLabels[selected.status]}</dd></div><div><dt className="text-zinc-500">Sản phẩm</dt><dd className="mt-1">{selected.items.join(", ")}</dd></div><div className="flex justify-between gap-3 border-t pt-3 font-semibold"><dt>Tổng tiền mẫu</dt><dd>{selected.total} <span className="font-normal text-zinc-500">(đơn vị chưa rõ)</span></dd></div></dl></section></div>}
    </section>
  );
}
