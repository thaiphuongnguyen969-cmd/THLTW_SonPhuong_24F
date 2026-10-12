import Link from "next/link";
import { mockProducts } from "@/lib/mock-data";

// Hàm định dạng giá tiền an toàn
const formatMoney = (amount: any) => {
  const num = Number(amount) || 0;
  return num.toLocaleString("vi-VN") + " ₫";
};

// Cập nhật lại kiểu dữ liệu params thành Promise theo chuẩn Next.js mới
interface PageProps {
  params: Promise<{
    id: string;
  }>;
}

export default async function ProductDetail({ params }: PageProps) {
  const resolvedParams = await params;
  const productId = Number(resolvedParams.id);

  // Tìm sản phẩm, nếu không thấy tự động lấy sản phẩm đầu tiên để demo không bao giờ bị lỗi
  const product = mockProducts.find((p) => p.id === productId) || mockProducts[0];

  return (
    <main className="mx-auto max-w-7xl px-4 py-10">
      <div className="mb-6 text-sm text-gray-500">
        <Link href="/products" className="hover:underline">Sản phẩm</Link> / <span className="text-gray-800 font-medium">{product.name}</span>
      </div>

      <div className="grid grid-cols-1 gap-10 md:grid-cols-2">
        <div className="aspect-square overflow-hidden rounded-xl border bg-gray-100 shadow-sm">
          {product.image ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={product.image} alt={product.name} className="h-full w-full object-cover" />
          ) : (
            <div className="flex h-full items-center justify-center text-gray-400">Không có hình ảnh</div>
          )}
        </div>

        <div className="flex flex-col justify-start">
          <p className="text-sm uppercase tracking-wide text-blue-600 font-semibold">{product.category?.name || "Danh mục"}</p>
          <h1 className="mt-2 text-3xl font-bold text-gray-900">{product.name}</h1>

          <div className="mt-4 flex items-center gap-3">
            <span className="text-2xl font-bold text-red-600">
              {formatMoney(product.sale_price ?? product.price)}
            </span>
            {product.sale_price && (
              <span className="text-base text-gray-400 line-through">
                {formatMoney(product.price)}
              </span>
            )}
          </div>

          <div className="mt-6 border-t pt-4">
            <h3 className="font-semibold text-gray-800">Mô tả sản phẩm:</h3>
            <p className="mt-2 text-gray-600 leading-relaxed">
              {(product as any).detail || (product as any).summary || "Đang cập nhật mô tả chi tiết cho sản phẩm này..."}
            </p>
          </div>

          <div className="mt-8 flex gap-4">
            <button className="flex-1 rounded-lg bg-blue-600 py-3 text-center font-medium text-white shadow hover:bg-blue-700 transition">
              Thêm vào giỏ hàng
            </button>
            <Link
              href="/products"
              className="rounded-lg border border-gray-300 px-6 py-3 text-center font-medium text-gray-700 hover:bg-gray-50 transition"
            >
              Quay lại
            </Link>
          </div>
        </div>
      </div>
    </main>
  );
}