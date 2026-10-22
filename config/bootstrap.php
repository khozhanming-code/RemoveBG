<?php

define('ROOT_PATH', dirname(__DIR__));

$configFile = ROOT_PATH . '/config/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
} else {
    $config = [
        'app_env' => 'development',
        'provider' => 'pixelcut',
        'api_key' => 'sk_aba3ea9382f34a4b919984bc36eadd1f', // 填入您的 Pixelcut Token
        'max_upload_size' => 5 * 1024 * 1024,
        'max_image_width' => 4096,
        'max_image_height' => 4096,
        'temp_retention_minutes' => 30
    ];
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
$logDir = ROOT_PATH . '/storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_error.log');