"use client";

import Link from "next/link";
import { useState } from "react";
import type { FormEvent } from "react";

export function CustomerAuthForm({ mode }: { mode: "login" | "register" }) {
  const isRegister = mode === "register";
  const [showPassword, setShowPassword] = useState(false);
  const [notice, setNotice] = useState("");

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    if (isRegister && formData.get("password") !== formData.get("password_confirmation")) {
      setNotice("Mật khẩu xác nhận chưa khớp.");
      return;
    }
    setNotice(isRegister
      ? "Biểu mẫu đã được kiểm tra. Đăng ký chưa kết nối với hệ thống."
      : "Đăng nhập chưa được kết nối với hệ thống. Không có thông tin nào được gửi đi.");
  }

  return (
    <main className="mx-auto flex min-h-[65vh] max-w-6xl items-center justify-center px-4 py-10 sm:px-6">
      <section className="grid w-full max-w-4xl overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm md:grid-cols-[0.85fr_1.15fr]">
        <div className="hidden flex-col justify-between bg-zinc-900 p-8 text-white md:flex">
          <Link href="/" className="text-lg font-semibold tracking-wide">Sơn Phương Model</Link>
          <div><p className="text-xs uppercase tracking-[0.2em] text-zinc-400">Tài khoản khách hàng</p><h1 className="mt-3 text-3xl font-semibold">{isRegister ? "Tạo tài khoản" : "Chào mừng trở lại"}</h1><p className="mt-3 text-sm leading-6 text-zinc-300">Giao diện tài khoản mẫu; chức năng xác thực chưa được kết nối.</p></div>
          <Link href="/" className="text-sm text-zinc-300 hover:text-white">← Quay về cửa hàng</Link>
        </div>

        <div className="p-6 sm:p-9">
          <Link href="/" className="text-sm font-semibold text-zinc-700 md:hidden">← Sơn Phương Model</Link>
          <p className="mt-5 text-xs uppercase tracking-widest text-zinc-500">{isRegister ? "Khách hàng mới" : "Tài khoản khách hàng"}</p>
          <h2 className="mt-2 text-3xl font-semibold">{isRegister ? "Đăng ký" : "Đăng nhập Sơn Phương Model"}</h2>
          <p className="mt-3 text-sm leading-6 text-zinc-600">Chưa kết nối xác thực thật. Biểu mẫu này không gửi hoặc lưu thông tin.</p>

          <form onSubmit={handleSubmit} className="mt-7 space-y-4">
            {isRegister && <label className="block text-sm font-medium">Họ và tên<input name="name" autoComplete="name" required maxLength={100} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>}
            <label className="block text-sm font-medium">Email<input name="email" type="email" autoComplete="email" required maxLength={100} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>
            {isRegister && <label className="block text-sm font-medium">Số điện thoại (không bắt buộc)<input name="phone" type="tel" autoComplete="tel" maxLength={20} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>}
            <label className="block text-sm font-medium">Mật khẩu
              <span className="mt-2 flex rounded-xl border border-zinc-300 focus-within:border-zinc-700">
                <input name="password" type={showPassword ? "text" : "password"} autoComplete={isRegister ? "new-password" : "current-password"} required minLength={6} className="min-w-0 flex-1 rounded-l-xl px-4 py-3 font-normal outline-none" />
                <button type="button" onClick={() => setShowPassword((visible) => !visible)} aria-label={showPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"} className="px-4 text-xs text-zinc-600">{showPassword ? "Ẩn" : "Hiện"}</button>
              </span>
            </label>
            {isRegister && <label className="block text-sm font-medium">Xác nhận mật khẩu<input name="password_confirmation" type={showPassword ? "text" : "password"} autoComplete="new-password" required minLength={6} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>}
            <button type="submit" className="w-full rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white hover:bg-zinc-700">{isRegister ? "Tạo tài khoản mẫu" : "Đăng nhập"}</button>
          </form>

          {notice && <p role="status" className="mt-4 rounded-xl bg-amber-50 p-3 text-sm leading-6 text-amber-900">{notice}</p>}
          <p className="mt-6 text-center text-sm text-zinc-600">
            {isRegister ? "Đã có tài khoản?" : "Chưa có tài khoản?"}{" "}
            <Link href={isRegister ? "/login" : "/register"} className="font-medium text-zinc-900 underline">{isRegister ? "Đăng nhập" : "Đăng ký"}</Link>
          </p>
        </div>
      </section>
    </main>
  );
}
