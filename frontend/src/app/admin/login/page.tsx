"use client";

import Link from "next/link";
import { useState } from "react";
import type { FormEvent } from "react";

export default function AdminLoginPage() {
  const [showPassword, setShowPassword] = useState(false);
  const [notice, setNotice] = useState("");
  const [busy, setBusy] = useState(false);

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setNotice("");
    window.setTimeout(() => {
      setBusy(false);
      setNotice("Đây là giao diện demo. Xác thực quản trị chưa được kết nối; không có phiên đăng nhập nào được tạo.");
    }, 400);
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
        <p className="mb-5 rounded-xl bg-amber-50 p-3 text-sm leading-6 text-amber-900">Xác thực thật chưa được kết nối. Không nhập mật khẩu thật vào biểu mẫu demo này.</p>
        <form onSubmit={submit} className="space-y-5">
          <label className="block text-sm font-medium text-zinc-700">Email hoặc tên đăng nhập<input name="identity" type="text" autoComplete="username" required className="mt-2 w-full rounded-xl border border-zinc-300 bg-stone-50 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>
          <label className="block text-sm font-medium text-zinc-700">Mật khẩu
            <span className="mt-2 flex rounded-xl border border-zinc-300 bg-stone-50 focus-within:border-zinc-700">
              <input name="password" type={showPassword ? "text" : "password"} autoComplete="current-password" required className="min-w-0 flex-1 rounded-l-xl bg-transparent px-4 py-3 font-normal outline-none" />
              <button type="button" onClick={() => setShowPassword((value) => !value)} aria-label={showPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"} className="px-4 text-xs text-zinc-600">{showPassword ? "Ẩn" : "Hiện"}</button>
            </span>
          </label>
          <button type="submit" disabled={busy} className="w-full rounded-full bg-zinc-900 px-6 py-3 font-medium text-white disabled:cursor-wait disabled:opacity-70">{busy ? "Đang kiểm tra demo…" : "Thử đăng nhập demo"}</button>
        </form>
        {notice && <p role="status" className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">{notice}</p>}
      </section>
    </main>
  );
}
