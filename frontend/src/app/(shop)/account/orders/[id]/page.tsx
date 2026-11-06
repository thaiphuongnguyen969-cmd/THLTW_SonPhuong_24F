"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { AccountAuthGate } from "@/components/shop/AccountAuthGate";
import { getOrder } from "@/lib/api/orders";
import { getApiErrorMessage } from "@/lib/api/client";
import { formatPriceValue } from "@/lib/format-price-value";
import { useCustomerAuth } from "@/lib/customer-auth-context";
import type { Order } from "@/types/order";

function OrderDetailContent({ orderId }: { orderId: number }) {
  const { getAccessToken, invalidateSession } = useCustomerAuth();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const loadOrder = useCallback(async () => {
    const token = getAccessToken();
    if (!token) { invalidateSession(); return; }
    setLoading(true); setError("");
    try { setOrder(await getOrder(orderId, token)); }
    catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể tải chi tiết đơn hàng."));
    } finally { setLoading(false); }
  }, [getAccessToken, invalidateSession, orderId]);

  useEffect(() => {
    let active = true;
    queueMicrotask(() => { if (active) void loadOrder(); });
    return () => { active = false; };
  }, [loadOrder]);

  if (loading) return <main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10" role="status">Đang tải chi tiết đơn hàng…</main>;
  if (error || !order) return <main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10"><p role="alert" className="rounded-xl bg-red-50 p-4 text-sm text-red-800">{error || "Không tìm thấy đơn hàng."}</p><Link href="/account/orders" className="mt-5 inline-flex text-sm underline">Quay lại đơn hàng</Link><button type="button" onClick={() => void loadOrder()} className="ml-5 text-sm underline">Thử lại</button></main>;

  const address = order.shipping_address;
  return (
    <main className="shop-order-detail mx-auto min-h-[60vh] max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <Link href="/account/orders" className="text-sm text-zinc-500 hover:text-zinc-900">← Đơn hàng của tôi</Link>
      <div className="shop-commerce-heading mt-4 flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs uppercase tracking-widest text-zinc-500">Chi tiết đơn hàng</p><h1 className="mt-2 text-3xl font-semibold">{order.order_code ?? `Đang cập nhật (#${order.id})`}</h1></div><div className="text-right"><p className="shop-order-status inline-flex rounded-full px-3 py-1 text-sm font-medium">{order.status}</p><p className="mt-1 text-sm text-zinc-600">Thanh toán: {order.payment_status ?? "Chưa có trạng thái"}</p></div></div>
      <div className="mt-7 grid gap-6 lg:grid-cols-[1fr_320px]">
        <section className="shop-order-detail__sections space-y-6">
          <div className="shop-commerce-card rounded-2xl border border-zinc-200 bg-white p-5"><h2 className="text-lg font-semibold">Sản phẩm</h2><div className="mt-4 divide-y divide-zinc-100">{order.items.map((item) => <article key={item.id} className="flex flex-wrap items-start justify-between gap-3 py-4 first:pt-0 last:pb-0"><div><h3 className="font-medium">{item.product_name}</h3><p className="mt-1 text-sm text-zinc-500">{[item.size, item.color].filter(Boolean).join(" · ") || "Phiên bản không còn thông tin"}{item.sku ? ` · SKU ${item.sku}` : ""}</p><p className="mt-1 text-sm text-zinc-500">{formatPriceValue(item.unit_price)} × {item.quantity}</p></div><p className="font-semibold">{formatPriceValue(item.subtotal)}</p></article>)}</div></div>
          <div className="shop-commerce-card rounded-2xl border border-zinc-200 bg-white p-5"><h2 className="text-lg font-semibold">Địa chỉ giao hàng</h2><p className="mt-3 font-medium">{address.recipient_name || "Người nhận"}</p>{address.phone && <p className="mt-1 text-sm text-zinc-600">{address.phone}</p>}<p className="mt-2 text-sm leading-6 text-zinc-600">{[address.address_detail, address.ward, address.district, address.province].filter(Boolean).join(", ")}</p></div>
          <div className="shop-commerce-card rounded-2xl border border-zinc-200 bg-white p-5"><h2 className="text-lg font-semibold">Lịch sử trạng thái</h2>{order.status_histories.length ? <ol className="mt-4 space-y-4">{order.status_histories.map((history, index) => <li key={`${history.created_at ?? "history"}-${index}`} className="shop-order-history border-l-2 border-zinc-200 pl-4"><p className="font-medium">{history.to_status}</p>{history.note && <p className="mt-1 text-sm text-zinc-600">{history.note}</p>}{history.created_at && <time className="mt-1 block text-xs text-zinc-500">{new Date(history.created_at).toLocaleString("vi-VN")}</time>}</li>)}</ol> : <p className="mt-3 text-sm text-zinc-500">Chưa có lịch sử trạng thái.</p>}</div>
        </section>
        <aside className="shop-order-detail__summary shop-commerce-card h-fit rounded-2xl border border-zinc-200 bg-white p-5"><h2 className="text-lg font-semibold">Thanh toán</h2><p className="mt-3 text-sm text-zinc-600">{order.payment_method === "cod" ? "Thanh toán khi nhận hàng" : order.payment_method ?? "Chưa xác định"}</p><div className="mt-4 space-y-2 border-t border-zinc-100 pt-4 text-sm"><div className="flex justify-between gap-3"><span className="text-zinc-600">Tạm tính</span><span>{order.amounts.subtotal === null ? "—" : formatPriceValue(order.amounts.subtotal)}</span></div><div className="flex justify-between gap-3"><span className="text-zinc-600">Giảm giá</span><span>{order.amounts.discount === null ? "—" : formatPriceValue(order.amounts.discount)}</span></div><div className="flex justify-between gap-3"><span className="text-zinc-600">Phí giao hàng</span><span>{order.amounts.shipping_fee === null ? "—" : formatPriceValue(order.amounts.shipping_fee)}</span></div><div className="shop-order-total flex justify-between gap-3 border-t border-zinc-100 pt-3 text-base font-semibold"><span>Tổng cộng</span><span>{formatPriceValue(order.amounts.total)}</span></div></div>{order.payments.map((payment) => <div key={payment.id} className="mt-4 rounded-xl bg-zinc-50 p-3 text-sm"><p>Giao dịch: {payment.transaction_code || "Chưa phát sinh"}</p><p className="mt-1 text-zinc-600">{payment.status} · {formatPriceValue(payment.amount)}</p></div>)}{order.note && <p className="mt-4 text-sm text-zinc-600">Ghi chú: {order.note}</p>}{order.cancelled_reason && <p className="mt-3 text-sm text-red-700">Lý do hủy: {order.cancelled_reason}</p>}<p className="mt-4 text-xs text-zinc-500">Đặt lúc: {order.created_at ? new Date(order.created_at).toLocaleString("vi-VN") : "—"}</p></aside>
      </div>
    </main>
  );
}

export default function OrderDetailPage() {
  const params = useParams<{ id: string }>();
  const orderId = Number(params.id);
  if (!Number.isInteger(orderId) || orderId < 1) return <AccountAuthGate><main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10">Mã đơn hàng không hợp lệ.</main></AccountAuthGate>;
  return <AccountAuthGate><OrderDetailContent orderId={orderId} /></AccountAuthGate>;
}
