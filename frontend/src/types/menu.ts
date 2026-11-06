export type Menu = {
  id: number;
  name: string;
  link_type: "internal" | "external";
  url: string;
  sort_order: number;
};
