# HƯỚNG DẪN KIỂM THỬ E2E: RENDER BIỂU ĐỒ DOANH THU VỚI CHART.JS
@status: 🟢 Active | @target: Skaaa Ecosystem | @update: 2026-06-24

Tài liệu này hướng dẫn chi tiết quy trình thiết lập từ con số 0 để xây dựng cơ sở dữ liệu phẳng, kết nối API và nhúng thư viện Chart.js hiển thị biểu đồ động ngoài frontend sử dụng block **Skaaa Code**.

---

## 📊 BƯỚC 1: Khởi tạo CSDL phẳng lưu doanh thu (`Skaaa Data Pro`)

1.  Truy cập **WordPress Admin** -> Vào menu **Skaaa Data Pro** (hoặc chọn `Manage Database` từ Dashboard chính).
2.  Click vào nút **Create Table** (Tạo bảng mới) ở Sidebar:
    *   **Table Name**: `revenue`
    *   **Display Name**: `Revenue Report`
    *   **Icon**: Chọn biểu tượng `dashicons-chart-bar`
    *   Nhấn **Create Table** để xác nhận.
3.  Click vào bảng `Revenue Report` vừa tạo để mở giao diện lưới Grid View (Airtable-like).
4.  Thêm 2 cột dữ liệu bằng cách click vào biểu tượng `[+]` ở bên phải tiêu đề cột:
    *   **Cột 1**: Display Name: `Month` | Type: `Short Text` (Chữ ngắn).
    *   **Cột 2**: Display Name: `Amount` | Type: `Number` (Số nguyên).
5.  Thêm 6 dòng dữ liệu mẫu trực tiếp trên lưới:
    *   Dòng 1: Month = `January`, Amount = `1200`
    *   Dòng 2: Month = `February`, Amount = `1900`
    *   Dòng 3: Month = `March`, Amount = `3000`
    *   Dòng 4: Month = `April`, Amount = `5000`
    *   Dòng 5: Month = `May`, Amount = `4000`
    *   Dòng 6: Month = `June`, Amount = `6500`

---

## 🔓 BƯỚC 2: Cấu hình App Portal (Mở cổng REST API cho bảng)

Để Javascript ở frontend có thể fetch được dữ liệu này ra vẽ biểu đồ, ta cần mở cổng API cho bảng `revenue`:
1.  Tại giao diện lưới của bảng `revenue`, click vào tab **Settings** (ở sidebar bên phải).
2.  Cấu hình **App Portal**:
    *   **Portal Status**: Bật **Active** (Cho phép API).
    *   **Slug**: Nhập `revenue-api`.
    *   **Allowed Roles**: Để **trống** (điều này cho phép khách truy cập vãng lai ngoài frontend cũng có thể gọi API tải chart mà không cần đăng nhập).
3.  Nhấn **Save Settings**.

*Lúc này, endpoint REST API dữ liệu của bạn đã sẵn sàng hoạt động tại địa chỉ:*
`{domain_cua_ban}/wp-json/skaaa-data/v1/portal/revenue/rows`

---

## 📚 BƯỚC 3: Đăng ký thư viện Chart.js vào Scripts Library

1.  Tại Dashboard chính của Skaaa, click vào card **Scripts Library** (hoặc truy cập URL: `?page=skaaa-data-pro-scripts`).
2.  Click **Add Script** (Thêm script mới):
    *   **Script Name**: `Chart JS Library`
    *   **Script ID**: `chartjs-lib`
    *   **Script Type**: Chọn **JS External File** (File JS ngoài).
    *   **Inject Location**: Chọn **Header (wp_head)**.
    *   **File URL (External CDN)**: Điền URL CDN của Chart.js:
        `https://cdn.jsdelivr.net/npm/chart.js`
    *   **Load Condition**: Chọn **Conditional pages/apps** (Để im không tick trang nào, script này sẽ tự động ở chế độ `block_only` - chỉ nạp khi có block yêu cầu).
    *   Tích chọn **Enable immediately** (Kích hoạt ngay).
3.  Nhấn **Save Script**.

---

## 🎨 BƯỚC 4: Tạo trang hiển thị & Dựng block Skaaa Code

1.  Vào **Pages** -> **Add New Page** (Tạo trang mới).
2.  Đặt tiêu đề trang: `Báo Cáo Doanh Thu Doanh Nghiệp`.
3.  **Kéo block Skaaa Code thứ 1 (Vẽ Canvas hiển thị)**:
    *   Tìm và kéo block **Skaaa Code** vào trang.
    *   Ở sidebar bên phải block:
        *   **Source Mode**: Chọn `Inline Code`.
        *   **Inject Location**: Chọn `Inline` (in tại chỗ).
        *   Nhập đoạn HTML tạo thẻ Canvas vào ô **Code Editor**:
            ```html
            <div style="max-width: 700px; margin: 40px auto; padding: 24px; background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
                <h3 style="font-family: 'Outfit', sans-serif; text-align: center; color: #1e293b; margin-top: 0; margin-bottom: 24px; font-weight: 700;">Biểu Đồ Doanh Thu Nửa Đầu Năm 2026</h3>
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>
            ```
4.  **Kéo block Skaaa Code thứ 2 (Nhúng thư viện Chart.js)**:
    *   Kéo thêm block **Skaaa Code** đặt ngay phía dưới block Canvas.
    *   Ở sidebar bên phải:
        *   **Source Mode**: Chọn `Scripts Library`.
        *   **Select Script**: Chọn `Chart JS Library (chartjs-lib)` vừa tạo ở Bước 3.
5.  **Kéo block Skaaa Code thứ 3 (JS kết nối API & Vẽ Biểu đồ)**:
    *   Kéo thêm block **Skaaa Code** thứ 3 đặt ở cuối.
    *   Ở sidebar bên phải:
        *   **Source Mode**: Chọn `Inline Code`.
        *   **Inject Location**: Chọn `Footer` (Đẩy xuống chân trang để JS chạy sau khi Canvas và thư viện Chart.js đã tải xong).
        *   Nhập đoạn script JS sau vào ô **Code Editor**:
            ```html
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Gọi API Portal của Skaaa Data Pro để lấy dữ liệu phẳng
                fetch('/wp-json/skaaa-data/v1/portal/revenue/rows?per_page=100')
                    .then(response => response.json())
                    .then(res => {
                        if (res.success && res.data) {
                            // Vì API lấy order DESC nên đảo mảng lại cho đúng thứ tự tháng 1 -> 6
                            const sortedData = res.data.reverse();
                            
                            // Trích xuất labels (Tháng) và amounts (Doanh thu)
                            const labels = sortedData.map(item => item.month);
                            const amounts = sortedData.map(item => parseFloat(item.amount));

                            // Khởi tạo và vẽ biểu đồ hình cột bằng Chart.js
                            const ctx = document.getElementById('revenueChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: 'Doanh thu ($)',
                                        data: amounts,
                                        backgroundColor: 'rgba(99, 102, 241, 0.15)', // Màu xanh Indigo trong suốt
                                        borderColor: 'rgb(99, 102, 241)', // Viền xanh Indigo đậm
                                        borderWidth: 2,
                                        borderRadius: 8,
                                        borderSkipped: false
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                                        x: { grid: { display: false } }
                                    }
                                }
                            });
                        }
                    })
                    .catch(err => console.error('Lỗi tải dữ liệu biểu đồ:', err));
            });
            </script>
            ```
6.  Nhấn **Publish** (Đăng trang) và truy cập xem trang ngoài Frontend.
