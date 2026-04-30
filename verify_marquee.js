const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();

    const indexContent = fs.readFileSync('fyp-movie-recommender/php_backend/index.php', 'utf8');
    const cssContent = fs.readFileSync('fyp-movie-recommender/php_backend/assets/css/index.css', 'utf8');

    // Minimal mock for PHP tags and variables
    let html = indexContent
        .replace(/<\?php[\s\S]*?\?>/g, '') // Remove PHP blocks
        .replace(/\{\{.*?\}\}/g, '') // Remove any double braces
        .replace(/htmlspecialchars\((.*?)\)/g, ''); // Mock htmlspecialchars

    // Insert CSS
    html = html.replace('</head>', `<style>${cssContent}</style></head>`);

    // Inject mock movies into the marquee area
    const mockMarquee = `
        <div class="marquee-wrapper">
            <div class="marquee-content">
                <div class="movie-glass-card">
                    <img src="INVALID_URL" onerror="this.style.display='none'; this.parentElement.querySelector('.fallback-title').style.display='block';">
                    <div class="fallback-title" style="display:none;">
                        <i class="bi bi-film mb-3 d-block" style="font-size: 2rem; opacity: 0.5;"></i>
                        <span>Broken Poster Example</span>
                    </div>
                    <div class="glass-overlay"></div>
                </div>
                <div class="movie-glass-card">
                    <img src="https://image.tmdb.org/t/p/w500/qJ2tW6WMUDp92SKyJJw9Q7z0mcy.jpg">
                    <div class="fallback-title" style="display:none;">
                        <span>The Dark Knight</span>
                    </div>
                    <div class="glass-overlay"></div>
                </div>
            </div>
        </div>
    `;

    html = html.replace(/<section class="movie-marquee-section[\s\S]*?<\/section>/, mockMarquee);

    await page.setContent(html);
    await page.waitForTimeout(2000); // Wait for animations/onerror

    await page.screenshot({ path: 'verification/marquee_robustness.png', fullPage: true });
    await browser.close();
})();
