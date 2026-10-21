# Kế hoạch Hành động & Backlog Post-MVP (Milestone 1)

> [!NOTE]
> Tài liệu này lưu trữ danh sách các hạng mục phát triển sau giai đoạn MVP, được sắp xếp theo thứ tự ưu tiên từ quan trọng/gấp nhất đến ít quan trọng/chưa cần gấp, nhằm chuẩn bị nền tảng vững chắc cho hệ sinh thái Skaaa Ecosystem tiến lên Milestone 1.

---

## 1. MỤC TIÊU TỔNG QUÁT (MASTER GOALS)
- **Tối ưu hóa & Đóng gói:** Chuẩn bị các cấu phần mở rộng cho phân hệ Giao diện, Biên dịch và Tự động hóa.
- **Tiêu chuẩn hóa:** Tổ chức tài liệu và mã nguồn theo hướng mô-đun hóa cao, giảm thiểu phụ thuộc chéo giữa 4 Plugins chính.
- **Trải nghiệm Nocode Nâng cao:** Đưa các cấu hình phức tạp (như script, tương tác, phân quyền) về dạng trực quan trên Gutenberg.

---

## 2. MA TRẬN SẮP XẾP ƯU TIÊN CÔNG VIỆC

### 🔴 ƯU TIÊN 1: QUAN TRỌNG & RẤT GẤP (Milestone 1 Lõi - Bắt buộc làm ngay)

#### A. Refactor Cơ Sở Dữ Liệu Logic Engine (MySQL Flat Table)
*Giải quyết nợ kỹ thuật lưu trữ của hệ thống tự động hóa.*
- [x] **Di chuyển Storage sang bảng phẳng:**
  - [x] Di chuyển toàn bộ dữ liệu đồ thị workflow đang lưu trữ trong `wp_options` (`skaaa_logic_simple_workflows`) sang bảng phẳng MySQL `skaaa_data_sys_workflows` (v1.1.0).
  - [x] Giúp loại bỏ hoàn toàn hiện tượng tự động nạp (autoload bloat) khi đồ thị phình to và triệt tiêu race condition khi lưu nhiều luồng.

#### B. Phân loại Skaaa Organisms (Skaaa Organisms Categorization & Folder Management)
*Khắc phục khẩn cấp tình trạng rối loạn UI quản trị khi số lượng Organisms (Symbols/Reusable Blocks) phình to.*
- [x] **Mở rộng Schema lưu trữ:**
  - [x] Nâng cấp bảng flat table `skaaa_data_sys_organisms` bằng cách bổ sung cột `category` (chuỗi định danh/slug) hoặc thiết lập hệ thống tag phân loại.
- [x] **Tối ưu UI Quản lý (Organisms Dashboard):**
  - [x] Xây dựng bộ lọc theo Thư mục/Danh mục (Category Filter Tabs) trực quan trên Dashboard quản trị Organisms.
  - [x] Cho phép người dùng tạo, sửa, xóa các Danh mục phân loại động và kéo thả Organisms vào danh mục tương ứng.
- [x] **Nâng cấp Gutenberg Inserter (+):**
  - [x] Phân chia danh sách Organisms trong tab Inserter của Gutenberg thành các nhóm rõ ràng (ví dụ: `Header`, `Footer`, `Hero Section`, `Data Cards`, `Forms`) thay vì đổ đống toàn bộ danh sách, giúp tăng tốc độ tìm kiếm block.

#### C. AI JSON Blueprint Import (Logic Engine)
*Nền tảng giao tiếp với AI, biến Logic Engine thành Execution Runtime.*
- [x] **Chuẩn hóa & Import:**
  - [x] Chuẩn hóa schema JSON của đồ thị DAG.
  - [x] Triển khai tính năng Import trực tiếp mã JSON Blueprint do AI tự động sinh (qua giao diện hoặc endpoint REST API) để tự động dựng nodes và connections tương ứng trên Canvas.

#### D. Hoàn thiện Node Render HTML (Render Template)
*Cấu phần render email/modal động hiện đang trống trơn.*
- [x] **Nâng cấp UI Settings:**
  -[x] Xây dựng giao diện cấu hình trực quan trên Settings Panel (hỗ trợ code editor nhỏ cho Raw HTML và preview trực quan trước khi lưu kết quả render vào biến payload).

---

### 🟡 ƯU TIÊN 2: QUAN TRỌNG NHƯNG CHƯA GẤP (Milestone 1 Phụ - Triển khai sau khi xong các tác vụ lõi)

#### E. Hoàn thiện UI cho SkaaaFX AST Evaluator (Data Picker)
*Đơn giản hóa trải nghiệm lập công thức biểu thức của Power User.*
- [x] **Autocomplete & Data Picker:**
  - [x] Xây dựng giao diện Autocomplete/Gợi ý biến (Data Picker) ở `SettingsPanel.jsx` để giúp người dùng Nocode viết cú pháp SkaaaFX dễ dàng hơn.

#### F. Kế thừa Chuyển hướng cấp độ App (App-Level Redirect Fallback)
*Quản trị bảo mật tập trung ở mức Workspace (Theo dõi chi tiết tại [pm_workspace_storage.md](file:///home/chiconcota/Local%20Sites/skaaa-core-builder/app/public/.skaaa-ai/1-overview/project-managers/pm_workspace_storage.md)).*
- [x] **Workspace Security Fallback:**
  - [x] Chuẩn hóa lưu trữ Workspace từ `wp_options` về bảng phẳng MySQL `wp_skaaa_data_sys_apps`.
  - [x] Thiết lập bảng cấu hình redirect chung cho toàn bộ App (Workspace).
  - [x] Cơ chế tự động fallback về `wp-login.php` hoặc URL tùy chọn khi người dùng không đủ quyền truy cập App View.

#### G. Thư viện mã nguồn tập trung (Skaaa Scripts Library) & Khối `skaaa-code`
*Giải quyết bài toán nhúng và quản lý custom JS/CSS an toàn, hiệu năng cao.*
*Chi tiết tiến độ và kế hoạch hành động riêng tại:*
- [pm_skaaa_scripts_library.md](file:///home/chiconcota/Local%20Sites/skaaa-core-builder/app/public/.skaaa-ai/1-overview/project-managers/pm_skaaa_scripts_library.md)
- [pm_skaaa_code_block.md](file:///home/chiconcota/Local%20Sites/skaaa-core-builder/app/public/.skaaa-ai/1-overview/project-managers/pm_skaaa_code_block.md)

- [x] **Skaaa Scripts Library:**
  - [x] Giao diện quản lý các đoạn mã (như Alpine store, CDN Chart, Google Analytics).
  - [x] Cấu hình nạp code: Header, Footer, Inline, nạp Global hay nạp theo điều kiện trang.
- [ ] **Khối `skaaa-code`:**
  - [x] Thay thế Custom HTML block. Hỗ trợ code editor trực quan (Monaco/CodeMirror) hoặc liên kết script từ Thư viện.
  - [x] Cơ chế Server-side Deduplication: Chống nạp lặp CSS/JS trùng nhau khi render nhiều khối trên cùng một trang.

---

### 🔵 ƯU TIÊN 3: PHÁT TRIỂN TIẾP THEO (Post-MVP Backlog - Milestone 1 mở rộng)

#### H. Tích hợp UI cho Async Worker/Queue (Logic Engine)
- [ ] **Edge/Node Async Flag:**
  - [ ] Xây dựng Edge custom UI hoặc Node setting trên React Flow để người dùng có thể bật/tắt cờ `async` trực quan trên các liên kết.

#### I. Phân quyền hiển thị (RBAC UI - Block-level)
*Ẩn/hiện thành phần UI dựa trên Role người dùng ở Frontend.*
- [ ] **RBAC Tầng hiển thị:**
  - [ ] Thiết kế React Inspector panel cho phép chọn User Roles được phép xem block.
  - [ ] Xử lý logic PHP Server side: Kiểm tra `current_user_can()` hoặc đối chiếu role trước khi render HTML block.

#### J. Tối ưu hóa Skaaapine Engine (Interaction Simulator)
- [ ] **Hạng mục tối ưu tương lai:**
  - [ ] Nâng cấp tương thích đầy đủ cho cơ chế liên kết dữ liệu hai chiều `x-model` đối với thẻ input text và text-area (hiện mới ổn định cho checkbox/radio).
  - [ ] Xử lý tự động đóng/mở các panel block lồng nhau (Nested Components) mà không gây gián đoạn luồng kéo thả của React.

#### K. Inline Tailwind Highlighter & Gutenberg Format API (Skaaa No-Code Design)
*Cho phép người dùng gán style Tailwind trực tiếp vào một cụm từ ngay giữa khối Text (Heading / Paragraph).*
- [ ] **Gutenberg Format API (`registerFormatType`):**
  - Đăng ký Format Type `skaaa/tailwind-inline` sử dụng `@wordpress/rich-text`.
  - Hiển thị nút công cụ "🎨 Skaaa Inline Style" trên Inline Floating Toolbar khi người dùng bôi đen text trong khối `skaaaaa-builder/text`.
  - Cung cấp Popover chọn nhanh màu Design Tokens (`text-primary`, `text-secondary`, `bg-primary/10`, `font-bold`...) hoặc ô nhập class Tailwind tùy biến.
  - Tự động bọc cụm từ được chọn bằng `<span class="...">`.
- [ ] **JIT Parity & Scanner Verification:**
  - Tận dụng cơ chế JIT Scanner có sẵn tại `Style_Manager::extract_block_classes()` (đã có regex quét class trong thuộc tính `content` và `innerHTML`).
  - Đảm bảo `SkaaaWind` trong Gutenberg Canvas tự động re-compile CSS tức thì khi áp dụng format.

---


### 🟢 ƯU TIÊN 4: ĐỊNH HƯỚNG TƯƠNG LAI (Roadmap Phase 6+)

#### K. Trình biên dịch Editor (SkaaaWind JS Core)
- [x] **Tailwind Compiler Client-side:**
  - [x] Viết bộ parser Tailwind JIT bằng Vanilla JS chạy trực tiếp trên browser của Gutenberg Editor (SkaaaWind).
  - [x] Loại bỏ hoàn toàn sự phụ thuộc vào Tailwind CDN ngoài editor để tăng tốc độ load và bảo mật offline.
  - [x] Đồng bộ hóa màu Dark Mode (`darkMode: 'class'`) thời gian thực ngay trong màn hình thiết kế kèm nút Dark/Light Mode toggle trực tiếp trên toolbar.

#### L. Mở rộng Trigger & Circuit Breaker (Logic Engine)
- [ ] **Trigger ngoại vi & Bảo vệ:**
  - [ ] Triển khai `[T2] Webhook In` đón nhận payload từ bên thứ ba (Stripe, Momo, Zalo).
  - [ ] Triển khai `Cron Trigger` lập lịch chạy định kỳ sử dụng Action Scheduler.
  - [ ] Viết bộ lọc chặn đệ quy vô hạn (Circuit Breaker) trong workflow và dọn dẹp state trung gian sau khi chạy để tránh tràn RAM.
- [ ] **Hệ thống Node Cộng đồng (Community Nodes & Pluggable Actions Framework - Milestone 2+):**
  - [ ] Thiết kế cơ chế tách biệt các hành động của Client Response và Trigger thành các Node con độc lập trên Sidebar (User Nodes / Community Nodes).
  - [ ] Cho phép bên thứ ba đăng ký và đóng gói các custom node thành các module/add-on cài đặt riêng lẻ.
- [ ] **Đóng gói Thư viện Lõi (Packaging Phase - Định hướng Tương lai):**
  - [ ] Khi API của SkaaaFX, Skaaapine đã ổn định và "đóng băng" (không thay đổi lớn), thực hiện tách các thư mục lõi (`inc/skaaafx/`, `inc/design-engine/`...) thành các repository độc lập.
  - [ ] Phát hành lên Packagist (cho Composer) và npm registry để cộng đồng mã nguồn mở cùng phát triển.

