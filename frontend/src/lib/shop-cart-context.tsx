"use client";

import { createContext, useContext, useMemo, useState } from "react";
import type { ReactNode } from "react";
import { mockCart } from "@/lib/mock-data";
import type { Cart, CartItem } from "@/types/cart";
import type { Product } from "@/types/product";

type ShopCartContextValue = {
  cart: Cart;
  addItem: (product: Product, variantId: number, quantity: number) => void;
  setQuantity: (itemId: number, quantity: number) => void;
  removeItem: (itemId: number) => void;
};

const ShopCartContext = createContext<ShopCartContextValue | null>(null);

function createInitialCart(): Cart {
  return {
    ...mockCart,
    items: mockCart.items.map((item) => ({ ...item })),
  };
}

export function ShopCartProvider({ children }: { children: ReactNode }) {
  const [cart, setCart] = useState(createInitialCart);

  const value = useMemo<ShopCartContextValue>(() => ({
    cart,
    addItem(product, variantId, quantity) {
      const variant = product.variants?.find((item) => item.id === variantId);
      if (!variant) return;

      const unitPrice = String(variant.price ?? product.sale_price ?? product.price);
      setCart((current) => {
        const existing = current.items.find((item) => item.variant.id === variantId);
        const items = existing
          ? current.items.map((item) => {
              if (item.variant.id !== variantId) return item;
              const nextQuantity = item.quantity + quantity;
              return {
                ...item,
                quantity: nextQuantity,
                subtotal: (Number(unitPrice) * nextQuantity).toFixed(2),
              };
            })
          : [
              ...current.items,
              {
                id: variantId,
                quantity,
                product: {
                  id: product.id,
                  name: product.name,
                  slug: product.slug,
                  image: product.image ?? product.images?.[0]?.image_url ?? null,
                },
                variant: {
                  id: variant.id,
                  size: variant.size,
                  color: variant.color,
                },
                unit_price: unitPrice,
                subtotal: (Number(unitPrice) * quantity).toFixed(2),
                available: variant.status === "active" && variant.stock >= quantity,
              } satisfies CartItem,
            ];

        return {
          items,
          total_quantity: items.reduce((total, item) => total + item.quantity, 0),
          total: items.reduce((total, item) => total + Number(item.subtotal), 0).toFixed(2),
        };
      });
    },
    setQuantity(itemId, quantity) {
      setCart((current) => {
        const items = current.items
          .map((item) => item.id !== itemId ? item : {
            ...item,
            quantity,
            subtotal: (Number(item.unit_price) * quantity).toFixed(2),
          });

        return {
          items,
          total_quantity: items.reduce((total, item) => total + item.quantity, 0),
          total: items.reduce((total, item) => total + Number(item.subtotal), 0).toFixed(2),
        };
      });
    },
    removeItem(itemId) {
      setCart((current) => {
        const items = current.items.filter((item) => item.id !== itemId);
        return {
          items,
          total_quantity: items.reduce((total, item) => total + item.quantity, 0),
          total: items.reduce((total, item) => total + Number(item.subtotal), 0).toFixed(2),
        };
      });
    },
  }), [cart]);

  return <ShopCartContext.Provider value={value}>{children}</ShopCartContext.Provider>;
}

export function useShopCart(): ShopCartContextValue {
  const context = useContext(ShopCartContext);
  if (!context) {
    throw new Error("useShopCart must be used within ShopCartProvider");
  }

  return context;
}
