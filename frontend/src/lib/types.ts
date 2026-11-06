export type ApiResponse<T> = {
  success: boolean;
  message?: string;
  data?: T;
  meta?: Record<string, unknown>;
  links?: Record<string, string | null>;
};

export type PaginatedResponse<T> = {
  data: T[];
  success: boolean;
  message?: string;
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
  };
};
