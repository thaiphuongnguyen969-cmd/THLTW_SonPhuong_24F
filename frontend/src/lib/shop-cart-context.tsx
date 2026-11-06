"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from "react";
import type { ReactNode } from "react";
import { addCartItem, clearCart as clearCartRequest, getCart, removeCartItem, updateCartItem } from "@/lib/api/cart";
import { getApiErrorMessage } from "@/lib/api/client";
import { useCustomerAuth } from "@/lib/customer-auth-context";
import type { Cart } from "@/types/cart";

type ShopCartContextValue = {
  cart: Cart;
  loading: boolean;
  mutating: boolean;
  error: string;
  refresh: () => Promise<void>;
  addItem: (variantId: number, quantity: number) => Promise<void>;
  setQuantity: (itemId: number, quantity: number) => Promise<void>;
  removeItem: (itemId: number) => Promise<void>;
  clear: () => Promise<void>;
};

const ShopCartContext = createContext<ShopCartContextValue | null>(null);

const EMPTY_CART: Cart = { items: [], total_quantity: 0, total: "0.00" };

export function ShopCartProvider({ children }: { children: ReactNode }) {
  const { user, loading: authLoading, getAccessToken, invalidateSession } = useCustomerAuth();
  const [serverCart, setServerCart] = useState<Cart>(EMPTY_CART);
  const [cartOwnerId, setCartOwnerId] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);
  const [mutating, setMutating] = useState(false);
  const [error, setError] = useState("");
  const requestVersion = useRef(0);

  const refresh = useCallback(async (preserveError = false) => {
    if (!user) return;
    const token = getAccessToken();
    if (!token) {
      invalidateSession();
      return;
    }

    const version = ++requestVersion.current;
    setLoading(true);
    if (!preserveError) setError("");
    try {
      const nextCart = await getCart(token);
      if (requestVersion.current === version) {
        setServerCart(nextCart);
        setCartOwnerId(user.id);
      }
    } catch (requestError) {
      if (requestVersion.current === version) {
        const status = (requestError as { response?: { status?: number } })?.response?.status;
        if (status === 401) invalidateSession();
        setError(getApiErrorMessage(requestError, "Không thể tải giỏ hàng."));
      }
      throw requestError;
    } finally {
      if (requestVersion.current === version) setLoading(false);
    }
  }, [getAccessToken, invalidateSession, user]);

  useEffect(() => {
    if (authLoading || !user) return;
    let active = true;
    queueMicrotask(() => {
      if (active) void refresh().catch(() => undefined);
    });
    return () => {
      active = false;
      requestVersion.current += 1;
    };
  }, [authLoading, refresh, user]);

  const performMutation = useCallback(async (operation: (token: string) => Promise<unknown>) => {
    if (!user) throw new Error("Vui lòng đăng nhập để sử dụng giỏ hàng.");
    const token = getAccessToken();
    if (!token) {
      invalidateSession();
      throw new Error("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
    }

    setMutating(true);
    setError("");
    try {
      await operation(token);
      await refresh();
    } catch (requestError) {
      const status = (requestError as { response?: { status?: number } })?.response?.status;
      if (status === 401) invalidateSession();
      setError(getApiErrorMessage(requestError, "Không thể cập nhật giỏ hàng."));
      if (status === 404 || status === 409) void refresh(true).catch(() => undefined);
      throw requestError;
    } finally {
      setMutating(false);
    }
  }, [getAccessToken, invalidateSession, refresh, user]);

  const cart = user && cartOwnerId === user.id ? serverCart : EMPTY_CART;

  const value = useMemo<ShopCartContextValue>(() => ({
    cart,
    loading: authLoading || Boolean(user && (cartOwnerId !== user.id || loading)),
    mutating,
    error,
    refresh,
    addItem: async (variantId, quantity) => performMutation((token) => addCartItem({ product_variant_id: variantId, quantity }, token)),
    setQuantity: async (itemId, quantity) => performMutation((token) => updateCartItem(itemId, quantity, token)),
    removeItem: async (itemId) => performMutation((token) => removeCartItem(itemId, token)),
    clear: async () => performMutation((token) => clearCartRequest(token)),
  }), [authLoading, cart, cartOwnerId, error, loading, mutating, performMutation, refresh, user]);

  return <ShopCartContext.Provider value={value}>{children}</ShopCartContext.Provider>;
}

export function useShopCart(): ShopCartContextValue {
  const context = useContext(ShopCartContext);
  if (!context) {
    throw new Error("useShopCart must be used within ShopCartProvider");
  }

  return context;
}
