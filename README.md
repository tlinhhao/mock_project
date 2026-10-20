## Chạy dự án

Trong thư mục gốc của dự án:

```bash
docker compose up -d --build
```

Mở trình duyệt:

```text
http://localhost:8080
```

Đăng ký tài khoản tại `/register`, sau đó đăng nhập. Các trang việc cần làm và blog yêu cầu đăng nhập.

## Lệnh thường dùng

Chạy migrate thủ công:

```bash
docker exec todo-list-app php oil refine migrate
```

Tạo dữ liệu blog mẫu (mặc định 4 bài):

```bash
docker exec todo-list-app php oil r seedblogs
```

## Cổng và database

| Thành phần | Giá trị |
| --- | --- |
| Ứng dụng | http://localhost:8080 |
| MySQL từ máy host | `127.0.0.1:3306` |
| Database | `todo_list` |
| User / mật khẩu | `user` / `user` |
| Root / mật khẩu | `root` / `root` |

Trong container, ứng dụng kết nối MySQL qua host `mysql-local`.
