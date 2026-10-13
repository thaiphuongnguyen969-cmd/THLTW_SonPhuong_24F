import type { Brand } from "@/types/brand";
import type { Category } from "@/types/category";
import type { Cart } from "@/types/cart";
import type { Product } from "@/types/product";

export const mockCategories: Category[] = [
  { id: 1, name: "New Arrivals", slug: "new-arrivals", status: "active", description: "Fresh picks" },
  { id: 2, name: "Tailoring", slug: "tailoring", status: "active", description: "Sharp essentials" },
  { id: 3, name: "Accessories", slug: "accessories", status: "active", description: "Finishing touches" },
  { id: 4, name: "Essentials", slug: "essentials", status: "active", description: "Everyday wardrobe" },
];

export const mockBrands: Brand[] = [
  { id: 1, name: "Monarch", slug: "monarch", status: "active", logo: "M" },
  { id: 2, name: "Noir & Co.", slug: "noir-and-co", status: "active", logo: "N" },
  { id: 3, name: "Nordline", slug: "nordline", status: "active", logo: "N" },
  { id: 4, name: "Aster Studio", slug: "aster-studio", status: "active", logo: "A" },
];

export const mockProducts: Product[] = [
  {
    id: 1,
    name: "Cropped Wool Blazer",
    slug: "cropped-wool-blazer",
    description: "Tailored structure with a soft brushed finish.",
    price: 189,
    sale_price: 149,
    status: "active",
    image: "https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=900&q=80",
    category: { id: 1, name: "Tailoring", slug: "tailoring" },
    brand: { id: 1, name: "Monarch", slug: "monarch", logo: "M" },
    variants: [{ id: 11, product_id: 1, sku: "CWB-S", stock: 8, status: "active", size: { id: 1, name: "S" }, color: { id: 1, name: "Stone", hex_code: "#d6d3d1" } }],
  },
  {
    id: 2,
    name: "Minimal Leather Tote",
    slug: "minimal-leather-tote",
    description: "Structured everyday carry with refined detailing.",
    price: 220,
    sale_price: 180,
    status: "active",
    image: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80",
    category: { id: 3, name: "Accessories", slug: "accessories" },
    brand: { id: 2, name: "Noir & Co.", slug: "noir-and-co", logo: "N" },
    variants: [{ id: 12, product_id: 2, sku: "MLT-ONE", stock: 14, status: "active", size: { id: 3, name: "One Size" }, color: { id: 2, name: "Black", hex_code: "#111827" } }],
  },
  {
    id: 3,
    name: "Structured Knit Set",
    slug: "structured-knit-set",
    description: "A relaxed knit layer for elevated layering.",
    price: 170,
    sale_price: 145,
    status: "active",
    image: "https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80",
    category: { id: 4, name: "Essentials", slug: "essentials" },
    brand: { id: 3, name: "Nordline", slug: "nordline", logo: "N" },
    variants: [{ id: 13, product_id: 3, sku: "SKS-M", stock: 9, status: "active", size: { id: 2, name: "M" }, color: { id: 3, name: "Soft Ivory", hex_code: "#fafaf9" } }],
  },
  {
    id: 4,
    name: "Classic Denim Layer",
    slug: "classic-denim-layer",
    description: "Timeless denim with a clean finish and relaxed drape.",
    price: 140,
    sale_price: 115,
    status: "active",
    image: "https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=900&q=80",
    category: { id: 2, name: "New Arrivals", slug: "new-arrivals" },
    brand: { id: 4, name: "Aster Studio", slug: "aster-studio", logo: "A" },
    variants: [{ id: 14, product_id: 4, sku: "CDL-L", stock: 7, status: "active", size: { id: 4, name: "L" }, color: { id: 4, name: "Indigo", hex_code: "#4f46e5" } }],
  },
];

export const mockCart: Cart = {
  items: [
    {
      id: 101,
      quantity: 1,
      product: { id: 1, name: "Cropped Wool Blazer", slug: "cropped-wool-blazer", image: mockProducts[0].image ?? null },
      variant: { id: 11, size: { id: 1, name: "S" }, color: { id: 1, name: "Stone", hex_code: "#d6d3d1" } },
      unit_price: "149.00",
      subtotal: "149.00",
      available: true,
    },
    {
      id: 102,
      quantity: 2,
      product: { id: 2, name: "Minimal Leather Tote", slug: "minimal-leather-tote", image: mockProducts[1].image ?? null },
      variant: { id: 12, size: { id: 3, name: "One Size" }, color: { id: 2, name: "Black", hex_code: "#111827" } },
      unit_price: "180.00",
      subtotal: "360.00",
      available: true,
    },
  ],
  total_quantity: 3,
  total: "509.00",
};
