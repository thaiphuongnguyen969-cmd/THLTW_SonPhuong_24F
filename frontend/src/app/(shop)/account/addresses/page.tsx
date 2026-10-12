import Link from "next/link";

export default function AccountAddressesPage() {
  return (
    <main className="mx-auto min-h-[60vh] max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
      <Link href="/account" className="text-sm text-zinc-500 hover:text-zinc-900">← Tài khoản</Link>
      <h1 className="mt-4 text-3xl font-semibold">Địa chỉ nhận hàng</h1>
      <div className="mt-6 rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center">
        <p className="font-medium">Chưa có địa chỉ nào</p>
        <p className="mt-2 text-sm text-zinc-500">Quản lý địa chỉ sẽ khả dụng khi tài khoản được tích hợp.</p>
      </div>
    </main>
  );
}
