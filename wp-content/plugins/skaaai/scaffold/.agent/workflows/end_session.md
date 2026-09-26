---
description: Kết thúc ca làm việc (Niêm phong bộ nhớ, ghi Sổ Quyết Định & Checkpoint bàn giao)
---

# KẾT THÚC CA LÀM VIỆC (end_session.md)
@trigger: Khi Giám Đốc gõ /end_session hoặc hoàn thành công việc trong ca

> Mục tiêu: Tổng hợp sản phẩm đã làm trong ca, niêm phong bàn giao vào checkpoint để ca sau tiếp tục mượt mà.

---

## BƯỚC 1: RÀ SOÁT & TỔNG HỢP SẢN PHẨM TRONG CA
1. Liệt kê toàn bộ các block, trang (Page/Post) hoặc bảng phẳng MySQL (`wp_skaaa_data_*`) đã tạo/chỉnh sửa trong phiên.
2. Lấy sẵn URL xem trước (Preview URL) của các trang đã xuất bản để Giám Đốc nghiệm thu trực tiếp.
3. Chạy kiểm định nhanh bằng tool:
   ```bash
   php .agent/harness/block-tool.php --validate="<chuỗi_block>"
   ```

---

## BƯỚC 2: CẬP NHẬT SỔ BÀN GIAO CA KÍP (checkpoint.md)
Mở tệp `.skaaa-ai/2-company-memory/checkpoint.md` và ghi đè nội dung mới với đủ 4 mục chuẩn:
1. **Thời gian & Trạng thái hiện tại:** Ngày giờ cập nhật, trạng thái dự án.
2. **Danh mục sản phẩm hoàn thành (File & Page Manifest):**
   - Danh sách trang web / Landing page đã tạo kèm link preview.
   - Danh sách bảng CSDL hoặc file tuỳ biến đã can thiệp.
3. **Vấn đề đã xử lý & Kiểm định chất lượng:**
   - Đã kiểm tra giao diện đạt chuẩn Flat DOM, `@click.prevent`, không lỗi console.
4. **Kế hoạch bàn giao ca kế tiếp (Next Actions):**
   - Ghi rõ việc cần làm tiếp theo để nhân viên ca sau vào đọc là làm được ngay mà không cần hỏi lại.

---

## BƯỚC 3: GHI SỔ QUYẾT ĐỊNH (decision-log.md)
- Nếu trong ca có quyết định kiến trúc quan trọng do Giám Đốc chốt (chọn phong cách layout, quy định cấu trúc bảng form, đổi token màu sắc...), ghi ngắn gọn 1 mục lên đầu file `.skaaa-ai/2-company-memory/decision-log.md`.

---

## BƯỚC 4: BÁO CÁO BÀN GIAO CHO GIÁM ĐỐC
Báo cáo lễ phép cho Giám Đốc:
*"Dạ thưa sếp, ca làm việc đã hoàn tất và được niêm phong tại checkpoint.md. Toàn bộ tài nguyên và link nghiệm thu đã sẵn sàng để ca tiếp theo tiếp tục vận hành ngay ạ!"*
