"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import type { FormEvent } from "react";
import { getApiErrorMessage } from "@/lib/api/client";
import { useCustomerAuth } from "@/lib/customer-auth-context";

export function CustomerAuthForm({ mode }: { mode: "login" | "register" }) {
  const isRegister = mode === "register";
  const router = useRouter();
  const auth = useCustomerAuth();
  const [showPassword, setShowPassword] = useState(false);
  const [notice, setNotice] = useState("");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    if (isRegister && formData.get("password") !== formData.get("password_confirmation")) {
      setError("Mật khẩu xác nhận chưa khớp.");
      return;
    }
    setError("");
    setNotice("");
    setSubmitting(true);
    try {
      const email = String(formData.get("email") ?? "").trim();
      const password = String(formData.get("password") ?? "");
      if (isRegister) {
        const name = String(formData.get("name") ?? "").trim();
        const phone = String(formData.get("phone") ?? "").trim();
        await auth.register({ name, email, password, ...(phone ? { phone } : {}) });
        setNotice("Đăng ký thành công. Vui lòng đăng nhập để tiếp tục.");
        form.reset();
      } else {
        await auth.login({ email, password });
        const requestedNext = new URLSearchParams(window.location.search).get("next");
        let safeNext = "/account";
        if (requestedNext?.startsWith("/") && !requestedNext.startsWith("//")) {
          const parsedNext = new URL(requestedNext, window.location.origin);
          if (parsedNext.origin === window.location.origin) {
            safeNext = `${parsedNext.pathname}${parsedNext.search}${parsedNext.hash}`;
          }
        }
        router.replace(safeNext);
      }
    } catch (submitError) {
      setError(getApiErrorMessage(submitError, isRegister ? "Đăng ký thất bại." : "Đăng nhập thất bại."));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="mx-auto flex min-h-[65vh] max-w-6xl items-center justify-center px-4 py-10 sm:px-6">
      <section className="grid w-full max-w-4xl overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm md:grid-cols-[0.85fr_1.15fr]">
        <div className="hidden flex-col justify-between bg-zinc-900 p-8 text-white md:flex">
          <Link href="/" className="text-lg font-semibold tracking-wide">Sơn Phương Model</Link>
          <div><p className="text-xs uppercase tracking-[0.2em] text-zinc-400">Tài khoản khách hàng</p><h1 className="mt-3 text-3xl font-semibold">{isRegister ? "Tạo tài khoản" : "Chào mừng trở lại"}</h1><p className="mt-3 text-sm leading-6 text-zinc-300">{isRegister ? "Tạo tài khoản khách hàng tại Sơn Phương Model." : "Đăng nhập để xem thông tin tài khoản của bạn."}</p></div>
          <Link href="/" className="text-sm text-zinc-300 hover:text-white">← Quay về cửa hàng</Link>
        </div>

        <div className="p-6 sm:p-9">
          <Link href="/" className="text-sm font-semibold text-zinc-700 md:hidden">← Sơn Phương Model</Link>
          <p className="mt-5 text-xs uppercase tracking-widest text-zinc-500">{isRegister ? "Khách hàng mới" : "Tài khoản khách hàng"}</p>
          <h2 className="mt-2 text-3xl font-semibold">{isRegister ? "Đăng ký" : "Đăng nhập Sơn Phương Model"}</h2>
          <p className="mt-3 text-sm leading-6 text-zinc-600">{isRegister ? "Thông tin đăng ký được gửi an toàn đến hệ thống." : "Đăng nhập bằng email và mật khẩu tài khoản của bạn."}</p>

          <form onSubmit={handleSubmit} className="mt-7 space-y-4">
            {isRegister && <label className="block text-sm font-medium">Họ và tên<input name="name" autoComplete="name" required maxLength={100} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>}
            <label className="block text-sm font-medium">Email<input name="email" type="email" autoComplete="email" required maxLength={100} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>
            {isRegister && <label className="block text-sm font-medium">Số điện thoại (không bắt buộc)<input name="phone" type="tel" autoComplete="tel" maxLength={20} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>}
            <label className="block text-sm font-medium">Mật khẩu
              <span className="mt-2 flex rounded-xl border border-zinc-300 focus-within:border-zinc-700">
                <input name="password" type={showPassword ? "text" : "password"} autoComplete={isRegister ? "new-password" : "current-password"} required minLength={isRegister ? 6 : undefined} className="min-w-0 flex-1 rounded-l-xl px-4 py-3 font-normal outline-none" />
                <button type="button" onClick={() => setShowPassword((visible) => !visible)} aria-label={showPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"} className="px-4 text-xs text-zinc-600">{showPassword ? "Ẩn" : "Hiện"}</button>
              </span>
            </label>
            {isRegister && <label className="block text-sm font-medium">Xác nhận mật khẩu<input name="password_confirmation" type={showPassword ? "text" : "password"} autoComplete="new-password" required minLength={6} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" /></label>}
            <button type="submit" disabled={submitting || auth.loading} className="w-full rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60">{submitting ? "Đang xử lý…" : isRegister ? "Tạo tài khoản" : "Đăng nhập"}</button>
          </form>

          {error && <p role="alert" className="mt-4 rounded-xl bg-red-50 p-3 text-sm leading-6 text-red-800">{error}</p>}
          {notice && <p role="status" className="mt-4 rounded-xl bg-green-50 p-3 text-sm leading-6 text-green-900">{notice} {isRegister && <Link className="font-medium underline" href="/login">Đăng nhập</Link>}</p>}
          <p className="mt-6 text-center text-sm text-zinc-600">
            {isRegister ? "Đã có tài khoản?" : "Chưa có tài khoản?"}{" "}
            <Link href={isRegister ? "/login" : "/register"} className="font-medium text-zinc-900 underline">{isRegister ? "Đăng nhập" : "Đăng ký"}</Link>
          </p>
        </div>
      </section>
    </main>
  );
}
