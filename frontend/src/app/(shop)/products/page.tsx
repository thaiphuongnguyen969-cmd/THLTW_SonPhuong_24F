import Link from "next/link";
import { mockProducts } from "@/lib/mock-data";

// Hàm định dạng giá tiền (đã ép kiểu number an toàn cho TypeScript)
const formatMoney = (amount: any) => {
  const num = Number(amount) || 0;
  return num.toLocaleString("vi-VN") + " ₫";
};

export default function ProductsPage() {
  const products = mockProducts.filter((p) => p.status === "active");

  return (
    <main className="mx-auto max-w-7xl px-4 py-10">
      <h1 className="mb-6 text-3xl font-semibold">Sản phẩm</h1>

      <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
        {products.map((p) => (
          <Link
            key={p.id}
            href={`/products/${p.id}`}
            className="group rounded-lg border bg-white p-3 transition hover:shadow-lg"
          >
            <div className="aspect-square overflow-hidden rounded-md bg-gray-100">
              {p.image && (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={p.image} alt={p.name} className="h-full w-full object-cover group-hover:scale-105 transition duration-300" />
              )}
            </div>
            <p className="mt-3 text-xs text-gray-500">{p.category?.name}</p>
            <h2 className="font-medium group-hover:underline">{p.name}</h2>
            <div className="mt-1 flex items-center gap-2">
              <span className="font-semibold text-red-600">
                {formatMoney(p.sale_price ?? p.price)}
              </span>
              {p.sale_price && (
                <span className="text-sm font-normal text-gray-400 line-through">
                  {formatMoney(p.price)}
                </span>
              )}
            </div>
          </Link>
        ))}
      </div>
    </main>
  );
}