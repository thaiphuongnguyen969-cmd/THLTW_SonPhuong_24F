"use client";

import { createContext, useCallback, useContext, useEffect, useRef, useState } from "react";
import type { ReactNode } from "react";
import { getApiErrorMessage } from "@/lib/api/client";
import { getCurrentUser, login as loginRequest, logout as logoutRequest, register as registerRequest } from "@/lib/api/auth";
import type { RegisterPayload } from "@/lib/api/auth";
import type { User } from "@/types/user";

const TOKEN_KEY = "sonphuong.customer.token";

type CustomerAuthContextValue = {
  user: User | null;
  loading: boolean;
  error: string;
  login: (payload: { email: string; password: string }) => Promise<void>;
  register: (payload: RegisterPayload) => Promise<void>;
  logout: () => Promise<{ serverConfirmed: boolean; message: string }>;
  getAccessToken: () => string | null;
  invalidateSession: () => void;
};

const CustomerAuthContext = createContext<CustomerAuthContextValue | null>(null);

export function CustomerAuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const version = useRef(0);

  const getAccessToken = useCallback(() => {
    try {
      return window.sessionStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  }, []);

  const invalidateSession = useCallback(() => {
    version.current += 1;
    try {
      window.sessionStorage.removeItem(TOKEN_KEY);
    } catch {
      // Browser storage may be unavailable; the in-memory session is still cleared.
    }
    setUser(null);
    setError("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
  }, []);

  useEffect(() => {
    let active = true;
    const currentVersion = version.current;
    let token: string | null = null;
    try {
      token = window.sessionStorage.getItem(TOKEN_KEY);
    } catch {
      queueMicrotask(() => {
        if (active) {
          setError("Không thể đọc phiên đăng nhập trên trình duyệt này.");
          setLoading(false);
        }
      });
      return () => { active = false; };
    }

    if (!token) {
      queueMicrotask(() => {
        if (active && version.current === currentVersion) setLoading(false);
      });
      return () => { active = false; };
    }

    void getCurrentUser(token)
      .then((currentUser) => {
        if (active && version.current === currentVersion) {
          setUser(currentUser);
          setError("");
        }
      })
      .catch((requestError: unknown) => {
        if (!active || version.current !== currentVersion) return;
        const status = (requestError as { response?: { status?: number } })?.response?.status;
        if (status === 401 || status === 403) {
          window.sessionStorage.removeItem(TOKEN_KEY);
          setUser(null);
          setError(status === 403 ? "Tài khoản hiện không được phép truy cập." : "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
        } else {
          setError(getApiErrorMessage(requestError, "Không thể xác minh phiên đăng nhập."));
        }
      })
      .finally(() => {
        if (active && version.current === currentVersion) setLoading(false);
      });

    return () => { active = false; };
  }, []);

  const login = useCallback(async (payload: { email: string; password: string }) => {
    setLoading(true);
    setError("");
    try {
      const result = await loginRequest(payload);
      if (!result.access_token || !result.user) throw new Error("Phản hồi đăng nhập không hợp lệ.");
      window.sessionStorage.setItem(TOKEN_KEY, result.access_token);
      version.current += 1;
      setUser(result.user);
    } catch (requestError) {
      throw new Error(getApiErrorMessage(requestError, "Đăng nhập thất bại."));
    } finally {
      setLoading(false);
    }
  }, []);

  const register = useCallback(async (payload: RegisterPayload) => {
    await registerRequest(payload);
  }, []);

  const logout = useCallback(async () => {
    const token = window.sessionStorage.getItem(TOKEN_KEY);
    let serverConfirmed = false;
    let message = "Đã xóa phiên đăng nhập trên thiết bị này, nhưng máy chủ chưa xác nhận thu hồi token.";
    version.current += 1;
    if (token) {
      try {
        await logoutRequest(token);
        serverConfirmed = true;
        message = "Bạn đã đăng xuất.";
      } catch {
        // Always clear this browser's session; report separately that server revocation was not confirmed.
      }
    } else {
      serverConfirmed = true;
      message = "Bạn đã đăng xuất.";
    }
    window.sessionStorage.removeItem(TOKEN_KEY);
    setUser(null);
    setError("");
    return { serverConfirmed, message };
  }, []);

  return (
    <CustomerAuthContext.Provider value={{ user, loading, error, login, register, logout, getAccessToken, invalidateSession }}>
      {children}
    </CustomerAuthContext.Provider>
  );
}

export function useCustomerAuth() {
  const context = useContext(CustomerAuthContext);
  if (!context) throw new Error("useCustomerAuth phải được dùng bên trong CustomerAuthProvider.");
  return context;
}
