import Link from "next/link";

export default function AccountPage() {
  return (
    <main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <p className="text-xs uppercase tracking-widest text-zinc-500">Sơn Phương Model</p>
      <h1 className="mt-2 text-3xl font-semibold">Tài khoản khách hàng</h1>
      <p className="mt-3 max-w-2xl rounded-xl bg-amber-50 p-4 text-sm leading-6 text-amber-900">Khu vực tài khoản mẫu. Chưa có đăng nhập hoặc dữ liệu khách hàng được kết nối.</p>
      <div className="mt-7 grid gap-4 sm:grid-cols-2">
        <Link href="/account/orders" className="rounded-2xl border border-zinc-200 bg-white p-5 hover:shadow-sm"><h2 className="font-semibold">Đơn hàng</h2><p className="mt-2 text-sm text-zinc-600">Xem trạng thái và lịch sử đơn hàng.</p></Link>
        <Link href="/account/addresses" className="rounded-2xl border border-zinc-200 bg-white p-5 hover:shadow-sm"><h2 className="font-semibold">Địa chỉ nhận hàng</h2><p className="mt-2 text-sm text-zinc-600">Quản lý địa chỉ giao hàng.</p></Link>
      </div>
      <Link href="/login" className="mt-6 inline-flex rounded-full bg-zinc-900 px-5 py-3 text-sm font-medium text-white">Đăng nhập khách hàng</Link>
    </main>
  );
}
