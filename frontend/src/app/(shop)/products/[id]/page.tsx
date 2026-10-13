import { notFound } from "next/navigation";
import { ProductDetail } from "@/components/shop/ProductDetail";
import { mockProducts } from "@/lib/mock-data";

export default async function ProductDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const product = mockProducts.find((item) => String(item.id) === id);
  if (!product) notFound();

  return <ProductDetail product={product} />;
}
