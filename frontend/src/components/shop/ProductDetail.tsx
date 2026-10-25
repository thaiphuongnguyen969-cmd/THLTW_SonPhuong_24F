"use client";

import Link from "next/link";
import Image from "next/image";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { formatPriceValue } from "@/lib/format-price-value";
import { getProductImageUrl } from "@/lib/product-image-url";
import { useShopCart } from "@/lib/shop-cart-context";
import { useCustomerAuth } from "@/lib/customer-auth-context";
import type { Product } from "@/types/product";

export function ProductDetail({ product }: { product: Product }) {
  const { addItem, mutating, error: cartError } = useShopCart();
  const auth = useCustomerAuth();
  const router = useRouter();
  const variants = product.variants ?? [];
  const initialVariantId = variants.length === 1 && variants[0].available === true ? variants[0].id : null;
  const [selectedVariantId, setSelectedVariantId] = useState<number | null>(initialVariantId);
  const [quantity, setQuantity] = useState(1);
  const [added, setAdded] = useState(false);
  const [adding, setAdding] = useState(false);
  const selectedVariant = variants.find((variant) => variant.id === selectedVariantId);
  const image = getProductImageUrl(product.image ?? product.images?.[0]?.image_url);
  const [failedImage, setFailedImage] = useState<string | null>(null);
  const regularPrice = selectedVariant?.price ?? product.price;
  const price = product.sale_price ?? regularPrice;
  const hasSalePrice = product.sale_price !== null && product.sale_price !== undefined;
  const canAdd = selectedVariant?.available === true;

  async function handleAddToCart() {
    setAdded(false);
    if (!auth.user) {
      const next = `${window.location.pathname}${window.location.search}`;
      router.push(`/login?next=${encodeURIComponent(next)}`);
      return;
    }
    if (!selectedVariant || !canAdd || quantity < 1 || !Number.isInteger(quantity)) return;
    setAdding(true);
    try {
      await addItem(selectedVariant.id, quantity);
      setAdded(true);
    } catch {
      // The cart context exposes a localized error for this request.
    } finally {
      setAdding(false);
    }
  }

  return (
    <main className="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 md:py-12 lg:grid-cols-2 lg:px-8">
      <div className="flex min-h-72 items-center justify-center overflow-hidden rounded-[2rem] bg-stone-100">
        {image && failedImage !== image ? <Image unoptimized width={1200} height={1200} src={image} alt={product.name} className="max-h-[680px] w-full object-contain" onError={() => setFailedImage(image)} /> : <p className="p-10 text-sm text-zinc-500">Hình ảnh sản phẩm hiện không khả dụng.</p>}
      </div>
      <section className="flex flex-col justify-center">
        <Link href="/products" className="text-sm text-zinc-500 hover:text-zinc-900">← Quay lại sản phẩm</Link>
        <p className="mt-6 text-xs uppercase tracking-widest text-zinc-500">{product.brand?.name ?? "Sơn Phương Model"}</p>
        <h1 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{product.name}</h1>
        <div className="mt-5 flex flex-wrap items-baseline gap-3"><p className="text-3xl font-semibold">{formatPriceValue(price)}</p>{hasSalePrice && <p className="text-lg text-zinc-400 line-through">{formatPriceValue(regularPrice)}</p>}</div>
        <p className="mt-5 whitespace-pre-line leading-7 text-zinc-600">{product.description || "Chưa có mô tả cho sản phẩm này."}</p>
        {product.category && <p className="mt-4 text-sm text-zinc-500">Danh mục: {product.category.name}</p>}

        {variants.length > 0 && (
          <fieldset className="mt-7">
            <legend className="text-sm font-medium">Phiên bản</legend>
            <div className="mt-3 flex flex-wrap gap-2">
              {variants.map((variant) => (
                <button key={variant.id} type="button" disabled={variant.available !== true} aria-pressed={selectedVariantId === variant.id} onClick={() => { setSelectedVariantId(variant.id); setQuantity(1); setAdded(false); }} className={`rounded-full border px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-45 ${selectedVariantId === variant.id ? "border-zinc-900 bg-zinc-900 text-white" : "border-zinc-300 bg-white text-zinc-700"}`}>
                  {variant.size.name} · {variant.color.name}{variant.available === false ? " (Hết hàng)" : ""}
                </button>
              ))}
            </div>
            {selectedVariant && <p className="mt-3 text-sm text-zinc-500">{selectedVariant.available === true ? "Còn hàng" : "Tạm hết hàng"}</p>}
          </fieldset>
        )}

        {selectedVariant && (
          <div className="mt-6 flex items-center gap-3">
            <label htmlFor="product-quantity" className="text-sm font-medium">Số lượng</label>
            <input id="product-quantity" type="number" min={1} step={1} value={quantity} onChange={(event) => { setQuantity(Math.max(1, Math.floor(Number(event.target.value) || 1))); setAdded(false); }} className="w-24 rounded-xl border border-zinc-300 bg-white px-3 py-2" />
          </div>
        )}

        <div className="mt-7 flex flex-wrap gap-3">
          {canAdd ? (
            <button type="button" disabled={adding || mutating || added} onClick={() => void handleAddToCart()} className="rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-default disabled:opacity-60">{adding ? "Đang thêm…" : added ? "Đã thêm vào giỏ hàng" : "Thêm vào giỏ hàng"}</button>
          ) : <button type="button" disabled className="cursor-not-allowed rounded-full bg-zinc-300 px-6 py-3 text-sm font-medium text-zinc-600">{variants.length ? "Không thể thêm phiên bản này" : "Chưa có phiên bản để thêm"}</button>}
          <Link href="/cart" className="rounded-full border border-zinc-300 bg-white px-6 py-3 text-sm font-medium">Xem giỏ hàng</Link>
        </div>
        {added && <p role="status" className="mt-3 text-sm text-emerald-700">Đã thêm sản phẩm vào giỏ hàng.</p>}
        {cartError && <p role="alert" className="mt-3 text-sm text-red-700">{cartError}</p>}
      </section>
    </main>
  );
}
