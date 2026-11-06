nhóm Sơn , Phương
1 hướng dẫn chạy ứng dụng :
-backend : php artisan serve
-frontend : npm run dev
chạy theo trình tự backend trước để đồng bộ api sau đó chạy frontend rồi mới vào web.
2. tên sql : thltw_sonphuong_24f
3.hướng dẫn mua hàng :
: đăng nhập -> chọn sản phẩm -> thanh toán -> chọn phương thức thanh toán -> checkout -> xem liệu trình đơn hàng 
admin: có request từ trang shop -> admin oder -> thao táo chi tiết đơn hàng -> báo về lại cho khách hàng
4. thông tin đăng nhập web 
user
tài khoản : hai@gmail.com
mật khẩu : 12345678
admin
tài khoản : sonphuong@gmail.com
mật khẩu:s101078#
5.env.local
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
