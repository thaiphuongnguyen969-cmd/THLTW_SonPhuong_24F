"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { getApiErrorMessage } from "@/lib/api/client";
import {
  resetPassword,
  sendPasswordResetOtp,
  verifyPasswordResetOtp,
} from "@/lib/api/auth";

type Step = "email" | "otp" | "password";

export function ForgotPasswordForm() {
  const router = useRouter();
  const [step, setStep] = useState<Step>("email");
  const [email, setEmail] = useState("");
  const [resetToken, setResetToken] = useState("");
  const [otp, setOtp] = useState("");
  const [secondsLeft, setSecondsLeft] = useState(0);
  const [resendSeconds, setResendSeconds] = useState(0);
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);
  const [notice, setNotice] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    if (step !== "otp" || secondsLeft <= 0) return;
    const timer = window.setInterval(() => setSecondsLeft((seconds) => Math.max(0, seconds - 1)), 1000);
    return () => window.clearInterval(timer);
  }, [step, secondsLeft]);

  useEffect(() => {
    if (step !== "otp" || resendSeconds <= 0) return;
    const timer = window.setInterval(() => setResendSeconds((seconds) => Math.max(0, seconds - 1)), 1000);
    return () => window.clearInterval(timer);
  }, [step, resendSeconds]);

  function showError(requestError: unknown) {
    setError(getApiErrorMessage(requestError, "Không thể hoàn tất yêu cầu. Vui lòng thử lại."));
    setNotice("");
  }

  async function handleSendOtp(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setNotice("");
    setSubmitting(true);
    try {
      const response = await sendPasswordResetOtp(email.trim());
      setEmail(email.trim());
      setNotice(response.message);
      setSecondsLeft(300);
      setResendSeconds(60);
      setStep("otp");
    } catch (requestError) {
      showError(requestError);
    } finally {
      setSubmitting(false);
    }
  }

  async function handleVerifyOtp(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setNotice("");
    setSubmitting(true);
    try {
      const response = await verifyPasswordResetOtp(email, otp);
      setResetToken(response.reset_token);
      setSecondsLeft(response.expires_in);
      setStep("password");
    } catch (requestError) {
      showError(requestError);
    } finally {
      setSubmitting(false);
    }
  }

  async function handleResetPassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const password = String(formData.get("password") ?? "");
    const passwordConfirmation = String(formData.get("password_confirmation") ?? "");
    if (password !== passwordConfirmation) {
      setError("Mật khẩu xác nhận chưa khớp.");
      setNotice("");
      return;
    }

    setError("");
    setNotice("");
    setSubmitting(true);
    try {
      const response = await resetPassword({
        reset_token: resetToken,
        password,
        password_confirmation: passwordConfirmation,
      });
      setSuccess(true);
      setNotice(response.message);
      window.setTimeout(() => router.replace("/login"), 2000);
    } catch (requestError) {
      showError(requestError);
    } finally {
      setSubmitting(false);
    }
  }

  async function handleResendOtp() {
    setError("");
    setNotice("");
    setSubmitting(true);
    try {
      const response = await sendPasswordResetOtp(email);
      setNotice(response.message);
      setOtp("");
      setSecondsLeft(300);
      setResendSeconds(60);
    } catch (requestError) {
      showError(requestError);
      if ((requestError as { response?: { status?: number } })?.response?.status === 429) {
        setResendSeconds(60);
      }
    } finally {
      setSubmitting(false);
    }
  }

  const stepNumber = step === "email" ? 1 : step === "otp" ? 2 : 3;

  return (
    <main className="mx-auto flex min-h-[65vh] max-w-6xl items-center justify-center px-4 py-10 sm:px-6">
      <section className="grid w-full max-w-4xl overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm md:grid-cols-[0.85fr_1.15fr]">
        <div className="hidden flex-col justify-between bg-zinc-900 p-8 text-white md:flex">
          <Link href="/" className="text-lg font-semibold tracking-wide">Sơn Phương Model</Link>
          <div>
            <p className="text-xs uppercase tracking-[0.2em] text-zinc-400">Bảo mật tài khoản</p>
            <h1 className="mt-3 text-3xl font-semibold">Khôi phục mật khẩu</h1>
            <p className="mt-3 text-sm leading-6 text-zinc-300">Xác minh email để tạo mật khẩu mới an toàn.</p>
          </div>
          <Link href="/login" className="text-sm text-zinc-300 hover:text-white">← Quay lại đăng nhập</Link>
        </div>

        <div className="p-6 sm:p-9">
          <Link href="/" className="text-sm font-semibold text-zinc-700 md:hidden">← Sơn Phương Model</Link>
          <p className="mt-5 text-xs uppercase tracking-widest text-zinc-500">Bước {stepNumber} / 3</p>
          <h2 className="mt-2 text-3xl font-semibold">
            {success ? "Hoàn tất" : step === "email" ? "Quên mật khẩu?" : step === "otp" ? "Xác minh email" : "Tạo mật khẩu mới"}
          </h2>
          <p className="mt-3 text-sm leading-6 text-zinc-600">
            {success
              ? "Bạn sẽ được chuyển về trang đăng nhập trong giây lát."
              : step === "email"
                ? "Nhập email đã đăng ký để nhận mã xác minh."
                : step === "otp"
                  ? `Nhập mã 6 chữ số đã gửi đến ${email}.`
                  : "OTP đã được xác minh. Hãy chọn mật khẩu mới."}
          </p>

          {!success && step === "email" && (
            <form onSubmit={handleSendOtp} className="mt-7 space-y-4">
              <label className="block text-sm font-medium">Email
                <input name="email" type="email" autoComplete="email" required maxLength={100} value={email} onChange={(event) => setEmail(event.target.value)} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" />
              </label>
              <button type="submit" disabled={submitting} className="w-full rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60">{submitting ? "Đang gửi…" : "Gửi mã OTP"}</button>
            </form>
          )}

          {!success && step === "otp" && (
            <form onSubmit={handleVerifyOtp} className="mt-7 space-y-4">
              <label className="block text-sm font-medium">Mã OTP
                <input name="otp" type="text" inputMode="numeric" autoComplete="one-time-code" pattern="[0-9]{6}" maxLength={6} required value={otp} onChange={(event) => setOtp(event.target.value.replace(/\D/g, "").slice(0, 6))} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 text-center text-xl tracking-[0.5em] outline-none focus:border-zinc-700" />
              </label>
              <p className="text-sm text-zinc-600">Mã còn hiệu lực {Math.floor(secondsLeft / 60)}:{String(secondsLeft % 60).padStart(2, "0")}.</p>
              <button type="submit" disabled={submitting || otp.length !== 6} className="w-full rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60">{submitting ? "Đang xác minh…" : "Xác minh OTP"}</button>
              <button type="button" onClick={handleResendOtp} disabled={submitting || resendSeconds > 0} className="w-full rounded-full border border-zinc-300 px-5 py-3 text-sm font-medium text-zinc-800 disabled:cursor-not-allowed disabled:opacity-60">{resendSeconds > 0 ? `Gửi lại mã sau ${resendSeconds}s` : "Gửi lại OTP"}</button>
            </form>
          )}

          {!success && step === "password" && (
            <form onSubmit={handleResetPassword} className="mt-7 space-y-4">
              <label className="block text-sm font-medium">Mật khẩu mới
                <span className="mt-2 flex rounded-xl border border-zinc-300 focus-within:border-zinc-700">
                  <input name="password" type={showPassword ? "text" : "password"} autoComplete="new-password" required minLength={8} maxLength={255} className="min-w-0 flex-1 rounded-l-xl px-4 py-3 font-normal outline-none" />
                  <button type="button" onClick={() => setShowPassword((visible) => !visible)} aria-label={showPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"} className="px-4 text-xs text-zinc-600">{showPassword ? "Ẩn" : "Hiện"}</button>
                </span>
              </label>
              <label className="block text-sm font-medium">Xác nhận mật khẩu
                <input name="password_confirmation" type={showPassword ? "text" : "password"} autoComplete="new-password" required minLength={8} maxLength={255} className="mt-2 w-full rounded-xl border border-zinc-300 px-4 py-3 font-normal outline-none focus:border-zinc-700" />
              </label>
              <button type="submit" disabled={submitting || secondsLeft <= 0} className="w-full rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60">{submitting ? "Đang cập nhật…" : "Đặt mật khẩu mới"}</button>
              {secondsLeft <= 0 && <p role="alert" className="text-sm text-red-800">Phiên xác minh đã hết hạn. Vui lòng gửi OTP mới.</p>}
            </form>
          )}

          {error && <p role="alert" className="mt-4 rounded-xl bg-red-50 p-3 text-sm leading-6 text-red-800">{error}</p>}
          {notice && <p role="status" className="mt-4 rounded-xl bg-green-50 p-3 text-sm leading-6 text-green-900">{notice}</p>}
          {success && <Link href="/login" className="mt-5 block w-full rounded-full bg-zinc-900 px-5 py-3 text-center text-sm font-medium text-white">Về trang đăng nhập</Link>}
          {!success && step !== "email" && <button type="button" onClick={() => { setStep("email"); setError(""); setNotice(""); }} className="mt-5 w-full text-center text-sm font-medium text-zinc-700 underline">Đổi email</button>}
          <p className="mt-6 text-center text-sm text-zinc-600">
            Nhớ mật khẩu rồi? <Link href="/login" className="font-medium text-zinc-900 underline">Đăng nhập</Link>
          </p>
        </div>
      </section>
    </main>
  );
}
