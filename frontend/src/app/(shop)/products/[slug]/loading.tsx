export default function ProductDetailLoading() {
  return (
    <main role="status" className="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 md:py-12 lg:grid-cols-2 lg:px-8">
      <div className="min-h-72 animate-pulse rounded-[2rem] bg-stone-200 lg:min-h-[32rem]" />
      <div className="flex flex-col justify-center gap-4">
        <div className="h-4 w-28 animate-pulse rounded bg-stone-200" />
        <div className="h-10 w-3/4 animate-pulse rounded bg-stone-200" />
        <div className="h-8 w-36 animate-pulse rounded bg-stone-200" />
        <p className="text-sm text-zinc-500">Đang tải thông tin sản phẩm…</p>
      </div>
    </main>
  );
}
