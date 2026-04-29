<?php
// mood_face.php - Premium Biometric Interface
session_start();

require_once 'includes/config.php';
require_once 'database/connection.php';
require_once 'includes/header.php';
set_page_title("MoodAI | Facial Recognition Unit");

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['username'] ?? 'Operative';

if (!$user_id) {
    header("Location: login.php");
    exit();
}
?>
<link rel="stylesheet" href="assets/css/mood_face.css">

<main class="container pb-5 fade-in-section">

    <!-- Interface Header -->
    <header class="hero-section-premium mb-5" data-aos="fade-down">
        <span class="interface-tag animate-reveal">Tool: Camera Mood Detector</span>
        <h1 class="display-4 fw-800 animate-reveal">Mood by Camera</h1>
    </header>

    <div class="row g-4 align-items-stretch">
        
        <!-- Sidebar: Objectives -->
        <div class="col-lg-3">
            <aside class="interface-card" data-aos="fade-right" data-aos-delay="100">
                <span class="card-label">How to use</span>
                
                <div class="objective-item">
                    <span class="obj-number">01</span>
                    <p class="obj-text">Turn on your camera to start the link.</p>
                </div>
                <div class="objective-item">
                    <span class="obj-number">02</span>
                    <p class="obj-text">Put your face in the center of the screen.</p>
                </div>
                <div class="objective-item">
                    <span class="obj-number">03</span>
                    <p class="obj-text">Take a photo to find your mood.</p>
                </div>

                <div class="mt-4 pt-3 border-top border-dark">
                    <span class="card-label mb-2" style="font-size: 0.65rem;">Status</span>
                    <div id="statusIndicator" class="small fw-bold animate-flicker" style="color: #444;">Waiting for camera...</div>
                </div>
            </aside>
        </div>

        <!-- Main Content: Scanner -->
        <div class="col-lg-6">
            <div class="interface-card text-center position-relative overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                <span class="card-label">Live Camera View</span>
                
                <div class="scanner-container mb-4">
                    <video id="webcamPreview" autoplay muted playsinline></video>
                    <canvas id="canvasCapture" style="display:none;"></canvas>

                    <!-- Biometric Overlay -->
                    <div class="scanner-overlay">
                        <div class="corner top-left"></div>
                        <div class="corner top-right"></div>
                        <div class="corner bottom-left"></div>
                        <div class="corner bottom-right"></div>
                        <div id="scanLine" class="scan-line"></div>
                    </div>

                    <div id="webcamPlaceholder" class="placeholder-content">
                        <i class="bi bi-webcam text-muted" style="font-size: 3rem;"></i>
                        <p class="mt-3 text-muted small fw-bold">CAMERA IS OFF</p>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row gap-2 justify-content-center">
                    <button id="startButton" class="btn btn-protocol btn-primary-protocol flex-grow-1">
                        <i class="bi bi-cpu me-2"></i> Turn On Camera
                    </button>
                    <button id="captureButton" class="btn btn-protocol flex-grow-1" disabled>
                        <i class="bi bi-eye me-2"></i> Take Photo
                    </button>
                </div>

                <div id="statusMessage" class="mt-3 small fw-bold text-muted text-uppercase tracking-wider"></div>
            </div>

        </div>

        <!-- Sidebar: Neural Feed -->
        <div class="col-lg-3">
            <aside class="interface-card" data-aos="fade-left" data-aos-delay="300">
                <span class="card-label">AI Analysis Log</span>
                <div id="neuralFeed" class="neural-feed-container">
                    <!-- Javascript will populate this -->
                </div>

                <div class="mt-4 pt-3 border-top border-dark text-center">
                    <i class="bi bi-person-bounding-box display-4 mb-2 d-block" style="color: var(--accent-red); opacity: 0.5;"></i>
                    <p class="small mb-0" style="font-size: 0.65rem; color: var(--text-white); opacity: 0.7;">Face Scan: Good<br>Secure Connection</p>
                </div>
            </aside>
        </div>

    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
<script src="assets/js/mood_face.js"></script>