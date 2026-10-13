export function formatPriceValue(value: number | string): string {
  const amount = Number(value);
  if (!Number.isFinite(amount)) return "Chưa có giá";

  return new Intl.NumberFormat("vi-VN", {
    maximumFractionDigits: 2,
  }).format(amount);
}
