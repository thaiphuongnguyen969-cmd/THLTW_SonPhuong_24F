import type { ApiResponse } from "@/lib/types";
import type { User } from "@/types/user";
import { apiFetch } from "@/lib/api/client";

export async function login(payload: { email: string; password: string }) {
  return apiFetch<ApiResponse<{ token: string; user: User }>>("/login", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  });
}

export async function getCurrentUser(token: string) {
  return apiFetch<ApiResponse<User>>("/me", {
    headers: { Authorization: `Bearer ${token}` },
  });
}
