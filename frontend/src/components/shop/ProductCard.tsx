"use client";

import Link from "next/link";
import { useState } from "react";
import { formatPriceValue } from "@/lib/format-price-value";
import { getProductImageUrl } from "@/lib/product-image-url";
import type { Product } from "@/types/product";

export function ProductCard({ product }: { product: Product }) {
  const hasDiscount = product.is_promotion === true || product.has_promotion === true;
  const displayPrice = product.effective_price ?? product.price;
  const originalPrice = product.original_price;
  const image = getProductImageUrl(product.image ?? product.images?.[0]?.image_url);
  const [failedImage, setFailedImage] = useState<string | null>(null);

  return (
    <article className="shop-product-card flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white">
      <div className="aspect-[4/5] w-full shrink-0 overflow-hidden bg-stone-100">
        {image && failedImage !== image ? (
          <img
            src={image}
            alt={product.name}
            className="shop-product-image h-full w-full object-contain"
            onError={() => setFailedImage(image)}
          />
        ) : (
          <div className="flex h-full items-center justify-center px-4 text-center text-sm text-zinc-500">
            Chưa có hình ảnh
          </div>
        )}
      </div>
      <div className="flex flex-1 flex-col p-5">
        <div className="flex min-h-5 min-w-0 items-center justify-between gap-3 text-[10px] uppercase tracking-[0.18em] text-zinc-500">
          <span className="truncate">{product.brand?.name ?? "Nổi bật"}</span>
          <span className="shrink-0">{hasDiscount ? "Khuyến mãi" : Number(product.total_sold) > 0 ? "Hot" : "Sản phẩm"}</span>
        </div>
        <h3 className="mt-3 line-clamp-2 min-h-14 text-lg leading-7 font-medium text-zinc-900">
          {product.name}
        </h3>
        <div className="mt-3 flex min-h-7 flex-wrap items-baseline gap-x-3 gap-y-1">
          <span className="text-lg font-semibold text-zinc-900">{product.price_from && !hasDiscount ? "Từ " : ""}{formatPriceValue(displayPrice)}</span>
          {hasDiscount && originalPrice !== null && originalPrice !== undefined && product.discount_percentage !== null && product.discount_percentage !== undefined ? (
            <span className="text-sm text-zinc-400 line-through">{formatPriceValue(originalPrice)}</span>
          ) : null}
          {hasDiscount && product.discount_percentage != null && <span className="text-xs font-medium text-red-700">Giảm {product.discount_percentage}%</span>}
        </div>
        <p className="mt-1 min-h-8 text-xs leading-4 text-zinc-500">Đơn vị giá đang chờ xác minh</p>
        <Link
          href={`/products/${encodeURIComponent(product.slug)}`}
          className="mt-auto flex min-h-11 w-full items-center justify-center rounded-full border border-zinc-300 bg-white px-4 py-3 text-center text-sm font-medium text-zinc-900 transition-colors hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900"
        >
          Xem sản phẩm
        </Link>
      </div>
    </article>
  );
}
