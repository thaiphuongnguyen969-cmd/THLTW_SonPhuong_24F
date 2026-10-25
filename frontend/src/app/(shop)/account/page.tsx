"use client";

import Link from "next/link";
import { AccountAuthGate } from "@/components/shop/AccountAuthGate";
import { useCustomerAuth } from "@/lib/customer-auth-context";

function AccountContent() {
  const { user } = useCustomerAuth();
  return (
    <main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <p className="text-xs uppercase tracking-widest text-zinc-500">Sơn Phương Model</p>
      <h1 className="mt-2 text-3xl font-semibold">Tài khoản khách hàng</h1>
      <section className="mt-5 rounded-2xl border border-zinc-200 bg-white p-5"><h2 className="text-lg font-semibold">Xin chào, {user?.name}</h2><p className="mt-2 text-sm text-zinc-600">{user?.email}</p>{user?.phone && <p className="mt-1 text-sm text-zinc-600">{user.phone}</p>}</section>
      <div className="mt-7 grid gap-4 sm:grid-cols-2">
        <Link href="/account/orders" className="rounded-2xl border border-zinc-200 bg-white p-5 hover:shadow-sm"><h2 className="font-semibold">Đơn hàng</h2><p className="mt-2 text-sm text-zinc-600">Xem trạng thái và lịch sử đơn hàng.</p></Link>
        <Link href="/account/addresses" className="rounded-2xl border border-zinc-200 bg-white p-5 hover:shadow-sm"><h2 className="font-semibold">Địa chỉ nhận hàng</h2><p className="mt-2 text-sm text-zinc-600">Quản lý địa chỉ giao hàng.</p></Link>
      </div>
      <p className="mt-5 text-sm text-zinc-500">Thông tin đơn hàng và địa chỉ sẽ hiển thị khi các API tương ứng được triển khai.</p>
    </main>
  );
}

export default function AccountPage() {
  return <AccountAuthGate><AccountContent /></AccountAuthGate>;
}
