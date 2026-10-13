"use client";

import { useState } from "react";
import { Sidebar } from "@/components/admin/Sidebar";
import { Topbar } from "@/components/admin/Topbar";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const [menuOpen, setMenuOpen] = useState(false);
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
