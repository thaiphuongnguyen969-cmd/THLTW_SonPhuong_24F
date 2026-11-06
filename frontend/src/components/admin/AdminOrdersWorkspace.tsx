"use client";

import { useEffect, useState } from "react";
import { getApiErrorMessage } from "@/lib/api/client";
import { getAdminOrder, listAdminOrders, updateAdminOrderStatus, type AdminOrder } from "@/lib/api/admin-orders";
import { getAdminToken } from "@/lib/admin-auth";
import { formatPriceValue } from "@/lib/format-price-value";

const PAGE_SIZE = 15;
const statusLabels: Record<string, string> = {
  "Chờ xác nhận": "Chờ xác nhận",
  "Đã xác nhận": "Đã xác nhận",
  "Đang chuẩn bị hàng": "Đang chuẩn bị hàng",
  "Đang giao hàng": "Đang giao hàng",
  "Đã giao hàng": "Đã giao hàng",
  "Đã hủy": "Đã hủy",
};
const transitionLabels: Record<string, string> = {
  "Đã xác nhận": "Xác nhận đơn",
  "Đang chuẩn bị hàng": "Bắt đầu chuẩn bị hàng",
  "Đang giao hàng": "Chuyển sang đang giao hàng",
  "Đã giao hàng": "Hoàn thành đơn",
  "Đã hủy": "Hủy đơn",
};

function formatDate(value: string | null): string {
  if (!value) return "—";
  const parsed = new Date(`${value.replace(" ", "T")}Z`);
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString("vi-VN");
}

function formatPaymentMethod(value: string | null): string {
  if (value === "cod") return "Thanh toán khi nhận hàng (COD)";
  return value || "Chưa xác định";
}

export function AdminOrdersWorkspace() {
  const [orders, setOrders] = useState<AdminOrder[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [selected, setSelected] = useState<AdminOrder | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [detailError, setDetailError] = useState("");
  const [reloadKey, setReloadKey] = useState(0);
  const [notice, setNotice] = useState("");
  const [pendingStatus, setPendingStatus] = useState("");
  const [reason, setReason] = useState("");
  const [statusBusy, setStatusBusy] = useState(false);
  const [statusError, setStatusError] = useState("");

  useEffect(() => {
    let active = true;
    const token = getAdminToken();
    const timer = window.setTimeout(() => {
      if (!token) {
        setOrders([]);
        setError("Phiên quản trị không còn hợp lệ. Vui lòng đăng nhập lại.");
        setLoading(false);
        return;
      }

      void listAdminOrders(token, {
        page,
        per_page: PAGE_SIZE,
        search: query.trim() || undefined,
        status: status || undefined,
      }).then((result) => {
        if (!active) return;
        setOrders(result.data);
        setLastPage(result.last_page);
        setTotal(result.total);
      }).catch((reason: unknown) => {
        if (!active) return;
        setOrders([]);
        setError(getApiErrorMessage(reason, "Không thể tải danh sách đơn hàng."));
      }).finally(() => {
        if (active) setLoading(false);
      });
    }, query ? 300 : 0);

    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [page, query, reloadKey, status]);

  async function showDetails(order: AdminOrder) {
    const token = getAdminToken();
    setSelected(order);
    setDetailError("");
    if (!token) {
      setDetailError("Phiên quản trị không còn hợp lệ. Vui lòng đăng nhập lại.");
      return;
    }
    setDetailLoading(true);
    try {
      setSelected(await getAdminOrder(token, order.id));
    } catch (reason) {
      setDetailError(getApiErrorMessage(reason, "Không thể tải chi tiết đơn hàng."));
    } finally {
      setDetailLoading(false);
    }
  }

  async function submitStatusChange() {
    if (!selected || !pendingStatus) return;
    const token = getAdminToken();
    if (!token) {
      setStatusError("Phiên quản trị không còn hợp lệ. Vui lòng đăng nhập lại.");
      return;
    }

    setStatusBusy(true);
    setStatusError("");
    try {
      const updated = await updateAdminOrderStatus(token, selected.id, {
        status: pendingStatus,
        expected_status: selected.status,
        reason: reason.trim() || undefined,
      });
      setSelected(updated);
      setOrders((current) => current.map((order) => order.id === updated.id ? updated : order));
      setPendingStatus("");
      setReason("");
      setNotice(`Đã cập nhật đơn ${updated.order_code || `#${updated.id}`} sang trạng thái “${updated.status}”.`);
      setReloadKey((current) => current + 1);
      setLoading(true);
    } catch (reasonError) {
      const message = getApiErrorMessage(reasonError, "Không thể cập nhật trạng thái đơn hàng.");
      setStatusError(message);
      if (message.includes("đã được cập nhật bởi thao tác khác")) {
        try {
          setSelected(await getAdminOrder(token, selected.id));
          setPendingStatus("");
        } catch {
          // Preserve the original conflict message when reloading also fails.
        }
      }
    } finally {
      setStatusBusy(false);
    }
  }

  return (
    <section className="space-y-5">
      <header>
        <h1 className="text-2xl font-semibold">Quản lý đơn hàng</h1>
        <p className="mt-1 text-sm text-zinc-500">Theo dõi đơn hàng được đặt từ cửa hàng.</p>
      </header>

      {notice && <div role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{notice}</div>}

      <div className="grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 sm:grid-cols-2">
        <label className="text-sm font-medium">
          Tìm mã đơn, tên hoặc email khách hàng
          <input value={query} onChange={(event) => { setQuery(event.target.value); setPage(1); setError(""); setLoading(true); }} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-2.5 font-normal" />
        </label>
        <label className="text-sm font-medium">
          Lọc trạng thái
          <select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); setError(""); setLoading(true); }} className="mt-2 w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 font-normal">
            <option value="">Tất cả trạng thái</option>
            {Object.entries(statusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
          </select>
        </label>
      </div>

      {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>}

      <div className="overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table className="min-w-[850px] w-full text-left text-sm">
          <thead className="bg-stone-100 text-xs uppercase tracking-wide text-zinc-500">
            <tr><th className="px-4 py-3">Mã đơn</th><th className="px-4 py-3">Khách hàng</th><th className="px-4 py-3">Ngày đặt</th><th className="px-4 py-3">Tổng tiền</th><th className="px-4 py-3">Đơn hàng</th><th className="px-4 py-3">Thanh toán</th><th className="px-4 py-3">Thao tác</th></tr>
          </thead>
          <tbody className="divide-y divide-zinc-100">
            {loading ? <tr><td colSpan={7} className="px-4 py-12 text-center text-zinc-500">Đang tải đơn hàng…</td></tr>
              : orders.length === 0 ? <tr><td colSpan={7} className="px-4 py-12 text-center text-zinc-500">{error ? "Chưa tải được đơn hàng." : "Chưa có đơn hàng phù hợp."}</td></tr>
                : orders.map((order) => <tr key={order.id}>
                  <td className="px-4 py-3 font-medium">{order.order_code || `#${order.id}`}</td>
                  <td className="px-4 py-3"><span className="block">{order.customer?.name || "Tài khoản đã xóa"}</span><span className="text-xs text-zinc-500">{order.customer?.email || "—"}</span></td>
                  <td className="whitespace-nowrap px-4 py-3">{formatDate(order.created_at)}</td>
                  <td className="whitespace-nowrap px-4 py-3">{formatPriceValue(order.amounts.total)}</td>
                  <td className="px-4 py-3">{statusLabels[order.status] ?? order.status}</td>
                  <td className="px-4 py-3">{order.payment_status || "Chưa xác định"}<span className="mt-1 block text-xs text-zinc-500">{formatPaymentMethod(order.payment_method)}</span></td>
                  <td className="px-4 py-3"><button type="button" onClick={() => void showDetails(order)} className="font-medium underline">Chi tiết</button></td>
                </tr>)}
          </tbody>
        </table>
      </div>

      {!loading && !error && total > 0 && <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-600">
        <span>{total} đơn hàng · Trang {page}/{lastPage}</span>
        <div className="flex gap-2"><button type="button" disabled={page <= 1} onClick={() => { setPage((current) => current - 1); setError(""); setLoading(true); }} className="rounded-lg border px-3 py-2 disabled:opacity-40">Trước</button><button type="button" disabled={page >= lastPage} onClick={() => { setPage((current) => current + 1); setError(""); setLoading(true); }} className="rounded-lg border px-3 py-2 disabled:opacity-40">Sau</button></div>
      </div>}

      {selected && <div className="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-0 sm:items-center sm:p-5" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) setSelected(null); }}>
        <section role="dialog" aria-modal="true" aria-labelledby="admin-order-detail-title" className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-t-2xl bg-white p-5 sm:rounded-2xl sm:p-6">
          <div className="flex items-start justify-between gap-4"><div><h2 id="admin-order-detail-title" className="text-xl font-semibold">Đơn hàng {selected.order_code || `#${selected.id}`}</h2><p className="mt-1 text-sm text-zinc-500">Đặt lúc {formatDate(selected.created_at)}</p></div><button type="button" onClick={() => setSelected(null)} className="text-sm underline">Đóng</button></div>
          {detailLoading && <p role="status" className="mt-5 text-sm text-zinc-500">Đang tải chi tiết…</p>}
          {detailError && <p role="alert" className="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800">{detailError}</p>}
          {statusError && !pendingStatus && <p role="alert" className="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800">{statusError}</p>}
          {!detailLoading && !detailError && <div className="mt-5 grid gap-5 md:grid-cols-2">
            <div className="space-y-4 text-sm">
              <section><h3 className="font-semibold">Khách hàng</h3><p className="mt-1">{selected.customer?.name || "Tài khoản đã xóa"}</p><p className="text-zinc-600">{selected.customer?.email || "—"}</p><p className="text-zinc-600">{selected.customer?.phone || "—"}</p></section>
              <section><h3 className="font-semibold">Địa chỉ giao hàng</h3><p className="mt-1">{selected.shipping_address.recipient_name || selected.customer?.name || "—"}</p><p>{selected.shipping_address.phone || "—"}</p><p className="text-zinc-600">{[selected.shipping_address.address_detail, selected.shipping_address.ward, selected.shipping_address.district, selected.shipping_address.province].filter(Boolean).join(", ") || "—"}</p></section>
              <section><h3 className="font-semibold">Thanh toán</h3><p className="mt-1">{formatPaymentMethod(selected.payment_method)}</p><p className="text-zinc-600">Trạng thái: {selected.payment_status || "Chưa xác định"}</p>{selected.payments.map((payment) => <p key={payment.id} className="mt-1 text-zinc-600">{payment.status} · {formatPriceValue(payment.amount)}{payment.paid_at ? ` · ${formatDate(payment.paid_at)}` : ""}</p>)}</section>
              {selected.note && <section><h3 className="font-semibold">Ghi chú</h3><p className="mt-1 whitespace-pre-wrap text-zinc-600">{selected.note}</p></section>}
              {selected.cancelled_reason && <section><h3 className="font-semibold">Lý do hủy</h3><p className="mt-1 whitespace-pre-wrap text-zinc-600">{selected.cancelled_reason}</p></section>}
            </div>
            <div className="space-y-4 text-sm">
              <section><h3 className="font-semibold">Sản phẩm ({selected.items.length})</h3><div className="mt-2 divide-y rounded-xl border">{selected.items.map((item) => <div key={item.id} className="flex justify-between gap-3 p-3"><div><p className="font-medium">{item.product_name}</p><p className="text-xs text-zinc-500">{[item.sku && `SKU ${item.sku}`, item.size && `Size ${item.size}`, item.color].filter(Boolean).join(" · ") || "Biến thể không xác định"} · SL {item.quantity}</p></div><div className="shrink-0 text-right"><p>{formatPriceValue(item.unit_price)}</p><p className="text-xs text-zinc-500">{formatPriceValue(item.subtotal)}</p></div></div>)}</div></section>
              <section className="space-y-2 border-t pt-3"><h3 className="font-semibold">Tổng kết</h3><div className="flex justify-between"><span>Tạm tính</span><span>{selected.amounts.subtotal === null ? "—" : formatPriceValue(selected.amounts.subtotal)}</span></div><div className="flex justify-between"><span>Giảm giá</span><span>{selected.amounts.discount === null ? "—" : formatPriceValue(selected.amounts.discount)}</span></div><div className="flex justify-between"><span>Phí giao hàng</span><span>{selected.amounts.shipping_fee === null ? "—" : formatPriceValue(selected.amounts.shipping_fee)}</span></div><div className="flex justify-between border-t pt-2 font-semibold"><span>Tổng cộng</span><span>{formatPriceValue(selected.amounts.total)}</span></div></section>
              <section><h3 className="font-semibold">Lịch sử trạng thái</h3>{selected.status_histories.length === 0 ? <p className="mt-1 text-zinc-500">Chưa có lịch sử trạng thái.</p> : <ol className="mt-2 space-y-3 border-l pl-4">{selected.status_histories.map((history, index) => <li key={`${history.created_at}-${index}`}><p className="font-medium">{history.from_status ? `${history.from_status} → ` : ""}{history.to_status}</p><p className="text-xs text-zinc-500">{formatDate(history.created_at)}{history.changed_by_name ? ` · ${history.changed_by_name}` : ""}{history.note ? ` · ${history.note}` : ""}</p></li>)}</ol>}</section>
              {selected.allowed_status_transitions.length > 0 && <section className="border-t pt-4"><h3 className="font-semibold">Xử lý đơn hàng</h3><p className="mt-1 text-zinc-600">Trạng thái hiện tại: {selected.status}</p><div className="mt-3 flex flex-wrap gap-2">{selected.allowed_status_transitions.map((nextStatus) => <button key={nextStatus} type="button" disabled={statusBusy} onClick={() => { setPendingStatus(nextStatus); setReason(""); setStatusError(""); }} className={`rounded-xl px-3 py-2 text-sm font-medium disabled:opacity-50 ${nextStatus === "Đã hủy" ? "border border-red-200 text-red-700" : "bg-zinc-900 text-white"}`}>{transitionLabels[nextStatus] ?? nextStatus}</button>)}</div></section>}
              {pendingStatus && <section role="alertdialog" aria-labelledby="order-status-confirm-title" aria-describedby="order-status-confirm-description" className="rounded-xl border border-amber-300 bg-amber-50 p-4"><h3 id="order-status-confirm-title" className="font-semibold">Xác nhận thay đổi trạng thái</h3><p id="order-status-confirm-description" className="mt-2 text-sm">Chuyển từ “{selected.status}” sang “{pendingStatus}”?</p><label className="mt-3 block text-sm font-medium">Ghi chú hoặc lý do (không bắt buộc)<textarea value={reason} maxLength={1000} onChange={(event) => setReason(event.target.value)} rows={3} className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-normal" /></label>{statusError && <p role="alert" className="mt-2 text-sm text-red-800">{statusError}</p>}<div className="mt-3 flex justify-end gap-2"><button type="button" disabled={statusBusy} onClick={() => { setPendingStatus(""); setStatusError(""); }} className="rounded-lg border px-3 py-2 text-sm disabled:opacity-50">Quay lại</button><button type="button" disabled={statusBusy} onClick={() => void submitStatusChange()} className="rounded-lg bg-zinc-900 px-3 py-2 text-sm text-white disabled:opacity-50">{statusBusy ? "Đang cập nhật…" : "Xác nhận"}</button></div></section>}
            </div>
          </div>}
        </section>
      </div>}
    </section>
  );
}
