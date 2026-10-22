<?php
/**
 * RemoveBG — Cron 计划任务清理入口
 */

// 仅允许 CLI 模式运行，或带有安全秘钥的 HTTP 触发
if (php_sapi_name() !== 'cli') {
    $cronKey = $_GET['key'] ?? '';
    $expectedKey = 'CHANGE_THIS_CRON_SECRET_KEY'; // 请在部署时修改此秘钥
    
    if (empty($cronKey) || $cronKey !== $expectedKey) {
        http_response_code(403);
        die('无权访问此脚本。');
    }
}

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/includes/cleanup.php';

$retention = $config['temp_retention_minutes'] ?? 30;
$result = clean_expired_temp_files($retention);

if (php_sapi_name() === 'cli') {
    echo "[" . date('Y-m-d H:i:s') . "] 清理完成。共删除 " . $result['deleted_files'] . " 个过期临时文件。\n";
} else {
    header('Content-Type: application/json');
    echo json_encode($result);
}