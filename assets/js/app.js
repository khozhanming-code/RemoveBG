/**
 * RemoveBG — 前端交互与 Canvas 图像处理逻辑
 */
document.addEventListener('DOMContentLoaded', () => {
    const imageInput = document.getElementById('image-input');
    const dropZone = document.getElementById('drop-zone');
    const uploadSection = document.getElementById('upload-section');
    const loadingSection = document.getElementById('loading-section');
    const editorSection = document.getElementById('editor-section');
    const uploadError = document.getElementById('upload-error');
    const btnReset = document.getElementById('btn-reset');
    const btnDownload = document.getElementById('btn-download');
    
    const resultCanvas = document.getElementById('result-canvas');
    const ctx = resultCanvas.getContext('2d');
    const previewContainer = document.getElementById('preview-container');
    const bgOptions = document.querySelectorAll('.bg-option');
    const colorPicker = document.getElementById('custom-color-picker');
    const colorText = document.getElementById('custom-color-text');

    let currentProcessedImage = null; // 保存加载后的透明图像对象
    let selectedBackground = 'transparent'; // 当前选中的背景类型

    // 拖拽高亮效果
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        }, false);
    });

    // 监听文件选择
    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleImageUpload(files[0]);
        }
    });

    imageInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleImageUpload(e.target.files[0]);
        }
    });

    // 异步上传并处理图片
    function handleImageUpload(file) {
        // 前端基础校验
        const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showError('不支持的文件格式。请上传 JPG、PNG 或 WebP 格式的图片。');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            showError('文件大小超过限制，最大支持 5MB。');
            return;
        }

        hideError();
        showLoading(true);

        const formData = new FormData();
        formData.append('image', file);

        // 提交到后端处理脚本 (process.php)
        fetch('process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            if (data.success) {
                loadProcessedImageToCanvas(data.image_url);
            } else {
                showError(data.error || '处理图片时发生未知错误。');
                showUpload(true);
            }
        })
        .catch(error => {
            showLoading(false);
            showError('网络连接失败或服务器响应异常。');
            showUpload(true);
        });
    }

    // 将处理后的透明图片加载到 Canvas 中
    function loadProcessedImageToCanvas(url) {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            currentProcessedImage = img;
            renderCanvas();
            showEditor(true);
        };
        img.onerror = () => {
            showError('无法加载处理后的图像。');
            showUpload(true);
        };
        img.src = url;
    }

    // Canvas 渲染与背景合成核心逻辑
    function renderCanvas() {
        if (!currentProcessedImage) return;

        resultCanvas.width = currentProcessedImage.naturalWidth;
        resultCanvas.height = currentProcessedImage.naturalHeight;

        // 清空画布
        ctx.clearRect(0, 0, resultCanvas.width, resultCanvas.height);

        // 如果选择的不是透明，则先填充背景色
        if (selectedBackground !== 'transparent') {
            ctx.fillStyle = selectedBackground;
            ctx.fillRect(0, 0, resultCanvas.width, resultCanvas.height);
        }

        // 绘制去背景后的图像
        ctx.drawImage(currentProcessedImage, 0, 0);
    }

    // 切换背景选项
    bgOptions.forEach(btn => {
        btn.addEventListener('click', () => {
            bgOptions.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedBackground = btn.getAttribute('data-bg');
            
            if (selectedBackground === 'transparent') {
                previewContainer.classList.add('bg-checkerboard');
            } else {
                previewContainer.classList.remove('bg-checkerboard');
            }
            renderCanvas();
        });
    });

    // 自定义颜色拾取事件
    colorPicker.addEventListener('input', (e) => {
        const color = e.target.value;
        colorText.value = color;
        selectedBackground = color;
        
        bgOptions.forEach(b => b.classList.remove('active'));
        previewContainer.classList.remove('bg-checkerboard');
        renderCanvas();
    });

    // 下载按钮事件
    btnDownload.addEventListener('click', () => {
        if (!currentProcessedImage) return;

        const link = document.createElement('a');
        link.download = 'removebg-result-' + Date.now() + (selectedBackground === 'transparent' ? '.png' : '.jpg');
        link.expires = 0;
        
        // 根据背景类型导出对应格式
        const mimeType = selectedBackground === 'transparent' ? 'image/png' : 'image/jpeg';
        link.href = resultCanvas.toDataURL(mimeType, 0.92);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 重置按钮事件
    btnReset.addEventListener('click', () => {
        currentProcessedImage = null;
        imageInput.value = '';
        showUpload(true);
    });

    // 视图切换辅助函数
    function showUpload(show) {
        uploadSection.classList.toggle('d-none', !show);
        loadingSection.classList.add('d-none');
        editorSection.classList.add('d-none');
    }

    function showLoading(show) {
        uploadSection.classList.add('d-none');
        loadingSection.classList.toggle('d-none', !show);
        editorSection.classList.add('d-none');
    }

    function showEditor(show) {
        uploadSection.classList.add('d-none');
        loadingSection.classList.add('d-none');
        editorSection.classList.toggle('d-none', !show);
    }

    function showError(message) {
        uploadError.textContent = message;
        uploadError.classList.remove('d-none');
    }

    function hideError() {
        uploadError.textContent = '';
        uploadError.classList.add('d-none');
    }
});