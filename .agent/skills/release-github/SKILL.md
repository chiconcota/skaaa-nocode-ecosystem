---
name: release-github
description: Quy trình tự động hóa kiểm tra phiên bản SemVer, đóng gói ZIP và phát hành release trên GitHub
---

# RELEASE AUTOMATION WORKFLOW (/release-github)
@status: IMPLEMENTED | @purpose: Quy trình đóng gói & Phát hành phiên bản mới | @last_update: 2026-06-05

Tài liệu này hướng dẫn cách sử dụng công cụ tự động hóa để đóng gói các Plugin thuộc hệ sinh thái **Skaaa No-Code Ecosystem** và phát hành chúng lên GitHub Releases một cách chuẩn xác, sạch sẽ nhất.

---

## 1. CẤU PHẦN TỰ ĐỘNG HÓA (COMPONENTS)

* **Script chính:** `wp-content/plugins/release.js`
  * Chịu trách nhiệm tự động quét log, tạo ghi chú phát hành và nén ZIP các plugin.
* **Script phụ trợ:** `wp-content/plugins/zip-all.js`
  * Chứa cấu hình nén zip mặc định của hệ thống.

### Danh sách các tệp được nén (Plugins):
1. `skaaa-no-code-design` (`wp-content/plugins/skaaa-no-code-design/`)
2. `skaaa-data-pro` (`wp-content/plugins/skaaa-data-pro/`)
3. `skaaa-logic-engine` (`wp-content/plugins/skaaa-logic-engine/`)
4. `skaaai` (`wp-content/plugins/skaaai/`)

---

## 2. ĐIỀU KIỆN TIÊN QUYẾT (PREREQUISITES)

Trước khi kích hoạt quy trình phát hành, lập trình viên/AI phải đảm bảo:
1. **Nâng cấp số phiên bản (SemVer):** Cập nhật số phiên bản chính xác tại Comment Header của file PHP chính của các plugin cần phát hành (ví dụ: `Version: 1.2.2`).
2. **Ghi log hệ thống:** Viết đầy đủ nhật ký thay đổi của phiên bản mới vào phần `## 6. RECENT LOGS` của file `.skaaa-ai/1-overview/system_map.md`. Dòng log bắt buộc phải chứa ký tự phiên bản dạng `(vX.Y.Z)` hoặc `vX.Y.Z` để script nhận diện được.

---

## 3. CÁC BƯỚC THỰC HIỆN

### Bước 1: Kiểm tra trạng thái Git
Đảm bảo đã commit tất cả thay đổi trên nhánh đang làm việc.
```bash
git status
```

### Bước 2: Chạy script đóng gói
```bash
node wp-content/plugins/zip-all.js
```

### Bước 3: Kiểm tra tính toàn vẹn của tệp ZIP
```bash
unzip -l wp-content/plugins/skaaai-v1.2.2.zip | grep scaffold
```
