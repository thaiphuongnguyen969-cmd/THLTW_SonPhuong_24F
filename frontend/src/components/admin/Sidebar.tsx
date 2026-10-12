"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const links = [
  { href: "/admin", label: "Tổng quan" },
  { href: "/admin/products", label: "Quản lý sản phẩm" },
  { href: "/admin/categories", label: "Quản lý danh mục" },
  { href: "/admin/brands", label: "Quản lý thương hiệu" },
  { href: "/admin/orders", label: "Quản lý đơn hàng" },
  { href: "/admin/customers", label: "Khách hàng" },
];

export function Sidebar({ mobile = false, onNavigate }: { mobile?: boolean; onNavigate?: () => void }) {
  const pathname = usePathname();
  return (
    <aside className={`admin-sidebar border-r ${mobile ? "w-full" : "hidden w-64 shrink-0 lg:block"}`}>
      <div className="flex h-20 items-center gap-3 border-b border-zinc-200 px-6">
        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-900 text-xs font-semibold text-white">SP</span>
        <div><p className="text-xs uppercase tracking-[0.2em] text-zinc-500">Quản trị</p><p className="font-semibold text-zinc-900">Sơn Phương Model</p></div>
      </div>
      <nav className="space-y-1 p-4" aria-label="Điều hướng quản trị">
        {links.map((link) => {
          const active = link.href === "/admin" ? pathname === link.href : pathname.startsWith(link.href);
          return <Link key={link.href} href={link.href} onClick={onNavigate} aria-current={active ? "page" : undefined} className={`block rounded-xl px-4 py-3 text-sm font-medium transition ${active ? "bg-zinc-900 text-white" : "text-zinc-600 hover:bg-stone-100 hover:text-zinc-900"}`}>{link.label}</Link>;
        })}
      </nav>
    </aside>
  );
}
