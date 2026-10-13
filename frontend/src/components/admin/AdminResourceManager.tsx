"use client";

import { useMemo, useState } from "react";
import type { FormEvent } from "react";
import { adminStatusLabels } from "@/lib/admin-demo-data";

export type AdminRecord = {
  id: number;
  [key: string]: string | number | null | undefined;
};

export type AdminField = {
  name: string;
  label: string;
  required?: boolean;
  type?: "text" | "number" | "textarea" | "select";
  options?: { value: string; label: string }[];
};

export type AdminColumn = {
  key: string;
  label: string;
  kind?: "status" | "price" | "image";
};

export function AdminResourceManager({
  title,
  description,
  initialRows,
  columns,
  fields,
  emptyMessage,
}: {
  title: string;
  description: string;
  initialRows: AdminRecord[];
  columns: AdminColumn[];
  fields: AdminField[];
  emptyMessage: string;
}) {
  const [rows, setRows] = useState(initialRows);
  const [query, setQuery] = useState("");
  const [editing, setEditing] = useState<AdminRecord | null>(null);
  const [formError, setFormError] = useState("");
  const [notice, setNotice] = useState("");

  const visibleRows = useMemo(() => rows.filter((row) =>
    Object.values(row).some((value) => String(value ?? "").toLocaleLowerCase("vi").includes(query.trim().toLocaleLowerCase("vi"))),
  ), [query, rows]);

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

  function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!editing) return;
    const data = new FormData(event.currentTarget);
    const next = { ...editing };
    for (const field of fields) {
      const value = String(data.get(field.name) ?? "").trim();
      if (field.required && !value) {
        setFormError(`Vui lòng nhập ${field.label.toLocaleLowerCase("vi")}.`);
        return;
      }
      next[field.name] = field.type === "number" && value ? Number(value) : value;
    }

    if (editing.id === 0) {
      const newId = Math.max(0, ...rows.map((row) => row.id)) + 1;
      setRows((current) => [...current, { ...next, id: newId }]);
      setNotice("Đã thêm bản ghi vào danh sách demo trong bộ nhớ trang.");
    } else {
      setRows((current) => current.map((row) => row.id === editing.id ? next : row));
      setNotice("Đã cập nhật bản ghi demo trong bộ nhớ trang.");
    }
    setEditing(null);
    setFormError("");
  }

  function remove(row: AdminRecord) {
    if (!window.confirm(`Xóa bản ghi demo “${String(row.name ?? row.title ?? `#${row.id}`)}” khỏi danh sách hiện tại?`)) return;
    setRows((current) => current.filter((item) => item.id !== row.id));
    setNotice("Đã xóa bản ghi demo khỏi danh sách trong bộ nhớ trang; không có dữ liệu server nào thay đổi.");
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div><h1 className="text-2xl font-semibold">{title}</h1><p className="mt-1 text-sm text-zinc-500">{description}</p></div>
        <button type="button" onClick={startCreate} className="rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-700">Thêm mới</button>
      </div>
      <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Dữ liệu mẫu. Thao tác chỉ thay đổi giao diện hiện tại, không lưu lên máy chủ.</p>
      {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}

      <label className="block max-w-md text-sm font-medium text-zinc-700">Tìm kiếm
        <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={`Tìm trong ${title.toLocaleLowerCase("vi")}`} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 font-normal outline-none focus:border-zinc-600" />
      </label>

      <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-stone-100 text-xs uppercase tracking-wide text-zinc-500"><tr>{columns.map((column) => <th key={column.key} className="whitespace-nowrap px-4 py-3 font-medium">{column.label}</th>)}<th className="px-4 py-3 font-medium">Thao tác</th></tr></thead>
          <tbody className="divide-y divide-zinc-100">
            {visibleRows.map((row) => (
              <tr key={row.id}>
                {columns.map((column) => {
                  const value = row[column.key];
                  return <td key={column.key} className="max-w-64 px-4 py-3 text-zinc-700">
                    {column.kind === "image" ? (value ? <img src={String(value)} alt="" className="h-12 w-12 rounded-lg object-cover" /> : <span className="text-xs text-zinc-400">Chưa có ảnh</span>)
                      : column.kind === "status" ? <span className="rounded-full bg-zinc-100 px-2.5 py-1 text-xs">{adminStatusLabels[String(value)] ?? String(value ?? "Chưa có")}</span>
                        : column.kind === "price" ? <span>{String(value ?? "Chưa có")} <span className="text-xs text-zinc-400">(đơn vị chưa xác minh)</span></span>
                          : <span className="break-words">{String(value ?? "—")}</span>}
                  </td>;
                })}
                <td className="whitespace-nowrap px-4 py-3"><button type="button" onClick={() => startEdit(row)} className="mr-3 text-sm font-medium underline">Sửa</button><button type="button" onClick={() => remove(row)} className="text-sm font-medium text-red-700 underline">Xóa</button></td>
              </tr>
            ))}
            {visibleRows.length === 0 && <tr><td colSpan={columns.length + 1} className="px-5 py-12 text-center text-zinc-500">{rows.length === 0 ? emptyMessage : "Không tìm thấy kết quả phù hợp."}</td></tr>}
          </tbody>
        </table>
      </div>

      {editing && (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) setEditing(null); }}>
          <section role="dialog" aria-modal="true" aria-labelledby="admin-record-form-title" className="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl sm:p-7">
            <div className="flex items-start justify-between gap-4"><div><h2 id="admin-record-form-title" className="text-xl font-semibold">{editing.id === 0 ? "Thêm bản ghi demo" : "Sửa bản ghi demo"}</h2><p className="mt-1 text-sm text-zinc-500">Các thay đổi chỉ tồn tại trong trạng thái giao diện hiện tại.</p></div><button type="button" onClick={() => setEditing(null)} aria-label="Đóng biểu mẫu" className="rounded-lg px-3 py-1 text-zinc-500 hover:bg-stone-100">Đóng</button></div>
            <form onSubmit={save} className="mt-5 space-y-4">
              {fields.map((field) => <label key={field.name} className="block text-sm font-medium text-zinc-700">{field.label}
                {field.type === "textarea" ? <textarea name={field.name} required={field.required} defaultValue={String(editing[field.name] ?? "")} rows={3} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 font-normal" />
                  : field.type === "select" ? <select name={field.name} required={field.required} defaultValue={String(editing[field.name] ?? "")} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 font-normal"><option value="">Chọn</option>{field.options?.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select>
                    : <input name={field.name} type={field.type === "number" ? "number" : "text"} min={field.type === "number" ? "0" : undefined} step={field.type === "number" ? "0.01" : undefined} required={field.required} defaultValue={String(editing[field.name] ?? "")} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 font-normal" />}
              </label>)}
              {formError && <p role="alert" className="text-sm text-red-700">{formError}</p>}
              <div className="flex justify-end gap-3 border-t border-zinc-100 pt-4"><button type="button" onClick={() => setEditing(null)} className="rounded-xl border border-zinc-300 px-4 py-2 text-sm">Hủy</button><button type="submit" className="rounded-xl bg-zinc-900 px-4 py-2 text-sm font-medium text-white">Lưu demo</button></div>
            </form>
          </section>
        </div>
      )}
    </section>
  );
}
