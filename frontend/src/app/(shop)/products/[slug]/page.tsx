import { notFound, redirect } from "next/navigation";
import Link from "next/link";
import { ProductDetail } from "@/components/shop/ProductDetail";
import { getProductBySlug, getRelatedProducts } from "@/lib/api/products";
import { getApiErrorMessage, isApiNotFound } from "@/lib/api/client";

export default async function ProductDetailPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  let response;
  try {
    response = await getProductBySlug(slug);
  } catch (error) {
    if (isApiNotFound(error)) notFound();
    return (
      <main className="mx-auto max-w-3xl px-4 py-20 text-center">
        <h1 className="text-2xl font-semibold">Không thể tải sản phẩm</h1>
        <p role="alert" className="mt-3 text-zinc-600">{getApiErrorMessage(error, "Đã xảy ra lỗi khi tải sản phẩm.")}</p>
        <Link href="/products" className="mt-6 inline-block rounded-full bg-zinc-900 px-5 py-3 text-sm text-white">Quay lại danh sách</Link>
      </main>
    );
  }
  const product = response.data;
  if (!response.success || !product) notFound();

  // Old product links used numeric IDs; keep them working and canonicalize to slug URLs.
  if (/^\d+$/.test(slug) && product.slug !== slug) {
    redirect(`/products/${encodeURIComponent(product.slug)}`);
  }

  const relatedProducts = await getRelatedProducts(product.slug).catch(() => []);
  return <ProductDetail product={product} relatedProducts={relatedProducts} />;
}
