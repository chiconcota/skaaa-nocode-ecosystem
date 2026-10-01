---
name: debug-workflow
description: Quy trình 5 bước điều tra, chẩn đoán nguyên nhân gốc rễ (RCA) và sửa lỗi ngoại khoa trong hệ sinh thái Skaaa
---

# DEBUG WORKFLOW — SKAAA NO-CODE ECOSYSTEM
@trigger: Khi User yêu cầu sửa lỗi, debug, hoặc khắc phục sự cố trong hệ sinh thái Skaaa.
@role: Elite Senior Debugging Engineer — Chỉ sửa lỗi, không viết tính năng mới trừ khi được yêu cầu rõ ràng.

## 0. CORE SKILLS (Tư duy cần có)
- Root Cause Analysis (RCA): Trace-logging, exception analysis, logical flow auditing.
- Context Isolation: Chỉ đọc code/tài liệu liên quan đến bug, bỏ qua phần không liên quan.
- Regression Prevention: Đánh giá tác động lan tỏa trước khi sửa.
- Surgical Fix: Sửa chính xác dòng lỗi, KHÔNG rewrite cả file.

## PIPELINE: 5 BƯỚC BẮT BUỘC (KHÔNG ĐƯỢC BỎ BƯỚC)

### Step 0 — Memory Check (Nạp ký ức trước khi làm bất cứ gì)
Mục đích: Tránh debug lại bug đã phân tích từ phiên trước.
1. Đọc `.skaaa-ai/2-memory/checkpoint.md` — Xem bug đang dở dang, giả thuyết cũ, và hướng debug đã thử.
2. Đọc `.skaaa-ai/2-memory/decision-log.md` (phần gần nhất) — Xem quyết định thiết kế liên quan.
3. Nếu checkpoint có ghi gợi ý debug cụ thể → Ưu tiên thực hiện gợi ý đó trước, không bắt đầu lại từ đầu.
4. Hỏi User xem bug thuộc Plugin nào → Chỉ đọc tài liệu trong `.skaaa-ai/3-ecosystem/[Tên Plugin]/`.
5. Nếu bug hoàn toàn mới: Bỏ qua bước 3, chuyển thẳng sang Step 1.

### Step 1 — Comprehend & Clarify (CHƯA ĐƯỢC CHẠM VÀO CODE)
Mục đích: Hiểu đúng vấn đề trước khi hành động.
1. Phân tích thông tin đầu vào: mã nguồn, dữ liệu test, hành vi mong đợi vs. hành vi thực tế, log lỗi.
2. **Cache Invalidation Check:** Kiểm tra xem sự cố có phải do Stale Cache (Transient CSS JIT `skaaa_dynamic_tailwind_classes_cache`, cache Organisms `organisms-cache.json`, hoặc Design Tokens `tokens.json`) hay không.
3. Trả lời 2 câu hỏi bắt buộc:
   - "Logic gãy ở đâu?" — Mô tả chính xác điểm đứt gãy.
   - "Tại sao nó xảy ra?" — Giải thích nguyên nhân gốc rễ (không phải triệu chứng).

### Step 2 — Hypothesize & Verify
Mục đích: Chứng minh nguyên nhân trước khi sửa.
1. Đặt giả thuyết: "Nếu [nguyên nhân] đúng, thì [hiện tượng] sẽ xảy ra khi [điều kiện]."
2. Kiểm chứng giả thuyết bằng read-only tools: `view_file`, `grep_search`, hoặc `run_command` (chạy script test cô lập).
3. Đánh giá tác động lan tỏa (Blast Radius): Kiểm tra xem việc sửa đổi có ảnh hưởng đến các module khác hay không.

### Step 3 — Surgical Fix
Mục đích: Sửa dứt điểm, can thiệp tối thiểu.
1. Sửa chính xác dòng gây lỗi bằng `replace_file_content` hoặc `multi_replace_file_content`.
2. Tuân thủ 100% các luật kỹ thuật:
   - An toàn bảo mật (Sanitize, Nonce, Escaping).
   - i18n: Chuỗi hiển thị tiếng Anh, bọc hàm chuẩn.
   - Decoupled: Giao tiếp qua WP Hooks hoặc Alpine.store.

### Step 4 — Verify & Document
Mục đích: Đảm bảo bug đã hết, không sinh bug mới, và lưu vết.
1. Kiểm tra lại hành vi lỗi xem đã biến mất chưa.
2. Kiểm tra các chức năng liên quan xem có bị gãy không (Regression check).
3. Ghi lại kết quả vào `.skaaa-ai/2-memory/checkpoint.md` và `.skaaa-ai/2-memory/decision-log.md`.
