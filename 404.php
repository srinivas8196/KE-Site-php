<?php
// Include header
include 'kheader.php';
?>

<section style="padding: 130px 0 50px; background-color: #f8f8f8; min-height: 100vh; position: relative; overflow: hidden; display: flex; align-items: center;">
    <!-- Animated background elements -->
    <div class="map-bg" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; background-image: url('assets/images/destination/bali.webp'); background-size: cover; background-position: center; opacity: 0.06; z-index: 0; filter: blur(3px);"></div>
    
    <!-- Floating travel elements -->
    <div class="floating-element" style="position: absolute; top: 15%; left: 5%; font-size: 40px; opacity: 0.15; animation: float-item 8s ease-in-out infinite;">✈️</div>
    <div class="floating-element" style="position: absolute; top: 25%; right: 8%; font-size: 35px; opacity: 0.15; animation: float-item 7s ease-in-out 1s infinite;">🏝️</div>
    <div class="floating-element" style="position: absolute; bottom: 15%; right: 12%; font-size: 38px; opacity: 0.15; animation: float-item 9s ease-in-out 2s infinite;">🧳</div>
    <div class="floating-element" style="position: absolute; bottom: 20%; left: 10%; font-size: 36px; opacity: 0.15; animation: float-item 10s ease-in-out 3s infinite;">🌴</div>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Modern glass-morphism card -->
                <div class="error-card" style="position: relative; overflow: hidden; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.85); border-radius: 20px; box-shadow: 0 25px 45px rgba(0,0,0,0.1), 0 0 0 1px rgba(255,255,255,0.1) inset; border: 1px solid rgba(255,255,255,0.2); animation: card-appear 0.8s ease-out forwards;">
                    
                    <!-- Top section with panoramic view -->
                    <div style="height: 220px; background-image: url('assets/images/destination/bali.webp'); background-size: cover; background-position: center; position: relative; overflow: hidden;">
                        <!-- Gradient overlay -->
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.2) 100%);"></div>
                        
                        <!-- Animated particles -->
                        <div class="particles-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden;">
                            <div class="particle" style="position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0.7;"></div>
                            <div class="particle" style="position: absolute; width: 3px; height: 3px; background: white; border-radius: 50%; opacity: 0.5;"></div>
                            <div class="particle" style="position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0.6;"></div>
                            <div class="particle" style="position: absolute; width: 1px; height: 1px; background: white; border-radius: 50%; opacity: 0.8;"></div>
                            <div class="particle" style="position: absolute; width: 2px; height: 2px; background: white; border-radius: 50%; opacity: 0.7;"></div>
                        </div>
                        
                        <!-- Enhanced compass design with 404 -->
                        <div class="compass-container" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <div class="compass" style="position: relative; width: 220px; height: 220px; background: rgba(255,255,255,0.98); border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.4), 0 0 0 5px rgba(255,255,255,0.2); animation: compass-glow 3s ease-in-out infinite, compass-appear 1s ease-out forwards;">
                                <!-- Outer ring -->
                                <div style="position: absolute; width: 200px; height: 200px; border: 3px solid #B4975A; border-radius: 50%; animation: pulse 2s ease-in-out infinite;"></div>
                                
                                <!-- Inner ring -->
                                <div style="position: absolute; width: 170px; height: 170px; border: 2px dashed #B4975A; border-radius: 50%; opacity: 0.7; animation: rotate 60s linear infinite;"></div>
                                
                                <!-- 404 display with solid color for better visibility -->
                                <div style="font-size: 68px; font-weight: 800; color: #B4975A; text-shadow: 0 2px 5px rgba(0,0,0,0.2); letter-spacing: 2px; animation: bounce 2s ease-in-out infinite; position: relative; z-index: 10;">404</div>
                                
                                <!-- Compass points with better visibility -->
                                <div style="position: absolute; top: 15px; left: 50%; transform: translateX(-50%); font-size: 18px; font-weight: bold; color: #333; z-index: 6;">N</div>
                                <div style="position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%); font-size: 18px; font-weight: bold; color: #333; z-index: 6;">S</div>
                                <div style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 18px; font-weight: bold; color: #333; z-index: 6;">W</div>
                                <div style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 18px; font-weight: bold; color: #333; z-index: 6;">E</div>
                                
                                <!-- Compass scale lines -->
                                <div style="position: absolute; width: 180px; height: 180px; border-radius: 50%; z-index: 2;">
                                    <?php for($i = 0; $i < 72; $i++) { 
                                        $rotation = $i * 5;
                                        $width = $i % 2 ? "1px" : "2px";
                                        $height = $i % 2 ? "6px" : "10px";
                                        echo "<div style='position: absolute; width: {$width}; height: {$height}; background: #B4975A; opacity: 0.6; top: 0; left: 50%; transform: translateX(-50%) rotate({$rotation}deg); transform-origin: bottom center;'></div>";
                                    } ?>
                                </div>
                                
                                <!-- Animated compass needle -->
                                <div class="compass-needle" style="position: absolute; width: 4px; height: 85px; background: #B4975A; top: calc(50% - 42px); left: calc(50% - 2px); transform-origin: bottom center; transform: rotate(45deg); z-index: 3; animation: compass-search 6s ease-in-out infinite;">
                                    <div style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 18px solid #B4975A;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logo -->
                        <div style="position: absolute; top: 20px; left: 20px; animation: fade-in 1.5s ease-out forwards;">
                            <img src="assets/images/logo/KE-White.png" alt="Karma Experience" style="height: 40px;">
                        </div>
                    </div>
                    
                    <!-- Content area - reduced padding and compacted layout -->
                    <div style="padding: 40px 40px 30px; text-align: center;">
                        <h1 style="font-size: 36px; color: #333; font-weight: 700; margin-bottom: 15px; animation: fade-up 0.8s ease-out 0.3s both;">Destination Off The Map!</h1>
                        
                        <p style="font-size: 16px; color: #666; max-width: 720px; margin: 0 auto 20px; line-height: 1.6; animation: fade-up 0.8s ease-out 0.5s both;">
                            It seems you've ventured into uncharted territory! Our luxury travel experts haven't mapped this destination yet. Let us guide you back to the routes we've carefully curated for an exceptional travel experience.
                        </p>
                        
                        <!-- Modern travel itinerary - compacted -->
                        <div style="background: rgba(249, 247, 242, 0.7); backdrop-filter: blur(5px); border: 1px solid rgba(233, 226, 208, 0.5); border-radius: 12px; padding: 20px; max-width: 700px; margin: 0 auto 30px; text-align: left; animation: fade-up 0.8s ease-out 0.7s both;">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #B4975A 0%, #e2c992 100%); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin-right: 15px; box-shadow: 0 5px 15px rgba(180, 151, 90, 0.3);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 16 16">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 13px; text-transform: uppercase; color: #B4975A; font-weight: 600; letter-spacing: 1px; margin-bottom: 2px;">Current Location</div>
                                    <div style="font-size: 16px; color: #444; font-weight: 500;">Page Not Found (404)</div>
                                </div>
                            </div>
                            
                            <div style="width: 2px; height: 25px; background: linear-gradient(to bottom, #B4975A, rgba(180, 151, 90, 0.2)); margin-left: 21px; animation: growing-line 1s ease-out 1s both;"></div>
                            
                            <div style="display: flex; align-items: center; opacity: 0; animation: fade-in 1s ease-out 1.3s forwards;">
                                <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #333 0%, #555 100%); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin-right: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 16 16">
                                        <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4H2.5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 13px; text-transform: uppercase; color: #333; font-weight: 600; letter-spacing: 1px; margin-bottom: 2px;">Recommended Destination</div>
                                    <div style="font-size: 16px; color: #444; font-weight: 500;">Karma Experience Homepage</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modern action buttons -->
                        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; animation: fade-up 0.8s ease-out 0.9s both;">
                            <a href="index.php" class="btn-primary" style="background: linear-gradient(135deg, #B4975A 0%, #e2c992 100%); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; transition: all 0.3s ease; box-shadow: 0 6px 15px rgba(180, 151, 90, 0.3); position: relative; overflow: hidden;">
                                <div style="position: relative; z-index: 2; display: flex; align-items: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px;">
                                        <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4H2.5z"/>
                                    </svg>
                                    Return to Homepage
                                </div>
                            </a>
                            <a href="our-destinations.php" class="btn-secondary" style="background: linear-gradient(135deg, #333 0%, #555 100%); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; transition: all 0.3s ease; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2); position: relative; overflow: hidden;">
                                <div style="position: relative; z-index: 2; display: flex; align-items: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px;">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                    </svg>
                                    Explore Our Destinations
                                </div>
                            </a>
                            <a href="javascript:history.back()" class="btn-outline" style="background: transparent; border: 2px solid #333; color: #333; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; transition: all 0.3s ease; position: relative; overflow: hidden;">
                                <div style="position: relative; z-index: 2; display: flex; align-items: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 8px;">
                                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                                    </svg>
                                    Go Back
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Modern animation keyframes */
@keyframes float-item {
    0%, 100% { transform: translateY(0) rotate(0); }
    50% { transform: translateY(-25px) rotate(5deg); }
}

@keyframes compass-search {
    0% { transform: rotate(45deg); }
    25% { transform: rotate(135deg); }
    50% { transform: rotate(225deg); }
    75% { transform: rotate(315deg); }
    100% { transform: rotate(405deg); }
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
}

@keyframes compass-glow {
    0%, 100% { box-shadow: 0 10px 30px rgba(0,0,0,0.4), 0 0 0 5px rgba(255,255,255,0.2); }
    50% { box-shadow: 0 10px 35px rgba(180, 151, 90, 0.5), 0 0 0 5px rgba(255,255,255,0.4); }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fade-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes card-appear {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes growing-line {
    from { height: 0; opacity: 0; }
    to { height: 25px; opacity: 1; }
}

/* Particle animations */
.particles-container .particle:nth-child(1) {
    top: 20%; left: 30%;
    animation: particle-float 6s ease-in-out infinite;
}
.particles-container .particle:nth-child(2) {
    top: 70%; left: 40%;
    animation: particle-float 8s ease-in-out infinite 1s;
}
.particles-container .particle:nth-child(3) {
    top: 40%; left: 80%;
    animation: particle-float 7s ease-in-out infinite 2s;
}
.particles-container .particle:nth-child(4) {
    top: 30%; left: 60%;
    animation: particle-float 9s ease-in-out infinite 3s;
}
.particles-container .particle:nth-child(5) {
    top: 80%; left: 20%;
    animation: particle-float 10s ease-in-out infinite 1.5s;
}

@keyframes particle-float {
    0%, 100% { transform: translateY(0) translateX(0); }
    25% { transform: translateY(-20px) translateX(10px); }
    50% { transform: translateY(0) translateX(20px); }
    75% { transform: translateY(20px) translateX(10px); }
}

/* Button effects */
.btn-primary::after, .btn-secondary::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -60%;
    width: 20%;
    height: 200%;
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(30deg);
    transition: all 0.6s ease;
    z-index: 1;
}

.btn-primary:hover::after, .btn-secondary:hover::after {
    left: 120%;
}

.btn-primary:hover, .btn-secondary:hover {
    transform: translateY(-5px);
}

.btn-primary:hover {
    box-shadow: 0 10px 20px rgba(180, 151, 90, 0.4);
}

.btn-secondary:hover {
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.btn-outline:hover {
    background: #333;
    color: white;
    transform: translateY(-5px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    section {
        padding-top: 110px;
    }
    .compass-container .compass {
        width: 200px;
        height: 200px;
    }
    .compass-container .compass > div[style*="font-size: 68px"] {
        font-size: 56px !important;
    }
}

@media (max-width: 576px) {
    section {
        padding-top: 100px;
    }
    .compass-container .compass {
        width: 180px;
        height: 180px;
    }
    .compass-container .compass > div[style*="font-size: 68px"] {
        font-size: 48px !important;
    }
}
</style>

<script>
// Add more particles dynamically
document.addEventListener('DOMContentLoaded', function() {
    const particlesContainer = document.querySelector('.particles-container');
    if (particlesContainer) {
        for (let i = 0; i < 15; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.width = Math.random() * 3 + 'px';
            particle.style.height = particle.style.width;
            particle.style.background = 'white';
            particle.style.borderRadius = '50%';
            particle.style.opacity = Math.random() * 0.5 + 0.3;
            particle.style.position = 'absolute';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.left = Math.random() * 100 + '%';
            
            // Create unique animation
            const duration = Math.random() * 10 + 5;
            const delay = Math.random() * 5;
            particle.style.animation = `particle-float ${duration}s ease-in-out infinite ${delay}s`;
            
            particlesContainer.appendChild(particle);
        }
    }
});
</script>

<?php
// Include footer
include 'kfooter.php';
?>
