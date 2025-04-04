<?php 
session_start();
// Get enquiry details from session
$resortName = $_SESSION['enquiry_resort'] ?? 'our resort';
$email = $_SESSION['enquiry_email'] ?? '';
$name = $_SESSION['enquiry_name'] ?? 'valued guest';

// Clear the session variables after retrieving them
unset($_SESSION['enquiry_resort']);
unset($_SESSION['enquiry_email']);
unset($_SESSION['enquiry_name']);
unset($_SESSION['success_message']);

require 'kheader.php'; 
?>

<div class="thank-you-wrapper">
    <div class="thank-you-banner">
        <div class="banner-overlay"></div>
        <div class="banner-content">
            <h1>Thank You</h1>
            <p>We appreciate your interest in Karma Experience</p>
        </div>
    </div>
    
    <div class="thank-you-container">
        <div class="success-animation">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
            </svg>
        </div>
        
        <div class="thank-you-content">
            <h1>Thank You, <span class="highlight"><?php echo htmlspecialchars($name); ?></span>!</h1>
            <p class="main-message">Your enquiry about <strong><?php echo htmlspecialchars($resortName); ?></strong> has been received.</p>
            
            <div class="information-card">
                <div class="card-header">
                    <i class="fas fa-envelope"></i>
                    <h3>What's Next?</h3>
                </div>
                <div class="card-body">
                    <ul class="steps-list">
                        <li>
                            <div class="step-icon"><i class="fas fa-paper-plane"></i></div>
                            <div class="step-content">
                                <h4>Confirmation Sent</h4>
                                <p>We've sent a confirmation email to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                            </div>
                        </li>
                        <li>
                            <div class="step-icon"><i class="fas fa-headset"></i></div>
                            <div class="step-content">
                                <h4>Personal Contact</h4>
                                <p>Our team will reach out to you within 24 hours</p>
                            </div>
                        </li>
                        <li>
                            <div class="step-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="step-content">
                                <h4>Plan Your Experience</h4>
                                <p>We'll help customize your perfect vacation</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Return to Home
                </a>
                <a href="/our-destinations.php" class="btn btn-secondary">
                    <i class="fas fa-globe-asia"></i> Explore More Destinations
                </a>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #5b8eb3;
    --secondary-color: #f0f7ff;
    --accent-color: #ff7e5f;
    --text-color: #384c6b;
    --light-text: #6c8096;
    --success-color: #4bb543;
    --border-radius: 12px;
    --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
    --gold-color: #B4975A;
}

.thank-you-wrapper {
    min-height: 80vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4ecf7 100%);
    padding: 0 0 60px 0;
}

/* New Banner Styles */
.thank-you-banner {
    width: 100%;
    height: 350px;
    background-image: url('assets/images/slider/India-Banner.webp');
    background-size: cover;
    background-position: center;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 40px;
    padding-top: 100px;
}

.banner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
}

.banner-content {
    position: relative;
    z-index: 2;
    text-align: center;
    color: white;
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 8px;
    padding: 30px 60px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    margin-top: 30px;
}

.banner-content h1 {
    font-size: 4rem;
    font-weight: 800;
    margin: 0 0 10px 0;
    letter-spacing: 2px;
    text-shadow: 0 3px 10px rgba(0, 0, 0, 0.5);
    color: white;
    text-transform: uppercase;
}

.banner-content p {
    font-size: 1.2rem;
    margin: 0;
    text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    opacity: 0.9;
}

.thank-you-container {
    max-width: 800px;
    width: 100%;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    position: relative;
    margin: 0 20px;
}

/* Animation */
.success-animation {
    text-align: center;
    margin: 30px 0;
}

.checkmark {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: block;
    margin: 0 auto;
    stroke-width: 2;
    stroke: var(--success-color);
    stroke-miterlimit: 10;
    box-shadow: inset 0px 0px 0px var(--success-color);
    animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
}

.checkmark__circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 2;
    stroke-miterlimit: 10;
    stroke: var(--success-color);
    fill: none;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark__check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}

@keyframes stroke {
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes scale {
    0%, 100% {
        transform: none;
    }
    50% {
        transform: scale3d(1.1, 1.1, 1);
    }
}

@keyframes fill {
    100% {
        box-shadow: inset 0px 0px 0px 30px rgba(75, 181, 67, 0.1);
    }
}

/* Content Styling */
.thank-you-content {
    padding: 0 40px 40px;
    text-align: center;
}

.thank-you-content h1 {
    color: var(--text-color);
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 10px;
    animation: fadeUp 0.8s forwards;
}

.highlight {
    color: var(--gold-color);
}

.main-message {
    color: var(--light-text);
    font-size: 1.2rem;
    margin-bottom: 30px;
    animation: fadeUp 1s forwards;
}

.information-card {
    background: var(--secondary-color);
    border-radius: var(--border-radius);
    margin: 30px 0;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    animation: fadeUp 1.2s forwards;
}

.card-header {
    background: var(--gold-color);
    color: white;
    padding: 15px 20px;
    display: flex;
    align-items: center;
}

.card-header i {
    font-size: 24px;
    margin-right: 10px;
}

.card-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.card-body {
    padding: 20px;
}

.steps-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.steps-list li {
    display: flex;
    padding: 15px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    animation: fadeInRight 0.6s forwards;
    animation-delay: calc(0.2s * var(--i));
    opacity: 0;
}

.steps-list li:last-child {
    border-bottom: none;
}

.step-icon {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: var(--gold-color);
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
    flex-shrink: 0;
}

.step-content {
    text-align: left;
}

.step-content h4 {
    margin: 0 0 5px 0;
    color: var(--text-color);
    font-size: 1rem;
    font-weight: 600;
}

.step-content p {
    margin: 0;
    color: var(--light-text);
    font-size: 0.9rem;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
    flex-wrap: wrap;
    animation: fadeUp 1.5s forwards;
}

.btn {
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: 600;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-size: 0.95rem;
}

.btn i {
    margin-right: 8px;
}

.btn-primary {
    background: var(--gold-color);
    color: white;
    box-shadow: 0 4px 12px rgba(180, 151, 90, 0.3);
}

.btn-primary:hover {
    background: #96793D;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(180, 151, 90, 0.4);
}

.btn-secondary {
    background: white;
    color: var(--text-color);
    border: 1px solid #dce7f2;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.btn-secondary:hover {
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInRight {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Make it responsive */
@media (max-width: 768px) {
    .thank-you-banner {
        height: 280px;
        padding-top: 80px;
    }
    
    .banner-content {
        padding: 20px 30px;
        margin-top: 20px;
    }
    
    .banner-content h1 {
        font-size: 2.5rem;
    }
    
    .banner-content p {
        font-size: 1rem;
    }
    
    .thank-you-wrapper {
        padding: 0 0 40px 0;
    }
    
    .thank-you-content {
        padding: 0 20px 30px;
    }
    
    .thank-you-content h1 {
        font-size: 1.8rem;
    }
    
    .main-message {
        font-size: 1.1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Animation delay for steps */
.steps-list li:nth-child(1) { --i: 1; }
.steps-list li:nth-child(2) { --i: 2; }
.steps-list li:nth-child(3) { --i: 3; }
</style>
<?php require 'kfooter.php'; ?>