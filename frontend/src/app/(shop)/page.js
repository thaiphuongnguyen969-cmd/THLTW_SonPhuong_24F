import Link from "next/link";
import { ProductCard } from "@/components/shop/ProductCard";
import { mockBrands, mockCategories, mockProducts } from "@/lib/mock-data";

const categoryLabels = {
  "New Arrivals": "Hàng mới",
  Tailoring: "Đồ may đo",
  Accessories: "Phụ kiện",
  Essentials: "Đồ thiết yếu",
};

export default function HomePage() {
  const featuredProducts = mockProducts.filter((product) => product.status === "active").slice(0, 4);
  const newProducts = mockProducts
    .filter((product) => product.status === "active" && product.created_at)
    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 4);

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
          <img src="https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1200&q=80" alt="Bộ sưu tập tại Sơn Phương Model" className="absolute inset-0 h-full w-full object-cover" />
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div className="mb-6 flex items-end justify-between gap-4">
          <div><p className="text-xs uppercase tracking-widest text-zinc-500">Khám phá</p><h2 className="mt-2 text-2xl font-semibold">Danh mục nổi bật</h2></div>
          <Link href="/products#categories" className="text-sm font-medium text-zinc-700">Xem danh mục</Link>
        </div>
        {mockCategories.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500">Chưa có danh mục để hiển thị.</p>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {mockCategories.map((category) => (
              <Link key={category.id} href={`/products?category=${category.slug}`} className="rounded-2xl border border-zinc-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                <span className="text-xs text-zinc-500">Danh mục</span>
                <h3 className="mt-2 text-lg font-medium">{categoryLabels[category.name] ?? category.name}</h3>
              </Link>
            ))}
          </div>
        )}
      </section>

      <section className="bg-white">
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
          <div className="mb-6 flex items-end justify-between gap-4">
            <div><p className="text-xs uppercase tracking-widest text-zinc-500">Tuyển chọn</p><h2 className="mt-2 text-2xl font-semibold">Sản phẩm nổi bật</h2></div>
            <Link href="/products" className="text-sm font-medium text-zinc-700">Xem tất cả</Link>
          </div>
          {featuredProducts.length === 0 ? <p className="rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500">Chưa có sản phẩm để hiển thị.</p> : <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">{featuredProducts.map((product) => <ProductCard key={product.id} product={product} />)}</div>}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="mb-6 flex items-end justify-between gap-4">
          <div><p className="text-xs uppercase tracking-widest text-zinc-500">Cập nhật</p><h2 className="mt-2 text-2xl font-semibold">Sản phẩm mới</h2></div>
          <Link href="/products" className="text-sm font-medium text-zinc-700">Xem tất cả</Link>
        </div>
        {newProducts.length === 0 ? <p className="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500">Chưa có thông tin ngày tạo để xác định sản phẩm mới.</p> : <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">{newProducts.map((product) => <ProductCard key={product.id} product={product} />)}</div>}
      </section>

      <section className="border-y border-black/5 bg-white">
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
          <div className="mb-6"><p className="text-xs uppercase tracking-widest text-zinc-500">Khám phá</p><h2 className="mt-2 text-2xl font-semibold">Thương hiệu</h2></div>
          {mockBrands.length === 0 ? <p className="rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500">Chưa có thương hiệu để hiển thị.</p> : <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">{mockBrands.map((brand) => <Link key={brand.id} href={`/products?brand=${brand.slug}`} className="rounded-xl border border-zinc-200 bg-stone-50 px-4 py-5 text-center font-medium hover:bg-stone-100">{brand.name}</Link>)}</div>}
        </div>
      </section>
    </main>
  );
}
