import { PublishedPostDetail } from "@/components/shop/PublishedPostDetail";

export default async function PostDetailPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  return <PublishedPostDetail slug={slug} />;
}
