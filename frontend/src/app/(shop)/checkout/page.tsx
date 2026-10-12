"use client";

import Link from "next/link";
import { useState } from "react";
import type { FormEvent } from "react";
import { formatPriceValue } from "@/lib/format-price-value";
import { useShopCart } from "@/lib/shop-cart-context";

export default function CheckoutPage() {
  const { cart } = useShopCart();
  const [notice, setNotice] = useState("");

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setNotice("Thông tin đã được kiểm tra trên giao diện. Hệ thống chưa kết nối đặt hàng và chưa tạo đơn.");
  }

  return (
    <main className="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
      <form onSubmit={handleSubmit} className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-7">
        <p className="text-xs uppercase tracking-widest text-zinc-500">Thông tin giao hàng</p>
        <h1 className="mt-2 text-3xl font-semibold">Thanh toán</h1>
        <p className="mt-3 rounded-xl bg-amber-50 p-3 text-sm leading-6 text-amber-900">Đây chỉ là biểu mẫu giao diện. Không gửi dữ liệu và không tạo đơn hàng thật.</p>
        <div className="mt-7 grid gap-4 sm:grid-cols-2">
          <label className="text-sm font-medium sm:col-span-2">Họ và tên<input name="name" autoComplete="name" required maxLength={100} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal" /></label>
          <label className="text-sm font-medium">Email<input name="email" type="email" autoComplete="email" required className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal" /></label>
          <label className="text-sm font-medium">Số điện thoại<input name="phone" type="tel" autoComplete="tel" required pattern="[+]?[0-9]{8,15}" className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal" /></label>
          <label className="text-sm font-medium sm:col-span-2">Địa chỉ<input name="address" autoComplete="street-address" required maxLength={255} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal" /></label>
          <label className="text-sm font-medium">Tỉnh / thành phố<input name="city" autoComplete="address-level1" required className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal" /></label>
          <label className="text-sm font-medium">Ghi chú (không bắt buộc)<input name="note" maxLength={300} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal" /></label>
        </div>
        <fieldset className="mt-7">
          <legend className="text-sm font-semibold">Phương thức thanh toán</legend>
          <label className="mt-3 flex items-center gap-3 rounded-xl border border-zinc-200 p-4 text-sm"><input type="radio" name="payment" value="cod" defaultChecked /> Thanh toán khi nhận hàng (minh họa)</label>
        </fieldset>
        <button type="submit" className="mt-7 w-full rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white">Kiểm tra thông tin</button>
        {notice && <p role="status" className="mt-4 text-sm text-emerald-800">{notice}</p>}
      </form>
      <aside className="h-fit rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 className="text-lg font-semibold">Đơn hàng mẫu</h2>
        {cart.items.length === 0 ? <p className="mt-4 text-sm text-zinc-600">Giỏ hàng mẫu đang trống.</p> : <ul className="mt-4 space-y-3">{cart.items.map((item) => <li key={item.id} className="flex justify-between gap-3 text-sm"><span className="text-zinc-600">{item.product.name} × {item.quantity}</span><span className="shrink-0 font-medium">{formatPriceValue(item.subtotal)}</span></li>)}</ul>}
        <div className="mt-5 flex justify-between border-t border-zinc-200 pt-4 font-semibold"><span>Tạm tính</span><span>{formatPriceValue(cart.total)}</span></div>
        <p className="mt-2 text-xs text-zinc-500">Đơn vị giá chưa xác minh; chưa gồm phí giao hàng.</p>
        <Link href="/cart" className="mt-5 inline-flex text-sm underline">Quay lại giỏ hàng</Link>
      </aside>
    </main>
  );
}
