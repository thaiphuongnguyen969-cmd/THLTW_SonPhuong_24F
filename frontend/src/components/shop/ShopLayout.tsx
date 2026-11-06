import Link from "next/link";
import type { ReactNode } from "react";
import { ShopCartProvider } from "@/lib/shop-cart-context";
import { CustomerAuthProvider } from "@/lib/customer-auth-context";
import { ShopHeader } from "@/components/shop/ShopHeader";

export function ShopLayout({ children }: { children: ReactNode }) {
  return (
    <CustomerAuthProvider>
      <ShopCartProvider>
      <div className="shop-theme flex min-h-screen flex-col">
        <ShopHeader />
        <div className="flex-1">{children}</div>
        <footer className="bg-zinc-950 text-zinc-200">
          <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
              <Link href="/" className="text-lg font-semibold tracking-wide text-white">Sơn Phương Model</Link>
              <p className="mt-3 max-w-sm text-sm leading-6 text-zinc-400">Cửa hàng mô hình Sơn Phương Model.</p>
            </div>
            <div>
              <h2 className="text-sm font-semibold text-white">Hỗ trợ khách hàng</h2>
              <p className="mt-3 text-sm leading-6 text-zinc-400">Thông tin hỗ trợ sẽ được cập nhật.</p>
            </div>
            <div>
              <h2 className="text-sm font-semibold text-white">Liên kết</h2>
              <div className="mt-3 flex flex-col items-start gap-2 text-sm text-zinc-400">
                <Link href="/products" className="hover:text-white">Sản phẩm</Link>
                <Link href="/account" className="hover:text-white">Tài khoản</Link>
                <Link href="/cart" className="hover:text-white">Giỏ hàng</Link>
              </div>
            </div>
          </div>
          <div className="border-t border-white/10 px-4 py-4 text-center text-xs text-zinc-500">© Sơn Phương Model</div>
        </footer>
      </div>
      </ShopCartProvider>
    </CustomerAuthProvider>
  );
}
