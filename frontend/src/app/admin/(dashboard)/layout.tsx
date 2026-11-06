"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Sidebar } from "@/components/admin/Sidebar";
import { Topbar } from "@/components/admin/Topbar";
import { clearAdminToken, getAdminToken } from "@/lib/admin-auth";
import { getCurrentUser } from "@/lib/api/auth";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const [menuOpen, setMenuOpen] = useState(false);
  const [authorized, setAuthorized] = useState(false);
  const router = useRouter();

  useEffect(() => {
    let active = true;
    const token = getAdminToken();
    if (!token) {
      router.replace("/admin/login");
      return () => { active = false; };
    }

    void getCurrentUser(token).then((user) => {
      if (!active) return;
      if (user.role === "admin") setAuthorized(true);
      else {
        clearAdminToken();
        router.replace("/admin/login");
      }
    }).catch(() => {
      if (!active) return;
      clearAdminToken();
      router.replace("/admin/login");
    });

    return () => { active = false; };
  }, [router]);

  if (!authorized) {
    return <main className="flex min-h-screen items-center justify-center bg-stone-100 text-sm text-zinc-600">Đang xác minh phiên quản trị…</main>;
  }

  return (
    <div className="admin-theme">
      <div className="flex min-h-screen">
        <Sidebar />
        <div className="flex min-w-0 flex-1 flex-col">
          <Topbar onMenuClick={() => setMenuOpen((open) => !open)} />
          {menuOpen && <div className="fixed inset-0 z-50 bg-zinc-950/40 lg:hidden" onMouseDown={(event) => { if (event.target === event.currentTarget) setMenuOpen(false); }}><div className="h-full w-72 max-w-[85vw] shadow-xl"><Sidebar mobile onNavigate={() => setMenuOpen(false)} /></div></div>}
          <main className="flex-1 p-4 sm:p-6">{children}</main>
        </div>
      </div>
    </div>
  );
}
