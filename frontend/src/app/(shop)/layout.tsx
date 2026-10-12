import type { ReactNode } from "react";
import { ShopLayout } from "@/components/shop/ShopLayout";

export default function ShopRouteLayout({ children }: { children: ReactNode }) {
  return <ShopLayout>{children}</ShopLayout>;
}
