export type ProductStatus = "active" | "inactive";

export type ProductImage = {
  id: number;
  product_id?: number;
  image_url: string;
  is_primary: boolean;
  sort_order: number;
};

export type ProductVariant = {
  id: number;
  product_id?: number;
  size: { id: number; name: string; sort_order?: number };
  color: { id: number; name: string; hex_code?: string | null };
  sku?: string;
  price?: string | number | null;
  available?: boolean;
  stock?: number;
  status?: string;
};

export type Promotion = {
  id: number;
  code: string;
  name: string;
  discount_type: string;
  discount_value: string | number;
  starts_at?: string | null;
  ends_at?: string | null;
};

export type Product = {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  price: number | string;
  sale_price?: number | string | null;
  status: ProductStatus;
  image?: string | null;
  quantity?: number | null;
  category?: { id: number; name: string; slug: string } | null;
  brand?: { id: number; name: string; slug: string; logo?: string | null } | null;
  images?: ProductImage[];
  variants?: ProductVariant[];
  promotions?: Promotion[];
  created_at?: string | null;
  updated_at?: string | null;
};
