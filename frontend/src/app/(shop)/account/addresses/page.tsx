"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import type { FormEvent } from "react";
import { AccountAuthGate } from "@/components/shop/AccountAuthGate";
import { createAddress, deleteAddress, getAddresses, setDefaultAddress, updateAddress } from "@/lib/api/addresses";
import type { AddressPayload } from "@/lib/api/addresses";
import { getApiErrorMessage } from "@/lib/api/client";
import { useCustomerAuth } from "@/lib/customer-auth-context";
import type { Address } from "@/types/address";

type AddressFields = Omit<AddressPayload, "phone" | "province" | "district" | "ward"> & {
  phone: string; province: string; district: string; ward: string;
};

const EMPTY_FIELDS: AddressFields = {
  label: "", recipient_name: "", phone: "", province: "", district: "", ward: "", address_detail: "",
};

function AddressContent() {
  const { user, loading: authLoading, getAccessToken, invalidateSession } = useCustomerAuth();
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [editing, setEditing] = useState<Address | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [fields, setFields] = useState<AddressFields>(EMPTY_FIELDS);

  const loadAddresses = useCallback(async () => {
    const token = getAccessToken();
    if (!token) {
      invalidateSession();
      return;
    }
    setLoading(true);
    setError("");
    try {
      setAddresses(await getAddresses(token));
    } catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể tải địa chỉ."));
    } finally {
      setLoading(false);
    }
  }, [getAccessToken, invalidateSession]);

  useEffect(() => {
    if (authLoading || !user) return;
    let active = true;
    queueMicrotask(() => { if (active) void loadAddresses(); });
    return () => { active = false; };
  }, [authLoading, user, loadAddresses]);

  function startCreate() {
    setEditing(null);
    setFields(EMPTY_FIELDS);
    setError("");
    setNotice("");
    setFormOpen(true);
  }

  function startEdit(address: Address) {
    setEditing(address);
    setFields({
      label: address.label,
      recipient_name: address.recipient_name,
      phone: address.phone ?? "",
      province: address.province ?? "",
      district: address.district ?? "",
      ward: address.ward ?? "",
      address_detail: address.address_detail,
    });
    setError("");
    setNotice("");
    setFormOpen(true);
  }

  async function saveAddress(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const token = getAccessToken();
    if (!token) { invalidateSession(); return; }
    const payload: AddressPayload = {
      label: fields.label.trim(),
      recipient_name: fields.recipient_name.trim(),
      phone: fields.phone.trim() || null,
      province: fields.province.trim() || null,
      district: fields.district.trim() || null,
      ward: fields.ward.trim() || null,
      address_detail: fields.address_detail.trim(),
    };
    setSaving(true);
    setError("");
    setNotice("");
    try {
      if (editing) await updateAddress(editing.id, payload, token);
      else await createAddress(payload, token);
      setFormOpen(false);
      setNotice(editing ? "Đã cập nhật địa chỉ." : "Đã thêm địa chỉ.");
      await loadAddresses();
    } catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể lưu địa chỉ."));
    } finally {
      setSaving(false);
    }
  }

  async function makeDefault(addressId: number) {
    const token = getAccessToken();
    if (!token) { invalidateSession(); return; }
    setError(""); setNotice(""); setSaving(true);
    try {
      await setDefaultAddress(addressId, token);
      setNotice("Đã đặt địa chỉ mặc định.");
      await loadAddresses();
    } catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể đặt địa chỉ mặc định."));
    } finally { setSaving(false); }
  }

  async function remove(address: Address) {
    if (!window.confirm(`Bạn có chắc muốn xóa địa chỉ “${address.label}”?`)) return;
    const token = getAccessToken();
    if (!token) { invalidateSession(); return; }
    setError(""); setNotice(""); setSaving(true);
    try {
      await deleteAddress(address.id, token);
      setNotice("Đã xóa địa chỉ.");
      await loadAddresses();
    } catch (requestError) {
      if ((requestError as { response?: { status?: number } })?.response?.status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể xóa địa chỉ."));
    } finally { setSaving(false); }
  }

  const fieldClass = "mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm";

  return (
    <main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <Link href="/account" className="text-sm text-zinc-500 hover:text-zinc-900">← Tài khoản</Link>
      <div className="mt-4 flex flex-wrap items-end justify-between gap-4"><div><p className="text-xs uppercase tracking-widest text-zinc-500">Tài khoản</p><h1 className="mt-2 text-3xl font-semibold">Địa chỉ nhận hàng</h1></div><button type="button" onClick={startCreate} className="rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white">Thêm địa chỉ</button></div>
      {notice && <p role="status" className="mt-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</p>}
      {error && <p role="alert" className="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800">{error}</p>}
      {formOpen && <form onSubmit={saveAddress} className="mt-6 grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-2">
        <h2 className="text-lg font-semibold sm:col-span-2">{editing ? "Chỉnh sửa địa chỉ" : "Địa chỉ mới"}</h2>
        <label className="text-sm font-medium">Nhãn địa chỉ<input required maxLength={100} value={fields.label} onChange={(e) => setFields({ ...fields, label: e.target.value })} className={fieldClass} placeholder="Nhà riêng, Công ty…" /></label>
        <label className="text-sm font-medium">Người nhận<input required maxLength={100} autoComplete="name" value={fields.recipient_name} onChange={(e) => setFields({ ...fields, recipient_name: e.target.value })} className={fieldClass} /></label>
        <label className="text-sm font-medium">Số điện thoại<input type="tel" maxLength={20} autoComplete="tel" value={fields.phone} onChange={(e) => setFields({ ...fields, phone: e.target.value })} className={fieldClass} /></label>
        <label className="text-sm font-medium">Tỉnh / thành phố<input maxLength={100} value={fields.province} onChange={(e) => setFields({ ...fields, province: e.target.value })} className={fieldClass} /></label>
        <label className="text-sm font-medium">Quận / huyện<input maxLength={100} value={fields.district} onChange={(e) => setFields({ ...fields, district: e.target.value })} className={fieldClass} /></label>
        <label className="text-sm font-medium">Phường / xã<input maxLength={100} value={fields.ward} onChange={(e) => setFields({ ...fields, ward: e.target.value })} className={fieldClass} /></label>
        <label className="text-sm font-medium sm:col-span-2">Địa chỉ chi tiết<textarea required value={fields.address_detail} onChange={(e) => setFields({ ...fields, address_detail: e.target.value })} className={fieldClass} rows={3} /></label>
        <div className="flex gap-3 sm:col-span-2"><button type="submit" disabled={saving} className="rounded-full bg-zinc-900 px-5 py-2.5 text-sm text-white disabled:opacity-50">{saving ? "Đang lưu…" : "Lưu địa chỉ"}</button><button type="button" disabled={saving} onClick={() => setFormOpen(false)} className="rounded-full border px-5 py-2.5 text-sm">Đóng</button></div>
      </form>}
      {loading ? <p role="status" className="mt-7 text-sm text-zinc-500">Đang tải địa chỉ…</p> : addresses.length === 0 ? <div className="mt-7 rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center"><p className="font-medium">{error ? "Chưa tải được danh sách địa chỉ" : "Bạn chưa lưu địa chỉ nào"}</p><p className="mt-2 text-sm text-zinc-500">{error ? "Kiểm tra kết nối rồi thử lại." : "Thêm địa chỉ để sử dụng khi thanh toán."}</p>{error && <button type="button" onClick={() => void loadAddresses()} className="mt-4 text-sm underline">Thử tải lại</button>}</div> : <div className="mt-7 grid gap-4 md:grid-cols-2">{addresses.map((address) => <article key={address.id} className="rounded-2xl border border-zinc-200 bg-white p-5"><div className="flex items-start justify-between gap-3"><div><h2 className="font-semibold">{address.label}</h2>{address.is_default && <span className="mt-2 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">Mặc định</span>}</div><p className="text-sm text-zinc-600">{address.recipient_name}</p></div><p className="mt-3 text-sm text-zinc-700">{address.address_detail}</p><p className="mt-1 text-sm text-zinc-500">{[address.ward, address.district, address.province].filter(Boolean).join(", ")}</p><p className="mt-1 text-sm text-zinc-500">{address.phone || "Chưa có số điện thoại"}</p><div className="mt-5 flex flex-wrap gap-4 text-sm"><button type="button" disabled={saving || address.is_default} onClick={() => void makeDefault(address.id)} className="text-zinc-700 underline disabled:opacity-40">Đặt mặc định</button><button type="button" disabled={saving} onClick={() => startEdit(address)} className="text-zinc-700 underline">Sửa</button><button type="button" disabled={saving} onClick={() => void remove(address)} className="text-red-700 underline disabled:opacity-40">Xóa</button></div></article>)}</div>}
    </main>
  );
}

export default function AccountAddressesPage() {
  return <AccountAuthGate><AddressContent /></AccountAuthGate>;
}
