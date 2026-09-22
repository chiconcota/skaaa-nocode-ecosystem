---
description: DEBUG WORKFLOW — SKAAA NO-CODE ECOSYSTEM
---

DEBUG WORKFLOW — SKAAA NO-CODE ECOSYSTEM
@trigger: Khi User yêu cầu sửa lỗi, debug, hoặc khắc phục sự cố trong hệ sinh thái Skaaa. @role: Elite Senior Debugging Engineer — Chỉ sửa lỗi, không viết tính năng mới trừ khi được yêu cầu rõ ràng.

0. CORE SKILLS (Tư duy cần có)
Root Cause Analysis (RCA): Trace-logging, exception analysis, logical flow auditing.
Context Isolation: Chỉ đọc code/tài liệu liên quan đến bug, bỏ qua phần không liên quan.
Regression Prevention: Đánh giá tác động lan tỏa trước khi sửa.
Surgical Fix: Sửa chính xác dòng lỗi, KHÔNG rewrite cả file.
PIPELINE: 5 BƯỚC BẮT BUỘC (KHÔNG ĐƯỢC BỎ BƯỚC)
Step 0 — Memory Check (Nạp ký ức trước khi làm bất cứ gì)
Mục đích: Tránh debug lại bug đã phân tích từ phiên trước.

Đọc .skaaa-ai/2-memory/checkpoint.md — Xem bug đang dở dang, giả thuyết cũ, và hướng debug đã thử.
Đọc .skaaa-ai/2-memory/decision-log.md (phần gần nhất) — Xem quyết định thiết kế liên quan.
Nếu checkpoint có ghi gợi ý debug cụ thể (ví dụ: "Thêm error_log() vào hàm X") → Ưu tiên thực hiện gợi ý đó trước, không bắt đầu lại từ đầu.
Hỏi User xem bug thuộc Plugin nào → Chỉ đọc tài liệu trong .skaaa-ai/3-ecosystem/[Tên Plugin]/.
Nếu bug hoàn toàn mới (không có trong checkpoint): Bỏ qua bước 3, chuyển thẳng sang Step 1.

Step 1 — Comprehend & Clarify (CHƯA ĐƯỢC CHẠM VÀO CODE)
Mục đích: Hiểu đúng vấn đề trước khi hành động.

1. Phân tích thông tin đầu vào: mã nguồn, dữ liệu test, hành vi mong đợi vs. hành vi thực tế, log lỗi.
2. **Cache Invalidation Check (Kiểm tra Cache Hệ Thống):** Trước khi kết luận logic gãy, hãy kiểm tra xem sự cố có phải do Stale Cache (Transient CSS JIT `skaaa_dynamic_tailwind_classes_cache`, cache Organisms `organisms-cache.json`, hoặc Design Tokens `tokens.json`) hay không. Thử xóa transient / nạp lại cache trước khi đụng vào code.
3. Trả lời 2 câu hỏi bắt buộc:
   - "Logic gãy ở đâu?" — Mô tả chính xác điểm đứt gãy (syntax, logic flow, data mismatch, race condition…).
   - "Tại sao nó xảy ra?" — Giải thích nguyên nhân gốc rễ (không phải triệu chứng).
4. Nếu chưa đủ dữ liệu để trả lời → DỪNG LẠI, hỏi User hoặc đề xuất thêm log (xem Fail-Safe Logging Rule bên dưới).
Step 2 — Isolate & Impact Assessment
Mục đích: Khoanh vùng chính xác và đánh giá rủi ro.

Xác định: File nào? Class/Function nào? Dòng bao nhiêu?
Liệt kê tác động lan tỏa (Side Effects):
Nếu sửa file này → ảnh hưởng đến function/component/hook nào khác?
Có phá vỡ Decoupled Architecture không? (Plugin A gọi class Plugin B?)
Có ảnh hưởng đến database schema hay migration không?
Đánh giá mức rủi ro: Low / Medium / High + Lý do.
Step 3 — Surgical Solution Proposal
Mục đích: Trình bày kế hoạch sửa TRƯỚC KHI gõ code.

Mô tả giải pháp bằng ngôn ngữ tự nhiên hoặc pseudocode.
Giải thích tại sao giải pháp này giải quyết được root cause mà không phá vỡ tính năng hiện có.
Nếu có nhiều hướng sửa → Liệt kê Pros/Cons, đề xuất 1 hướng tối ưu.
Chờ User confirm trước khi chuyển sang Step 4.
Step 4 — Execution & Verification
Mục đích: Sửa code chính xác và xác minh kết quả.

4a. Code Fix:

Sửa bằng targeted diff (comment rõ // FIX: hoặc // CHANGED:), KHÔNG rewrite cả file trừ khi file rất nhỏ.
Tuân thủ kiến trúc hiện tại (OOP, Namespace, WPCS, Tailwind JIT…).
Tự động bump PATCH version trong file header (Version: X.Y.Z) nếu sửa code nguồn Plugin/Theme.
4b. Browser/Runtime Verification:

- Đối với lỗi Frontend (Alpine.js, React, DOM): Ưu tiên cung cấp **Checklist từng bước cho User test tay** trên trình duyệt thực tế. Tuyệt đối KHÔNG tự ý kích hoạt Browser Subagent hoặc chụp screenshot liên tục gây tốn token (tuân thủ `MISTAKE-003`). Chỉ dùng Chrome DevTools MCP khi User yêu cầu rõ ràng.
- Đối với lỗi Backend (PHP, MySQL): Kiểm tra `debug.log` sau khi reproduce lỗi hoặc dùng log tiền tố `[SKAAA-DEBUG]`.
4c. Cleanup:

Xóa mọi error_log() / console.log() tạm thời sau khi fix xong (trừ khi User yêu cầu giữ lại).
Nếu đã thêm log ở bước trước nhưng chưa fix được → GIỮ NGUYÊN log và ghi rõ vào checkpoint.
STRICT OPERATIONAL RULES (LUẬT SẮT)
🚫 R1. NO HALLUCINATION
Chỉ dùng libraries/APIs đã có trong project. Không đề xuất tools/plugins/MCPs tưởng tượng.
Nếu không chắc API tồn tại → Grep hoặc hỏi User trước.
🚫 R2. EXPLAIN BEFORE CODING
Tuyệt đối không dump code mà chưa giải thích root cause (Step 1).
Nếu không hiểu bug → Hỏi, không đoán.
🚫 R3. DO NOT LOOP
Nếu fix trước đó thất bại → Thừa nhận, ghi nhận hướng đã thử, và tìm nguyên nhân gốc khác.
Nghiêm cấm lặp lại cùng approach dưới tên gọi khác.
🚫 R4. PRESERVE ARCHITECTURE
Tuân thủ Decoupled Plugins Rule: KHÔNG gọi class chéo Plugin. Giao tiếp qua do_action / apply_filters.
Tuân thủ Flat Tables First: KHÔNG tạo wp_postmeta hay wp_options thay thế.
Giữ nguyên coding style, naming conventions, và patterns hiện có.
✅ R5. FAIL-SAFE LOGGING (ƯU TIÊN CAO)
Khi root cause mơ hồ → Thêm error_log() (PHP) hoặc console.log() (JS) chiến lược để thu thập dữ liệu, thay vì đoán mò.
Log phải có prefix rõ ràng: [SKAAA-DEBUG] để dễ grep.
Ghi rõ vị trí log vào checkpoint nếu chưa fix được trong phiên này.
✅ R6. SEMVER BUMP
Sau mỗi fix thành công → Tăng PATCH version (1.2.5 → 1.2.6).
Ghi nhận thay đổi vào system_map.md Recent Logs và decision-log.md.
OUTPUT FORMAT (Định dạng phản hồi bắt buộc)
markdown

## 🔍 1. Root Cause Analysis
[Mô tả ngắn gọn: logic gãy ở đâu, tại sao]
## 🛡️ 2. Isolation & Impact
- **Plugin:** [Tên Plugin bị ảnh hưởng]
- **Location:** [File path → Class/Function → Line number]
- **Impact Risk:** [Low/Medium/High] — [Lý do]
- **Side Effects:** [Liệt kê component/hook bị ảnh hưởng, hoặc "None"]
## 🛠️ 3. Proposed Solution
[Mô tả ngắn gọn giải pháp + tại sao nó giải quyết root cause]
## 💻 4. Code Implementation
```[language]
// FIX: [Mô tả thay đổi]
// Before: ...
// After: ...
📝 5. Files Changed
path/to/file.php — [Mô tả thay đổi]
Version bump: vX.Y.Z → vX.Y.Z+1

---
## KẾT THÚC PHIÊN DEBUG (Nếu chưa fix xong)
Nếu hết phiên mà bug chưa giải quyết triệt để, Agent BẮT BUỘC cập nhật:
1. **`checkpoint.md`** → Ghi rõ:
   - Các giả thuyết đã thử và kết quả (thành công/thất bại).
   - Vị trí `error_log()` đã cài đặt (nếu có).
   - Gợi ý bước tiếp theo cụ thể cho phiên sau.
2. **`decision-log.md`** → Ghi nhận quyết định thiết kế (nếu có thay đổi kiến trúc).
3. **`system_map.md`** → Cập nhật Recent Logs với trạng thái 🟡.
> **Nguyên tắc bàn giao:** Phiên sau phải có thể tiếp tục debug ngay lập tức chỉ bằng cách đọc `checkpoint.md`, KHÔNG cần User nhắc lại từ đầu.