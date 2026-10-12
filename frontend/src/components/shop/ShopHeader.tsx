"use client";

import Link from "next/link";
import { useState } from "react";
import { useShopCart } from "@/lib/shop-cart-context";

export function ShopHeader() {
  const { cart } = useShopCart();
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <header className="shop-header sticky top-0 z-40 backdrop-blur">
      <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <Link href="/" className="flex items-center gap-3 text-base font-semibold tracking-wide sm:text-lg">
          <span className="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-900 text-xs text-white">SP</span>
          Sơn Phương Model
        </Link>
        <nav className="hidden items-center gap-6 text-sm text-zinc-600 lg:flex" aria-label="Điều hướng chính">
          <Link href="/">Trang chủ</Link>
          <Link href="/products">Sản phẩm</Link>
          <Link href="/products#categories">Danh mục</Link>
          <Link href="/products#brands">Thương hiệu</Link>
        </nav>
        <div className="order-3 flex w-full items-center gap-2 md:order-none md:w-auto">
          <form action="/products" className="flex min-w-0 flex-1 md:w-52">
            <label htmlFor="shop-search" className="sr-only">Tìm kiếm sản phẩm</label>
            <input id="shop-search" name="q" placeholder="Tìm sản phẩm" className="ui-control rounded-full bg-stone-50 py-2" />
          </form>
          <Link href="/account" aria-label="Tài khoản" className="hidden rounded-full border border-zinc-200 px-3 py-2 text-sm sm:inline-flex">Tài khoản</Link>
          <Link href="/login" className="rounded-full border border-zinc-200 px-3 py-2 text-sm">Đăng nhập</Link>
          <Link href="/cart" className="rounded-full bg-zinc-900 px-3 py-2 text-sm text-white">
            <span aria-hidden="true">🛒 </span>Giỏ hàng <span className="tabular-nums">({cart.total_quantity})</span>
          </Link>
        </div>
        <button type="button" className="rounded-full border border-zinc-200 px-3 py-2 text-sm lg:hidden" aria-expanded={menuOpen} aria-label={menuOpen ? "Đóng menu" : "Mở menu"} onClick={() => setMenuOpen((open) => !open)}>
          {menuOpen ? "Đóng" : "Menu"}
        </button>
      </div>
      {menuOpen && (
        <nav className="grid gap-1 border-t border-zinc-100 px-4 py-3 text-sm text-zinc-700 lg:hidden" aria-label="Điều hướng di động">
          <Link className="rounded-lg px-3 py-2 hover:bg-stone-100" href="/" onClick={() => setMenuOpen(false)}>Trang chủ</Link>
          <Link className="rounded-lg px-3 py-2 hover:bg-stone-100" href="/products" onClick={() => setMenuOpen(false)}>Sản phẩm</Link>
          <Link className="rounded-lg px-3 py-2 hover:bg-stone-100" href="/products#categories" onClick={() => setMenuOpen(false)}>Danh mục</Link>
          <Link className="rounded-lg px-3 py-2 hover:bg-stone-100" href="/products#brands" onClick={() => setMenuOpen(false)}>Thương hiệu</Link>
          <Link className="rounded-lg px-3 py-2 hover:bg-stone-100" href="/account" onClick={() => setMenuOpen(false)}>Tài khoản</Link>
        </nav>
      )}
    </header>
  );
}
