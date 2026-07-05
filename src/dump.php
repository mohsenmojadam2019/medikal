<?php

// dump.php - استخراج تمام کدهای پروژه به یک فایل متنی

// تنظیمات: پسوندهایی که می‌خواهیم بخوانیم
$extensions = ['php', 'json', 'env', 'yaml', 'yml'];

// پوشه‌هایی که نمی‌خواهیم بخوانیم
$exclude = [
    'vendor',
    'node_modules',
    '.git',
    'storage',
    'bootstrap/cache',
    'tests'
];

$output = '';

function readDirRecursive($dir, $extensions, $exclude, &$output)
{
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            if (!in_array($file, $exclude)) {
                readDirRecursive($path, $extensions, $exclude, $output);
            }
        } else {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array($ext, $extensions)) {
                $relativePath = str_replace(getcwd() . '/', '', $path);
                $output .= "--- $relativePath ---\n";
                $output .= file_get_contents($path) . "\n\n";
            }
        }
    }
}

readDirRecursive(getcwd(), $extensions, $exclude, $output);

// ذخیره فایل خروجی
$outputFile = 'backend.txt';
file_put_contents($outputFile, $output);

echo "✅ فایل '$outputFile' با موفقیت ایجاد شد.\n";
echo "📦 تعداد کاراکترها: " . strlen($output) . "\n";
echo "🔗 می‌توانید این فایل را در GitHub Gist آپلود کنید.\n";
