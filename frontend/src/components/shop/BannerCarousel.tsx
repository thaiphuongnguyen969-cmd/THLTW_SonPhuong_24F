"use client";

import Link from "next/link";
import { useCallback, useEffect, useRef, useState } from "react";
import type { ReactNode } from "react";
import type { Banner } from "@/types/banner";

export function BannerCarousel({
  banners,
  loading,
  error,
}: {
  banners: Banner[];
  loading: boolean;
  error?: string;
}) {
  const [activeIndex, setActiveIndex] = useState(0);
  const [hasInteracted, setHasInteracted] = useState(false);
  const [isHovered, setIsHovered] = useState(false);
  const [pageVisible, setPageVisible] = useState(true);
  const [reducedMotion, setReducedMotion] = useState<boolean | null>(null);
  const [outgoingBanner, setOutgoingBanner] = useState<Banner | null>(null);
  const outgoingTimer = useRef<number | null>(null);
  const banner = banners[activeIndex % Math.max(banners.length, 1)];
  const currentIndex = activeIndex % Math.max(banners.length, 1);
  const transitionTo = useCallback((index: number) => {
    if (index === currentIndex) return;

    if (outgoingTimer.current !== null) window.clearTimeout(outgoingTimer.current);
    setOutgoingBanner(banner ?? null);
    setActiveIndex(index);
    outgoingTimer.current = window.setTimeout(() => {
      setOutgoingBanner(null);
      outgoingTimer.current = null;
    }, 320);
  }, [banner, currentIndex]);

  useEffect(() => {
    const motionPreference = window.matchMedia("(prefers-reduced-motion: reduce)");
    const updateMotionPreference = () => setReducedMotion(motionPreference.matches);
    const updatePageVisibility = () => setPageVisible(!document.hidden);

    updateMotionPreference();
    updatePageVisibility();
    motionPreference.addEventListener("change", updateMotionPreference);
    document.addEventListener("visibilitychange", updatePageVisibility);

    return () => {
      motionPreference.removeEventListener("change", updateMotionPreference);
      document.removeEventListener("visibilitychange", updatePageVisibility);
    };
  }, []);

  useEffect(() => {
    if (banners.length < 2 || reducedMotion !== false || !pageVisible || isHovered || hasInteracted) return;

    const timer = window.setInterval(() => {
      transitionTo((currentIndex + 1) % banners.length);
    }, 6000);
    return () => window.clearInterval(timer);
  }, [banners.length, currentIndex, hasInteracted, isHovered, pageVisible, reducedMotion, transitionTo]);

  function selectBanner(index: number) {
    setHasInteracted(true);
    transitionTo(index);
  }

  useEffect(() => () => {
    if (outgoingTimer.current !== null) window.clearTimeout(outgoingTimer.current);
  }, []);

  function renderBannerLink(content: ReactNode, target: Banner) {
    if (!target.link_url) return content;
    if (target.link_url.startsWith("/")) {
      return <Link href={target.link_url} className="block h-full">{content}</Link>;
    }
    return <a href={target.link_url} className="block h-full">{content}</a>;
  }

  function renderBannerImage(item: Banner, transitionClass: string) {
    const className = `shop-banner-image ${transitionClass} absolute inset-0 bg-gradient-to-br from-stone-200 to-zinc-300 bg-cover bg-center`;

    if (item.image) {
      return (
        <div
          key={item.id}
          role="img"
          aria-label={item.title}
          className={className}
          style={{ backgroundImage: `url("${item.image}"), linear-gradient(to bottom right, #e7e5e4, #d4d4d8)` }}
        />
      );
    }

    return (
      <div key={item.id} role="img" aria-label={item.title} className={`${className} flex items-center justify-center px-6 text-center text-lg font-medium text-zinc-600`}>
        {item.title}
      </div>
    );
  }

  return (
    <section
      className="mx-auto max-w-7xl px-4 py-8 sm:px-6 md:py-12 lg:px-8"
      aria-label="Banner cửa hàng"
      onPointerEnter={() => setIsHovered(true)}
      onPointerLeave={() => setIsHovered(false)}
      onFocusCapture={() => setHasInteracted(true)}
    >
      {error && <p role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Không thể tải banner: {error}</p>}
      {banner ? (
        <div className="grid gap-6 overflow-hidden rounded-[2rem] border border-zinc-200 bg-white p-5 shadow-sm sm:p-8 lg:grid-cols-[0.85fr_1.15fr] lg:items-center lg:gap-10 lg:p-10">
          <div className="flex flex-col justify-center">
            <div key={banner.id} className="shop-banner-copy">
              <span className="mb-4 inline-flex w-fit rounded-full border border-zinc-200 bg-stone-50 px-3 py-1 text-xs font-medium text-zinc-600">Sơn Phương Model</span>
              <h1 className="max-w-xl text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">{banner.title}</h1>
              {banner.description && <p className="mt-4 max-w-lg text-base leading-7 text-zinc-600">{banner.description}</p>}
              {banner.link_url && banner.button_text && (
                <div className="mt-6">
                  {banner.link_url.startsWith("/") ? (
                    <Link href={banner.link_url} className="inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white hover:bg-zinc-700">{banner.button_text}</Link>
                  ) : (
                    <a href={banner.link_url} className="inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white hover:bg-zinc-700">{banner.button_text}</a>
                  )}
                </div>
              )}
            </div>
            {banners.length > 1 && (
              <div className="mt-6 flex items-center gap-2" role="group" aria-label="Điều khiển banner">
                <button type="button" aria-label="Banner trước" onClick={() => selectBanner((currentIndex - 1 + banners.length) % banners.length)} className="rounded-full border border-zinc-300 px-3 py-2 text-sm hover:bg-stone-100">←</button>
                {banners.map((item, index) => (
                  <button
                    key={item.id}
                    type="button"
                    aria-label={`Hiển thị banner ${index + 1}`}
                    aria-current={index === currentIndex ? "true" : undefined}
                    onClick={() => selectBanner(index)}
                    className={`shop-banner-indicator h-2.5 w-7 rounded-full ${index === currentIndex ? "is-active bg-zinc-900" : "bg-zinc-300"}`}
                  />
                ))}
                <button type="button" aria-label="Banner tiếp theo" onClick={() => selectBanner((currentIndex + 1) % banners.length)} className="rounded-full border border-zinc-300 px-3 py-2 text-sm hover:bg-stone-100">→</button>
              </div>
            )}
          </div>
          <div className="relative min-h-64 overflow-hidden rounded-[1.5rem] bg-stone-200 sm:min-h-80 lg:min-h-[25rem]">
            {outgoingBanner && <div aria-hidden="true">{renderBannerImage(outgoingBanner, "shop-banner-image--outgoing")}</div>}
            {renderBannerLink(renderBannerImage(banner, "shop-banner-image--incoming"), banner)}
          </div>
        </div>
      ) : (
        <div className="mx-auto grid max-w-7xl gap-8 rounded-[2rem] bg-white py-4 sm:px-2 md:py-8 lg:grid-cols-[1.05fr_0.95fr]">
          <div className="flex flex-col justify-center">
            <span className="mb-4 inline-flex w-fit rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600">Sơn Phương Model</span>
            <h1 className="max-w-xl text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">Khám phá bộ sưu tập mô hình</h1>
            <p className="mt-5 max-w-lg text-base leading-7 text-zinc-600">Khám phá các sản phẩm và bộ sưu tập đang có trong cửa hàng.</p>
            <div className="mt-7 flex flex-wrap gap-3">
              <Link href="/products" className="rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white hover:bg-zinc-700">Xem sản phẩm</Link>
              <Link href="/products#categories" className="rounded-full border border-zinc-300 bg-white px-6 py-3 text-sm font-medium text-zinc-800 hover:bg-stone-100">Khám phá danh mục</Link>
            </div>
          </div>
          <div className="shop-banner-placeholder relative min-h-64 overflow-hidden rounded-[2rem] bg-stone-200 sm:min-h-96">
            <div className="absolute inset-0 flex items-center justify-center text-sm text-zinc-500">{loading ? "Đang tải banner…" : "Sơn Phương Model"}</div>
          </div>
        </div>
      )}
    </section>
  );
}
