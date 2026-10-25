"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import type { FormEvent } from "react";
import Link from "next/link";
import { getAdminToken } from "@/lib/admin-auth";
import { getApiErrorMessage } from "@/lib/api/client";
import type { AdminListParams, AdminPage } from "@/lib/api/client";

export type AdminRecord = {
  id: number;
  [key: string]: string | number | null | undefined;
};
export type AdminPayload = Record<string, string | number | null>;
export type AdminField = {
  name: string;
  label: string;
  required?: boolean;
  type?: "text" | "number" | "textarea" | "select";
  options?: { value: string; label: string }[];
};
export type AdminColumn = { key: string; label: string; kind?: "status" | "price" | "image" };
export type AdminResource = {
  list: (token: string, params: AdminListParams) => Promise<AdminPage<AdminRecord>>;
  create: (token: string, payload: AdminPayload) => Promise<AdminRecord>;
  update: (token: string, record: AdminRecord, payload: AdminPayload) => Promise<AdminRecord>;
  remove: (token: string, record: AdminRecord) => Promise<void>;
  loadFieldOptions?: (token: string) => Promise<Record<string, AdminField["options"]>>;
};

export function AdminResourceManager({
  title, description, resource, columns, fields, emptyMessage, trashHref,
}: {
  title: string;
  description: string;
  resource: AdminResource;
  columns: AdminColumn[];
  fields: AdminField[];
  emptyMessage: string;
  trashHref: string;
}) {
  const [rows, setRows] = useState<AdminRecord[]>([]);
  const [options, setOptions] = useState<Record<string, AdminField["options"]>>({});
  const [token, setToken] = useState("");
  const [queryInput, setQueryInput] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [refreshKey, setRefreshKey] = useState(0);
  const [editing, setEditing] = useState<AdminRecord | null>(null);
  const [formError, setFormError] = useState("");
  const [notice, setNotice] = useState("");
  const [loadError, setLoadError] = useState("");
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const { list, create, update, remove, loadFieldOptions } = resource;

  const refresh = useCallback(async (activeToken: string) => {
    setLoading(true);
    setLoadError("");
    try {
      const result = await list(activeToken, { page, per_page: 15, search: search || undefined });
      setRows(result.data);
      setPagination({
        current_page: result.current_page,
        last_page: result.last_page,
        per_page: result.per_page,
        total: result.total,
      });
    } catch (error) {
      setLoadError(getApiErrorMessage(error, "Không thể tải dữ liệu."));
    } finally {
      setLoading(false);
    }
  }, [list, page, search]);

  useEffect(() => {
    void Promise.resolve().then(() => {
      const activeToken = getAdminToken() ?? "";
      setToken(activeToken);
      if (!activeToken) setLoadError("Vui lòng đăng nhập bằng tài khoản quản trị để sử dụng chức năng này.");
    });
  }, []);

  useEffect(() => {
    void Promise.resolve().then(() => {
      if (token) return refresh(token);
      setLoading(false);
    });
  }, [token, refresh, refreshKey]);

  useEffect(() => {
    if (!token || !loadFieldOptions) return;
    void loadFieldOptions(token)
      .then(setOptions)
      .catch((error: unknown) => setLoadError(getApiErrorMessage(error, "Không thể tải lựa chọn biểu mẫu.")));
  }, [token, loadFieldOptions]);

  const pageNumbers = useMemo(() => {
    const start = Math.max(1, Math.min(pagination.current_page - 1, pagination.last_page - 3));
    const end = Math.min(pagination.last_page, start + 3);
    return Array.from({ length: Math.max(0, end - start + 1) }, (_, index) => start + index);
  }, [pagination.current_page, pagination.last_page]);

  function submitSearch(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPage(1);
    setSearch(queryInput.trim());
  }

  function startCreate() {
    setEditing({ id: 0 });
    setFormError("");
    setNotice("");
  }

  function startEdit(row: AdminRecord) {
    setEditing({ ...row });
    setFormError("");
    setNotice("");
  }

  function closeForm() {
    if (!busy) {
      setEditing(null);
      setFormError("");
    }
  }

  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!editing || !token || busy) return;
    const form = new FormData(event.currentTarget);
    const payload: AdminPayload = {};
    for (const field of fields) {
      const value = String(form.get(field.name) ?? "").trim();
      if (field.required && !value) {
        setFormError(`Vui lòng nhập ${field.label.toLocaleLowerCase("vi")}.`);
        return;
      }
      payload[field.name] = field.type === "number" ? (value ? Number(value) : null) : (value || null);
    }

    const creating = editing.id === 0;
    setBusy(true);
    setFormError("");
    setNotice("");
    try {
      if (creating) await create(token, payload);
      else await update(token, editing, payload);
      setEditing(null);
      setNotice(creating ? "Đã tạo bản ghi thành công." : "Đã cập nhật bản ghi thành công.");
      if (creating) {
        setQueryInput("");
        setSearch("");
        setPage(1);
      }
      setRefreshKey((value) => value + 1);
    } catch (error) {
      setFormError(getApiErrorMessage(error, "Không thể lưu dữ liệu."));
    } finally {
      setBusy(false);
    }
  }

  async function deleteRecord(row: AdminRecord) {
    if (!token || busy || !window.confirm(`Bạn có chắc muốn xóa “${String(row.name ?? `#${row.id}`)}” không?`)) return;
    setBusy(true);
    setNotice("");
    setLoadError("");
    try {
      await remove(token, row);
      setNotice("Đã chuyển bản ghi vào thùng rác.");
      if (rows.length === 1 && page > 1) setPage((value) => value - 1);
      else setRefreshKey((value) => value + 1);
    } catch (error) {
      setLoadError(getApiErrorMessage(error, "Không thể xóa dữ liệu."));
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold">{title}</h1>
          <p className="mt-1 text-sm text-zinc-500">{description}</p>
        </div>
        <div className="flex gap-2">
          <Link href={trashHref} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-800 hover:bg-stone-100">Thùng rác</Link>
          <button type="button" onClick={startCreate} disabled={!token || loading || busy} className="rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-50">+ Thêm mới</button>
        </div>
      </div>

      {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
      {loadError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{loadError}{!token && <> <Link href="/admin/login" className="font-semibold underline">Đăng nhập</Link></>}</div>}

      <form onSubmit={submitSearch} className="flex max-w-xl gap-2">
        <label className="flex-1 text-sm font-medium text-zinc-700">Tìm kiếm
          <input value={queryInput} onChange={(event) => setQueryInput(event.target.value)} placeholder={`Tìm trong ${title.toLocaleLowerCase("vi")}`} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 font-normal outline-none focus:border-zinc-600" />
        </label>
        <button type="submit" disabled={loading} className="self-end rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium hover:bg-stone-100 disabled:opacity-50">Tìm</button>
      </form>

      <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-stone-100 text-xs uppercase tracking-wide text-zinc-500">
            <tr><th className="px-4 py-3 font-medium">STT</th>{columns.map((column) => <th key={column.key} className="whitespace-nowrap px-4 py-3 font-medium">{column.label}</th>)}<th className="px-4 py-3 font-medium">Thao tác</th></tr>
          </thead>
          <tbody className="divide-y divide-zinc-100">
            {rows.map((row, index) => <tr key={row.id}>
              <td className="px-4 py-3 text-zinc-500">{(pagination.current_page - 1) * pagination.per_page + index + 1}</td>
              {columns.map((column) => {
                const value = row[column.key];
                return <td key={column.key} className="max-w-64 px-4 py-3 text-zinc-700">
                  {column.kind === "image" ? (value ? <img src={String(value)} alt="" className="h-12 w-12 rounded-lg object-cover" /> : "Chưa có ảnh")
                    : column.kind === "status" ? <span className="rounded-full bg-zinc-100 px-2.5 py-1 text-xs">{value === "active" ? "Đang hoạt động" : value === "inactive" ? "Ngừng hoạt động" : String(value ?? "Chưa có")}</span>
                      : column.kind === "price" ? <span>{String(value ?? "Chưa có")}</span>
                        : <span className="break-words">{String(value ?? "—")}</span>}
                </td>;
              })}
              <td className="whitespace-nowrap px-4 py-3">
                <button type="button" disabled={busy} onClick={() => startEdit(row)} className="mr-3 text-sm font-medium underline disabled:opacity-50">Sửa</button>
                <button type="button" disabled={busy} onClick={() => void deleteRecord(row)} className="text-sm font-medium text-red-700 underline disabled:opacity-50">Xóa</button>
              </td>
            </tr>)}
            {loading && <tr><td colSpan={columns.length + 2} className="px-5 py-12 text-center text-zinc-500">Đang tải dữ liệu…</td></tr>}
            {!loading && rows.length === 0 && <tr><td colSpan={columns.length + 2} className="px-5 py-12 text-center text-zinc-500">{search ? "Không tìm thấy kết quả phù hợp." : emptyMessage}</td></tr>}
          </tbody>
        </table>
      </div>

      <footer className="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-600">
        <span>{pagination.total} bản ghi · Trang {pagination.current_page} / {pagination.last_page}</span>
        <nav className="flex items-center gap-1" aria-label="Phân trang">
          <button type="button" disabled={page <= 1 || loading} onClick={() => setPage((value) => value - 1)} className="rounded-lg border border-zinc-300 px-3 py-2 disabled:opacity-40">← Trước</button>
          {pageNumbers.map((number) => <button key={number} type="button" aria-current={number === page ? "page" : undefined} disabled={loading} onClick={() => setPage(number)} className={`min-w-9 rounded-lg border px-3 py-2 ${number === page ? "border-zinc-900 bg-zinc-900 text-white" : "border-zinc-300"}`}>{number}</button>)}
          <button type="button" disabled={page >= pagination.last_page || loading} onClick={() => setPage((value) => value + 1)} className="rounded-lg border border-zinc-300 px-3 py-2 disabled:opacity-40">Sau →</button>
        </nav>
      </footer>

      {editing && <div className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) closeForm(); }}>
        <section role="dialog" aria-modal="true" aria-labelledby="admin-record-form-title" className="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl sm:p-7">
          <div className="flex items-start justify-between gap-4"><div><h2 id="admin-record-form-title" className="text-xl font-semibold">{editing.id === 0 ? "Thêm bản ghi" : "Sửa bản ghi"}</h2><p className="mt-1 text-sm text-zinc-500">Dữ liệu được lưu qua API Laravel.</p></div><button type="button" onClick={closeForm} disabled={busy} aria-label="Đóng biểu mẫu" className="rounded-lg px-3 py-1 text-zinc-500 hover:bg-stone-100">Đóng</button></div>
          <form key={editing.id || "new"} onSubmit={(event) => void save(event)} className="mt-5 space-y-4">
            {fields.map((field) => {
              const fieldOptions = options[field.name] ?? field.options;
              return <label key={field.name} className="block text-sm font-medium text-zinc-700">{field.label}
                {field.type === "textarea" ? <textarea name={field.name} required={field.required} defaultValue={String(editing[field.name] ?? "")} rows={3} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 font-normal" />
                  : field.type === "select" ? <select name={field.name} required={field.required} defaultValue={String(editing[field.name] ?? "")} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 font-normal"><option value="">Chọn</option>{fieldOptions?.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select>
                    : <input name={field.name} type={field.type === "number" ? "number" : "text"} min={field.type === "number" ? "0" : undefined} step={field.type === "number" ? "0.01" : undefined} required={field.required} defaultValue={String(editing[field.name] ?? "")} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 font-normal" />}
              </label>;
            })}
            {formError && <p role="alert" className="text-sm text-red-700">{formError}</p>}
            <div className="flex justify-end gap-3 border-t border-zinc-100 pt-4"><button type="button" disabled={busy} onClick={closeForm} className="rounded-xl border border-zinc-300 px-4 py-2 text-sm">Hủy</button><button type="submit" disabled={busy} className="rounded-xl bg-zinc-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">{busy ? "Đang lưu…" : "Lưu"}</button></div>
          </form>
        </section>
      </div>}
    </section>
  );
}
