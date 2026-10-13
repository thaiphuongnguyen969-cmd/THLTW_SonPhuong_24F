"use client";

import Link from "next/link";
import { useState } from "react";
import { formatPriceValue } from "@/lib/format-price-value";
import { useShopCart } from "@/lib/shop-cart-context";
import type { Product } from "@/types/product";

export function ProductDetail({ product }: { product: Product }) {
  const { addItem } = useShopCart();
  const variants = product.variants ?? [];
  const [selectedVariantId, setSelectedVariantId] = useState(variants[0]?.id ?? 0);
  const [quantity, setQuantity] = useState(1);
  const [added, setAdded] = useState(false);
  const selectedVariant = variants.find((variant) => variant.id === selectedVariantId);
  const image = product.image ?? product.images?.[0]?.image_url;
  const price = selectedVariant?.price ?? product.sale_price ?? product.price;
  const canAdd = Boolean(selectedVariant && selectedVariant.status === "active" && selectedVariant.stock > 0 && quantity <= selectedVariant.stock);

  return (
    <main className="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 md:py-12 lg:grid-cols-2 lg:px-8">
      <div className="flex min-h-72 items-center justify-center overflow-hidden rounded-[2rem] bg-stone-100">
        {image ? <img src={image} alt={product.name} className="max-h-[680px] w-full object-cover" /> : <p className="p-10 text-sm text-zinc-500">Sản phẩm chưa có hình ảnh.</p>}
      </div>
      <section className="flex flex-col justify-center">
        <Link href="/products" className="text-sm text-zinc-500 hover:text-zinc-900">← Quay lại sản phẩm</Link>
        <p className="mt-6 text-xs uppercase tracking-widest text-zinc-500">{product.brand?.name ?? "Sơn Phương Model"}</p>
        <h1 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{product.name}</h1>
        <p className="mt-5 text-3xl font-semibold">{formatPriceValue(price)}</p>
        <p className="mt-1 text-xs text-zinc-500">Đơn vị giá đang chờ xác minh</p>
        <p className="mt-5 whitespace-pre-line leading-7 text-zinc-600">{product.description || "Chưa có mô tả cho sản phẩm này."}</p>

        {variants.length > 0 && (
          <fieldset className="mt-7">
            <legend className="text-sm font-medium">Phiên bản</legend>
            <div className="mt-3 flex flex-wrap gap-2">
              {variants.map((variant) => (
                <button key={variant.id} type="button" aria-pressed={selectedVariantId === variant.id} onClick={() => { setSelectedVariantId(variant.id); setQuantity(1); setAdded(false); }} className={`rounded-full border px-4 py-2 text-sm ${selectedVariantId === variant.id ? "border-zinc-900 bg-zinc-900 text-white" : "border-zinc-300 bg-white text-zinc-700"}`}>
                  {variant.size.name} · {variant.color.name}
                </button>
              ))}
            </div>
            {selectedVariant && <p className="mt-3 text-sm text-zinc-500">{selectedVariant.stock > 0 ? `Còn ${selectedVariant.stock} sản phẩm trong dữ liệu mẫu` : "Tạm hết hàng"}</p>}
          </fieldset>
        )}

        {selectedVariant && (
          <div className="mt-6 flex items-center gap-3">
            <label htmlFor="product-quantity" className="text-sm font-medium">Số lượng</label>
            <input id="product-quantity" type="number" min={1} max={selectedVariant.stock} value={quantity} onChange={(event) => { setQuantity(Math.max(1, Number(event.target.value) || 1)); setAdded(false); }} className="w-24 rounded-xl border border-zinc-300 bg-white px-3 py-2" />
          </div>
        )}

        <div className="mt-7 flex flex-wrap gap-3">
          {canAdd ? (
            <button type="button" disabled={added} onClick={() => { addItem(product, selectedVariantId, quantity); setAdded(true); }} className="rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-default disabled:bg-emerald-800">{added ? "Đã thêm vào giỏ hàng" : "Thêm vào giỏ hàng"}</button>
          ) : <button type="button" disabled className="cursor-not-allowed rounded-full bg-zinc-300 px-6 py-3 text-sm font-medium text-zinc-600">{variants.length ? "Không thể thêm phiên bản này" : "Chưa có phiên bản để thêm"}</button>}
          <Link href="/cart" className="rounded-full border border-zinc-300 bg-white px-6 py-3 text-sm font-medium">Xem giỏ hàng</Link>
        </div>
        {added && <p role="status" className="mt-3 text-sm text-emerald-700">Đã thêm sản phẩm vào giỏ hàng mẫu.</p>}
      </section>
    </main>
  );
}
