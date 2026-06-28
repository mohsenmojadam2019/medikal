// dump.js - استخراج تمام کدهای پروژه Next.js به یک فایل متنی

const fs = require('fs');
const path = require('path');

// تنظیمات: پسوندهایی که می‌خواهیم بخوانیم
const extensions = ['.js', '.jsx', '.ts', '.tsx', '.json', '.env', '.yaml', '.yml', '.md'];

// پوشه‌هایی که نمی‌خواهیم بخوانیم
const exclude = [
    'node_modules',
    '.next',
    '.git',
    'dist',
    'build',
    '.env.local',
    'yarn.lock',
    'package-lock.json',
    'pnpm-lock.yaml'
];

let output = '';

function readDirRecursive(dir) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        if (file === '.' || file === '..') continue;
        const fullPath = path.join(dir, file);
        const stat = fs.statSync(fullPath);
        if (stat.isDirectory()) {
            if (!exclude.includes(file)) {
                readDirRecursive(fullPath);
            }
        } else {
            const ext = path.extname(file);
            if (extensions.includes(ext)) {
                const relativePath = path.relative(process.cwd(), fullPath);
                output += `--- ${relativePath} ---\n`;
                output += fs.readFileSync(fullPath, 'utf8') + '\n\n';
            }
        }
    }
}

readDirRecursive(process.cwd());

// ذخیره فایل خروجی
const outputFile = 'code_dump_nextjs.txt';
fs.writeFileSync(outputFile, output);

console.log(`✅ فایل '${outputFile}' با موفقیت ایجاد شد.`);
console.log(`📦 تعداد کاراکترها: ${output.length}`);