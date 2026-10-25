"use client";

import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { getAdminToken } from "@/lib/admin-auth";
import { getApiErrorMessage } from "@/lib/api/client";
import {
  listAdminTrash,
  permanentlyDeleteAdminTrashRecord,
  restoreAdminTrashRecord,
  type TrashRecord,
  type TrashType,
} from "@/lib/api/admin-trash";

const typeLabels: Record<TrashType, string> = {
  category: "Danh mục",
  brand: "Thương hiệu",
  product: "Sản phẩm",
  post: "Bài viết",
  menu: "Menu",
};

export default function AdminTrashPage() {
  const [records, setRecords] = useState<TrashRecord[]>([]);
  const [type, setType] = useState<TrashType | "all">("all");
  const [queryInitialized, setQueryInitialized] = useState(false);
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [refreshKey, setRefreshKey] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [busyKey, setBusyKey] = useState("");

  useEffect(() => {
    void Promise.resolve().then(() => {
      const requestedType = new URLSearchParams(window.location.search).get("type");
      if (requestedType === "product" || requestedType === "category" || requestedType === "brand" || requestedType === "post" || requestedType === "menu") {
        setType(requestedType);
      }
      setQueryInitialized(true);
    });
  }, []);

  useEffect(() => {
    if (!queryInitialized) return;
    let active = true;
    void Promise.resolve().then(async () => {
      const token = getAdminToken();
      if (!token) {
        if (active) { setError("Phiên quản trị không còn hợp lệ. Đăng nhập lại để tiếp tục."); setLoading(false); }
        return;
      }
      setLoading(true);
      setError("");
      try {
        const result = await listAdminTrash(token, { type, search, page, per_page: 15 });
        if (!active) return;
        setRecords(result.data);
        setLastPage(result.meta.last_page);
        setTotal(result.meta.total);
      } catch (requestError) {
        if (active) setError(getApiErrorMessage(requestError, "Không thể tải thùng rác."));
      } finally {
        if (active) setLoading(false);
      }
    });
    return () => { active = false; };
  }, [queryInitialized, type, search, page, refreshKey]);

  async function performRestore(record: TrashRecord) {
    const token = getAdminToken();
    if (!token || busyKey) return;
    const key = `${record.type}:${record.id}`;
    setBusyKey(key); setError(""); setNotice("");
    try {
      await restoreAdminTrashRecord(token, record);
      setNotice(`Đã khôi phục ${typeLabels[record.type].toLocaleLowerCase("vi")} “${record.name}”.`);
      setRefreshKey((value) => value + 1);
    } catch (requestError) { setError(getApiErrorMessage(requestError, "Không thể khôi phục bản ghi.")); }
    finally { setBusyKey(""); }
  }

  async function performPermanentDelete(record: TrashRecord) {
    if (!window.confirm(`Xóa vĩnh viễn “${record.name}”? Thao tác này không thể hoàn tác.`)) return;
    const token = getAdminToken();
    if (!token || busyKey) return;
    const key = `${record.type}:${record.id}`;
    setBusyKey(key); setError(""); setNotice("");
    try {
      await permanentlyDeleteAdminTrashRecord(token, record);
      setNotice(`Đã xóa vĩnh viễn “${record.name}”.`);
      setRefreshKey((value) => value + 1);
    } catch (requestError) { setError(getApiErrorMessage(requestError, "Không thể xóa vĩnh viễn bản ghi.")); }
    finally { setBusyKey(""); }
  }

  function applySearch(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPage(1);
    setSearch(searchInput.trim());
  }

  return <section className="space-y-5">
    <header>
      <h1 className="text-2xl font-semibold text-zinc-900">Thùng rác</h1>
      <p className="mt-1 text-sm text-zinc-500">Khôi phục bản ghi đã xóa mềm hoặc xóa vĩnh viễn khi không còn dữ liệu liên quan.</p>
    </header>

    {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
    {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}

    <div className="flex flex-wrap items-end gap-3">
      <label className="block text-sm font-medium text-zinc-700">Loại dữ liệu
        <select value={type} onChange={(event) => { setType(event.target.value as TrashType | "all"); setPage(1); }} className="mt-2 block min-w-48 rounded-xl border border-zinc-300 bg-white px-3 py-2.5 font-normal">
          <option value="all">Tất cả</option><option value="category">Danh mục</option>
          <option value="brand">Thương hiệu</option><option value="product">Sản phẩm</option><option value="post">Bài viết</option><option value="menu">Menu</option>
        </select>
      </label>
      <form onSubmit={applySearch} className="flex flex-1 flex-wrap gap-2">
        <label className="min-w-56 flex-1 text-sm font-medium text-zinc-700">Tìm theo tên
          <input value={searchInput} onChange={(event) => setSearchInput(event.target.value)} maxLength={100} className="mt-2 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-normal" placeholder="Nhập tên cần tìm" />
        </label>
        <button type="submit" className="self-end rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-700">Tìm kiếm</button>
      </form>
    </div>

    <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
      <table className="min-w-full text-left text-sm">
        <thead className="bg-stone-100 text-xs uppercase tracking-wide text-zinc-500"><tr>
          <th className="px-4 py-3 font-medium">Tên</th><th className="px-4 py-3 font-medium">Loại</th>
          <th className="px-4 py-3 font-medium">Slug</th><th className="px-4 py-3 font-medium">Trạng thái</th>
          <th className="px-4 py-3 font-medium">Thời điểm xóa</th><th className="px-4 py-3 font-medium">Thao tác</th>
        </tr></thead>
        <tbody className="divide-y divide-zinc-100">
          {records.map((record) => {
            const key = `${record.type}:${record.id}`;
            return <tr key={key}>
              <td className="px-4 py-3 font-medium text-zinc-900">{record.name}<span className="ml-2 text-xs text-zinc-400">#{record.id}</span></td>
              <td className="px-4 py-3 text-zinc-700">{typeLabels[record.type]}</td>
              <td className="px-4 py-3 text-zinc-600">{record.slug ?? "—"}</td>
              <td className="px-4 py-3 text-zinc-600">{record.status === "active" ? "Đang hoạt động" : "Ngừng hoạt động"}</td>
              <td className="whitespace-nowrap px-4 py-3 text-zinc-600">{new Date(record.deleted_at.replace(" ", "T")).toLocaleString("vi-VN")}</td>
              <td className="whitespace-nowrap px-4 py-3">
                <button type="button" disabled={Boolean(busyKey)} onClick={() => void performRestore(record)} className="mr-3 font-medium text-zinc-800 underline disabled:opacity-50">{busyKey === key ? "Đang xử lý…" : "Khôi phục"}</button>
                <button type="button" disabled={Boolean(busyKey)} onClick={() => void performPermanentDelete(record)} className="font-medium text-red-700 underline disabled:opacity-50">Xóa vĩnh viễn</button>
              </td>
            </tr>;
          })}
          {loading && <tr><td colSpan={6} className="px-5 py-12 text-center text-zinc-500">Đang tải thùng rác…</td></tr>}
          {!loading && records.length === 0 && <tr><td colSpan={6} className="px-5 py-12 text-center text-zinc-500">Thùng rác đang trống.</td></tr>}
        </tbody>
      </table>
    </div>

    <footer className="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-600">
      <span>{total} bản ghi · Trang {page} / {lastPage}</span>
      <div className="flex gap-2">
        <button type="button" disabled={page <= 1 || loading} onClick={() => setPage((value) => value - 1)} className="rounded-lg border border-zinc-300 px-3 py-2 disabled:opacity-40">Trước</button>
        <button type="button" disabled={page >= lastPage || loading} onClick={() => setPage((value) => value + 1)} className="rounded-lg border border-zinc-300 px-3 py-2 disabled:opacity-40">Sau</button>
      </div>
    </footer>
  </section>;
}
