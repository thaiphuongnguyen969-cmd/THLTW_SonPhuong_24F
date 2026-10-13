"use client";

import { useMemo, useState } from "react";
import { demoCustomers } from "@/lib/admin-demo-data";

export function AdminCustomersWorkspace() {
  const [query, setQuery] = useState("");
  const [selected, setSelected] = useState<(typeof demoCustomers)[number] | null>(null);
  const visible = useMemo(() => demoCustomers.filter((customer) =>
    `${customer.name} ${customer.email}`.toLocaleLowerCase("vi").includes(query.trim().toLocaleLowerCase("vi")),
  ), [query]);

  return (
    <section className="space-y-5">
      <div><h1 className="text-2xl font-semibold">Khách hàng</h1><p className="mt-1 text-sm text-zinc-500">Thông tin minh họa; không có thông tin xác thực hoặc dữ liệu khách hàng thật.</p></div>
      <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Danh sách demo. Không có thao tác khóa hoặc xóa tài khoản.</p>
      <label className="block max-w-md text-sm font-medium">Tìm khách hàng<input value={query} onChange={(event) => setQuery(event.target.value)} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 font-normal" /></label>
      <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white"><table className="min-w-full text-left text-sm"><thead className="bg-stone-100 text-xs uppercase tracking-wide text-zinc-500"><tr><th className="px-4 py-3">Mã mẫu</th><th className="px-4 py-3">Khách hàng</th><th className="px-4 py-3">Email mẫu</th><th className="px-4 py-3">Điện thoại</th><th className="px-4 py-3">Thao tác</th></tr></thead><tbody className="divide-y divide-zinc-100">{visible.map((customer) => <tr key={customer.id}><td className="px-4 py-3">#{customer.id}</td><td className="px-4 py-3 font-medium">{customer.name}</td><td className="px-4 py-3">{customer.email}</td><td className="px-4 py-3">{customer.phone}</td><td className="px-4 py-3"><button type="button" onClick={() => setSelected(customer)} className="underline">Xem chi tiết</button></td></tr>)}{visible.length === 0 && <tr><td colSpan={5} className="px-4 py-12 text-center text-zinc-500">Không tìm thấy khách hàng trong dữ liệu demo.</td></tr>}</tbody></table></div>
      {selected && <div className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) setSelected(null); }}><section role="dialog" aria-modal="true" aria-labelledby="customer-detail-title" className="w-full max-w-md rounded-t-2xl bg-white p-6 sm:rounded-2xl"><div className="flex justify-between gap-3"><h2 id="customer-detail-title" className="text-xl font-semibold">Chi tiết khách hàng mẫu</h2><button type="button" onClick={() => setSelected(null)} className="text-sm underline">Đóng</button></div><dl className="mt-5 space-y-3 text-sm"><div><dt className="text-zinc-500">Tên</dt><dd>{selected.name}</dd></div><div><dt className="text-zinc-500">Email minh họa</dt><dd>{selected.email}</dd></div><div><dt className="text-zinc-500">Điện thoại</dt><dd>{selected.phone}</dd></div></dl></section></div>}
    </section>
  );
}
