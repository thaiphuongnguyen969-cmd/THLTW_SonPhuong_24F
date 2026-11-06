"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import { getApiErrorMessage } from "@/lib/api/client";
import { getPublishedPost, type PublicPost } from "@/lib/api/admin-posts";

export function PublishedPostDetail({ slug }: { slug: string }) {
  const [post, setPost] = useState<PublicPost | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    void Promise.resolve().then(async () => {
      setLoading(true); setError("");
      try { const result = await getPublishedPost(slug); if (active) setPost(result); }
      catch (cause) { if (active) setError(getApiErrorMessage(cause, "Không thể tải bài viết.")); }
      finally { if (active) setLoading(false); }
    });
    return () => { active = false; };
  }, [slug]);

  if (loading) return <main className="mx-auto max-w-4xl px-4 py-16 text-center text-sm text-zinc-500">Đang tải bài viết…</main>;
  if (error || !post) return <main className="mx-auto max-w-4xl px-4 py-16"><p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error || "Không tìm thấy bài viết."}</p><Link href="/posts" className="mt-5 inline-block underline">Quay lại danh sách bài viết</Link></main>;

  return <main className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8"><Link href="/posts" className="text-sm text-zinc-600 underline">← Tất cả bài viết</Link><article className="mt-6"><p className="text-sm text-zinc-500">{post.published_at ? new Date(post.published_at.replace(" ", "T") + "Z").toLocaleDateString("vi-VN") : ""}{post.author ? ` · ${post.author}` : ""}</p><h1 className="mt-3 text-3xl font-semibold leading-tight sm:text-4xl">{post.title}</h1>{post.excerpt && <p className="mt-4 text-lg leading-8 text-zinc-600">{post.excerpt}</p>}{post.thumbnail && <Image src={post.thumbnail} alt="" width={1200} height={700} unoptimized className="mt-8 max-h-[34rem] w-full rounded-2xl object-cover" />}<div className="mt-8 whitespace-pre-wrap text-base leading-8 text-zinc-700">{post.content}</div></article></main>;
}
