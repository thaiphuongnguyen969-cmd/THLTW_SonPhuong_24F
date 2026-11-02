"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import type { FormEvent } from "react";
import { createOrder } from "@/lib/api/orders";
import { getAddresses } from "@/lib/api/addresses";
import { getApiErrorMessage } from "@/lib/api/client";
import { formatPriceValue } from "@/lib/format-price-value";
import { useShopCart } from "@/lib/shop-cart-context";
import { useCustomerAuth } from "@/lib/customer-auth-context";
import type { Address } from "@/types/address";
import type { Order } from "@/types/order";

export default function CheckoutPage() {
  const { cart, loading: cartLoading, error: cartError, refresh: refreshCart } = useShopCart();
  const { user, loading: authLoading, getAccessToken, invalidateSession } = useCustomerAuth();
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [addressLoading, setAddressLoading] = useState(true);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  const [note, setNote] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [order, setOrder] = useState<Order | null>(null);

  const loadAddresses = useCallback(async () => {
    const token = getAccessToken();
    if (!token) { invalidateSession(); return; }
    setAddressLoading(true); setError("");
    try {
      const items = await getAddresses(token);
      setAddresses(items);
      setSelectedAddressId((current) => current && items.some((item) => item.id === current)
        ? current
        : items.find((item) => item.is_default)?.id ?? items[0]?.id ?? null);
    } catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể tải địa chỉ giao hàng."));
    } finally { setAddressLoading(false); }
  }, [getAccessToken, invalidateSession]);

  useEffect(() => {
    if (authLoading || !user) return;
    let active = true;
    queueMicrotask(() => { if (active) void loadAddresses(); });
    return () => { active = false; };
  }, [authLoading, user, loadAddresses]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!selectedAddressId || cart.items.length === 0 || cart.items.some((item) => !item.available)) return;
    const token = getAccessToken();
    if (!token) { invalidateSession(); return; }
    setSubmitting(true); setError("");
    try {
      const createdOrder = await createOrder({ address_id: selectedAddressId, note: note.trim() || null }, token);
      setOrder(createdOrder);
      await refreshCart().catch(() => undefined);
    } catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể tạo đơn hàng."));
      if ((requestError as { response?: { status?: number } })?.response?.status === 409) await refreshCart().catch(() => undefined);
    } finally { setSubmitting(false); }
  }

  if (authLoading) return <main className="shop-checkout-state mx-auto min-h-[60vh] max-w-5xl px-4 py-12" role="status">Đang xác minh tài khoản…</main>;
  if (!user) return <main className="shop-checkout-state mx-auto min-h-[60vh] max-w-4xl px-4 py-16 text-center"><h1 className="text-3xl font-semibold">Đăng nhập để thanh toán</h1><p className="mt-3 text-zinc-600">Giỏ hàng và địa chỉ thanh toán được lưu theo tài khoản của bạn.</p><Link href="/login?next=%2Fcheckout" className="shop-commerce-cta mt-7 inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm text-white">Đăng nhập</Link></main>;

  if (order) return <main className="shop-checkout-success mx-auto min-h-[60vh] max-w-3xl px-4 py-14 text-center sm:px-6"><div className="shop-commerce-card rounded-3xl border border-emerald-200 bg-white p-8 sm:p-12"><p className="text-sm font-medium text-emerald-700">Đặt hàng thành công</p><h1 className="mt-3 text-3xl font-semibold">Cảm ơn bạn đã mua hàng</h1><p className="mt-4 text-zinc-600">Mã đơn: <span className="font-semibold text-zinc-900">{order.order_code ?? `Đang cập nhật (#${order.id})`}</span></p><p className="mt-2 text-zinc-600">Tổng đơn hàng: <span className="font-semibold text-zinc-900">{formatPriceValue(order.amounts.total)}</span></p><p className="mt-2 text-sm text-zinc-500">Thanh toán khi nhận hàng · {order.status}</p><Link href={`/account/orders/${order.id}`} className="shop-commerce-cta mt-7 inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm text-white">Xem đơn hàng</Link><Link href="/products" className="shop-commerce-secondary-cta ml-3 inline-flex rounded-full border px-6 py-3 text-sm">Tiếp tục mua sắm</Link></div></main>;

  if (cartLoading || addressLoading) return <main className="shop-checkout-state mx-auto min-h-[60vh] max-w-5xl px-4 py-12" role="status">Đang tải thông tin thanh toán…</main>;

  return (
    <main className="shop-checkout-page mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
      <form onSubmit={handleSubmit} className="shop-checkout-form rounded-2xl border border-zinc-200 bg-white p-5 sm:p-7">
        <p className="text-xs uppercase tracking-widest text-zinc-500">Thông tin giao hàng</p><h1 className="mt-2 text-3xl font-semibold">Thanh toán</h1>
        {error && <div role="alert" className="shop-checkout-message shop-checkout-message--error mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800"><p>{error}</p><button type="button" onClick={() => void loadAddresses()} className="mt-2 underline">Tải lại địa chỉ</button></div>}
        {cartError && <div role="alert" className="shop-checkout-message shop-checkout-message--warning mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900"><p>{cartError}</p><button type="button" onClick={() => void refreshCart().catch(() => undefined)} className="mt-2 underline">Tải lại giỏ hàng</button></div>}
        {addresses.length === 0 ? <div className="shop-checkout-message shop-checkout-message--warning mt-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-900"><p>Bạn cần lưu địa chỉ trước khi đặt hàng.</p><Link href="/account/addresses" className="mt-3 inline-flex font-medium underline">Thêm địa chỉ</Link></div> : <fieldset className="mt-7"><legend className="text-sm font-semibold">Chọn địa chỉ nhận hàng</legend><div className="mt-3 space-y-3">{addresses.map((address) => <label key={address.id} data-selected={selectedAddressId === address.id} className={`shop-checkout-address flex cursor-pointer gap-3 rounded-xl border p-4 ${selectedAddressId === address.id ? "border-zinc-900" : "border-zinc-200"}`}><input type="radio" name="address_id" value={address.id} checked={selectedAddressId === address.id} onChange={() => setSelectedAddressId(address.id)} /><span className="text-sm"><span className="font-medium">{address.label}{address.is_default ? " · Mặc định" : ""}</span><span className="mt-1 block">{address.recipient_name}{address.phone ? ` · ${address.phone}` : ""}</span><span className="mt-1 block text-zinc-600">{[address.address_detail, address.ward, address.district, address.province].filter(Boolean).join(", ")}</span></span></label>)}</div><Link href="/account/addresses" className="mt-4 inline-flex text-sm underline">Quản lý địa chỉ</Link></fieldset>}
        <label className="mt-6 block text-sm font-medium">Ghi chú (không bắt buộc)<textarea value={note} onChange={(event) => setNote(event.target.value)} rows={3} className="shop-checkout-control mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal" /></label>
        <div className="shop-checkout-payment mt-6 rounded-xl border border-zinc-200 p-4"><p className="text-sm font-medium">Phương thức thanh toán</p><p className="mt-1 text-sm text-zinc-600">Thanh toán khi nhận hàng (COD)</p></div>
        <button type="submit" disabled={submitting || !selectedAddressId || cart.items.length === 0 || cart.items.some((item) => !item.available)} className="shop-commerce-cta shop-checkout-submit mt-7 w-full rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50">{submitting ? "Đang đặt hàng…" : "Đặt hàng"}</button>
        {cart.items.some((item) => !item.available) && <p className="mt-3 text-sm text-red-700">Có sản phẩm không khả dụng. Hãy quay lại giỏ hàng để cập nhật.</p>}
      </form>
      <aside className="shop-checkout-summary h-fit rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 className="text-lg font-semibold">Tóm tắt đơn hàng</h2>
        {cart.items.length === 0 ? <p className="mt-4 text-sm text-zinc-600">Giỏ hàng đang trống.</p> : <ul className="mt-4 space-y-4">{cart.items.map((item) => <li key={item.id} className="flex justify-between gap-3 text-sm"><span className="text-zinc-600">{item.product.name} · {item.variant.size.name} / {item.variant.color.name} × {item.quantity}<span className="mt-1 block">{formatPriceValue(item.unit_price)} mỗi sản phẩm</span></span><span className="shrink-0 font-medium">{formatPriceValue(item.subtotal)}</span></li>)}</ul>}
        <div className="mt-5 flex justify-between border-t border-zinc-200 pt-4 font-semibold"><span>Tổng giỏ hàng</span><span>{formatPriceValue(cart.total)}</span></div>
        <p className="mt-2 text-xs text-zinc-500">Tổng cuối cùng được backend tính lại khi tạo đơn.</p>
        <Link href="/cart" className="mt-5 inline-flex text-sm underline">Quay lại giỏ hàng</Link>
      </aside>
    </main>
  );
}
