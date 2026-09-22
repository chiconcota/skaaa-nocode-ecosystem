# PROJECT MANAGER: PLUGGABLE NODES FRAMEWORK (HỆ THỐNG CẮM RÚT NODE CỘNG ĐỒNG)
@status: 🟢 Done | @target_milestone: MILESTONE 2 | @last_update: 2026-07-09

> [!NOTE]
> Tài liệu này quản lý tiến độ, kiến trúc và kế hoạch triển khai của **Pluggable Nodes Framework** - hệ thống cắm rút cho phép các plugin bên thứ ba đăng ký các Node tùy biến vào đồ thị DAG của **Skaaa Logic Engine** mà không cần sửa đổi mã nguồn Core.

---

## 1. MỤC TIÊU CỐT LÕI (CORE GOALS)
- **Kiến trúc Decoupled mở rộng:** Định hình một bộ chuẩn đăng ký Node. Giúp tách rời 100% các tích hợp bên ngoài (như Telegram, Stripe, Video Engine) ra khỏi nhân Logic Core.
- **Dynamic Form Settings (JSON Schema):** Bên thứ ba chỉ cần khai báo metadata dạng JSON Schema ở PHP backend để React frontend tự động vẽ ra Form cấu hình tương ứng trong Settings Panel, loại bỏ việc bắt buộc viết code React cho nhà phát triển add-on.
- **An toàn & Bảo mật:** Hạn chế chạy mã PHP thô trực tiếp trong canvas. Toàn bộ logic thực thi của bên thứ ba được gói cứng trong Class kế thừa interface `Skaaa_Logic_Node` được kiểm soát chặt chẽ.

---

## 2. THIẾT KẾ KIẾN TRÚC & PHÂN CHIA TRÁCH NHIỆM

```text
  [ Plugin thứ 3 (PHP) ]
           │
           ▼ (Hook vào filter 'skaaa_logic_registered_nodes')
  [ Skaaa Logic Core (PHP) ] ──(wp_localize_script)──> [ window.SKAAA_LOGIC_PLUGINS (JS Context) ]
                                                                      │
                                                                      ▼
                                                     [ React Flow Canvas & Sidebar (React) ]
                                                                      │
                                                                      ▼ (Đọc JSON Schema)
                                                     [ Dynamic Form Settings Panel ]
```

### A. Tầng Backend (PHP Core & Plugin API)
- **Filter API:** Cung cấp filter hook `skaaa_logic_registered_nodes` cho phép đăng ký:
  - Class thực thi (implement `Skaaa_Logic_Node`).
  - Label, Mô tả, Category, Icon.
  - JSON Schema để dựng UI (các field: `text`, `number`, `select`, `textarea`, `toggle`...).
- **API Registry:** Class `Skaaa_Node_Registry` thu thập, quản lý và xác thực tính hợp lệ của các Node đăng ký.
- **Localize Script Data:** Chuyển đổi mảng đăng ký PHP thành biến JavaScript toàn cục `window.SKAAA_LOGIC_PLUGINS` khi tải trang Builder.

### B. Tầng Frontend (React Flow Editor)
- **Sidebar Integration:** React đọc từ `window.SKAAA_LOGIC_PLUGINS` để tự động render danh sách Node có thể kéo thả tương ứng với từng Category.
- **Dynamic Form Settings Panel:** Khi người dùng click vào một node dạng cắm rút, một component React trung gian (`DynamicNodeSettings`) sẽ phân giải JSON Schema của node đó để render giao diện thiết lập cài đặt động, tự động đồng bộ hóa giá trị thay đổi vào `node.data`.

---

## 3. KẾ HOẠCH HÀNH ĐỘNG (ACTION ITEMS)

- [x] **Phase 1: Xây dựng Backend Registry API (PHP)**
  - [x] Khởi tạo class `Skaaa_Node_Registry` để làm nơi lưu trữ và điều phối tập trung.
  - [x] Triển khai WP filter `skaaa_logic_registered_nodes` và hàm helper đăng ký.
  - [x] Nạp các node mặc định hiện tại (`Set Data`, `DB Action`...) thông qua chính registry này để đồng nhất kiến trúc.
  - [x] Đẩy dữ liệu đăng ký sang JS thông qua `wp_localize_script`.

- [x] **Phase 2: React Core Dynamic Sidebar Loading (Frontend JS)**
  - [x] Sửa đổi component `Sidebar.jsx` để đọc động danh sách nodes từ `window.SKAAA_DAG_CONTEXT.AVAILABLE_NODES` thay vì dùng hằng số static.
  - [x] Cấu hình cơ chế kéo thả cho các Node động (truyền đúng class và JSON Schema sang payload của React Flow).

- [x] **Phase 3: Xây dựng Dynamic Settings Panel (JSON Schema Form Builder)**
  - [x] Tạo component React `DynamicNodeSettings.jsx` để tự động render Form dựa trên các kiểu trường dữ liệu phổ biến:
    - Kiểu `text`, `number`, `password`.
    - Kiểu `textarea` (hỗ trợ component gợi ý biến `SkaaaFXInput`/`SkaaaFXTextarea`).
    - Kiểu `select` (dropdown tĩnh).
    - Kiểu `toggle`/`checkbox` bật/tắt.
  - [x] Đồng bộ hóa dữ liệu form động về state `nodes` của React Flow.

- [x] **Phase 4: Tích hợp Thực tế & Kiểm thử E2E**
  - [x] Tạo 1 Plugin Demo bên thứ ba mẫu (ví dụ: `skaaa-telegram-sender`) để test cắm rút E2E.
  - [x] Xác nhận:
    1. Node xuất hiện ở Sidebar và kéo thả được trên Canvas.
    2. Click vào node hiển thị đúng Form Settings động và lưu được cấu hình.
    3. Chạy thử Workflow, Node chạy đúng logic PHP `execute()` và truyền dữ liệu mượt mà.

- [x] **Phase 5: Tích hợp Extensions Manager lên Skaaa System Dashboard**
  - [x] Viết helper quét các plugin addon có tiền tố `skaaa-` (loại trừ các plugin core).
  - [x] Triển khai API AJAX trong `Framework_UI` xử lý bật/tắt (`activate_plugins`/`deactivate_plugins`) và xóa vật lý (`delete_plugins`) của addons.
  - [x] Thiết kế UI/UX dạng Card cho từng addon, tích hợp Toggle Switch và icon Thùng rác (Xóa) kèm confirm bảo mật trên Dashboard chính.

---

## 4. TIÊU CHÍ NGHIỆM THU (ACCEPTANCE CRITERIA)
1. Thêm một file plugin mới bên ngoài không được làm lỗi hoặc crash core Logic Engine.
2. Trình kéo thả React Flow tự động nhận diện và hiển thị các Node của plugin ngoài mà không cần chạy lại lệnh `npm run build` cho mã nguồn Core.
3. Dữ liệu cấu hình nhập từ form động phải được lưu trữ chuẩn xác dưới dạng JSON trong cột `graph` của bảng phẳng MySQL.
4. Cơ chế chạy của Workflow Runner khởi tạo và thực thi đúng class của bên thứ ba, xử lý error port đúng tiêu chuẩn.
