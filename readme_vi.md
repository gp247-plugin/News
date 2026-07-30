**🌐 Ngôn ngữ:** **Tiếng Việt** | [English](readme.md)

# Plugin Tin tức cho GP247

Plugin quản lý tin tức cho GP247 Framework với các tính năng sau:

## Tính năng chính

- Quản lý danh mục tin tức đa cấp
- Quản lý bài viết theo danh mục
- Hỗ trợ đa ngôn ngữ cho danh mục và bài viết
- URL thân thiện SEO

## Giấy phép

GP247 News Plugin là phần mềm mã nguồn mở theo giấy phép [MIT](https://opensource.org/licenses/MIT).

## Hướng dẫn cài đặt

GP247 hỗ trợ 3 cách cài đặt tiện ích (plugin/template): **Online** (từ thư viện chính thức), **Import** (tải lên file `.zip`) và **Thủ công** (chép thư mục vào server).

Tham khảo tài liệu chính thức: [Hướng dẫn cài đặt tiện ích](https://gp247.net/vi/docs/user-guide-extension/guide-to-installing-the-extension.html).

### Cách 1: Cài đặt Online (từ thư viện)

Bước 0 — Đăng ký API License (chỉ làm một lần):
- Vào phần cấu hình **API License** trong Admin.
- Nhấn "Đăng ký/Thiết lập" để lấy license key miễn phí.
- Hệ thống tự lưu key vào biến `GP247_API_LICENSE` trong file `.env`.

Các bước cài:
- Vào menu **Plugin** (hoặc Template) và chọn tab **Online**.
- Duyệt thư viện tiện ích GP247, dùng tìm kiếm/lọc để chọn plugin phù hợp phiên bản Core hiện tại.
- Nhấn **Install** — hệ thống tự tải về, kiểm tra tương thích, giải nén và cài đặt.
- Cài xong sẽ có thông báo thành công và plugin hiện trong danh sách đã cài.
- Lưu ý: tiện ích trả phí cần thêm license key riêng của tiện ích đó, ngoài API License miễn phí.

### Cách 2: Import file zip
- Vào menu **Plugin** và chọn tab **Import**.
- Chọn file `.zip` của plugin từ máy rồi nhấn **Upload** — hệ thống tự kiểm tra và cài đặt.
- Yêu cầu với file zip:
  - Chỉ chấp nhận định dạng `.zip`, dung lượng tối đa 50MB.
  - Phải chứa file `gp247.json` ở thư mục gốc.
  - Không được trùng `configKey` với tiện ích đã có.

### Cách 3: Cài đặt thủ công
- Giải nén mã nguồn và chép thư mục plugin (chứa `AppConfig.php` và `gp247.json`) vào:
  - `app/GP247/Plugins/News`
- Nếu plugin có thư mục `public`, chép nội dung của nó sang:
  - `public/GP247/Plugins/News`
- Plugin sẽ tự xuất hiện trong Admin > Extensions > Plugins (trạng thái chưa cài). Tìm "News" và nhấn **Install** để kích hoạt.

## Sau khi cài đặt
- Plugin thường hoạt động ngay; có thể có tùy chọn Enable/Disable và Config.
- Hệ thống tự xóa cache. Nếu cần, chạy thêm:

```bash
php artisan optimize:clear
```

## Tham khảo
- Hướng dẫn cài đặt tiện ích: https://gp247.net/vi/docs/user-guide-extension/guide-to-installing-the-extension.html
- GitHub (News Plugin): https://github.com/gp247net/news

