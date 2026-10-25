"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { getApiErrorMessage } from "@/lib/api/client";
import { getMenus } from "@/lib/api/menus";
import { useShopCart } from "@/lib/shop-cart-context";
import { useCustomerAuth } from "@/lib/customer-auth-context";
import type { Menu } from "@/types/menu";

export function ShopHeader() {
  const { cart } = useShopCart();
  const auth = useCustomerAuth();
  const [menuOpen, setMenuOpen] = useState(false);
  const [menus, setMenus] = useState<Menu[]>([]);
  const [menuLoading, setMenuLoading] = useState(true);
  const [menuError, setMenuError] = useState("");
  const [authNotice, setAuthNotice] = useState("");
  const [loggingOut, setLoggingOut] = useState(false);

  async function handleLogout() {
    setLoggingOut(true);
    const result = await auth.logout();
    setAuthNotice(result.serverConfirmed ? "" : result.message);
    setLoggingOut(false);
  }

  useEffect(() => {
    const controller = new AbortController();

    void getMenus(controller.signal)
      .then((items) => {
        setMenus(items);
        setMenuError("");
      })
      .catch((error: unknown) => {
        if (!controller.signal.aborted) {
          setMenuError(getApiErrorMessage(error, "Không thể tải menu."));
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) setMenuLoading(false);
      });

    return () => controller.abort();
  }, []);

  function renderMenuItems(mobile = false) {
    const linkClassName = mobile
      ? "rounded-lg px-3 py-2 hover:bg-stone-100"
      : "transition hover:text-zinc-950";

    if (menuLoading) {
      return <span role="status" className="text-sm text-zinc-500">Đang tải menu…</span>;
    }
    if (menuError) {
      return <span role="status" className="text-sm text-zinc-500">Không thể tải menu</span>;
    }
    if (menus.length === 0) {
      return <span className="text-sm text-zinc-500">Chưa có mục điều hướng</span>;
    }

    return menus.map((menu) => {
      if (menu.link_type === "internal") {
        return (
          <Link key={menu.id} className={linkClassName} href={menu.url} onClick={mobile ? () => setMenuOpen(false) : undefined}>
            {menu.name}
          </Link>
        );
      }

      return (
        <a key={menu.id} className={linkClassName} href={menu.url} target="_blank" rel="noopener noreferrer" onClick={mobile ? () => setMenuOpen(false) : undefined}>
          {menu.name}
        </a>
      );
    });
  }

  return (
    <header className="shop-header sticky top-0 z-40 backdrop-blur">
      <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <Link href="/" className="flex items-center gap-3 text-base font-semibold tracking-wide sm:text-lg">
          <span className="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-900 text-xs text-white">SP</span>
          Sơn Phương Model
        </Link>
        <nav className="hidden items-center gap-6 text-sm text-zinc-600 lg:flex" aria-label="Điều hướng chính">
          {renderMenuItems()}
        </nav>
        <div className="order-3 flex w-full items-center gap-2 md:order-none md:w-auto">
          <form action="/products" className="flex min-w-0 flex-1 md:w-52">
            <label htmlFor="shop-search" className="sr-only">Tìm kiếm sản phẩm</label>
            <input id="shop-search" name="q" placeholder="Tìm sản phẩm" className="ui-control rounded-full bg-stone-50 py-2" />
          </form>
          {!auth.loading && auth.user ? <>
            <Link href="/account" aria-label="Tài khoản" className="hidden rounded-full border border-zinc-200 px-3 py-2 text-sm sm:inline-flex">Tài khoản</Link>
            <button type="button" onClick={() => void handleLogout()} disabled={loggingOut} className="rounded-full border border-zinc-200 px-3 py-2 text-sm disabled:opacity-60">{loggingOut ? "Đang đăng xuất…" : "Đăng xuất"}</button>
          </> : !auth.loading && <Link href="/login" className="rounded-full border border-zinc-200 px-3 py-2 text-sm">Đăng nhập</Link>}
          <Link href="/cart" className="rounded-full bg-zinc-900 px-3 py-2 text-sm text-white">
            <span aria-hidden="true">🛒 </span>Giỏ hàng <span className="tabular-nums">({cart.total_quantity})</span>
          </Link>
        </div>
        <button type="button" className="rounded-full border border-zinc-200 px-3 py-2 text-sm lg:hidden" aria-expanded={menuOpen} aria-label={menuOpen ? "Đóng menu" : "Mở menu"} onClick={() => setMenuOpen((open) => !open)}>
          {menuOpen ? "Đóng" : "Menu"}
        </button>
      </div>
      {authNotice && <p role="status" className="mx-auto max-w-7xl px-4 pb-2 text-sm text-amber-800 sm:px-6 lg:px-8">{authNotice}</p>}
      {menuOpen && (
        <nav className="grid gap-1 border-t border-zinc-100 px-4 py-3 text-sm text-zinc-700 lg:hidden" aria-label="Điều hướng di động">
          {renderMenuItems(true)}
          <Link className="rounded-lg px-3 py-2 hover:bg-stone-100" href="/account" onClick={() => setMenuOpen(false)}>Tài khoản</Link>
          {!auth.loading && auth.user && <button type="button" disabled={loggingOut} className="rounded-lg px-3 py-2 text-left hover:bg-stone-100 disabled:opacity-60" onClick={() => { setMenuOpen(false); void handleLogout(); }}>{loggingOut ? "Đang đăng xuất…" : "Đăng xuất"}</button>}
          {!auth.loading && !auth.user && <Link className="rounded-lg px-3 py-2 hover:bg-stone-100" href="/login" onClick={() => setMenuOpen(false)}>Đăng nhập</Link>}
        </nav>
      )}
    </header>
  );
}
