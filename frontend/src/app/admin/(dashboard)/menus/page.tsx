"use client";

import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { getAdminToken } from "@/lib/admin-auth";
import { getApiErrorMessage } from "@/lib/api/client";
import { createAdminMenu, deleteAdminMenu, getAdminMenu, listAdminMenus, updateAdminMenu, type AdminMenu, type AdminMenuInput } from "@/lib/api/admin-menus";

const emptyForm: AdminMenuInput = { name: "", link_type: "internal", url: "/", sort_order: 0, status: "active" };
const inputClass = "mt-1 block w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm";

export default function AdminMenusPage() {
  const [menus, setMenus] = useState<AdminMenu[]>([]);
  const [form, setForm] = useState<AdminMenuInput>(emptyForm);
  const [editing, setEditing] = useState<number | null>(null);
  const [detail, setDetail] = useState<AdminMenu | null>(null);
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [refresh, setRefresh] = useState(0);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => {
    let active = true;
    void Promise.resolve().then(async () => {
      const token = getAdminToken();
      if (!token) { if (active) { setError("Phiên quản trị không hợp lệ. Vui lòng đăng nhập lại."); setLoading(false); } return; }
      setLoading(true); setError("");
      try {
        const result = await listAdminMenus(token, { page, per_page: 15, search });
        if (!active) return;
        setMenus(result.data); setLastPage(result.last_page); setTotal(result.total);
      } catch (requestError) { if (active) setError(getApiErrorMessage(requestError, "Không thể tải danh sách menu.")); }
      finally { if (active) setLoading(false); }
    });
    return () => { active = false; };
  }, [page, search, refresh]);

  function startEdit(menu: AdminMenu) {
    setEditing(menu.id);
    setForm({ name: menu.name, link_type: menu.link_type, url: menu.url, sort_order: menu.sort_order, status: menu.status });
    setDetail(null);
  }

  function resetForm() { setEditing(null); setForm(emptyForm); }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const token = getAdminToken();
    if (!token || saving) return;
    const url = form.url.trim();
    let validUrl = false;
    if (form.link_type === "internal") validUrl = /^\/(?!\/)[^\s\\]*$/.test(url);
    else {
      try { const parsed = new URL(url); validUrl = parsed.protocol === "https:" && !parsed.username && !parsed.password; } catch { validUrl = false; }
    }
    if (!form.name.trim()) { setError("Vui lòng nhập tên menu."); return; }
    if (!validUrl) { setError("Đường dẫn nội bộ phải bắt đầu bằng một dấu /; URL ngoài phải là HTTPS hợp lệ."); return; }
    setSaving(true); setError(""); setNotice("");
    try {
      const payload = { ...form, name: form.name.trim(), url };
      if (editing) { await updateAdminMenu(token, editing, payload); setNotice("Đã cập nhật menu."); }
      else { await createAdminMenu(token, payload); setNotice("Đã tạo menu."); setPage(1); }
      resetForm(); setRefresh((value) => value + 1);
    } catch (requestError) { setError(getApiErrorMessage(requestError, "Không thể lưu menu.")); }
    finally { setSaving(false); }
  }

  async function inspect(menu: AdminMenu) {
    const token = getAdminToken(); if (!token) return;
    setError("");
    try { setDetail(await getAdminMenu(token, menu.id)); }
    catch (requestError) { setError(getApiErrorMessage(requestError, "Không thể tải chi tiết menu.")); }
  }

  async function toggleStatus(menu: AdminMenu) {
    const token = getAdminToken(); if (!token || busyId !== null) return;
    setBusyId(menu.id); setError(""); setNotice("");
    try { await updateAdminMenu(token, menu.id, { status: menu.status === "active" ? "inactive" : "active" }); setNotice("Đã cập nhật trạng thái menu."); setRefresh((value) => value + 1); }
    catch (requestError) { setError(getApiErrorMessage(requestError, "Không thể cập nhật trạng thái.")); }
    finally { setBusyId(null); }
  }

  async function remove(menu: AdminMenu) {
    if (!window.confirm(`Chuyển menu “${menu.name}” vào thùng rác?`)) return;
    const token = getAdminToken(); if (!token || busyId !== null) return;
    setBusyId(menu.id); setError(""); setNotice("");
    try { await deleteAdminMenu(token, menu.id); setNotice("Đã chuyển menu vào thùng rác."); setRefresh((value) => value + 1); }
    catch (requestError) { setError(getApiErrorMessage(requestError, "Không thể xóa menu.")); }
    finally { setBusyId(null); }
  }

  return <section className="space-y-5">
    <header><h1 className="text-2xl font-semibold text-zinc-900">Quản lý menu</h1><p className="mt-1 text-sm text-zinc-500">Tạo và sắp xếp liên kết hiển thị trên website.</p></header>
    {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
    {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}

    <form onSubmit={submit} className="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-3">
      <h2 className="text-lg font-semibold sm:col-span-2 lg:col-span-3">{editing ? "Chỉnh sửa menu" : "Thêm menu"}</h2>
      <label className="text-sm font-medium text-zinc-700">Tên menu<input required maxLength={100} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} /></label>
      <label className="text-sm font-medium text-zinc-700">Loại liên kết<select value={form.link_type} onChange={(e) => setForm({ ...form, link_type: e.target.value as AdminMenuInput["link_type"] })} className={inputClass}><option value="internal">Trang nội bộ</option><option value="external">URL ngoài (HTTPS)</option></select></label>
      <label className="text-sm font-medium text-zinc-700">Đường dẫn<input required maxLength={2048} value={form.url} onChange={(e) => setForm({ ...form, url: e.target.value })} placeholder={form.link_type === "internal" ? "/san-pham" : "https://example.com"} className={inputClass} /></label>
      <label className="text-sm font-medium text-zinc-700">Thứ tự hiển thị<input type="number" min={0} max={2147483647} value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: Number(e.target.value) })} className={inputClass} /></label>
      <label className="text-sm font-medium text-zinc-700">Trạng thái<select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value as AdminMenuInput["status"] })} className={inputClass}><option value="active">Đang hiển thị</option><option value="inactive">Đang ẩn</option></select></label>
      <div className="flex items-end gap-2"><button disabled={saving} className="rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50">{saving ? "Đang lưu…" : editing ? "Lưu thay đổi" : "Thêm menu"}</button>{editing && <button type="button" onClick={resetForm} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">Hủy sửa</button>}</div>
    </form>

    <form onSubmit={(e) => { e.preventDefault(); setPage(1); setSearch(searchInput.trim()); }} className="flex flex-wrap gap-2">
      <label className="min-w-56 flex-1 text-sm font-medium text-zinc-700">Tìm theo tên hoặc URL<input value={searchInput} maxLength={100} onChange={(e) => setSearchInput(e.target.value)} className={inputClass} placeholder="Nhập từ khóa" /></label>
      <button className="self-end rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white">Tìm kiếm</button>
    </form>

    <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white"><table className="min-w-full text-left text-sm"><thead className="bg-stone-100 text-xs uppercase text-zinc-500"><tr><th className="px-4 py-3">Tên</th><th className="px-4 py-3">Đường dẫn</th><th className="px-4 py-3">Thứ tự</th><th className="px-4 py-3">Trạng thái</th><th className="px-4 py-3">Thao tác</th></tr></thead><tbody className="divide-y divide-zinc-100">
      {menus.map((menu) => <tr key={menu.id}><td className="px-4 py-3 font-medium">{menu.name}</td><td className="max-w-xs truncate px-4 py-3 text-zinc-600">{menu.url}</td><td className="px-4 py-3">{menu.sort_order}</td><td className="px-4 py-3">{menu.status === "active" ? "Đang hiển thị" : "Đang ẩn"}</td><td className="whitespace-nowrap px-4 py-3"><button type="button" onClick={() => void inspect(menu)} className="mr-3 underline">Chi tiết</button><button type="button" onClick={() => startEdit(menu)} className="mr-3 underline">Sửa</button><button type="button" disabled={busyId !== null} onClick={() => void toggleStatus(menu)} className="mr-3 underline disabled:opacity-50">{busyId === menu.id ? "Đang xử lý…" : menu.status === "active" ? "Ẩn" : "Hiện"}</button><button type="button" disabled={busyId !== null} onClick={() => void remove(menu)} className="text-red-700 underline disabled:opacity-50">Xóa</button></td></tr>)}
      {loading && <tr><td colSpan={5} className="px-5 py-12 text-center text-zinc-500">Đang tải menu…</td></tr>}
      {!loading && !error && menus.length === 0 && <tr><td colSpan={5} className="px-5 py-12 text-center text-zinc-500">Chưa có menu phù hợp.</td></tr>}
    </tbody></table></div>
    <footer className="flex flex-wrap justify-between gap-3 text-sm text-zinc-600"><span>{total} menu · Trang {page} / {lastPage}</span><div className="flex gap-2"><button disabled={page <= 1 || loading} onClick={() => setPage((value) => value - 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">Trước</button><button disabled={page >= lastPage || loading} onClick={() => setPage((value) => value + 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">Sau</button></div></footer>
    {detail && <div role="dialog" aria-modal="true" aria-labelledby="menu-detail-title" className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onClick={() => setDetail(null)}><div className="w-full max-w-lg space-y-3 rounded-2xl bg-white p-6" onClick={(e) => e.stopPropagation()}><h2 id="menu-detail-title" className="text-xl font-semibold">Chi tiết menu</h2><p><strong>Tên:</strong> {detail.name}</p><p><strong>Liên kết:</strong> {detail.url} ({detail.link_type === "internal" ? "nội bộ" : "ngoài"})</p><p><strong>Thứ tự:</strong> {detail.sort_order}</p><p><strong>Trạng thái:</strong> {detail.status === "active" ? "Đang hiển thị" : "Đang ẩn"}</p><p><strong>Ngày tạo:</strong> {new Date(detail.created_at).toLocaleString("vi-VN")}</p><button onClick={() => setDetail(null)} className="rounded-xl border px-4 py-2">Đóng</button></div></div>}
  </section>;
}
