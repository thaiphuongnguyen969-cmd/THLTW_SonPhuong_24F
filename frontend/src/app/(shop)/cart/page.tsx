"use client";

import Link from "next/link";
import { formatPriceValue } from "@/lib/format-price-value";
import { useShopCart } from "@/lib/shop-cart-context";

export default function CartPage() {
  const { cart, removeItem, setQuantity } = useShopCart();

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
              {item.product.image ? <img src={item.product.image} alt={item.product.name} className="h-36 w-full rounded-xl bg-stone-100 object-cover sm:w-28" /> : <div className="flex h-36 w-full items-center justify-center rounded-xl bg-stone-100 text-xs text-zinc-500 sm:w-28">Chưa có ảnh</div>}
              <div className="flex min-w-0 flex-1 flex-col justify-between gap-4 sm:flex-row">
                <div><Link href={`/products/${item.product.id}`} className="font-medium hover:underline">{item.product.name}</Link><p className="mt-1 text-sm text-zinc-500">{item.variant.size.name} · {item.variant.color.name}</p><p className="mt-3 text-sm font-medium">{formatPriceValue(item.unit_price)} <span className="font-normal text-zinc-500">· đơn vị giá chưa xác minh</span></p></div>
                <div className="flex items-end justify-between gap-4 sm:flex-col sm:items-end">
                  <label className="text-xs text-zinc-500">Số lượng<input type="number" min={1} value={item.quantity} onChange={(event) => setQuantity(item.id, Math.max(1, Number(event.target.value) || 1))} className="ml-2 w-16 rounded-lg border border-zinc-300 px-2 py-1 text-sm text-zinc-900" /></label>
                  <div className="text-right"><p className="font-semibold">{formatPriceValue(item.subtotal)}</p><button type="button" onClick={() => removeItem(item.id)} className="mt-2 text-sm text-red-700 underline">Xóa</button></div>
                </div>
              </div>
            </article>
          ))}
        </div>
      </section>
      <aside className="h-fit rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 className="text-lg font-semibold">Tóm tắt giỏ hàng mẫu</h2>
        <div className="mt-5 flex justify-between text-sm text-zinc-600"><span>Số lượng</span><span>{cart.total_quantity}</span></div>
        <div className="mt-3 flex justify-between border-t border-zinc-200 pt-4 font-semibold"><span>Tạm tính</span><span>{formatPriceValue(cart.total)}</span></div>
        <p className="mt-2 text-xs leading-5 text-zinc-500">Đơn vị giá chưa được xác minh. Đây là dữ liệu mẫu; chưa có phí giao hàng hoặc đơn hàng thực.</p>
        <Link href="/checkout" className="mt-6 flex justify-center rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white">Tiếp tục đến thanh toán</Link>
        <Link href="/products" className="mt-3 flex justify-center rounded-full border border-zinc-300 px-5 py-3 text-sm">Tiếp tục mua sắm</Link>
      </aside>
    </main>
  );
}
