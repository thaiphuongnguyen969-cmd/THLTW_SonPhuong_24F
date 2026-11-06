"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import type { FormEvent } from "react";
import { useParams } from "next/navigation";
import { getAdminToken } from "@/lib/admin-auth";
import { getApiErrorMessage } from "@/lib/api/client";
import {
  createAdminProductVariant,
  getAdminVariantOptions,
  listAdminProductVariants,
  updateAdminProductVariant,
  type AdminProductVariant,
  type VariantOptions,
} from "@/lib/api/admin-product-variants";
import { formatPriceValue } from "@/lib/format-price-value";

function emptyVariant(): AdminProductVariant {
  return {
    id: 0,
    size_id: 0,
    color_id: 0,
    sku: "",
    price: null,
    sale_price: null,
    original_price: "",
    effective_price: "",
    is_promotion: false,
    discount_percentage: 0,
    stock: 0,
    status: "active",
    size: { id: 0, name: "" },
    color: { id: 0, name: "" },
  };
}

export default function AdminProductVariantsPage() {
  const params = useParams<{ slug: string }>();
  const productSlug = params.slug;
  const [variants, setVariants] = useState<AdminProductVariant[]>([]);
  const [options, setOptions] = useState<VariantOptions>({ sizes: [], colors: [] });
  const [editing, setEditing] = useState<AdminProductVariant | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [refreshKey, setRefreshKey] = useState(0);

  const load = useCallback(async () => {
    await Promise.resolve();
    setLoading(true);
    setError("");
    const activeToken = getAdminToken() ?? "";
    if (!activeToken) {
      setError("Vui lòng đăng nhập bằng tài khoản quản trị.");
      setLoading(false);
      return;
    }
    try {
      const [result, choices] = await Promise.all([
        listAdminProductVariants(activeToken, productSlug, { page: 1, per_page: 100 }),
        getAdminVariantOptions(activeToken),
      ]);
      setVariants(result.data);
      setOptions(choices);
    } catch (cause) {
      setError(getApiErrorMessage(cause, "Không thể tải biến thể sản phẩm."));
    } finally {
      setLoading(false);
    }
  }, [productSlug]);

  useEffect(() => {
    const timer = window.setTimeout(() => void load(), 0);
    return () => window.clearTimeout(timer);
  }, [load, refreshKey]);

  function startCreate() {
    setError("");
    setNotice("");
    setEditing(emptyVariant());
  }

  function startEdit(variant: AdminProductVariant) {
    setError("");
    setNotice("");
    setEditing({ ...variant });
  }

  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const token = getAdminToken() ?? "";
    if (!editing || busy) return;
    if (!token) {
      setError("Vui lòng đăng nhập bằng tài khoản quản trị.");
      return;
    }
    const form = new FormData(event.currentTarget);
    const rawPrice = String(form.get("price") ?? "").trim();
    const rawSalePrice = String(form.get("sale_price") ?? "").trim();
    const price = rawPrice ? Number(rawPrice) : null;
    const salePrice = rawSalePrice ? Number(rawSalePrice) : null;

    if (salePrice !== null && (!price || salePrice <= 0 || salePrice >= price)) {
      setError("Giá khuyến mãi phải lớn hơn 0 và thấp hơn giá gốc của biến thể.");
      return;
    }

    const payload = {
      sku: String(form.get("sku") ?? "").trim(),
      size_id: Number(form.get("size_id")),
      color_id: Number(form.get("color_id")),
      price,
      sale_price: salePrice,
      stock: Number(form.get("stock")),
      status: String(form.get("status")) as "active" | "inactive",
      ...(editing.id > 0 ? { expected_stock: editing.stock } : {}),
    };

    setBusy(true);
    setError("");
    setNotice("");
    try {
      if (editing.id === 0) {
        await createAdminProductVariant(token, productSlug, payload);
        setNotice("Đã tạo biến thể sản phẩm.");
      } else {
        await updateAdminProductVariant(token, productSlug, editing.id, payload);
        setNotice("Đã cập nhật biến thể sản phẩm.");
      }
      setEditing(null);
      setRefreshKey((value) => value + 1);
    } catch (cause) {
      setError(getApiErrorMessage(cause, "Không thể lưu biến thể sản phẩm."));
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="space-y-5">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <Link href="/admin/products" className="text-sm text-zinc-600 underline">← Quản lý sản phẩm</Link>
          <h1 className="mt-2 text-2xl font-semibold">Biến thể sản phẩm</h1>
          <p className="mt-1 text-sm text-zinc-500">Sản phẩm: {productSlug}</p>
        </div>
        <button type="button" onClick={startCreate} disabled={loading || busy || options.sizes.length === 0 || options.colors.length === 0} className="rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50">+ Thêm biến thể</button>
      </div>

      {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</p>}
      {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
      {!loading && options.sizes.length === 0 && <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Chưa có lựa chọn kích thước. Hãy cấu hình size trước khi tạo biến thể.</p>}
      {!loading && options.colors.length === 0 && <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Chưa có lựa chọn màu sắc. Hãy cấu hình color trước khi tạo biến thể.</p>}

      <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table className="min-w-full text-left text-sm">
          <thead className="bg-stone-100 text-xs uppercase tracking-wide text-zinc-500"><tr><th className="px-4 py-3">SKU</th><th className="px-4 py-3">Size / màu</th><th className="px-4 py-3">Giá gốc</th><th className="px-4 py-3">Giá hiệu lực</th><th className="px-4 py-3">Tồn kho</th><th className="px-4 py-3">Trạng thái</th><th className="px-4 py-3">Thao tác</th></tr></thead>
          <tbody className="divide-y divide-zinc-100">
            {loading && <tr><td colSpan={7} className="px-5 py-12 text-center text-zinc-500">Đang tải biến thể…</td></tr>}
            {!loading && variants.map((variant) => <tr key={variant.id}>
              <td className="px-4 py-3 font-medium">{variant.sku}</td>
              <td className="px-4 py-3">{variant.size.name} · {variant.color.name}</td>
              <td className="px-4 py-3">{variant.price ? formatPriceValue(variant.price) : "Theo giá sản phẩm"}</td>
              <td className="px-4 py-3">{formatPriceValue(variant.effective_price)}{variant.is_promotion && <span className="ml-2 text-xs font-medium text-red-700">Khuyến mãi {variant.discount_percentage}%</span>}</td>
              <td className="px-4 py-3">{variant.stock}</td>
              <td className="px-4 py-3">{variant.status === "active" ? "Đang hoạt động" : "Ngừng hoạt động"}</td>
              <td className="px-4 py-3"><button type="button" disabled={busy} onClick={() => startEdit(variant)} className="underline disabled:opacity-50">Sửa</button></td>
            </tr>)}
            {!loading && variants.length === 0 && <tr><td colSpan={7} className="px-5 py-12 text-center text-zinc-500">Sản phẩm chưa có biến thể.</td></tr>}
          </tbody>
        </table>
      </div>

      {editing && <div className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5">
        <section role="dialog" aria-modal="true" aria-labelledby="variant-form-title" className="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl sm:p-7">
          <div className="flex items-start justify-between gap-4"><div><h2 id="variant-form-title" className="text-xl font-semibold">{editing.id === 0 ? "Thêm biến thể" : "Sửa biến thể"}</h2><p className="mt-1 text-sm text-zinc-500">Giá gốc và giá khuyến mãi được kiểm tra bởi API.</p></div><button type="button" onClick={() => setEditing(null)} disabled={busy} className="rounded-lg px-3 py-1 text-zinc-500 hover:bg-stone-100">Đóng</button></div>
          <form key={editing.id || "new"} onSubmit={(event) => void save(event)} className="mt-5 space-y-4">
            <label className="block text-sm font-medium">SKU<input name="sku" required maxLength={100} defaultValue={editing.sku} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 font-normal" /></label>
            <label className="block text-sm font-medium">Kích thước<select name="size_id" required defaultValue={editing.size_id ? String(editing.size_id) : ""} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 font-normal"><option value="">Chọn kích thước</option>{options.sizes.map((size) => <option key={size.id} value={size.id}>{size.name}</option>)}</select></label>
            <label className="block text-sm font-medium">Màu sắc<select name="color_id" required defaultValue={editing.color_id ? String(editing.color_id) : ""} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 font-normal"><option value="">Chọn màu sắc</option>{options.colors.map((color) => <option key={color.id} value={color.id}>{color.name}</option>)}</select></label>
            <label className="block text-sm font-medium">Giá gốc của biến thể<input name="price" type="number" min="0" max="9999999999.99" step="0.01" defaultValue={editing.price ?? ""} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 font-normal" /><span className="mt-1 block text-xs font-normal text-zinc-500">Để trống để dùng giá sản phẩm.</span></label>
            <label className="block text-sm font-medium">Giá khuyến mãi<input name="sale_price" type="number" min="0.01" max="9999999999.99" step="0.01" defaultValue={editing.sale_price ?? ""} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 font-normal" /><span className="mt-1 block text-xs font-normal text-zinc-500">Để trống nếu biến thể không giảm giá.</span></label>
            <label className="block text-sm font-medium">Tồn kho<input name="stock" type="number" required min="0" max="4294967295" step="1" defaultValue={editing.stock} className="mt-2 w-full rounded-xl border border-zinc-300 px-3 py-2 font-normal" /></label>
            <label className="block text-sm font-medium">Trạng thái<select name="status" required defaultValue={editing.status} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 font-normal"><option value="active">Đang hoạt động</option><option value="inactive">Ngừng hoạt động</option></select></label>
            <div className="flex justify-end gap-3 border-t border-zinc-100 pt-4"><button type="button" disabled={busy} onClick={() => setEditing(null)} className="rounded-xl border border-zinc-300 px-4 py-2 text-sm">Hủy</button><button type="submit" disabled={busy} className="rounded-xl bg-zinc-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">{busy ? "Đang lưu…" : "Lưu biến thể"}</button></div>
          </form>
        </section>
      </div>}
    </section>
  );
}
