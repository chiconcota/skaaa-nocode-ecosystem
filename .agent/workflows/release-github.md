---
description: RELEASE AUTOMATION WORKFLOW
---

# RELEASE AUTOMATION WORKFLOW
@status: IMPLEMENTED | @purpose: Quy trình đóng gói & Phát hành phiên bản mới | @last_update: 2026-06-05

Tài liệu này hướng dẫn cách sử dụng công cụ tự động hóa để đóng gói các Plugin thuộc hệ sinh thái **Skaaa No-Code Ecosystem** và phát hành chúng lên GitHub Releases một cách chuẩn xác, sạch sẽ nhất.

---

## 1. CẤU PHẦN TỰ ĐỘNG HÓA (COMPONENTS)

* **Script chính:** [release.js](file:///home/chiconcota/Local%20Sites/skaaa-core-builder/app/public/wp-content/plugins/release.js)
  * Chịu trách nhiệm tự động quét log, tạo ghi chú phát hành và nén ZIP các plugin.
* **Script phụ trợ:** [zip-all.js](file:///home/chiconcota/Local%20Sites/skaaa-core-builder/app/public/wp-content/plugins/zip-all.js)
  * Chứa cấu hình nén zip mặc định của hệ thống.

### Danh sách các tệp được nén (Plugins):
1. `skaaa-no-code-design` (Đường dẫn: `wp-content/plugins/skaaa-no-code-design/`)
2. `skaaa-data-pro` (Đường dẫn: `wp-content/plugins/skaaa-data-pro/`)
3. `skaaa-logic-engine` (Đường dẫn: `wp-content/plugins/skaaa-logic-engine/`)

### Quy tắc loại bỏ tệp (Ignore Patterns):
Script tự động bỏ qua các thư mục/file phát triển để tối ưu dung lượng:
* `node_modules/` (Thư viện phát triển JS)
* `src/` hoặc `assets/js/src/` (Mã nguồn React/JS thô chưa build)
* `package.json`, `package-lock.json`
* `webpack.config.js`, `vite.config.js`
* `build-zip.js`, `release.js`
* `.gitignore`
* Thư mục ẩn `.git/`

---

## 2. ĐIỀU KIỆN TIÊN QUYẾT (PREREQUISITES)

Trước khi kích hoạt quy trình phát hành, lập trình viên/AI phải đảm bảo:
1. **Nâng cấp số phiên bản (SemVer):** Cập nhật số phiên bản chính xác tại Comment Header của file PHP chính của các plugin cần phát hành (ví dụ: `Version: 1.1.11` trong `skaaa-logic-engine.php`).
2. **Ghi log hệ thống:** Viết đầy đủ nhật ký thay đổi của phiên bản mới vào phần `## 6. RECENT LOGS` của file [system_map.md](file:///home/chiconcota/Local%20Sites/skaaa-core-builder/app/public/.skaaa-ai/1-overview/system_map.md). Dòng log bắt buộc phải chứa ký tự phiên bản dạng `(vX.Y.Z)` hoặc `vX.Y.Z` để script nhận diện được.
3. **Môi trường Git sạch:** Đã commit toàn bộ code mới trên nhánh đang làm việc.

---

## 3. QUY TRÌNH PHÁT HÀNH CHI TIẾT (STEP-BY-STEP)

Quy trình phát hành được phân chia rõ ràng giữa **AI (Tự động hóa)** và **Bạn (Kiểm soát cuối)**:

### GIAI ĐOẠN 1: AI THỰC HIỆN (TỰ ĐỘNG HÓA)

#### Bước 1: Khởi chạy Script Đóng gói & Trích xuất Log
Bạn yêu cầu AI: *"Hãy đóng gói và phát hành phiên bản v[version]"* (ví dụ: `v1.1.11`). AI sẽ chạy:
```bash
# Tự động nhận diện mốc và đóng gói tới v1.1.11
node release.js v1.1.11
```
* **Kết quả đầu ra:**
  * 3 file zip sạch được sinh ra tại `wp-content/plugins/`.
  * Tệp ghi chú changelog tương ứng được tạo: `wp-content/plugins/release-notes-v1.1.11.md`.

#### Bước 2: Đánh Tag Git & Push lên GitHub
AI chạy lệnh Git để đánh dấu cột mốc phiên bản trên mã nguồn:
```bash
git tag v1.1.11
git push origin v1.1.11
```

---

### 🔔 ĐIỂM CHẠM BÀN GIAO (AI THÔNG BÁO CHO BẠN)
Sau khi hoàn thành Bước 2, AI gửi thông báo bàn giao:
> *"Tôi đã hoàn thành đóng gói 3 file ZIP và đẩy Git Tag `v1.1.11` lên GitHub thành công.*
> *Ghi chú changelog đã sẵn sàng tại: [release-notes-v1.1.11.md](file:///home/chiconcota/Local%20Sites/skaaa-core-builder/app/public/wp-content/plugins/release-notes-v1.1.11.md).*
> *Mời bạn thực hiện các bước tiếp theo trên giao diện GitHub Web."*

---

### GIAI ĐOẠN 2: BẠN THỰC HIỆN (THỦ CÔNG TRÊN WEB)

#### Bước 3: Tạo bản phát hành (Release) trên GitHub
1. Truy cập trang phát hành tag mới:
   [https://github.com/chiconcota/skaaa-nocode-ecosystem/releases/new?tag=v1.1.11](https://github.com/chiconcota/skaaa-nocode-ecosystem/releases/new?tag=v1.1.11)
2. Nhập tiêu đề Release (Release Title): `Release v1.1.11`.

#### Bước 4: Tải lên Assets & Hoàn tất
1. Mở file [release-notes-v1.1.11.md](file:///home/chiconcota/Local%20Sites/skaaa-core-builder/app/public/wp-content/plugins/release-notes-v1.1.11.md), copy toàn bộ nội dung và dán vào phần **"Describe this release"** trên GitHub.
2. Kéo thả 3 file ZIP đã đóng gói vào phần **"Attach binaries by dropping them here..."**:
   * `skaaa-no-code-design.zip`
   * `skaaa-data-pro.zip`
   * `skaaa-logic-engine.zip`
3. Nhấp nút **Publish release**. Hoàn thành!

---

## 4. CƠ CHẾ DỰ PHÒNG BẢO VỆ (FAIL-SAFE MECHANISM)

* **Thiếu thư viện Node:** Nếu môi trường chưa chạy `npm install` (thiếu module `archiver`), script tự động chuyển sang sử dụng lệnh `zip` mặc định của Linux hệ thống (`/usr/bin/zip`).
* **Không tìm thấy log:** Nếu phiên bản chưa được cập nhật vào `system_map.md`, script tự động tạo một tệp Release Notes mặc định tránh dừng chương trình đột ngột.
