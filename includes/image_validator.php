<?php
/**
 * 服务端严格图片校验
 */
function validate_uploaded_image(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => '文件上传失败。'];
    }

    // 检查文件大小
    global $config;
    if ($file['size'] > $config['max_upload_size']) {
        return ['success' => false, 'error' => '文件大小超过限制（最大 5MB）。'];
    }

    // 使用 finfo 获取真实 MIME 类型
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => '不支持的文件格式。仅支持 JPG、PNG、WebP。'];
    }

    // 验证图片尺寸与完整性
    $imageSize = @getimagesize($file['tmp_name']);
    if ($imageSize === false) {
        return ['success' => false, 'error' => '上传的文件不是有效的图片或文件已损坏。'];
    }

    [$width, $height] = $imageSize;
    if ($width > $config['max_image_width'] || $height > $config['max_image_height']) {
        return ['success' => false, 'error' => '图片分辨率超限，最大支持 4096×4096 像素。'];
    }

    return [
        'success' => true, 
        'extension' => $allowedTypes[$mimeType],
        'width' => $width,
        'height' => $height
    ];
}