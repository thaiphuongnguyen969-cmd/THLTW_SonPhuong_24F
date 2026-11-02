"use client";

import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { getAdminToken } from "@/lib/admin-auth";
import { getApiErrorMessage } from "@/lib/api/client";
import {
  createAdminBanner,
  deleteAdminBanner,
  listAdminBanners,
  updateAdminBanner,
  type AdminBanner,
  type AdminBannerInput,
} from "@/lib/api/banners";

type BannerDraft = Omit<AdminBannerInput, "image">;

const emptyDraft: BannerDraft = {
  title: "",
  description: "",
  link_url: "",
  button_text: "",
  status: "active",
  sort_order: 0,
};
const inputClass = "mt-1 block w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm font-normal";

export default function AdminBannersPage() {
  const [token, setToken] = useState("");
  const [banners, setBanners] = useState<AdminBanner[]>([]);
  const [editing, setEditing] = useState<AdminBanner | "new" | null>(null);
  const [draft, setDraft] = useState<BannerDraft>(emptyDraft);
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState<"all" | "active" | "inactive">("all");
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [refreshKey, setRefreshKey] = useState(0);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => {
    void Promise.resolve().then(() => setToken(getAdminToken() ?? ""));
  }, []);

  useEffect(() => {
    return () => {
      if (previewUrl) URL.revokeObjectURL(previewUrl);
    };
  }, [previewUrl]);

  useEffect(() => {
    let active = true;
    void Promise.resolve().then(async () => {
      if (!token) {
        if (active) {
          setError("Vui lòng đăng nhập bằng tài khoản quản trị.");
          setLoading(false);
        }
        return;
      }

      setLoading(true);
      setError("");
      try {
        const result = await listAdminBanners(token, { page, per_page: 15, search: search || undefined, status });
        if (!active) return;
        setBanners(result.data);
        setPagination({ current_page: result.current_page, last_page: result.last_page, total: result.total });
      } catch (requestError) {
        if (active) setError(getApiErrorMessage(requestError, "Không thể tải danh sách banner."));
      } finally {
        if (active) setLoading(false);
      }
    });
    return () => { active = false; };
  }, [page, refreshKey, search, status, token]);

  function startCreate() {
    setDraft(emptyDraft);
    setImageFile(null);
    setPreviewUrl(null);
    setEditing("new");
    setError("");
    setNotice("");
  }

  function startEdit(banner: AdminBanner) {
    setDraft({
      title: banner.title,
      description: banner.description ?? "",
      link_url: banner.link_url ?? "",
      button_text: banner.button_text ?? "",
      status: banner.status,
      sort_order: banner.sort_order,
    });
    setImageFile(null);
    setPreviewUrl(null);
    setEditing(banner);
    setError("");
    setNotice("");
  }

  function closeForm() {
    if (busy) return;
    setEditing(null);
    setImageFile(null);
    setPreviewUrl(null);
    setError("");
  }

  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!token || !editing || busy) return;
    if (editing === "new" && !imageFile) {
      setError("Vui lòng chọn ảnh banner.");
      return;
    }

    setBusy(true);
    setError("");
    setNotice("");
    const payload: AdminBannerInput = {
      ...draft,
      title: draft.title.trim(),
      description: draft.description?.trim() || null,
      link_url: draft.link_url?.trim() || null,
      button_text: draft.button_text?.trim() || null,
      image: imageFile,
    };

    try {
      if (editing === "new") {
        await createAdminBanner(token, payload);
        setNotice("Đã tạo banner.");
        setPage(1);
      } else {
        await updateAdminBanner(token, editing.id, payload);
        setNotice("Đã cập nhật banner.");
      }
      setEditing(null);
      setImageFile(null);
      setPreviewUrl(null);
      setRefreshKey((current) => current + 1);
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, "Không thể lưu banner."));
    } finally {
      setBusy(false);
    }
  }

  async function toggleStatus(banner: AdminBanner) {
    if (!token || busyId !== null) return;
    setBusyId(banner.id);
    setError("");
    setNotice("");
    try {
      await updateAdminBanner(token, banner.id, { status: banner.status === "active" ? "inactive" : "active" });
      setNotice("Đã cập nhật trạng thái banner.");
      setRefreshKey((current) => current + 1);
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, "Không thể cập nhật trạng thái banner."));
    } finally {
      setBusyId(null);
    }
  }

  async function remove(banner: AdminBanner) {
    if (!window.confirm(`Chuyển banner “${banner.title}” vào thùng rác?`)) return;
    if (!token || busyId !== null) return;
    setBusyId(banner.id);
    setError("");
    setNotice("");
    try {
      await deleteAdminBanner(token, banner.id);
      setNotice("Đã chuyển banner vào thùng rác.");
      if (banners.length === 1 && page > 1) setPage((current) => current - 1);
      else setRefreshKey((current) => current + 1);
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, "Không thể xóa banner."));
    } finally {
      setBusyId(null);
    }
  }

  function applySearch(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPage(1);
    setSearch(searchInput.trim());
  }

  const pageNumbers = Array.from(
    { length: Math.min(5, pagination.last_page) },
    (_, index) => Math.max(1, Math.min(pagination.current_page - 2, pagination.last_page - 4)) + index,
  );
  const currentImage = editing && editing !== "new" ? editing.image : null;

  return (
    <section className="space-y-5">
      <header className="flex flex-wrap items-end justify-between gap-3">
        <div><h1 className="text-2xl font-semibold">Quản lý banner</h1><p className="mt-1 text-sm text-zinc-500">Tạo banner hiển thị trên trang chủ và sắp xếp thứ tự ưu tiên.</p></div>
        <a href="/admin/trash?type=banner" className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">Thùng rác banner</a>
      </header>
      {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
      {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}

      {!editing && <button type="button" disabled={!token || loading} onClick={startCreate} className="rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-50">+ Tạo banner</button>}

      {editing && (
        <form onSubmit={(event) => void save(event)} className="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-3">
          <h2 className="text-lg font-semibold sm:col-span-2 lg:col-span-3">{editing === "new" ? "Tạo banner" : "Chỉnh sửa banner"}</h2>
          <label className="text-sm font-medium">Tiêu đề
            <input required maxLength={255} value={draft.title} onChange={(event) => setDraft({ ...draft, title: event.target.value })} className={inputClass} />
          </label>
          <label className="text-sm font-medium">Nút kêu gọi hành động
            <input maxLength={100} value={draft.button_text ?? ""} onChange={(event) => setDraft({ ...draft, button_text: event.target.value })} placeholder="Ví dụ: Khám phá ngay" className={inputClass} />
          </label>
          <label className="text-sm font-medium">Liên kết (nội bộ hoặc HTTPS)
            <input maxLength={2048} value={draft.link_url ?? ""} onChange={(event) => setDraft({ ...draft, link_url: event.target.value })} placeholder="/products hoặc https://example.com" className={inputClass} />
          </label>
          <label className="text-sm font-medium sm:col-span-2 lg:col-span-3">Mô tả
            <textarea maxLength={2000} rows={3} value={draft.description ?? ""} onChange={(event) => setDraft({ ...draft, description: event.target.value })} className={inputClass} />
          </label>
          <label className="text-sm font-medium">Ảnh banner (JPG, PNG, WebP · tối đa 5 MB)
            <input type="file" accept="image/jpeg,image/png,image/webp" required={editing === "new"} onChange={(event) => {
              const file = event.target.files?.[0] ?? null;
              setImageFile(file);
              setPreviewUrl(file ? URL.createObjectURL(file) : null);
            }} className={inputClass} />
          </label>
          <label className="text-sm font-medium">Trạng thái
            <select value={draft.status} onChange={(event) => setDraft({ ...draft, status: event.target.value as BannerDraft["status"] })} className={inputClass}>
              <option value="active">Đang hiển thị</option><option value="inactive">Đang ẩn</option>
            </select>
          </label>
          <label className="text-sm font-medium">Thứ tự hiển thị
            <input type="number" min={0} max={2147483647} required value={draft.sort_order} onChange={(event) => setDraft({ ...draft, sort_order: Number(event.target.value) })} className={inputClass} />
          </label>
          {(previewUrl || currentImage) && (
            <div className="sm:col-span-2 lg:col-span-3">
              <p className="mb-2 text-sm font-medium">Xem trước ảnh</p>
              <div role="img" aria-label={draft.title || "Ảnh banner"} className="h-60 w-full rounded-xl border border-zinc-200 bg-contain bg-left bg-no-repeat" style={{ backgroundImage: `url("${previewUrl ?? currentImage}")` }} />
            </div>
          )}
          <div className="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
            <button type="submit" disabled={busy} className="rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50">{busy ? "Đang lưu…" : editing === "new" ? "Tạo banner" : "Lưu thay đổi"}</button>
            <button type="button" disabled={busy} onClick={closeForm} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm disabled:opacity-50">Hủy</button>
          </div>
        </form>
      )}

      <div className="flex flex-wrap items-end gap-3">
        <form onSubmit={applySearch} className="flex min-w-64 flex-1 gap-2">
          <label className="flex-1 text-sm font-medium">Tìm theo tiêu đề<input value={searchInput} maxLength={100} onChange={(event) => setSearchInput(event.target.value)} placeholder="Nhập tiêu đề banner" className={inputClass} /></label>
          <button type="submit" disabled={loading} className="self-end rounded-xl border border-zinc-300 px-4 py-2.5 text-sm disabled:opacity-50">Tìm kiếm</button>
        </form>
        <label className="text-sm font-medium">Trạng thái
          <select value={status} onChange={(event) => { setStatus(event.target.value as typeof status); setPage(1); }} className={inputClass}>
            <option value="all">Tất cả</option><option value="active">Đang hiển thị</option><option value="inactive">Đang ẩn</option>
          </select>
        </label>
      </div>

      <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-stone-100 text-xs uppercase text-zinc-500"><tr><th className="px-4 py-3">Ảnh</th><th className="px-4 py-3">Tiêu đề</th><th className="px-4 py-3">Liên kết</th><th className="px-4 py-3">Thứ tự</th><th className="px-4 py-3">Trạng thái</th><th className="px-4 py-3">Ngày tạo</th><th className="px-4 py-3">Thao tác</th></tr></thead>
          <tbody className="divide-y divide-zinc-100">
            {banners.map((banner) => (
              <tr key={banner.id}>
                <td className="px-4 py-3">{banner.image ? <div aria-label={`Ảnh banner ${banner.title}`} role="img" className="h-14 w-24 rounded-lg bg-cover bg-center" style={{ backgroundImage: `url("${banner.image}")` }} /> : <span className="text-zinc-400">Chưa có ảnh</span>}</td>
                <td className="max-w-64 px-4 py-3"><span className="font-medium">{banner.title}</span>{banner.description && <span className="mt-1 block line-clamp-2 text-xs text-zinc-500">{banner.description}</span>}</td>
                <td className="max-w-48 truncate px-4 py-3 text-zinc-600">{banner.link_url || "—"}</td>
                <td className="px-4 py-3">{banner.sort_order}</td>
                <td className="px-4 py-3">{banner.status === "active" ? "Đang hiển thị" : "Đang ẩn"}</td>
                <td className="whitespace-nowrap px-4 py-3">{new Date(`${banner.created_at.replace(" ", "T")}Z`).toLocaleDateString("vi-VN")}</td>
                <td className="whitespace-nowrap px-4 py-3">
                  <button type="button" disabled={busy || busyId !== null} onClick={() => startEdit(banner)} className="mr-3 underline disabled:opacity-50">Sửa</button>
                  <button type="button" disabled={busy || busyId !== null} onClick={() => void toggleStatus(banner)} className="mr-3 underline disabled:opacity-50">{busyId === banner.id ? "Đang xử lý…" : banner.status === "active" ? "Ẩn" : "Hiện"}</button>
                  <button type="button" disabled={busy || busyId !== null} onClick={() => void remove(banner)} className="text-red-700 underline disabled:opacity-50">Xóa</button>
                </td>
              </tr>
            ))}
            {loading && <tr><td colSpan={7} className="px-5 py-12 text-center text-zinc-500">Đang tải banner…</td></tr>}
            {!loading && banners.length === 0 && <tr><td colSpan={7} className="px-5 py-12 text-center text-zinc-500">{search ? "Không tìm thấy banner phù hợp." : "Chưa có banner."}</td></tr>}
          </tbody>
        </table>
      </div>
      <footer className="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-600">
        <span>{pagination.total} banner · Trang {pagination.current_page} / {pagination.last_page}</span>
        <nav aria-label="Phân trang banner" className="flex gap-1">
          <button type="button" disabled={page <= 1 || loading} onClick={() => setPage((current) => current - 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">← Trước</button>
          {pageNumbers.map((number) => <button type="button" key={number} aria-current={number === page ? "page" : undefined} onClick={() => setPage(number)} className={`rounded-lg border px-3 py-2 ${number === page ? "bg-zinc-900 text-white" : ""}`}>{number}</button>)}
          <button type="button" disabled={page >= pagination.last_page || loading} onClick={() => setPage((current) => current + 1)} className="rounded-lg border px-3 py-2 disabled:opacity-40">Sau →</button>
        </nav>
      </footer>
    </section>
  );
}
