"use client";

import { useMemo, useState } from "react";
import { ProductCard } from "@/components/shop/ProductCard";
import { mockBrands, mockCategories } from "@/lib/mock-data";
import type { Product } from "@/types/product";

const categoryLabels: Record<string, string> = {
  "New Arrivals": "Hàng mới",
  Tailoring: "Đồ may đo",
  Accessories: "Phụ kiện",
  Essentials: "Đồ thiết yếu",
};

export function ProductCatalogue({
  products,
  initialFilters,
}: {
  products: Product[];
  initialFilters: { q?: string; category?: string; brand?: string };
}) {
  const [query, setQuery] = useState(initialFilters.q ?? "");
  const [category, setCategory] = useState(initialFilters.category ?? "");
  const [brand, setBrand] = useState(initialFilters.brand ?? "");
  const [sort, setSort] = useState("featured");

  const visibleProducts = useMemo(() => {
    const filtered = products.filter((product) =>
      product.status === "active"
      && product.name.toLocaleLowerCase("vi").includes(query.trim().toLocaleLowerCase("vi"))
      && (!category || product.category?.slug === category)
      && (!brand || product.brand?.slug === brand),
    );

    if (sort === "price-asc") filtered.sort((a, b) => Number(a.sale_price ?? a.price) - Number(b.sale_price ?? b.price));
    if (sort === "price-desc") filtered.sort((a, b) => Number(b.sale_price ?? b.price) - Number(a.sale_price ?? a.price));
    if (sort === "name") filtered.sort((a, b) => a.name.localeCompare(b.name, "vi"));
    return filtered;
  }, [brand, category, products, query, sort]);

  return (
    <main className="mx-auto min-h-[60vh] max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p className="text-xs uppercase tracking-widest text-zinc-500">Cửa hàng</p><h1 className="mt-2 text-3xl font-semibold">Sản phẩm</h1></div>
        <p className="text-sm text-zinc-500">{visibleProducts.length} sản phẩm trong dữ liệu mẫu</p>
      </div>

      <section aria-label="Tìm kiếm và lọc sản phẩm" className="mb-8 grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
        <label className="text-sm font-medium text-zinc-700">Tìm kiếm
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Tên sản phẩm" className="mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal outline-none focus:border-zinc-500" />
        </label>
        <label id="categories" className="text-sm font-medium text-zinc-700">Danh mục
          <select value={category} onChange={(event) => setCategory(event.target.value)} className="mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal">
            <option value="">Tất cả danh mục</option>
            {mockCategories.map((item) => <option key={item.id} value={item.slug}>{categoryLabels[item.name] ?? item.name}</option>)}
          </select>
        </label>
        <label id="brands" className="text-sm font-medium text-zinc-700">Thương hiệu
          <select value={brand} onChange={(event) => setBrand(event.target.value)} className="mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal">
            <option value="">Tất cả thương hiệu</option>
            {mockBrands.map((item) => <option key={item.id} value={item.slug}>{item.name}</option>)}
          </select>
        </label>
        <label className="text-sm font-medium text-zinc-700">Sắp xếp
          <select value={sort} onChange={(event) => setSort(event.target.value)} className="mt-2 w-full rounded-xl border border-zinc-200 bg-stone-50 px-4 py-3 font-normal">
            <option value="featured">Mặc định</option>
            <option value="name">Tên sản phẩm</option>
            <option value="price-asc">Giá tăng dần</option>
            <option value="price-desc">Giá giảm dần</option>
          </select>
        </label>
      </section>

      {visibleProducts.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-14 text-center">
          <h2 className="text-lg font-semibold">Không tìm thấy sản phẩm</h2>
          <p className="mt-2 text-sm text-zinc-500">Thử thay đổi từ khóa hoặc bộ lọc.</p>
          <button type="button" onClick={() => { setQuery(""); setCategory(""); setBrand(""); setSort("featured"); }} className="mt-5 rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white">Xóa bộ lọc</button>
        </div>
      ) : (
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {visibleProducts.map((product) => <ProductCard key={product.id} product={product} />)}
        </div>
      )}
    </main>
  );
}
