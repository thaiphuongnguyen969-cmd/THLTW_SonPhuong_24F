import type { User } from "@/types/user";
import { apiClient } from "@/lib/api/client";

export type LoginResponse = {
  message: string;
  access_token: string;
  token_type: "Bearer";
  user: User;
};

export type RegisterPayload = {
  name: string;
  email: string;
  password: string;
  phone?: string;
  address?: string;
};

export type ProfileUpdatePayload = {
  name: string;
  email: string;
  phone: string | null;
  address: string | null;
};

export type RegisterResponse = { message: string; user: User };
export type PasswordResetResponse = { message: string };
export type OtpVerificationResponse = {
  message: string;
  reset_token: string;
  expires_in: number;
};

export async function login(payload: { email: string; password: string }) {
  const response = await apiClient.post<LoginResponse>("/login", payload);
  return response.data;
}

export async function register(payload: RegisterPayload) {
  const response = await apiClient.post<RegisterResponse>("/register", payload);
  return response.data;
}

export async function sendPasswordResetOtp(email: string) {
  const response = await apiClient.post<PasswordResetResponse>("/forgot-password/send-otp", { email });
  return response.data;
}

export async function verifyPasswordResetOtp(email: string, otp: string) {
  const response = await apiClient.post<OtpVerificationResponse>("/forgot-password/verify-otp", {
    email,
    otp,
  });
  return response.data;
}

export async function resetPassword(payload: {
  reset_token: string;
  password: string;
  password_confirmation: string;
}) {
  const response = await apiClient.post<PasswordResetResponse>("/forgot-password/reset", payload);
  return response.data;
}

export async function getCurrentUser(token: string) {
  const response = await apiClient.get<{ user: User }>("/me", {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.user;
}

export async function updateCurrentUser(payload: ProfileUpdatePayload, token: string) {
  const response = await apiClient.patch<{ message: string; user: User }>("/me", payload, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data.user;
}

export async function logout(token: string) {
  const response = await apiClient.post<{ message: string }>("/logout", undefined, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.data;
}
