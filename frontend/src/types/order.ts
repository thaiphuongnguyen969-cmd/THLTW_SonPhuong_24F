export type OrderItem = {
  id: number;
  product_name: string;
  quantity: number;
  unit_price: string;
  subtotal: string;
};

export type Order = {
  id: number;
  order_number?: string;
  status: string;
  total: string;
  created_at?: string;
  items?: OrderItem[];
};
