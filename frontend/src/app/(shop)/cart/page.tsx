"use client";

import Link from "next/link";
import Image from "next/image";
import { formatPriceValue } from "@/lib/format-price-value";
import { useShopCart } from "@/lib/shop-cart-context";
import { useCustomerAuth } from "@/lib/customer-auth-context";
import { getProductImageUrl } from "@/lib/product-image-url";

export default function CartPage() {
  const { cart, loading, mutating, error, refresh, removeItem, setQuantity, clear } = useShopCart();
  const { user, loading: authLoading } = useCustomerAuth();

  if (authLoading || loading) {
    return <main className="mx-auto min-h-[60vh] max-w-4xl px-4 py-16 text-center" role="status">Đang tải giỏ hàng…</main>;
  }

  if (!user) {
    return (
      <main className="mx-auto min-h-[60vh] max-w-4xl px-4 py-16 text-center">
        <h1 className="text-3xl font-semibold">Giỏ hàng của bạn</h1>
        <p className="mt-3 text-zinc-600">Vui lòng đăng nhập để xem và lưu giỏ hàng trên tài khoản.</p>
        <Link href="/login?next=%2Fcart" className="mt-7 inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white">Đăng nhập</Link>
      </main>
    );
  }

  if (error) {
    return (
      <main className="mx-auto min-h-[60vh] max-w-4xl px-4 py-16 text-center">
        <h1 className="text-3xl font-semibold">Không thể tải giỏ hàng</h1>
        <p role="alert" className="mt-3 text-sm text-red-700">{error}</p>
        <button type="button" onClick={() => void refresh().catch(() => undefined)} className="mt-6 rounded-full bg-zinc-900 px-6 py-3 text-sm text-white">Thử lại</button>
      </main>
    );
  }

  if (cart.items.length === 0) {
    return (
      <main className="mx-auto min-h-[60vh] max-w-4xl px-4 py-16 text-center sm:px-6">
        <p className="text-5xl" aria-hidden="true">🛒</p>
        <h1 className="mt-5 text-3xl font-semibold">Giỏ hàng đang trống</h1>
        <p className="mt-3 text-zinc-600">Hãy khám phá sản phẩm và thêm lựa chọn của bạn.</p>
        <Link href="/products" className="mt-7 inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white">Tiếp tục mua sắm</Link>
      </main>
    );
  }

  return (
    <main className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
      <section>
        <div className="mb-7 flex items-end justify-between gap-3"><div><p className="text-xs uppercase tracking-widest text-zinc-500">Mua sắm</p><h1 className="mt-2 text-3xl font-semibold">Giỏ hàng</h1></div><span className="text-sm text-zinc-500">{cart.total_quantity} sản phẩm</span></div>
        <div className="space-y-4">
          {cart.items.map((item) => (
            <article key={item.id} className="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-4 sm:flex-row">
              {getProductImageUrl(item.product.image) ? <Image unoptimized width={600} height={600} src={getProductImageUrl(item.product.image) ?? ""} alt={item.product.name} className="h-36 w-full rounded-xl bg-stone-100 object-cover sm:w-28" /> : <div className="flex h-36 w-full items-center justify-center rounded-xl bg-stone-100 text-xs text-zinc-500 sm:w-28">Chưa có ảnh</div>}
              <div className="flex min-w-0 flex-1 flex-col justify-between gap-4 sm:flex-row">
                <div><Link href={`/products/${item.product.slug}`} className="font-medium hover:underline">{item.product.name}</Link><p className="mt-1 text-sm text-zinc-500">{item.variant.size.name} · {item.variant.color.name}</p><p className="mt-3 text-sm font-medium">{formatPriceValue(item.unit_price)}</p>{!item.available && <p className="mt-2 text-sm font-medium text-red-700">Sản phẩm hiện không khả dụng hoặc không đủ tồn kho.</p>}</div>
                <div className="flex items-end justify-between gap-4 sm:flex-col sm:items-end">
                  <div className="flex items-center gap-2 text-sm"><button type="button" aria-label="Giảm số lượng" disabled={mutating || item.quantity <= 1} onClick={() => void setQuantity(item.id, item.quantity - 1).catch(() => undefined)} className="h-9 w-9 rounded-lg border disabled:opacity-40">−</button><span className="min-w-8 text-center" aria-label="Số lượng">{item.quantity}</span><button type="button" aria-label="Tăng số lượng" disabled={mutating} onClick={() => void setQuantity(item.id, item.quantity + 1).catch(() => undefined)} className="h-9 w-9 rounded-lg border">+</button></div>
                  <div className="text-right"><p className="font-semibold">{formatPriceValue(item.subtotal)}</p><button type="button" disabled={mutating} onClick={() => void removeItem(item.id).catch(() => undefined)} className="mt-2 text-sm text-red-700 underline disabled:opacity-50">Xóa</button></div>
                </div>
              </div>
            </article>
          ))}
        </div>
      </section>
      <aside className="h-fit rounded-2xl border border-zinc-200 bg-white p-6">
        <div className="flex items-center justify-between gap-3"><h2 className="text-lg font-semibold">Tóm tắt giỏ hàng</h2><button type="button" disabled={mutating} onClick={() => { if (window.confirm("Bạn có chắc muốn xóa toàn bộ giỏ hàng?")) void clear().catch(() => undefined); }} className="text-sm text-red-700 underline disabled:opacity-50">Xóa tất cả</button></div>
        <div className="mt-5 flex justify-between text-sm text-zinc-600"><span>Số lượng</span><span>{cart.total_quantity}</span></div>
        <div className="mt-3 flex justify-between border-t border-zinc-200 pt-4 font-semibold"><span>Tạm tính</span><span>{formatPriceValue(cart.total)}</span></div>
        <p className="mt-2 text-xs leading-5 text-zinc-500">Giá và tổng tiền do máy chủ tính. Phí giao hàng được xác định khi xử lý đơn.</p>
        {error && <p role="alert" className="mt-3 text-sm text-red-700">{error}</p>}
        <Link aria-disabled={cart.items.some((item) => !item.available) || mutating} tabIndex={cart.items.some((item) => !item.available) || mutating ? -1 : undefined} href={cart.items.some((item) => !item.available) || mutating ? "#" : "/checkout"} className={`mt-6 flex justify-center rounded-full px-5 py-3 text-sm font-medium text-white ${cart.items.some((item) => !item.available) || mutating ? "cursor-not-allowed bg-zinc-400" : "bg-zinc-900"}`}>Tiếp tục đến thanh toán</Link>
        <Link href="/products" className="mt-3 flex justify-center rounded-full border border-zinc-300 px-5 py-3 text-sm">Tiếp tục mua sắm</Link>
      </aside>
    </main>
  );
}
