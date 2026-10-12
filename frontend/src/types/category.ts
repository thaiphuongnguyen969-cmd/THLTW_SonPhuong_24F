export type Category = {
  id: number;
  parent_id?: number | null;
  name: string;
  slug: string;
  description?: string | null;
  status: "active" | "inactive";
  image?: string | null;
  parent?: Partial<Category> | null;
  children?: Category[];
};
