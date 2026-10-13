import "./globals.css";

export const metadata = {
  title: "Sơn Phương Model | Thời trang hiện đại",
  description: "Cửa hàng thời trang Sơn Phương Model.",
};

export default function RootLayout({ children }) {
  return (
    <html lang="vi">
      <body>{children}</body>
    </html>
  );
}
