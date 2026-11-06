import axios from "axios";
import { apiClient, getApiErrorMessage } from "@/lib/api/client";

export type AdminDashboardOrder = {
  id: number;
  order_code: string | null;
  status: string;
  customer: {
    id: number;
    name: string;
  } | null;
  total: string;
  created_at: string | null;
};

export type AdminDashboardBestSeller = {
  id: number;
  name: string;
  slug: string;
  quantity_sold: number;
};

export type AdminDashboardData = {
  generated_at: string;
  totals: {
    products: number;
    categories: number;
    brands: number;
    orders: number;
    customers: number;
  };
  order_status_counts: Record<string, number>;
  other_order_statuses: Array<{
    status: string;
    count: number;
  }>;
  delivered_order_value: {
    all_time: string;
    this_month: string;
    month: string;
  };
  recent_orders: AdminDashboardOrder[];
  best_sellers: AdminDashboardBestSeller[];
};

type AdminDashboardResponse = {
  success: boolean;
  message?: string;
  data?: AdminDashboardData;
};

export async function getAdminDashboard(token: string): Promise<AdminDashboardData> {
  try {
    const response = await apiClient.get<AdminDashboardResponse>("/admin/dashboard", {
      headers: { Authorization: `Bearer ${token}` },
    });

    if (!response.data.success || !response.data.data) {
      throw new Error(response.data.message || "API không trả về dữ liệu Dashboard hợp lệ.");
    }

    return response.data.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      if (error.response?.status === 401) {
        throw new Error("Phiên quản trị đã hết hạn. Vui lòng đăng nhập lại.");
      }
      if (error.response?.status === 403) {
        throw new Error("Tài khoản hiện tại không có quyền xem Dashboard quản trị.");
      }
    }

    throw new Error(getApiErrorMessage(error, "Không thể tải dữ liệu Dashboard."));
  }
}
