"use client";

import Link from "next/link";
import { useState } from "react";
import type { FormEvent } from "react";
import { AccountAuthGate } from "@/components/shop/AccountAuthGate";
import { getApiErrorMessage } from "@/lib/api/client";
import { useCustomerAuth } from "@/lib/customer-auth-context";

function AccountContent() {
  const { user, updateProfile } = useCustomerAuth();
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [fields, setFields] = useState({ name: "", email: "", phone: "", address: "" });
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  if (!user) return null;
  const currentUser = user;

  function startEditing() {
    setFields({
      name: currentUser.name,
      email: currentUser.email,
      phone: currentUser.phone ?? "",
      address: currentUser.address ?? "",
    });
    setError("");
    setNotice("");
    setEditing(true);
  }

  function cancelEditing() {
    if (saving) return;
    setEditing(false);
    setError("");
    setNotice("");
  }

  async function saveProfile(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setError("");
    setNotice("");

    try {
      await updateProfile({
        name: fields.name.trim(),
        email: fields.email.trim(),
        phone: fields.phone.trim() || null,
        address: fields.address.trim() || null,
      });
      setEditing(false);
      setNotice("Đã cập nhật thông tin cá nhân.");
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, "Không thể cập nhật thông tin cá nhân."));
    } finally {
      setSaving(false);
    }
  }

  return (
    <main className="shop-account-page mx-auto min-h-[60vh] max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <p className="text-xs uppercase tracking-widest text-zinc-500">Sơn Phương Model</p>
      <h1 className="mt-2 text-3xl font-semibold">Tài khoản khách hàng</h1>

      <section className="shop-account-profile mt-5 rounded-2xl border border-zinc-200 bg-white p-5">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 className="text-lg font-semibold">Xin chào, {user.name}</h2>
            <p className="mt-1 text-sm text-zinc-600">{user.email}</p>
          </div>
          {!editing && <button type="button" onClick={startEditing} className="shop-account-action rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-stone-100">Chỉnh sửa thông tin</button>}
        </div>

        {notice && <p role="status" className="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</p>}
        {error && <p role="alert" className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800">{error}</p>}

        {editing ? (
          <form onSubmit={saveProfile} className="shop-account-form mt-5 grid gap-4 sm:grid-cols-2">
            <label className="text-sm font-medium">Họ tên
              <input required maxLength={100} autoComplete="name" value={fields.name} onChange={(event) => setFields({ ...fields, name: event.target.value })} className="shop-account-control mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-normal outline-none focus:border-zinc-700" />
            </label>
            <label className="text-sm font-medium">Email
              <input required type="email" maxLength={100} autoComplete="email" value={fields.email} onChange={(event) => setFields({ ...fields, email: event.target.value })} className="shop-account-control mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-normal outline-none focus:border-zinc-700" />
            </label>
            <label className="text-sm font-medium">Số điện thoại
              <input type="tel" maxLength={20} autoComplete="tel" value={fields.phone} onChange={(event) => setFields({ ...fields, phone: event.target.value })} className="shop-account-control mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-normal outline-none focus:border-zinc-700" />
            </label>
            <label className="text-sm font-medium">Địa chỉ
              <input maxLength={255} autoComplete="street-address" value={fields.address} onChange={(event) => setFields({ ...fields, address: event.target.value })} className="shop-account-control mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2.5 font-normal outline-none focus:border-zinc-700" />
            </label>
            <div className="flex gap-2 sm:col-span-2">
              <button type="submit" disabled={saving} className="shop-commerce-cta rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60">{saving ? "Đang lưu…" : "Lưu thay đổi"}</button>
              <button type="button" onClick={cancelEditing} disabled={saving} className="shop-account-action rounded-full border border-zinc-300 px-5 py-2.5 text-sm font-medium hover:bg-stone-100 disabled:cursor-not-allowed disabled:opacity-60">Hủy</button>
            </div>
          </form>
        ) : (
          <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
            <div><dt className="text-zinc-500">Số điện thoại</dt><dd className="mt-1 font-medium">{user.phone || "Chưa cập nhật"}</dd></div>
            <div><dt className="text-zinc-500">Địa chỉ</dt><dd className="mt-1 font-medium">{user.address || "Chưa cập nhật"}</dd></div>
          </dl>
        )}
      </section>

      <div className="shop-account-links mt-7 grid gap-4 sm:grid-cols-2">
        <Link href="/account/orders" className="shop-commerce-card rounded-2xl border border-zinc-200 bg-white p-5"><h2 className="font-semibold">Đơn hàng</h2><p className="mt-2 text-sm text-zinc-600">Xem trạng thái và lịch sử đơn hàng.</p></Link>
        <Link href="/account/addresses" className="shop-commerce-card rounded-2xl border border-zinc-200 bg-white p-5"><h2 className="font-semibold">Địa chỉ nhận hàng</h2><p className="mt-2 text-sm text-zinc-600">Quản lý địa chỉ giao hàng.</p></Link>
      </div>
      <p className="mt-5 text-sm text-zinc-500">Thông tin đơn hàng và địa chỉ sẽ hiển thị khi các API tương ứng được triển khai.</p>
    </main>
  );
}

export default function AccountPage() {
  return <AccountAuthGate><AccountContent /></AccountAuthGate>;
}
