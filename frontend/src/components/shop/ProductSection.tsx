import Link from "next/link";
import { ProductCard } from "@/components/shop/ProductCard";
import type { Product } from "@/types/product";

type ProductSectionProps = {
  title: string;
  eyebrow?: string;
  products: Product[];
  loading: boolean;
  error?: string;
  emptyMessage: string;
  href?: string;
  hideWhenEmpty?: boolean;
};

export function ProductSection({
  title,
  eyebrow,
  products,
  loading,
  error,
  emptyMessage,
  href,
  hideWhenEmpty = false,
}: ProductSectionProps) {
  if (!loading && !error && products.length === 0 && hideWhenEmpty) return null;

  return (
    <section className="bg-white">
      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="mb-6 flex items-end justify-between gap-4">
          <div>
            {eyebrow && <p className="text-xs uppercase tracking-widest text-zinc-500">{eyebrow}</p>}
            <h2 className="mt-2 text-2xl font-semibold">{title}</h2>
          </div>
          {href && <Link href={href} className="text-sm font-medium text-zinc-700">Xem tất cả</Link>}
        </div>
        {loading ? (
          <p role="status" className="rounded-2xl border border-zinc-200 p-8 text-center text-sm text-zinc-500">Đang tải sản phẩm…</p>
        ) : error ? (
          <p role="alert" className="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-800">{error}</p>
        ) : products.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500">{emptyMessage}</p>
        ) : (
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {products.slice(0, 4).map((product) => <ProductCard key={product.id} product={product} />)}
          </div>
        )}
      </div>
    </section>
  );
}
