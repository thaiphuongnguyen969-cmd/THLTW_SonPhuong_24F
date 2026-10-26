"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { ProductSection } from "@/components/shop/ProductSection";
import { getAllBrands } from "@/lib/api/brands";
import { getAllCategories } from "@/lib/api/categories";
import { getHotProducts, getProducts } from "@/lib/api/products";
import { getApiErrorMessage } from "@/lib/api/client";
import type { Brand } from "@/types/brand";
import type { Category } from "@/types/category";
import type { Product } from "@/types/product";

type LoadingState = {
  new: boolean;
  promotions: boolean;
  hot: boolean;
  categories: boolean;
  brands: boolean;
};

export default function HomePage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [promotions, setPromotions] = useState<Product[]>([]);
  const [hotProducts, setHotProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [brands, setBrands] = useState<Brand[]>([]);
  const [loading, setLoading] = useState<LoadingState>({
    new: true,
    promotions: true,
    hot: true,
    categories: true,
    brands: true,
  });
  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    let active = true;
    const load = <T,>(key: keyof LoadingState, request: Promise<T>, onSuccess: (value: T) => void) => {
      void request
        .then((value) => {
          if (active) onSuccess(value);
        })
        .catch((cause: unknown) => {
          if (active) {
            setErrors((current) => ({
              ...current,
              [key]: getApiErrorMessage(cause, "Không thể tải dữ liệu."),
            }));
          }
        })
        .finally(() => {
          if (active) setLoading((current) => ({ ...current, [key]: false }));
        });
    };

    load("new", getProducts({ page: 1, per_page: 4, sort_by: "created_at", sort_direction: "desc" }), (result) => setProducts(result.data));
    load("promotions", getProducts({ page: 1, per_page: 4, promotion: true }), (result) => setPromotions(result.data));
    load("hot", getHotProducts(), setHotProducts);
    load("categories", getAllCategories(), setCategories);
    load("brands", getAllBrands(), setBrands);

    return () => { active = false; };
  }, []);

  return (
    <main>
      <section className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
        <div className="flex flex-col justify-center">
          <span className="mb-4 inline-flex w-fit rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600">Sơn Phương Model</span>
          <h1 className="max-w-xl text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">Khám phá bộ sưu tập mô hình</h1>
          <p className="mt-5 max-w-lg text-base leading-7 text-zinc-600">Khám phá các sản phẩm và bộ sưu tập đang có trong cửa hàng.</p>
          <div className="mt-7 flex flex-wrap gap-3">
            <Link href="/products" className="rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white hover:bg-zinc-700">Xem sản phẩm</Link>
            <Link href="/products#categories" className="rounded-full border border-zinc-300 bg-white px-6 py-3 text-sm font-medium text-zinc-800 hover:bg-stone-100">Khám phá danh mục</Link>
          </div>
        </div>
        <div className="relative min-h-64 overflow-hidden rounded-[2rem] bg-stone-200 sm:min-h-96">
          <div className="absolute inset-0 flex items-center justify-center text-sm text-zinc-500">Sơn Phương Model</div>
        </div>
      </section>

      <ProductSection title="Sản phẩm mới" eyebrow="Mới cập nhật" products={products} loading={loading.new} error={errors.new} emptyMessage="Không có sản phẩm mới." href="/products?sort=featured" />
      <ProductSection title="Sản phẩm khuyến mãi" products={promotions} loading={loading.promotions} error={errors.promotions} emptyMessage="Hiện chưa có sản phẩm khuyến mãi." hideWhenEmpty />
      <ProductSection title="Sản phẩm hot" products={hotProducts} loading={loading.hot} error={errors.hot} emptyMessage="Chưa có dữ liệu sản phẩm bán chạy." hideWhenEmpty />

      <section id="categories" className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div className="mb-6 flex items-end justify-between gap-4">
          <div><p className="text-xs uppercase tracking-widest text-zinc-500">Khám phá</p><h2 className="mt-2 text-2xl font-semibold">Danh mục</h2></div>
          <Link href="/products#categories" className="text-sm font-medium text-zinc-700">Xem danh mục</Link>
        </div>
        {loading.categories ? <p role="status" className="rounded-2xl border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500">Đang tải danh mục…</p> : errors.categories ? <p role="alert" className="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-800">{errors.categories}</p> : categories.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500">Chưa có danh mục để hiển thị.</p>
        ) : <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{categories.map((category) => (
          <Link key={category.id} href={"/products?category=" + encodeURIComponent(category.slug)} className="rounded-2xl border border-zinc-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <span className="text-xs text-zinc-500">Danh mục</span><h3 className="mt-2 text-lg font-medium">{category.name}</h3>
          </Link>
        ))}</div>}
      </section>

      <section className="border-y border-black/5 bg-white">
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
          <div className="mb-6"><p className="text-xs uppercase tracking-widest text-zinc-500">Khám phá</p><h2 className="mt-2 text-2xl font-semibold">Thương hiệu</h2></div>
          {loading.brands ? <p role="status" className="rounded-2xl border border-zinc-200 p-8 text-center text-sm text-zinc-500">Đang tải thương hiệu…</p> : errors.brands ? <p role="alert" className="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-800">{errors.brands}</p> : brands.length === 0 ? <p className="rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500">Chưa có thương hiệu để hiển thị.</p> : <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">{brands.map((brand) => <Link key={brand.id} href={"/products?brand=" + encodeURIComponent(brand.slug)} className="rounded-xl border border-zinc-200 bg-stone-50 px-4 py-5 text-center font-medium hover:bg-stone-100">{brand.name}</Link>)}</div>}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <p className="rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-600">Sản phẩm nổi bật chưa được cấu hình trong API hiện tại.</p>
      </section>
    </main>
  );
}
