<?php
/**
 * RemoveBG — 安全图片输出与下载处理
 */

require_once __DIR__ . '/config/bootstrap.php';

// 获取 Token 与请求类型
$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? 'proc'; // proc (处理后) 或 orig (原图)
$download = isset($_GET['download']) && $_GET['download'] == '1';

// 验证 Token 格式（必须为 32 位 16 进制字符串，防止路径穿越）
if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
    http_response_code(400);
    die('无效的请求参数。');
}

$storageDir = __DIR__ . '/storage';
$subDir = ($type === 'orig') ? 'originals' : 'processed';

// 查找匹配文件
$files = glob($storageDir . '/' . $subDir . '/' . $token . '_*');

if (empty($files) || !file_exists($files[0])) {
    http_response_code(404);
    die('请求的图片不存在或已过期销毁。');
}

$filePath = $files[0];
$mimeType = mime_content_type($filePath);

// 设置安全的 HTTP 响应头
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=86400');

if ($download) {
    $downloadName = 'removebg-' . time() . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
} else {
    header('Content-Disposition: inline');
}

// 输出二进制图片流
readfile($filePath);
exit;