const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Helper: Trích xuất các số phiên bản dạng X.Y.Z hoặc vX.Y.Z từ một chuỗi
function extractVersions(str) {
    const regex = /v?(\d+\.\d+\.\d+)/g;
    const matches = [];
    let match;
    while ((match = regex.exec(str)) !== null) {
        matches.push(match[1]); // Trả về dạng chuẩn X.Y.Z
    }
    return matches;
}

// 1. Kiểm tra tham số phiên bản mục tiêu
const targetInput = process.argv[2];
if (!targetInput) {
    console.error('\x1b[31m[Lỗi] Vui lòng nhập phiên bản cần phát hành!\x1b[0m');
    console.log('Ví dụ: node release.js v2.0.0 [phiên_bản_trước_đó]');
    process.exit(1);
}

const targetVersion = targetInput.startsWith('v') ? targetInput.substring(1) : targetInput;
const tag = `v${targetVersion}`;

console.log(`\x1b[36m========== BẮT ĐẦU QUY TRÌNH PHÁT HÀNH HỆ SINH THÁI ${tag} ==========\x1b[0m`);

// Định nghĩa các đường dẫn từ thư mục gốc
const rootDir = path.join(__dirname, '..', '..');
const systemMapPath = path.join(rootDir, '.skaaa-ai', '1-overview', 'system_map.md');
const notesOutputPath = path.join(__dirname, `release-notes-${tag}.md`);
const outputZipName = `skaaa-nocode-ecosystem-${tag}.zip`;
const outputZipPath = path.join(__dirname, outputZipName);

// 2. Xác định phiên bản phát hành trước đó (Previous Version)
let previousVersion = process.argv[3];
if (previousVersion && previousVersion.startsWith('v')) {
    previousVersion = previousVersion.substring(1);
}

if (!previousVersion) {
    console.log('Đang tự động xác định phiên bản phát hành trước đó...');
    try {
        const gitTagsOutput = execSync('git tag --sort=-v:refname', { encoding: 'utf8' }).trim();
        if (gitTagsOutput) {
            const tags = gitTagsOutput.split('\n').map(t => t.trim().startsWith('v') ? t.trim().substring(1) : t.trim());
            const lastTag = tags.find(t => t !== targetVersion);
            if (lastTag) {
                previousVersion = lastTag;
                console.log(`-> Tìm thấy tag phiên bản cũ nhất gần nhất trên Git: \x1b[32mv${previousVersion}\x1b[0m`);
            }
        }
    } catch (e) {
        // Git tag thất bại hoặc chưa có tag nào
    }
}

if (!previousVersion && fs.existsSync(systemMapPath)) {
    try {
        const fileContent = fs.readFileSync(systemMapPath, 'utf8');
        const recentLogsIndex = fileContent.indexOf('## 6. RECENT LOGS');
        if (recentLogsIndex !== -1) {
            const logsSection = fileContent.substring(recentLogsIndex);
            const lines = logsSection.split('\n');
            for (let line of lines) {
                if (line.trim().startsWith('-')) {
                    const versions = extractVersions(line);
                    const foundPrev = versions.find(v => v !== targetVersion);
                    if (foundPrev) {
                        previousVersion = foundPrev;
                        console.log(`-> Tự động nhận diện phiên bản cũ gần nhất từ log hệ thống: \x1b[32mv${previousVersion}\x1b[0m`);
                        break;
                    }
                }
            }
        }
    } catch (err) {
        console.error('Lỗi khi đọc system_map.md để nhận diện phiên bản cũ:', err.message);
    }
}

if (previousVersion) {
    console.log(`=> Sẽ gom tất cả các cập nhật từ sau phiên bản \x1b[33mv${previousVersion}\x1b[0m cho đến hiện tại.`);
} else {
    console.log('=> Không tìm thấy phiên bản cũ. Sẽ lấy toàn bộ nhật ký hiện có.');
}

// 3. Trích xuất tất cả log từ sau previousVersion đến hiện tại
console.log('Quét system_map.md để tổng hợp changelog...');
let changelogContent = '';
const collectedLines = [];

if (fs.existsSync(systemMapPath)) {
    try {
        const fileContent = fs.readFileSync(systemMapPath, 'utf8');
        const recentLogsIndex = fileContent.indexOf('## 6. RECENT LOGS');
        
        if (recentLogsIndex !== -1) {
            const logsSection = fileContent.substring(recentLogsIndex);
            const lines = logsSection.split('\n');
            
            for (let line of lines) {
                const trimmed = line.trim();
                if (trimmed.startsWith('-')) {
                    if (previousVersion && (trimmed.includes(`v${previousVersion}`) || trimmed.includes(`(${previousVersion})`) || trimmed.includes(` ${previousVersion}`))) {
                        break;
                    }
                    collectedLines.push(trimmed);
                }
            }
            
            if (collectedLines.length > 0) {
                changelogContent = `### Changes in ${tag}\n\n` + collectedLines.join('\n') + '\n';
                console.log(`\x1b[32m[Thành công] Đã tổng hợp ${collectedLines.length} dòng log chưa phát hành.\x1b[0m`);
            } else {
                console.warn('\x1b[33m[Cảnh báo] Không tìm thấy cập nhật mới nào kể từ phiên bản cũ.\x1b[0m');
            }
        } else {
            console.warn('\x1b[33m[Cảnh báo] Không tìm thấy mục "## 6. RECENT LOGS" trong system_map.md\x1b[0m');
        }
    } catch (err) {
        console.error('[Lỗi] Lỗi khi đọc system_map.md:', err.message);
    }
}

if (!changelogContent) {
    changelogContent = `### Changes in ${tag}\n\n- Updates and bug fixes for version ${tag}.\n`;
}

fs.writeFileSync(notesOutputPath, changelogContent, 'utf8');
console.log(`Đã xuất file ghi chú phát hành: \x1b[34mwp-content/plugins/release-notes-${tag}.md\x1b[0m`);

// 4. Thực hiện đóng gói ZIP duy nhất cho Người dùng cuối
console.log(`Đang thực hiện đóng gói hệ sinh thái thành tệp duy nhất: ${outputZipName}...`);

if (fs.existsSync(outputZipPath)) {
    fs.unlinkSync(outputZipPath);
}

try {
    // Chỉ định đóng gói: wp-content (chứa 3 plugins), docs, README.md, LICENSE
    // Loại trừ các file dev, node_modules, .git, .agent, .skaaa-ai, zip file khác
    const excludePatterns = [
        '*/node_modules/*',
        '*/node_modules/**',
        '*/src/*',
        '*/src/**',
        '*.git*',
        '*/.git/*',
        '*/.git/**',
        '.agent/*',
        '.agent/**',
        '.skaaa-ai/*',
        '.skaaa-ai/**',
        '.gemini/*',
        '.gemini/**',
        'CONTRIBUTING.md',
        '*.zip',
        'wp-content/plugins/release.js',
        'wp-content/plugins/zip-all.js',
        'wp-content/plugins/temp-zip-logic.js',
        'wp-content/plugins/release-notes-*.md',
        'wp-content/plugins/skaaa-no-code-design/package.json',
        'wp-content/plugins/skaaa-no-code-design/package-lock.json',
        'wp-content/plugins/skaaa-no-code-design/webpack.config.js',
        'wp-content/plugins/skaaa-data-pro/package.json',
        'wp-content/plugins/skaaa-data-pro/package-lock.json',
        'wp-content/plugins/skaaa-logic-engine/package.json',
        'wp-content/plugins/skaaa-logic-engine/package-lock.json',
        'wp-content/plugins/skaaa-logic-engine/vite.config.js',
        'wp-content/plugins/skaaa-no-code-design/vite.config.js',
        'wp-content/plugins/skaaa-data-pro/vite.config.js'
    ];

    const excludeFlags = excludePatterns.map(p => `-x "${p}"`).join(' ');
    
    // Thư mục và file nguồn cần đóng gói (tương đối từ root)
    const sourcesToPack = [
        'wp-content/plugins/skaaa-no-code-design',
        'wp-content/plugins/skaaa-data-pro',
        'wp-content/plugins/skaaa-logic-engine',
        'docs',
        'README.md',
        'LICENSE'
    ].join(' ');

    const cmd = `zip -r -9 "${outputZipPath}" ${sourcesToPack} ${excludeFlags}`;
    
    // Chạy từ thư mục gốc để giữ cấu trúc thư mục đẹp mắt cho end-user
    execSync(cmd, { cwd: rootDir });
    
    const stats = fs.statSync(outputZipPath);
    console.log(`\x1b[32m[Thành công] Đã đóng gói gói phân phối End-User: ${outputZipName} (${(stats.size / 1024 / 1024).toFixed(2)} MB)\x1b[0m`);
} catch (err) {
    console.error(`\x1b[31m[Lỗi] Thất bại khi đóng gói ZIP phân phối:\x1b[0m`, err.message);
    process.exit(1);
}

console.log('\n\x1b[32m========== QUY TRÌNH ĐÓNG GÓI HOÀN TẤT VỚI THÀNH CÔNG! ==========\x1b[0m\n');
console.log(`\x1b[35mHƯỚNG DẪN PHÁT HÀNH PHIÊN BẢN MỚI TRÊN GITHUB (BẢN CHO NGƯỜI DÙNG CUỐI):\x1b[0m`);
console.log(`------------------------------------------------------------------`);
console.log(`Bước 1: Chạy lệnh Git để gắn Tag và push lên GitHub:`);
console.log(`   \x1b[33mgit tag ${tag}\x1b[0m`);
console.log(`   \x1b[33mgit push origin ${tag}\x1b[0m`);
console.log(`\nBước 2: Truy cập trang GitHub Releases của repo:`);
console.log(`   https://github.com/chiconcota/skaaa-nocode-ecosystem/releases/new?tag=${tag}`);
console.log(`\nBước 3: Sao chép nội dung file sau làm mô tả Release (Release Notes):`);
console.log(`   \x1b[34mwp-content/plugins/release-notes-${tag}.md\x1b[0m`);
console.log(`\nBước 4: Kéo thả tệp ZIP phân phối duy nhất dưới đây vào GitHub:`);
console.log(`   - \x1b[32mwp-content/plugins/${outputZipName}\x1b[0m`);
console.log(`\nBước 5: Nhấp "Publish release". Hoàn thành!`);
console.log(`------------------------------------------------------------------`);
