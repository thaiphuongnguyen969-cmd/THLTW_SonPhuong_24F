import { ProductCatalogue } from "@/components/shop/ProductCatalogue";
import { mockProducts } from "@/lib/mock-data";

export default async function ProductsPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; category?: string; brand?: string }>;
}) {
  const filters = await searchParams;
  return <ProductCatalogue products={mockProducts} initialFilters={filters} />;
}
