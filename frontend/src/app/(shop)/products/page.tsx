import { ProductCatalogue } from "@/components/shop/ProductCatalogue";

export default async function ProductsPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; category?: string; brand?: string; min_price?: string; max_price?: string; sort?: string; page?: string }>;
}) {
  const filters = await searchParams;
  return <ProductCatalogue initialFilters={filters} />;
}
