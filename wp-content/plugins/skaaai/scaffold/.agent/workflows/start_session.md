---
description: Bắt đầu ca làm việc của Công Ty Thu Nhỏ (Nạp Hồ Sơ Doanh Nghiệp & Sổ Bàn Giao)
---

# BẮT ĐẦU CA LÀM VIỆC (start_session.md)
@trigger: Khi Giám Đốc gõ /start_session hoặc bắt đầu ca làm việc mới

> Mục tiêu: Nạp nhanh hồ sơ công ty và nhận bàn giao tiến độ từ ca trước để sẵn sàng phục vụ Giám Đốc.

---

## BƯỚC 1: NẠP BỘ NHỚ & HỒ SƠ DOANH NGHIỆP (CONTEXT LOADING)
Mở và đọc tuần tự các tệp sau để nắm trọn vẹn ngữ cảnh:

1. **Sổ Bàn Giao Ca Trước (BẮT BUỘC ĐỌC ĐẦU TIÊN):**
   - Đọc `.skaaa-ai/2-company-memory/checkpoint.md` để biết ca trước đã làm xong gì và việc gì đang dở dang.
2. **Hồ Sơ Năng Lực & Bản Đồ Công Nghệ:**
   - Đọc `.skaaa-ai/1-company-profile/system-map.md` (Hệ thống 4 plugin + 1 theme).
3. **Quy Chuẩn Nhận Diện & Design Tokens:**
   - Đọc `.skaaa-ai/1-company-profile/brand-guidelines.md` (Bảng màu, Typography, Radius, Token doanh nghiệp).
4. **Sổ Tay Quyết Định Kiến Trúc:**
   - Đọc phần đầu của `.skaaa-ai/2-company-memory/decision-log.md` (Các quyết định Giám Đốc đã chốt).
5. **Hồ Sơ Dự Án Hiện Tại:**
   - Đọc `.skaaa-ai/3-project-dossier/client-brief.md` để kiểm tra thông tin Logo, Ảnh banner, Menu và Copywriting.

---

## BƯỚC 2: BÁO CÁO SẴN SÀNG CHO GIÁM ĐỐC (READY CHECK)
Phản hồi ngắn gọn và lễ phép cho Giám Đốc:
- Báo cáo đã nạp xong Hồ sơ công ty và Sổ bàn giao `checkpoint.md`.
- Nêu tóm tắt 1 dòng về tình trạng công việc hiện tại từ `checkpoint.md` hoặc `client-brief.md`.
- Sẵn sàng nhận lệnh: Hỏi Giám Đốc muốn tiếp tục công việc dở dang hay giao nhiệm vụ mới.
