<?php
// DISABLED FOR LOCALHOST/XAMPP
// reCAPTCHA Configuration - DISABLED FOR LOCAL DEVELOPMENT
define('RECAPTCHA_SITE_KEY', '6LeK2AkrAAAAAKhbi31oV2IQOlVfdR_V7Th9DZYQ');
define('RECAPTCHA_SECRET_KEY', '6LeK2AkrAAAAAD3CRR99ODwwF0YtCF7NLBPZP0u_');

// reCAPTCHA V3 Configuration - DISABLED FOR LOCAL DEVELOPMENT
define('RECAPTCHA_V3_SITE_KEY', '6LeK2AkrAAAAAKhbi31oV2IQOlVfdR_V7Th9DZYQ');
define('RECAPTCHA_V3_SECRET_KEY', '6LeK2AkrAAAAAD3CRR99ODwwF0YtCF7NLBPZP0u_');
define('RECAPTCHA_V3_SCORE_THRESHOLD', 0.5);  // Score threshold (0.0 to 1.0)

// Define a helper function to enable reCAPTCHA validation
function is_recaptcha_enabled() {
    return true; // Enable reCAPTCHA validation
} 