export type OrderItem = {
  id: number;
  product_variant_id: number | null;
  product_name: string;
  sku: string | null;
  size: string | null;
  color: string | null;
  quantity: number;
  unit_price: string;
  subtotal: string;
};

export type OrderAmounts = {
  subtotal: string | null;
  discount: string | null;
  shipping_fee: string | null;
  total: string;
};

export type OrderShippingAddress = {
  label?: string | null;
  recipient_name?: string | null;
  phone?: string | null;
  province?: string | null;
  district?: string | null;
  ward?: string | null;
  address_detail: string | null;
};

export type OrderPayment = {
  id: number;
  payment_method: string;
  transaction_code: string | null;
  amount: string;
  status: string;
  paid_at: string | null;
};

export type OrderStatusHistory = {
  from_status: string | null;
  to_status: string;
  note: string | null;
  created_at: string | null;
  changed_by?: number | null;
  changed_by_name?: string | null;
};

export type Order = {
  id: number;
  order_code: string | null;
  status: string;
  payment_method: string | null;
  payment_status: string | null;
  amounts: OrderAmounts;
  shipping_address: OrderShippingAddress;
  note: string | null;
  cancelled_reason: string | null;
  items: OrderItem[];
  payments: OrderPayment[];
  status_histories: OrderStatusHistory[];
  created_at: string | null;
  updated_at: string | null;
};
