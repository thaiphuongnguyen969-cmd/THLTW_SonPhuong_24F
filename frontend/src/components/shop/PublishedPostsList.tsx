"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import { getApiErrorMessage } from "@/lib/api/client";
import { listPublishedPosts, type PublicPost } from "@/lib/api/admin-posts";

export function PublishedPostsList() {
  const [posts, setPosts] = useState<PublicPost[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    void Promise.resolve().then(async () => {
      setLoading(true); setError("");
      try {
        const result = await listPublishedPosts({ page, per_page: 9 });
        if (active) { setPosts(result.data); setLastPage(result.last_page); }
      } catch (cause) { if (active) setError(getApiErrorMessage(cause, "Không thể tải bài viết.")); }
      finally { if (active) setLoading(false); }
    });
    return () => { active = false; };
  }, [page]);

  return <main className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <header className="mb-8"><p className="text-xs uppercase tracking-[0.2em] text-zinc-500">Góc chia sẻ</p><h1 className="mt-2 text-3xl font-semibold">Bài viết</h1><p className="mt-2 text-zinc-600">Tin tức và câu chuyện từ Sơn Phương Model.</p></header>
    {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}
    {loading ? <p className="rounded-xl border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500">Đang tải bài viết…</p> : posts.length === 0 ? <p className="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500">Hiện chưa có bài viết được xuất bản.</p> : <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">{posts.map((post) => <article key={post.slug} className="overflow-hidden rounded-2xl border border-zinc-200 bg-white"><Link href={`/posts/${post.slug}`} className="block">{post.thumbnail ? <Image src={post.thumbnail} alt="" width={800} height={500} unoptimized className="h-52 w-full object-cover" /> : <div className="h-52 bg-stone-100" />}<div className="p-5"><p className="text-xs text-zinc-500">{post.published_at ? new Date(post.published_at.replace(" ", "T") + "Z").toLocaleDateString("vi-VN") : ""}{post.author ? ` · ${post.author}` : ""}</p><h2 className="mt-2 text-lg font-semibold">{post.title}</h2>{post.excerpt && <p className="mt-2 line-clamp-3 text-sm leading-6 text-zinc-600">{post.excerpt}</p>}<span className="mt-4 inline-block text-sm font-medium underline">Đọc bài viết</span></div></Link></article>)}</div>}
    <nav aria-label="Phân trang bài viết" className="mt-8 flex justify-center gap-2"><button type="button" disabled={page <= 1 || loading} onClick={() => setPage((current) => current - 1)} className="rounded-lg border px-4 py-2 disabled:opacity-40">← Trước</button><span className="px-3 py-2 text-sm text-zinc-600">Trang {page} / {lastPage}</span><button type="button" disabled={page >= lastPage || loading} onClick={() => setPage((current) => current + 1)} className="rounded-lg border px-4 py-2 disabled:opacity-40">Sau →</button></nav>
  </main>;
}
