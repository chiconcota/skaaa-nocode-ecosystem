const fs = require('fs');
const archiver = require('./skaaa-data-pro/node_modules/archiver');
const path = require('path');

function getPluginVersion(pluginFolder) {
    const mainPhpPath = path.join(__dirname, pluginFolder, `${pluginFolder}.php`);
    if (fs.existsSync(mainPhpPath)) {
        const content = fs.readFileSync(mainPhpPath, 'utf8');
        const match = content.match(/Version:\s*([0-9.]+)/i);
        if (match) return match[1];
    }
    return '';
}

function getThemeVersion(themeDir) {
    const styleCssPath = path.join(themeDir, 'style.css');
    if (fs.existsSync(styleCssPath)) {
        const content = fs.readFileSync(styleCssPath, 'utf8');
        const match = content.match(/Version:\s*([0-9.]+)/i);
        if (match) return match[1];
    }
    return '';
}

const plugins = ['skaaa-no-code-design', 'skaaa-data-pro', 'skaaa-logic-engine', 'skaaai'];

plugins.forEach(pluginFolder => {
    const version = getPluginVersion(pluginFolder);
    const versionSuffix = version ? `-v${version}` : '';
    const versionedZipPath = path.join(__dirname, `${pluginFolder}${versionSuffix}.zip`);
    const standardZipPath = path.join(__dirname, `${pluginFolder}.zip`);

    const output = fs.createWriteStream(versionedZipPath);
    const archive = archiver('zip', { zlib: { level: 9 } });

    output.on('close', function() {
        // Đồng thời copy sang tên tiêu chuẩn để đảm bảo tương thích ngược
        if (versionedZipPath !== standardZipPath) {
            fs.copyFileSync(versionedZipPath, standardZipPath);
        }
        console.log(`[Thành công] Đã đóng gói: ${pluginFolder}${versionSuffix}.zip (${(archive.pointer() / 1024 / 1024).toFixed(2)} MB)`);
    });

    archive.on('error', function(err) { throw err; });
    archive.pipe(output);

    archive.glob('**/*', {
        cwd: path.join(__dirname, pluginFolder),
        ignore: [
            '**/node_modules/**', 
            '**/src/**', 
            '**/package.json', 
            '**/package-lock.json', 
            '**/webpack.config.js', 
            '**/vite.config.js', 
            '**/build-zip.js', 
            '**/.gitignore', 
            '**/.git/**'
        ]
    }, { prefix: pluginFolder });

    archive.finalize();
});

// Đóng gói Theme Skaaa Canvas
const themeFolder = 'skaaa-canvas';
const themesDir = path.join(__dirname, '..', 'themes');
const themeSourceDir = path.join(themesDir, themeFolder);

if (fs.existsSync(themeSourceDir)) {
    const themeVersion = getThemeVersion(themeSourceDir);
    const themeVersionSuffix = themeVersion ? `-v${themeVersion}` : '';

    // Xuất cả ở wp-content/themes/ và wp-content/plugins/ với version
    const outputLocations = [
        path.join(themesDir, `${themeFolder}${themeVersionSuffix}.zip`),
        path.join(__dirname, `${themeFolder}${themeVersionSuffix}.zip`)
    ];

    outputLocations.forEach(themeOutputFilePath => {
        const themeOutput = fs.createWriteStream(themeOutputFilePath);
        const themeArchive = archiver('zip', { zlib: { level: 9 } });

        themeOutput.on('close', function() {
            // Copy unversioned zip
            const unversionedPath = themeOutputFilePath.replace(`${themeVersionSuffix}.zip`, '.zip');
            if (unversionedPath !== themeOutputFilePath) {
                fs.copyFileSync(themeOutputFilePath, unversionedPath);
            }
            console.log(`[Thành công] Đã đóng gói Theme: ${path.relative(path.join(__dirname, '..', '..'), themeOutputFilePath)} (${(themeArchive.pointer() / 1024).toFixed(2)} KB)`);
        });

        themeArchive.on('error', function(err) { throw err; });
        themeArchive.pipe(themeOutput);

        themeArchive.glob('**/*', {
            cwd: themeSourceDir,
            ignore: [
                '**/node_modules/**', 
                '**/.git/**', 
                '**/.gitignore'
            ]
        }, { prefix: themeFolder });

        themeArchive.finalize();
    });
}
