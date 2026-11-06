"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { AccountAuthGate } from "@/components/shop/AccountAuthGate";
import { cancelOrder, getOrders } from "@/lib/api/orders";
import { getApiErrorMessage } from "@/lib/api/client";
import { formatPriceValue } from "@/lib/format-price-value";
import { useCustomerAuth } from "@/lib/customer-auth-context";
import type { Order } from "@/types/order";

function AccountOrdersContent() {
  const { user, loading: authLoading, getAccessToken, invalidateSession } = useCustomerAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [pendingOrder, setPendingOrder] = useState<number | null>(null);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  const loadOrders = useCallback(async (requestedPage: number) => {
    const token = getAccessToken();
    if (!token) { invalidateSession(); return; }
    setLoading(true);
    setError("");
    try {
      const result = await getOrders(token, { page: requestedPage, per_page: 10 });
      setOrders(result.data);
      setPage(result.meta.current_page);
      setLastPage(result.meta.last_page);
    } catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể tải danh sách đơn hàng."));
    } finally { setLoading(false); }
  }, [getAccessToken, invalidateSession]);

  useEffect(() => {
    if (authLoading || !user) return;
    let active = true;
    queueMicrotask(() => { if (active) void loadOrders(1); });
    return () => { active = false; };
  }, [authLoading, loadOrders, user]);

  async function handleCancel(order: Order) {
    if (order.status !== "Chờ xác nhận") return;
    if (!window.confirm(`Bạn có chắc muốn hủy đơn ${order.order_code ?? `#${order.id}`}?`)) return;
    const token = getAccessToken();
    if (!token) { invalidateSession(); return; }
    setPendingOrder(order.id); setError(""); setNotice("");
    try {
      await cancelOrder(order.id, token);
      setNotice("Đã gửi yêu cầu hủy đơn.");
      await loadOrders(page);
    } catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể hủy đơn hàng."));
    } finally { setPendingOrder(null); }
  }

  return (
    <main className="shop-orders-page mx-auto min-h-[60vh] max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <Link href="/account" className="text-sm text-zinc-500 hover:text-zinc-900">← Tài khoản</Link>
      <h1 className="mt-4 text-3xl font-semibold">Đơn hàng của tôi</h1>
      {notice && <p role="status" className="mt-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</p>}
      {error && <p role="alert" className="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800">{error}</p>}
      {loading ? <p role="status" className="shop-commerce-loading mt-7 text-sm text-zinc-500">Đang tải đơn hàng…</p> : orders.length === 0 ? <div className="shop-commerce-empty mt-7 rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center"><p className="font-medium">{error ? "Chưa tải được danh sách đơn hàng" : "Bạn chưa có đơn hàng"}</p>{error ? <button type="button" onClick={() => void loadOrders(page)} className="shop-account-action mt-4 text-sm underline">Thử tải lại</button> : <Link href="/products" className="shop-commerce-cta mt-4 inline-flex rounded-full bg-zinc-900 px-5 py-3 text-sm text-white">Khám phá sản phẩm</Link>}</div> : <div className="shop-order-list mt-7 space-y-4">{orders.map((order) => <article key={order.id} className="shop-commerce-card rounded-2xl border border-zinc-200 bg-white p-5"><div className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs uppercase tracking-wider text-zinc-500">Mã đơn</p><h2 className="mt-1 font-semibold">{order.order_code ?? `Đang cập nhật (#${order.id})`}</h2><p className="mt-1 text-sm text-zinc-500">{order.created_at ? new Date(order.created_at).toLocaleString("vi-VN") : ""}</p></div><div className="text-right"><p className="font-semibold">{formatPriceValue(order.amounts.total)}</p><p className="shop-order-status mt-1 inline-flex rounded-full px-3 py-1 text-xs text-zinc-600">{order.status} · {order.payment_status ?? "Chưa có trạng thái thanh toán"}</p></div></div><p className="mt-3 text-sm text-zinc-600">{order.items.length} sản phẩm · {order.payment_method === "cod" ? "Thanh toán khi nhận hàng" : order.payment_method ?? "Chưa xác định phương thức"}</p><div className="mt-4 flex flex-wrap gap-4 text-sm"><Link href={`/account/orders/${order.id}`} className="shop-account-action font-medium underline">Xem chi tiết</Link>{order.status === "Chờ xác nhận" && <button type="button" disabled={pendingOrder !== null} onClick={() => void handleCancel(order)} className="shop-account-action text-red-700 underline disabled:opacity-50">{pendingOrder === order.id ? "Đang hủy…" : "Hủy đơn"}</button>}</div></article>)}</div>}
      {!loading && lastPage > 1 && <nav className="shop-order-pagination mt-7 flex items-center justify-center gap-4" aria-label="Phân trang đơn hàng"><button type="button" disabled={loading || page <= 1} onClick={() => void loadOrders(page - 1)} className="shop-account-action rounded-full border px-4 py-2 text-sm disabled:opacity-40">Trước</button><span className="text-sm text-zinc-600">Trang {page} / {lastPage}</span><button type="button" disabled={loading || page >= lastPage} onClick={() => void loadOrders(page + 1)} className="shop-account-action rounded-full border px-4 py-2 text-sm disabled:opacity-40">Sau</button></nav>}
    </main>
  );
}

export default function AccountOrdersPage() {
  return <AccountAuthGate><AccountOrdersContent /></AccountAuthGate>;
}
