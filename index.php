<?php
/**
 * RemoveBG — AI Background Removal System Main Page
 */
require_once __DIR__ . '/config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RemoveBG — Smart AI Background Remover</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fa-solid fa-wand-magic-sparkles text-info me-2"></i>RemoveBG
            </a>
            <span class="navbar-text text-white-50 small">
                Stateless · Pure & Efficient AI Cutout
            </span>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container my-5">
        
        <!-- Hero Section -->
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold text-dark">Remove Image Background in Seconds</h1>
            <p class="lead text-secondary">Upload an image, let AI automatically remove the background, and download high-resolution transparent PNG or custom background images with one click.</p>
        </div>

        <!-- Step 1: Upload Section -->
        <div id="upload-section" class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 p-4 text-center bg-white">
                    <div id="drop-zone" class="upload-drop-zone p-5 rounded-4 border border-2 border-dashed position-relative">
                        <input type="file" id="image-input" name="image" accept="image/jpeg,image/png,image/webp" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer">
                        <div class="py-4">
                            <i class="fa-solid fa-cloud-arrow-up display-4 text-primary mb-3"></i>
                            <h4>Drag and drop image here, or <span class="text-primary text-decoration-underline">browse files</span></h4>
                            <p class="text-muted small mt-2">Supports JPG, PNG, WebP formats, max 5MB</p>
                        </div>
                    </div>
                    <!-- Error Container -->
                    <div id="upload-error" class="alert alert-danger mt-3 d-none" role="alert"></div>
                </div>
            </div>
        </div>

        <!-- Step 2: Loading State -->
        <div id="loading-section" class="row justify-content-center d-none">
            <div class="col-md-6 text-center py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                <h4 class="fw-bold">Removing background via AI...</h4>
                <p class="text-muted">This usually takes only a few seconds, please wait.</p>
            </div>
        </div>

        <!-- Step 3: Editor & Result Section -->
        <div id="editor-section" class="d-none">
            <div class="row g-4">
                <!-- Left: Result Preview -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold m-0"><i class="fa-solid fa-image me-2 text-primary"></i>Result Preview</h5>
                            <button id="btn-reset" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-rotate-left me-1"></i>Process Another
                            </button>
                        </div>
                        
                        <!-- Checkerboard Preview Container -->
                        <div id="preview-container" class="preview-stage rounded-3 position-relative overflow-hidden d-flex align-items-center justify-content-center bg-checkerboard" style="min-height: 350px;">
                            <canvas id="result-canvas" class="img-fluid max-height-400"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right: Background Customization & Download Controls -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-palette me-2 text-primary"></i>Background Customization</h5>
                            <p class="text-muted small">You can keep it transparent or change it to a solid color before downloading.</p>
                            
                            <!-- Background Option Buttons -->
                            <div class="d-grid gap-2 mb-4">
                                <button type="button" class="btn btn-outline-dark bg-option active" data-bg="transparent">
                                    <i class="fa-solid fa-chess-board me-2"></i>Transparent (PNG)
                                </button>
                                <button type="button" class="btn btn-outline-dark bg-option" data-bg="#ffffff">
                                    <i class="fa-solid fa-square text-white border me-2"></i>Solid White
                                </button>
                                <button type="button" class="btn btn-outline-dark bg-option" data-bg="#000000">
                                    <i class="fa-solid fa-square text-dark me-2"></i>Solid Black
                                </button>
                            </div>

                            <!-- Custom Color Picker -->
                            <div class="mb-4">
                                <label for="custom-color-picker" class="form-label small fw-bold text-secondary">Custom Color:</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color w-25" id="custom-color-picker" value="#3b82f6" title="Choose custom color">
                                    <input type="text" class="form-control" id="custom-color-text" value="#3b82f6" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Main Download Button -->
                        <div class="d-grid">
                            <button id="btn-download" class="btn btn-primary btn-lg rounded-3 shadow-sm">
                                <i class="fa-solid fa-download me-2"></i>Download HD Image
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="text-center py-4 text-muted small">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> RemoveBG. Pure stateless AI image processing system, all temporary files are automatically destroyed after download.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Frontend Logic -->
    <script src="assets/js/app.js"></script>
</body>
</html>