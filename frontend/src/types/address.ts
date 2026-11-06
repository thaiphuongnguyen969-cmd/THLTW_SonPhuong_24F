export type Address = {
  id: number;
  label: string;
  recipient_name: string;
  phone: string | null;
  province: string | null;
  district: string | null;
  ward: string | null;
  address_detail: string;
  is_default: boolean;
  created_at: string | null;
  updated_at: string | null;
};
