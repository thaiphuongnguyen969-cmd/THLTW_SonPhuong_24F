"use client";

import { useCallback, useEffect, useState } from "react";
import type { FormEvent } from "react";
import { getAdminToken } from "@/lib/admin-auth";
import { getApiErrorMessage } from "@/lib/api/client";
import { createAdminUser, listAdminUsers, setAdminUserStatus, updateAdminUser, type AdminUser } from "@/lib/api/admin-users";

export default function AdminUsersPage() {
  const [token, setToken] = useState("");
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [selected, setSelected] = useState<AdminUser | null>(null);
  const [editing, setEditing] = useState<AdminUser | "new" | null>(null);
  const [query, setQuery] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [pages, setPages] = useState({ current: 1, last: 1, perPage: 15, total: 0 });
  const [busyId, setBusyId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => { void Promise.resolve().then(() => setToken(getAdminToken() ?? "")); }, []);
  const load = useCallback(async () => {
    if (!token) { setLoading(false); setError("Vui lòng đăng nhập bằng tài khoản quản trị."); return; }
    setLoading(true); setError("");
    try {
      const result = await listAdminUsers(token, { page, per_page: 15, search: search || undefined });
      setUsers(result.data);
      setPages({ current: result.current_page, last: result.last_page, perPage: result.per_page, total: result.total });
    } catch (cause) { setError(getApiErrorMessage(cause, "Không thể tải danh sách tài khoản.")); }
    finally { setLoading(false); }
  }, [page, search, token]);
  useEffect(() => { void Promise.resolve().then(() => load()); }, [load]);

  const pageNumbers = Array.from({ length: Math.min(5, pages.last) }, (_, i) => Math.max(1, Math.min(pages.current - 2, pages.last - 4)) + i);
  function searchUsers(event: FormEvent) { event.preventDefault(); setPage(1); setSearch(query.trim()); }

  async function toggleStatus(user: AdminUser) {
    const next = user.status === "active" ? "blocked" : "active";
    if (!window.confirm(`Bạn có chắc muốn ${next === "active" ? "mở khóa" : "khóa"} tài khoản ${user.email}?`)) return;
    setBusyId(user.id); setError(""); setNotice("");
    try { await setAdminUserStatus(token, user, next); setNotice(`Đã ${next === "active" ? "mở khóa" : "khóa"} tài khoản ${user.email}.`); await load(); }
    catch (cause) { setError(getApiErrorMessage(cause, "Không thể cập nhật trạng thái.")); }
    finally { setBusyId(null); }
  }

  async function saveUser(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); if (!editing || busy) return;
    const form = new FormData(event.currentTarget);
    const payload = { name: String(form.get("name") ?? "").trim(), email: String(form.get("email") ?? "").trim(), phone: String(form.get("phone") ?? "").trim() || null, address: String(form.get("address") ?? "").trim() || null };
    const password = String(form.get("password") ?? "");
    if (!payload.name || !payload.email || (editing === "new" && password.length < 6)) { setError(editing === "new" ? "Vui lòng nhập tên, email và mật khẩu tối thiểu 6 ký tự." : "Vui lòng nhập tên và email hợp lệ."); return; }
    setBusy(true); setError(""); setNotice("");
    try {
      if (editing === "new") await createAdminUser(token, { ...payload, password });
      else await updateAdminUser(token, editing, payload);
      setEditing(null); setNotice(editing === "new" ? "Đã tạo tài khoản khách hàng." : "Đã cập nhật tài khoản.");
      if (editing === "new") { setPage(1); setSearch(""); setQuery(""); }
      await load();
    } catch (cause) { setError(getApiErrorMessage(cause, "Không thể lưu tài khoản.")); }
    finally { setBusy(false); }
  }

  return <section className="space-y-5">
    <header className="flex flex-wrap items-end justify-between gap-3"><div><h1 className="text-2xl font-semibold">Quản lý tài khoản</h1><p className="mt-1 text-sm text-zinc-500">Tìm kiếm, xem và quản lý trạng thái tài khoản trong hệ thống.</p></div><button type="button" onClick={() => { setError(""); setEditing("new"); }} className="rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white">+ Tạo khách hàng</button></header>
    {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
    {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
    <form onSubmit={searchUsers} className="flex max-w-xl gap-2"><label className="flex-1 text-sm font-medium">Tìm theo tên hoặc email<input value={query} onChange={(event) => setQuery(event.target.value)} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-2.5 font-normal" placeholder="Nhập tên hoặc email" /></label><button className="self-end rounded-xl border px-4 py-2.5" disabled={loading}>Tìm</button></form>
    <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white"><table className="min-w-full text-left text-sm"><thead className="bg-stone-100 text-xs uppercase text-zinc-500"><tr><th className="px-4 py-3">Tên</th><th className="px-4 py-3">Email</th><th className="px-4 py-3">Vai trò</th><th className="px-4 py-3">Trạng thái</th><th className="px-4 py-3">Ngày tạo</th><th className="px-4 py-3">Thao tác</th></tr></thead><tbody className="divide-y divide-zinc-100">
      {users.map((user) => <tr key={user.id}><td className="px-4 py-3 font-medium">{user.name}</td><td className="px-4 py-3">{user.email}</td><td className="px-4 py-3">{user.role === "admin" ? "Quản trị viên" : "Khách hàng"}</td><td className="px-4 py-3">{user.status === "active" ? "Đang hoạt động" : "Đã khóa"}</td><td className="whitespace-nowrap px-4 py-3">{user.created_at ? new Date(user.created_at.replace(" ", "T") + (user.created_at.endsWith("Z") ? "" : "Z")).toLocaleDateString("vi-VN") : "—"}</td><td className="whitespace-nowrap px-4 py-3"><button className="mr-3 underline" onClick={() => setSelected(user)}>Chi tiết</button><button className="mr-3 underline" onClick={() => { setError(""); setEditing(user); }}>Sửa</button>{user.role !== "admin" && <button disabled={busyId === user.id} className="text-red-700 underline disabled:opacity-50" onClick={() => void toggleStatus(user)}>{busyId === user.id ? "Đang xử lý…" : user.status === "active" ? "Khóa" : "Mở khóa"}</button>}</td></tr>)}
      {loading && <tr><td colSpan={6} className="px-5 py-12 text-center text-zinc-500">Đang tải tài khoản…</td></tr>}{!loading && users.length === 0 && <tr><td colSpan={6} className="px-5 py-12 text-center text-zinc-500">{search ? "Không tìm thấy tài khoản phù hợp." : "Chưa có tài khoản."}</td></tr>}
    </tbody></table></div>
    <footer className="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-600"><span>{pages.total} tài khoản · Trang {pages.current} / {pages.last}</span><nav aria-label="Phân trang" className="flex gap-1"><button disabled={page <= 1 || loading} onClick={() => setPage(page - 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">← Trước</button>{pageNumbers.map((number) => <button key={number} aria-current={number === page ? "page" : undefined} disabled={loading} onClick={() => setPage(number)} className={`rounded-lg border px-3 py-2 ${number === page ? "bg-zinc-900 text-white" : ""}`}>{number}</button>)}<button disabled={page >= pages.last || loading} onClick={() => setPage(page + 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">Sau →</button></nav></footer>
    {(selected || editing) && <div role="presentation" className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5" onMouseDown={(event) => { if (event.target === event.currentTarget && !busy) { setSelected(null); setEditing(null); } }}>
      {selected && <section role="dialog" aria-modal="true" aria-labelledby="user-detail-title" className="w-full max-w-lg rounded-t-2xl bg-white p-6 sm:rounded-2xl"><div className="flex justify-between"><h2 id="user-detail-title" className="text-xl font-semibold">Chi tiết tài khoản</h2><button onClick={() => setSelected(null)} className="underline">Đóng</button></div><dl className="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">{[["Tên", selected.name], ["Email", selected.email], ["Vai trò", selected.role === "admin" ? "Quản trị viên" : "Khách hàng"], ["Trạng thái", selected.status], ["Điện thoại", selected.phone || "—"], ["Địa chỉ", selected.address || "—"], ["Ngày tạo", selected.created_at || "—"]].map(([label, value]) => <div key={label}><dt className="text-zinc-500">{label}</dt><dd className="break-words">{value}</dd></div>)}</dl></section>}
      {editing && <section role="dialog" aria-modal="true" aria-labelledby="user-edit-title" className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white p-6 sm:rounded-2xl"><div className="flex justify-between"><h2 id="user-edit-title" className="text-xl font-semibold">{editing === "new" ? "Tạo khách hàng" : "Cập nhật tài khoản"}</h2><button disabled={busy} onClick={() => setEditing(null)} className="underline">Đóng</button></div><form onSubmit={(event) => void saveUser(event)} className="mt-5 space-y-4">{([["name", "Họ tên", true], ["email", "Email", true], ["phone", "Điện thoại", false], ["address", "Địa chỉ", false]] as const).map(([field, label, required]) => <label key={field} className="block text-sm font-medium">{label}<input name={field} type={field === "email" ? "email" : "text"} required={required} defaultValue={editing === "new" ? "" : editing[field] ?? ""} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-normal" /></label>)}{editing === "new" && <label className="block text-sm font-medium">Mật khẩu tạm thời<input name="password" type="password" minLength={6} required autoComplete="new-password" className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-normal" /></label>}<p className="text-xs text-zinc-500">Vai trò quản trị không thể thay đổi tại màn hình này.</p><div className="flex justify-end gap-3 border-t pt-4"><button type="button" disabled={busy} onClick={() => setEditing(null)} className="rounded-xl border px-4 py-2">Hủy</button><button disabled={busy} className="rounded-xl bg-zinc-900 px-4 py-2 text-white">{busy ? "Đang lưu…" : "Lưu"}</button></div></form></section>}
    </div>}
  </section>;
}
