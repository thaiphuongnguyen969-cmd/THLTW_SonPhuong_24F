export type UserRole = "admin" | "customer";

export type User = {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  status: "active" | "inactive";
  avatar?: string | null;
  phone?: string | null;
  address?: string | null;
};
