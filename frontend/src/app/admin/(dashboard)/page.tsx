"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { getAdminToken } from "@/lib/admin-auth";
import { getAdminDashboard, type AdminDashboardData } from "@/lib/api/admin-dashboard";

const numberFormatter = new Intl.NumberFormat("vi-VN");
const integerFormatter = new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 0 });
const decimalSeparator = new Intl.NumberFormat("vi-VN", { minimumFractionDigits: 1 })
  .formatToParts(1.1)
  .find((part) => part.type === "decimal")?.value ?? ",";

const overview = [
  { key: "products", label: "Tổng sản phẩm", href: "/admin/products" },
  { key: "categories", label: "Tổng danh mục", href: "/admin/categories" },
  { key: "brands", label: "Tổng thương hiệu", href: "/admin/brands" },
  { key: "orders", label: "Tổng đơn hàng", href: "/admin/orders" },
  { key: "customers", label: "Tổng khách hàng", href: "/admin/customers" },
] as const;

function formatAmount(value: string): string {
  const match = /^(-?)(\d+)(?:\.(\d+))?$/.exec(value);
  if (!match) return value;

  const [, sign, integer, fraction = ""] = match;
  const groupedInteger = integerFormatter.format(BigInt(integer));
  const decimals = fraction.padEnd(2, "0").slice(0, 2);
  return `${sign}${groupedInteger}${decimalSeparator}${decimals}`;
}

function formatDate(value: string | null): string {
  if (!value) return "—";
  const parsed = new Date(`${value.replace(" ", "T")}Z`);
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString("vi-VN");
}

function formatMonth(value: string): string {
  const match = /^(\d{4})-(\d{2})$/.exec(value);
  if (!match) return value;
  return `Tháng ${Number(match[2])}/${match[1]}`;
}

function DashboardSkeleton() {
  return (
    <div aria-label="Đang tải Dashboard" aria-busy="true" className="space-y-6">
      <div className="h-14 animate-pulse rounded-2xl bg-zinc-200" />
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        {overview.map((item) => (
          <div key={item.key} className="h-28 animate-pulse rounded-2xl bg-zinc-200" />
        ))}
      </div>
      <div className="grid gap-6 xl:grid-cols-2">
        <div className="h-64 animate-pulse rounded-2xl bg-zinc-200" />
        <div className="h-64 animate-pulse rounded-2xl bg-zinc-200" />
      </div>
    </div>
  );
}

export default function AdminDashboardPage() {
  const [dashboard, setDashboard] = useState<AdminDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    let active = true;
    void Promise.resolve()
      .then(() => {
        const token = getAdminToken();
        if (!token) throw new Error("Không tìm thấy phiên quản trị. Vui lòng đăng nhập lại.");
        return getAdminDashboard(token);
      })
      .then((result) => {
        if (!active) return;
        setDashboard(result);
        setError("");
      })
      .catch((reason: unknown) => {
        if (!active) return;
        setDashboard(null);
        setError(reason instanceof Error ? reason.message : "Không thể tải dữ liệu Dashboard.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [reloadKey]);

  function retry() {
    setError("");
    setLoading(true);
    setReloadKey((current) => current + 1);
  }

  if (loading) return <DashboardSkeleton />;

  if (error || !dashboard) {
    return (
      <section className="space-y-4">
        <header>
          <h1 className="text-2xl font-semibold">Tổng quan quản trị</h1>
          <p className="mt-1 text-sm text-zinc-500">Không thể tải dữ liệu Dashboard.</p>
        </header>
        <div role="alert" className="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
          <p>{error || "API không trả về dữ liệu Dashboard."}</p>
          <button
            type="button"
            onClick={retry}
            className="mt-4 rounded-full border border-red-300 px-4 py-2 font-medium hover:bg-red-100"
          >
            Thử lại
          </button>
        </div>
      </section>
    );
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold">Tổng quan quản trị</h1>
        <p className="mt-1 text-sm text-zinc-500">Số liệu được cập nhật từ hệ thống.</p>
      </header>

      <section aria-label="Thống kê tổng quan" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        {overview.map((item) => (
          <Link
            key={item.key}
            href={item.href}
            className="rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-400"
          >
            <p className="text-sm text-zinc-500">{item.label}</p>
            <p className="mt-3 text-3xl font-semibold">{numberFormatter.format(dashboard.totals[item.key])}</p>
          </Link>
        ))}
      </section>

      <section aria-label="Giá trị đơn đã giao" className="grid gap-4 md:grid-cols-2">
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-6">
          <p className="text-sm text-zinc-500">Giá trị đơn đã giao toàn thời gian</p>
          <p className="mt-3 text-2xl font-semibold sm:text-3xl">{formatAmount(dashboard.delivered_order_value.all_time)}</p>
        </div>
        <div className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-6">
          <p className="text-sm text-zinc-500">Giá trị đơn đã giao trong {formatMonth(dashboard.delivered_order_value.month)}</p>
          <p className="mt-3 text-2xl font-semibold sm:text-3xl">{formatAmount(dashboard.delivered_order_value.this_month)}</p>
        </div>
      </section>
      <p className="-mt-4 text-xs text-zinc-500">Đây là giá trị đơn đã giao, không phải doanh thu thực nhận.</p>

      <section className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-6">
        <div className="mb-4">
          <h2 className="text-lg font-semibold">Trạng thái đơn hàng</h2>
          <p className="mt-1 text-sm text-zinc-500">Số lượng theo trạng thái hiện tại trong hệ thống.</p>
        </div>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
          {Object.entries(dashboard.order_status_counts).map(([status, count]) => (
            <div key={status} className="rounded-xl border border-zinc-200 bg-stone-50 p-4">
              <p className="text-sm text-zinc-600">{status}</p>
              <p className="mt-2 text-2xl font-semibold">{numberFormatter.format(count)}</p>
            </div>
          ))}
        </div>
        {dashboard.other_order_statuses.length > 0 && (
          <div className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <h3 className="font-medium text-amber-950">Trạng thái khác cần kiểm tra</h3>
            <ul className="mt-2 space-y-1 text-sm text-amber-900">
              {dashboard.other_order_statuses.map((item) => (
                <li key={item.status} className="flex flex-wrap justify-between gap-2">
                  <span>{item.status || "(trạng thái trống)"}</span>
                  <span>{numberFormatter.format(item.count)} đơn</span>
                </li>
              ))}
            </ul>
          </div>
        )}
      </section>

      <section className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-6">
        <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
          <div>
            <h2 className="text-lg font-semibold">Đơn hàng gần đây</h2>
            <p className="mt-1 text-sm text-zinc-500">Tối đa 10 đơn mới nhất.</p>
          </div>
          <Link href="/admin/orders" className="text-sm font-medium underline underline-offset-4">
            Mở quản lý đơn hàng
          </Link>
        </div>
        {dashboard.recent_orders.length === 0 ? (
          <p className="rounded-xl bg-stone-50 px-4 py-10 text-center text-sm text-zinc-500">Chưa có đơn hàng.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-[700px] w-full text-left text-sm">
              <thead className="bg-stone-100 text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                  <th className="px-4 py-3">Mã đơn</th>
                  <th className="px-4 py-3">Khách hàng</th>
                  <th className="px-4 py-3">Ngày đặt</th>
                  <th className="px-4 py-3">Trạng thái</th>
                  <th className="px-4 py-3 text-right">Tổng tiền</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-100">
                {dashboard.recent_orders.map((order) => (
                  <tr key={order.id}>
                    <td className="whitespace-nowrap px-4 py-3 font-medium">
                      <Link href="/admin/orders" className="underline underline-offset-4">
                        {order.order_code || `#${order.id}`}
                      </Link>
                    </td>
                    <td className="px-4 py-3">
                      {order.customer?.name ?? <span className="text-zinc-500">Khách hàng không còn tồn tại</span>}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3">{formatDate(order.created_at)}</td>
                    <td className="px-4 py-3">{order.status}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-right">{formatAmount(order.total)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {dashboard.best_sellers.length > 0 && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 sm:p-6">
          <h2 className="text-lg font-semibold">Sản phẩm bán chạy</h2>
          <ul className="mt-4 divide-y divide-zinc-100">
            {dashboard.best_sellers.slice(0, 4).map((product) => (
              <li key={product.id} className="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                <span className="font-medium">{product.name}</span>
                <span className="text-sm text-zinc-500">{numberFormatter.format(product.quantity_sold)} sản phẩm đã bán</span>
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
}
