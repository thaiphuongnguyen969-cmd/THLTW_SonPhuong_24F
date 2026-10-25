"use client";

import Image from "next/image";
import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import type { FormEvent } from "react";
import { getAdminToken } from "@/lib/admin-auth";
import { getApiErrorMessage } from "@/lib/api/client";
import { createAdminPost, deleteAdminPost, getAdminPost, listAdminPosts, updateAdminPost, type AdminPost, type PostPayload } from "@/lib/api/admin-posts";

type Draft = Omit<PostPayload, "thumbnail">;
const blankDraft: Draft = { title: "", slug: "", excerpt: "", content: "", status: "draft", published_at: "" };

function slugify(value: string) {
  return value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().replace(/đ/g, "d").replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
}

function dateInput(value: string | null | undefined) {
  if (!value) return "";
  return value.replace(" ", "T").slice(0, 16);
}

export default function AdminPostsPage() {
  const [token, setToken] = useState("");
  const [posts, setPosts] = useState<AdminPost[]>([]);
  const [editing, setEditing] = useState<AdminPost | "new" | null>(null);
  const [selected, setSelected] = useState<AdminPost | null>(null);
  const [draft, setDraft] = useState<Draft>(blankDraft);
  const [thumbnail, setThumbnail] = useState<File | null>(null);
  const [queryInput, setQueryInput] = useState("");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [preview, setPreview] = useState(false);

  useEffect(() => { void Promise.resolve().then(() => setToken(getAdminToken() ?? "")); }, []);
  const load = useCallback(async () => {
    if (!token) { setLoading(false); setError("Vui lòng đăng nhập bằng tài khoản quản trị."); return; }
    setLoading(true); setError("");
    try {
      const result = await listAdminPosts(token, { page, per_page: 15, search: search || undefined, status });
      setPosts(result.data);
      setPagination({ current_page: result.current_page, last_page: result.last_page, per_page: result.per_page, total: result.total });
    } catch (cause) { setError(getApiErrorMessage(cause, "Không thể tải danh sách bài viết.")); }
    finally { setLoading(false); }
  }, [page, search, status, token]);
  useEffect(() => { void Promise.resolve().then(() => load()); }, [load]);

  function startCreate() {
    setDraft(blankDraft); setThumbnail(null); setPreview(false); setError(""); setEditing("new");
  }

  async function startEdit(post: AdminPost) {
    if (!token || busy) return;
    setBusy(true); setError("");
    try {
      const fullPost = await getAdminPost(token, post.slug);
      setDraft({ title: fullPost.title, slug: fullPost.slug, excerpt: fullPost.excerpt ?? "", content: fullPost.content, status: fullPost.status, published_at: dateInput(fullPost.published_at) });
      setThumbnail(null); setPreview(false); setEditing(fullPost);
    } catch (cause) { setError(getApiErrorMessage(cause, "Không thể mở bài viết.")); }
    finally { setBusy(false); }
  }

  async function savePost(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!editing || busy) return;
    setBusy(true); setError(""); setNotice("");
    const payload: PostPayload = { ...draft, thumbnail };
    try {
      if (editing === "new") await createAdminPost(token, payload);
      else await updateAdminPost(token, editing.slug, payload);
      setNotice(editing === "new" ? "Đã tạo bài viết." : "Đã cập nhật bài viết.");
      setEditing(null); setThumbnail(null); setPage(1); await load();
    } catch (cause) { setError(getApiErrorMessage(cause, "Không thể lưu bài viết.")); }
    finally { setBusy(false); }
  }

  async function removePost(post: AdminPost) {
    if (!window.confirm(`Chuyển bài viết “${post.title}” vào thùng rác?`)) return;
    setBusy(true); setError(""); setNotice("");
    try {
      await deleteAdminPost(token, post.slug);
      setNotice("Đã chuyển bài viết vào thùng rác.");
      if (posts.length === 1 && page > 1) setPage((current) => current - 1);
      else await load();
    } catch (cause) { setError(getApiErrorMessage(cause, "Không thể xóa bài viết.")); }
    finally { setBusy(false); }
  }

  function submitSearch(event: FormEvent) { event.preventDefault(); setPage(1); setSearch(queryInput.trim()); }
  const pageNumbers = Array.from({ length: Math.min(5, pagination.last_page) }, (_, index) => Math.max(1, Math.min(pagination.current_page - 2, pagination.last_page - 4)) + index);

  return <section className="space-y-5">
    <header className="flex flex-wrap items-end justify-between gap-3"><div><h1 className="text-2xl font-semibold">Quản lý bài viết</h1><p className="mt-1 text-sm text-zinc-500">Soạn nội dung văn bản, lưu bản nháp hoặc xuất bản theo lịch.</p></div><div className="flex gap-2"><Link href="/admin/trash?type=post" className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">Thùng rác bài viết</Link><button type="button" onClick={startCreate} className="rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white">+ Tạo bài viết</button></div></header>
    {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
    {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
    <div className="flex flex-wrap items-end gap-3"><form onSubmit={submitSearch} className="flex min-w-64 flex-1 gap-2"><label className="flex-1 text-sm font-medium">Tìm theo tiêu đề hoặc slug<input value={queryInput} onChange={(event) => setQueryInput(event.target.value)} maxLength={255} placeholder="Nhập tiêu đề hoặc slug" className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-2.5 font-normal" /></label><button disabled={loading} className="self-end rounded-xl border px-4 py-2.5 disabled:opacity-50">Tìm</button></form><label className="text-sm font-medium">Trạng thái<select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} className="mt-2 block rounded-xl border border-zinc-300 bg-white px-3 py-2.5 font-normal"><option value="all">Tất cả</option><option value="draft">Bản nháp</option><option value="published">Đã xuất bản</option></select></label></div>
    <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white"><table className="min-w-full text-left text-sm"><thead className="bg-stone-100 text-xs uppercase text-zinc-500"><tr><th className="px-4 py-3">Ảnh</th><th className="px-4 py-3">Tiêu đề / slug</th><th className="px-4 py-3">Tác giả</th><th className="px-4 py-3">Trạng thái</th><th className="px-4 py-3">Ngày tạo / xuất bản</th><th className="px-4 py-3">Thao tác</th></tr></thead><tbody className="divide-y divide-zinc-100">{posts.map((post) => <tr key={post.id}><td className="px-4 py-3">{post.thumbnail ? <Image src={post.thumbnail} alt="" width={56} height={56} unoptimized className="h-14 w-14 rounded-lg object-cover" /> : <span className="text-zinc-400">Chưa có ảnh</span>}</td><td className="max-w-72 px-4 py-3"><button type="button" className="block text-left font-medium underline" onClick={() => { setSelected(post); void getAdminPost(token, post.slug).then(setSelected).catch((cause) => setError(getApiErrorMessage(cause, "Không thể tải chi tiết bài viết."))); }}>{post.title}</button><span className="break-all text-xs text-zinc-500">{post.slug}</span></td><td className="px-4 py-3">{post.author?.name ?? "Không rõ"}</td><td className="px-4 py-3">{post.status === "published" ? "Đã xuất bản" : "Bản nháp"}</td><td className="whitespace-nowrap px-4 py-3"><div>{new Date(post.created_at.replace(" ", "T") + "Z").toLocaleDateString("vi-VN")}</div><div className="text-xs text-zinc-500">{post.published_at ? new Date(post.published_at.replace(" ", "T") + "Z").toLocaleString("vi-VN") : "Chưa xuất bản"}</div></td><td className="whitespace-nowrap px-4 py-3"><button type="button" disabled={busy} onClick={() => void startEdit(post)} className="mr-3 underline disabled:opacity-50">Sửa</button><button type="button" disabled={busy} onClick={() => void removePost(post)} className="text-red-700 underline disabled:opacity-50">Xóa</button></td></tr>)}{loading && <tr><td colSpan={6} className="px-5 py-12 text-center text-zinc-500">Đang tải bài viết…</td></tr>}{!loading && posts.length === 0 && <tr><td colSpan={6} className="px-5 py-12 text-center text-zinc-500">{search ? "Không tìm thấy bài viết phù hợp." : "Chưa có bài viết."}</td></tr>}</tbody></table></div>
    <footer className="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-600"><span>{pagination.total} bài viết · Trang {pagination.current_page} / {pagination.last_page}</span><nav aria-label="Phân trang" className="flex gap-1"><button type="button" disabled={page <= 1 || loading} onClick={() => setPage((current) => current - 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">← Trước</button>{pageNumbers.map((number) => <button type="button" key={number} aria-current={number === page ? "page" : undefined} onClick={() => setPage(number)} className={`rounded-lg border px-3 py-2 ${number === page ? "bg-zinc-900 text-white" : ""}`}>{number}</button>)}<button type="button" disabled={page >= pagination.last_page || loading} onClick={() => setPage((current) => current + 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">Sau →</button></nav></footer>
    {editing && <div role="presentation" className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5" onMouseDown={(event) => { if (event.target === event.currentTarget && !busy) setEditing(null); }}><section role="dialog" aria-modal="true" aria-labelledby="post-form-title" className="max-h-[94vh] w-full max-w-3xl overflow-y-auto rounded-t-2xl bg-white p-5 sm:rounded-2xl sm:p-7"><div className="flex items-start justify-between"><div><h2 id="post-form-title" className="text-xl font-semibold">{editing === "new" ? "Tạo bài viết" : "Chỉnh sửa bài viết"}</h2><p className="mt-1 text-sm text-zinc-500">Nội dung lưu dạng văn bản thuần, không chấp nhận HTML.</p></div><button type="button" disabled={busy} onClick={() => setEditing(null)} className="underline">Đóng</button></div><form onSubmit={(event) => void savePost(event)} className="mt-5 space-y-4">
      <div className="grid gap-4 sm:grid-cols-2"><label className="text-sm font-medium">Tiêu đề<input required maxLength={255} value={draft.title} onChange={(event) => setDraft((current) => ({ ...current, title: event.target.value, ...(editing === "new" ? { slug: slugify(event.target.value) } : {}) }))} className="mt-2 w-full rounded-xl border px-3 py-2.5 font-normal" /></label><label className="text-sm font-medium">Slug<input required pattern="[a-z0-9]+(-[a-z0-9]+)*" maxLength={255} value={draft.slug} onChange={(event) => setDraft((current) => ({ ...current, slug: event.target.value }))} className="mt-2 w-full rounded-xl border px-3 py-2.5 font-normal" /></label></div>
      <label className="block text-sm font-medium">Mô tả ngắn<textarea maxLength={1000} rows={2} value={draft.excerpt} onChange={(event) => setDraft((current) => ({ ...current, excerpt: event.target.value }))} className="mt-2 w-full rounded-xl border px-3 py-2.5 font-normal" /></label>
      <label className="block text-sm font-medium">Nội dung<textarea required maxLength={1000000} rows={12} value={draft.content} onChange={(event) => setDraft((current) => ({ ...current, content: event.target.value }))} className="mt-2 w-full rounded-xl border px-3 py-2.5 font-mono text-sm font-normal" /></label>
      <div className="flex items-center justify-between gap-3"><span className="text-sm font-medium">Xem trước nội dung</span><button type="button" onClick={() => setPreview((value) => !value)} className="text-sm underline">{preview ? "Quay lại chỉnh sửa" : "Xem trước"}</button></div>{preview && <div className="max-h-60 overflow-y-auto rounded-xl border bg-stone-50 p-4 text-sm leading-7 whitespace-pre-wrap">{draft.content || "Nội dung xem trước sẽ xuất hiện ở đây."}</div>}
      <div className="grid gap-4 sm:grid-cols-2"><label className="text-sm font-medium">Trạng thái<select value={draft.status} onChange={(event) => setDraft((current) => ({ ...current, status: event.target.value as Draft["status"] }))} className="mt-2 block w-full rounded-xl border bg-white px-3 py-2.5 font-normal"><option value="draft">Bản nháp</option><option value="published">Đã xuất bản</option></select></label><label className="text-sm font-medium">Thời điểm xuất bản<input type="datetime-local" value={draft.published_at} onChange={(event) => setDraft((current) => ({ ...current, published_at: event.target.value }))} className="mt-2 block w-full rounded-xl border px-3 py-2.5 font-normal" /><span className="mt-1 block text-xs text-zinc-500">Để trống sẽ dùng thời điểm hiện tại khi xuất bản.</span></label></div>
      <label className="block text-sm font-medium">Ảnh đại diện (JPG, PNG hoặc WebP, tối đa 5 MB)<input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => setThumbnail(event.target.files?.[0] ?? null)} className="mt-2 block w-full rounded-xl border px-3 py-2.5 font-normal" />{thumbnail && <span className="mt-1 block text-xs text-zinc-500">Đã chọn: {thumbnail.name}</span>}{editing !== "new" && editing.thumbnail && !thumbnail && <Image src={editing.thumbnail} alt="Ảnh hiện tại" width={80} height={80} unoptimized className="mt-2 h-20 w-20 rounded-lg object-cover" />}</label>
      <div className="flex justify-end gap-3 border-t pt-4"><button type="button" disabled={busy} onClick={() => setEditing(null)} className="rounded-xl border px-4 py-2">Hủy</button><button disabled={busy} className="rounded-xl bg-zinc-900 px-4 py-2 text-white disabled:opacity-50">{busy ? "Đang lưu…" : "Lưu bài viết"}</button></div></form></section></div>}
    {selected && <div role="presentation" className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5" onMouseDown={(event) => { if (event.target === event.currentTarget) setSelected(null); }}><section role="dialog" aria-modal="true" aria-labelledby="post-detail-title" className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-t-2xl bg-white p-6 sm:rounded-2xl"><div className="flex justify-between gap-3"><div><h2 id="post-detail-title" className="text-xl font-semibold">{selected.title}</h2><p className="text-sm text-zinc-500">/{selected.slug} · {selected.author?.name ?? "Không rõ tác giả"}</p></div><button type="button" onClick={() => setSelected(null)} className="underline">Đóng</button></div>{selected.thumbnail && <Image src={selected.thumbnail} alt="" width={900} height={400} unoptimized className="mt-4 max-h-72 w-full rounded-xl object-cover" />}<p className="mt-4 text-sm font-medium">{selected.excerpt}</p><article className="mt-4 whitespace-pre-wrap text-sm leading-7 text-zinc-700">{selected.content}</article></section></div>}
  </section>;
}
