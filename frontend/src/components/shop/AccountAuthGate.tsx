"use client";

import Link from "next/link";
import { useEffect } from "react";
import { useRouter } from "next/navigation";
import type { ReactNode } from "react";
import { useCustomerAuth } from "@/lib/customer-auth-context";

export function AccountAuthGate({ children }: { children: ReactNode }) {
  const { user, loading, error } = useCustomerAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) router.replace("/login");
  }, [loading, user, router]);

  if (loading) return <main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10" role="status">Đang xác minh tài khoản…</main>;
  if (!user) return (
    <main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10">
      <p role="alert" className="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">{error || "Bạn cần đăng nhập để xem khu vực tài khoản."}</p>
      <Link href="/login" className="mt-4 inline-flex rounded-full bg-zinc-900 px-5 py-3 text-sm text-white">Đăng nhập</Link>
    </main>
  );
  return <>{children}</>;
}
