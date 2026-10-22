<?php
/**
 * RemoveBG — 自动清理过期临时文件模块（无数据库）
 */

function clean_expired_temp_files(int $retentionMinutes = 30): array {
    $storageDir = dirname(__DIR__) . '/storage';
    $targets = [
        $storageDir . '/originals',
        $storageDir . '/processed',
        $storageDir . '/rate_limits'
    ];

    $deletedCount = 0;
    $expireTime = time() - ($retentionMinutes * 60);

    foreach ($targets as $dir) {
        if (!is_dir($dir)) {
            continue;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') {
                continue;
            }

            $filePath = $dir . '/' . $file;
            if (is_file($filePath) && filemtime($filePath) < $expireTime) {
                if (@unlink($filePath)) {
                    $deletedCount++;
                }
            }
        }
    }

    return [
        'success' => true,
        'deleted_files' => $deletedCount
    ];
}