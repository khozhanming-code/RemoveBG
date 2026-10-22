<?php
/**
 * RemoveBG — 后端图像接收与 AI 抠图处理网关
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/image_validator.php';
require_once __DIR__ . '/services/providers/RemoveBgProvider.php';

// 仅允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '不支持的请求方式。']);
    exit;
}

// 检查上传文件是否存在
if (!isset($_FILES['image']) || empty($_FILES['image']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '请先选择要上传的图片。']);
    exit;
}

// 1. 服务端严格图片校验
$validation = validate_uploaded_image($_FILES['image']);
if (!$validation['success']) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $validation['error']]);
    exit;
}

// 2. 保证临时存储目录存在并安全隔离
$storageDir = __DIR__ . '/storage';
$originalsDir = $storageDir . '/originals';
$processedDir = $storageDir . '/processed';

foreach ([$originalsDir, $processedDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 生成安全的随机文件名，防止路径穿越或同名覆盖
$fileExt = $validation['extension'];
$token = bin2hex(random_bytes(16));
$originalFilename = $token . '_orig.' . $fileExt;
$originalFilePath = $originalsDir . '/' . $originalFilename;

// 保存原始上传文件
if (!move_uploaded_file($_FILES['image']['tmp_name'], $originalFilePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '保存上传文件失败，请检查服务器文件夹权限。']);
    exit;
}

// 3. 调用 AI 背景移除服务商
$apiKey = $config['api_key'] ?? '';
$provider = new RemoveBgProvider($apiKey);
$result = $provider->removeBackground($originalFilePath);

// 如果处理失败，清理临时上传文件并返回错误信息
if (!$result['success']) {
    if (file_exists($originalFilePath)) {
        @unlink($originalFilePath);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $result['error']]);
    exit;
}

// 4. 保存生成的透明 PNG 图像
$processedFilename = $token . '_proc.png';
$processedFilePath = $processedDir . '/' . $processedFilename;

if (file_put_contents($processedFilePath, $result['data']) === false) {
    if (file_exists($originalFilePath)) {
        @unlink($originalFilePath);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '保存处理后的图片失败。']);
    exit;
}

// 5. 生成安全临时可读取图片 URL（通过 download.php 访问，避免直接暴露文件路径）
$imageUrl = 'download.php?token=' . urlencode($token) . '&type=proc';

// 6. 返回成功 JSON 响应
echo json_encode([
    'success' => true,
    'token' => $token,
    'image_url' => $imageUrl
]);
exit;