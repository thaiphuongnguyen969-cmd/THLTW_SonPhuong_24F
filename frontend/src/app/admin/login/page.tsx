"use client";

import Link from "next/link";
import { useState } from "react";
import type { FormEvent } from "react";
import { useRouter } from "next/navigation";
import { login, logout } from "@/lib/api/auth";
import { getApiErrorMessage } from "@/lib/api/client";
import { setAdminToken } from "@/lib/admin-auth";

export default function AdminLoginPage() {
  const router = useRouter();
  const [showPassword, setShowPassword] = useState(false);
  const [notice, setNotice] = useState("");
  const [busy, setBusy] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setNotice("");
    const form = new FormData(event.currentTarget);
    try {
      const result = await login({ email: String(form.get("identity") ?? "").trim(), password: String(form.get("password") ?? "") });
      if (result.user.role !== "admin") {
        try { await logout(result.access_token); } catch { /* The role check remains authoritative even if revocation fails. */ }
        throw new Error("Tài khoản này không có quyền quản trị.");
      }
      setAdminToken(result.access_token);
      setBusy(false);
      router.replace("/admin");
    } catch (error) {
      setBusy(false);
      setNotice(getApiErrorMessage(error, "Đăng nhập thất bại."));
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-stone-100 px-4 py-10">
      <section className="w-full max-w-md rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-lg sm:p-9">
        <Link href="/" className="text-sm text-zinc-500 hover:text-zinc-900">← Sơn Phương Model</Link>
        <div className="mb-7 mt-7 text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-900 text-xs font-semibold text-white">SP</div>
          <h1 className="mt-5 text-3xl font-semibold tracking-tight text-zinc-900">Đăng nhập quản trị</h1>
          <p className="mt-2 text-sm text-zinc-500">Sơn Phương Model</p>
        </div>
        <form onSubmit={(event) => void submit(event)} className="space-y-5">
          <label className="block text-sm font-medium text-zinc-700">Email<input name="identity" type="email" autoComplete="username" required className="mt-2 w-full rounded-xl border border-zinc-300 bg-stone-50 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>
          <label className="block text-sm font-medium text-zinc-700">Mật khẩu
            <span className="mt-2 flex rounded-xl border border-zinc-300 bg-stone-50 focus-within:border-zinc-700">
              <input name="password" type={showPassword ? "text" : "password"} autoComplete="current-password" required className="min-w-0 flex-1 rounded-l-xl bg-transparent px-4 py-3 font-normal outline-none" />
              <button type="button" onClick={() => setShowPassword((value) => !value)} aria-label={showPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"} className="px-4 text-xs text-zinc-600">{showPassword ? "Ẩn" : "Hiện"}</button>
            </span>
          </label>
          <button type="submit" disabled={busy} className="w-full rounded-full bg-zinc-900 px-6 py-3 font-medium text-white disabled:cursor-wait disabled:opacity-70">{busy ? "Đang đăng nhập…" : "Đăng nhập"}</button>
        </form>
        {notice && <p role="status" className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">{notice}</p>}
      </section>
    </main>
  );
}
