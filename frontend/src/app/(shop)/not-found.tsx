import Link from "next/link";

export default function ShopNotFound() {
  return (
    <main className="mx-auto flex min-h-[60vh] max-w-4xl flex-col items-center justify-center px-4 py-16 text-center sm:px-6">
      <p className="text-sm font-medium uppercase tracking-widest text-zinc-500">Lỗi 404</p>
      <h1 className="mt-3 text-3xl font-semibold">Không tìm thấy trang hoặc sản phẩm</h1>
      <p className="mt-3 max-w-xl text-zinc-600">Nội dung bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển.</p>
      <Link href="/products" className="mt-7 rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white">
        Xem sản phẩm
      </Link>
    </main>
  );
}
