export type CartItem = {
  id: number;
  quantity: number;
  product: {
    id: number;
    name: string;
    slug: string;
    image: string | null;
  };
  variant: {
    id: number;
    size: { id: number; name: string };
    color: { id: number; name: string; hex_code: string | null };
  };
  unit_price: string;
  subtotal: string;
  available: boolean;
};

export type Cart = {
  items: CartItem[];
  total_quantity: number;
  total: string;
};
