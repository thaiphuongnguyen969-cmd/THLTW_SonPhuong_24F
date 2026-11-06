"use client";

import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { useRouter } from "next/navigation";
import { ProductCard } from "@/components/shop/ProductCard";
import { getAllBrands } from "@/lib/api/brands";
import { getAllCategories } from "@/lib/api/categories";
import { getProducts } from "@/lib/api/products";
import { getApiErrorMessage } from "@/lib/api/client";
import type { Brand } from "@/types/brand";
import type { Category } from "@/types/category";
import type { Product } from "@/types/product";

type CatalogueFilters = {
  q: string;
  category: string;
  brand: string;
  min_price: string;
  max_price: string;
  sort: string;
};

const pageSize = 12;

export function ProductCatalogue({
  initialFilters,
}: {
  initialFilters: Partial<CatalogueFilters> & { page?: string };
}) {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [brands, setBrands] = useState<Brand[]>([]);
  const [optionsReady, setOptionsReady] = useState(false);
  const [optionsError, setOptionsError] = useState("");
  const [filters, setFilters] = useState<CatalogueFilters>({
    q: initialFilters.q ?? "",
    category: initialFilters.category ?? "",
    brand: initialFilters.brand ?? "",
    min_price: initialFilters.min_price ?? "",
    max_price: initialFilters.max_price ?? "",
    sort: initialFilters.sort ?? "featured",
  });
  const [applied, setApplied] = useState(filters);
  const startingPage = Number(initialFilters.page ?? "1");
  const [page, setPage] = useState(Number.isInteger(startingPage) && startingPage > 0 ? startingPage : 1);
  const [products, setProducts] = useState<Product[]>([]);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const hasActiveFilters = Boolean(
    filters.q.trim()
    || filters.category
    || filters.brand
    || filters.min_price
    || filters.max_price
    || filters.sort !== "featured",
  );

  useEffect(() => {
    let active = true;
    void Promise.resolve().then(async () => {
      try {
        const [categoryRows, brandRows] = await Promise.all([getAllCategories(), getAllBrands()]);
        if (!active) return;
        setCategories(categoryRows);
        setBrands(brandRows);
      } catch (cause) {
        if (active) setOptionsError(getApiErrorMessage(cause, "Không thể tải danh mục và thương hiệu."));
      } finally {
        if (active) setOptionsReady(true);
      }
    });
    return () => { active = false; };
  }, []);

  useEffect(() => {
    if (!optionsReady) return;
    let active = true;
    void Promise.resolve().then(async () => {
      setLoading(true);
      setError("");
      if (optionsError) {
        setError(optionsError);
        setProducts([]);
        setTotal(0);
        setLastPage(1);
        setLoading(false);
        return;
      }

      const category = applied.category ? categories.find((item) => item.slug === applied.category) : undefined;
      const brand = applied.brand ? brands.find((item) => item.slug === applied.brand) : undefined;
      if ((applied.category && !category) || (applied.brand && !brand)) {
        setProducts([]);
        setTotal(0);
        setLastPage(1);
        setLoading(false);
        return;
      }

      const minPrice = applied.min_price.trim();
      const maxPrice = applied.max_price.trim();
      if (minPrice && maxPrice && Number(minPrice) > Number(maxPrice)) {
        setError("Giá tối thiểu phải nhỏ hơn hoặc bằng giá tối đa.");
        setProducts([]);
        setTotal(0);
        setLastPage(1);
        setLoading(false);
        return;
      }

      const sortParams = applied.sort === "name"
        ? { sort_by: "name", sort_direction: "asc" }
        : applied.sort === "price-asc"
          ? { sort_by: "price", sort_direction: "asc" }
          : applied.sort === "price-desc"
            ? { sort_by: "price", sort_direction: "desc" }
            : { sort_by: "created_at", sort_direction: "desc" };

      try {
        const result = await getProducts({
          page,
          per_page: pageSize,
          search: applied.q.trim() || undefined,
          category_id: category?.id,
          brand_id: brand?.id,
          min_price: minPrice || undefined,
          max_price: maxPrice || undefined,
          ...sortParams,
        });
        if (!active) return;
        setProducts(result.data);
        setLastPage(result.meta.last_page);
        setTotal(result.meta.total);
      } catch (cause) {
        if (active) {
          setError(getApiErrorMessage(cause, "Không thể tải danh sách sản phẩm."));
          setProducts([]);
          setTotal(0);
          setLastPage(1);
        }
      } finally {
        if (active) setLoading(false);
      }
    });
    return () => { active = false; };
  }, [applied, page, categories, brands, optionsReady, optionsError]);

  function updateUrl(nextFilters: CatalogueFilters, nextPage: number) {
    const params = new URLSearchParams();
    if (nextFilters.q.trim()) params.set("q", nextFilters.q.trim());
    if (nextFilters.category) params.set("category", nextFilters.category);
    if (nextFilters.brand) params.set("brand", nextFilters.brand);
    if (nextFilters.min_price) params.set("min_price", nextFilters.min_price);
    if (nextFilters.max_price) params.set("max_price", nextFilters.max_price);
    if (nextFilters.sort !== "featured") params.set("sort", nextFilters.sort);
    if (nextPage > 1) params.set("page", String(nextPage));
    const query = params.toString();
    router.replace(query ? "/products?" + query : "/products", { scroll: false });
  }

  function applyFilters(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const next = { ...filters, q: filters.q.trim() };
    setFilters(next);
    setApplied(next);
    setPage(1);
    updateUrl(next, 1);
  }

  function goToPage(nextPage: number) {
    setPage(nextPage);
    updateUrl(applied, nextPage);
  }

  function resetFilters() {
    const cleared: CatalogueFilters = { q: "", category: "", brand: "", min_price: "", max_price: "", sort: "featured" };
    setFilters(cleared);
    setApplied(cleared);
    setPage(1);
    updateUrl(cleared, 1);
  }

  return (
    <main className="shop-catalog-page mx-auto min-h-[60vh] max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <div className="shop-catalog-heading mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p className="text-xs uppercase tracking-widest text-zinc-500">Cửa hàng</p><h1 className="mt-2 text-3xl font-semibold">Sản phẩm</h1></div>
        <p className="text-sm text-zinc-500">{total} sản phẩm</p>
      </div>

      <form onSubmit={applyFilters} data-has-filters={hasActiveFilters} className="shop-catalog-filters mb-8 grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-3">
        <label className="text-sm font-medium text-zinc-700">Tìm theo tên
          <input value={filters.q} onChange={(event) => setFilters({ ...filters, q: event.target.value })} maxLength={200} placeholder="Tên sản phẩm" className="shop-catalog-control mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal outline-none focus:border-zinc-500" />
        </label>
        <label id="categories" className="text-sm font-medium text-zinc-700">Danh mục
          <select value={filters.category} data-active={Boolean(filters.category)} onChange={(event) => setFilters({ ...filters, category: event.target.value })} className="shop-catalog-control mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal">
            <option value="">Tất cả danh mục</option>
            {categories.map((item) => <option key={item.id} value={item.slug}>{item.name}</option>)}
          </select>
        </label>
        <label id="brands" className="text-sm font-medium text-zinc-700">Thương hiệu
          <select value={filters.brand} data-active={Boolean(filters.brand)} onChange={(event) => setFilters({ ...filters, brand: event.target.value })} className="shop-catalog-control mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal">
            <option value="">Tất cả thương hiệu</option>
            {brands.map((item) => <option key={item.id} value={item.slug}>{item.name}</option>)}
          </select>
        </label>
        <label className="text-sm font-medium text-zinc-700">Giá tối thiểu
          <input type="number" min={0} value={filters.min_price} data-active={Boolean(filters.min_price)} onChange={(event) => setFilters({ ...filters, min_price: event.target.value })} className="shop-catalog-control mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal" />
        </label>
        <label className="text-sm font-medium text-zinc-700">Giá tối đa
          <input type="number" min={0} value={filters.max_price} data-active={Boolean(filters.max_price)} onChange={(event) => setFilters({ ...filters, max_price: event.target.value })} className="shop-catalog-control mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal" />
        </label>
        <label className="text-sm font-medium text-zinc-700">Sắp xếp
          <select value={filters.sort} data-active={filters.sort !== "featured"} onChange={(event) => setFilters({ ...filters, sort: event.target.value })} className="shop-catalog-control mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal">
            <option value="featured">Mới nhất trước</option>
            <option value="name">Tên sản phẩm</option>
            <option value="price-asc">Giá tăng dần</option>
            <option value="price-desc">Giá giảm dần</option>
          </select>
        </label>
        <div className="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-3">
          <button type="submit" className="shop-catalog-action rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white">Áp dụng</button>
          <button type="button" data-active={hasActiveFilters} onClick={resetFilters} className="shop-catalog-action rounded-full border border-zinc-300 bg-white px-5 py-3 text-sm font-medium">Xóa bộ lọc</button>
        </div>
      </form>

      {error && <p role="alert" className="shop-catalog-error mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}
      {loading ? (
        <p role="status" className="shop-catalog-loading rounded-2xl border border-zinc-200 bg-white px-6 py-14 text-center text-sm text-zinc-500">Đang tải sản phẩm…</p>
      ) : !error && products.length === 0 ? (
        <div className="shop-catalog-empty rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-14 text-center">
          <h2 className="text-lg font-semibold">Không tìm thấy sản phẩm phù hợp</h2>
          <p className="mt-2 text-sm text-zinc-500">Thử thay đổi từ khóa hoặc bộ lọc.</p>
        </div>
      ) : !error ? (
        <div className="shop-catalog-grid grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {products.map((product) => <ProductCard key={product.id} product={product} />)}
        </div>
      ) : null}

      <footer className="shop-catalog-pagination mt-8 flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-600">
        <span className="shop-catalog-page">Trang {page} / {lastPage}</span>
        <div className="flex gap-2">
          <button type="button" disabled={page <= 1 || loading || Boolean(error)} onClick={() => goToPage(page - 1)} className="shop-catalog-page-button rounded-lg border border-zinc-300 px-4 py-2 disabled:opacity-40">Trước</button>
          <button type="button" disabled={page >= lastPage || loading || Boolean(error)} onClick={() => goToPage(page + 1)} className="shop-catalog-page-button rounded-lg border border-zinc-300 px-4 py-2 disabled:opacity-40">Sau</button>
        </div>
      </footer>
    </main>
  );
}
