<?php
// Set session parameters BEFORE session_start
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Create sessions directory if it doesn't exist
if (!file_exists(dirname(__FILE__) . '/sessions')) {
    mkdir(dirname(__FILE__) . '/sessions', 0777, true);
}

// Set session save path BEFORE session_start
$sessionPath = dirname(__FILE__) . '/sessions';
session_save_path($sessionPath);

// Start session
session_start();

// Generate CSRF token if needed
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once('kheader.php');
$pdo = require 'db.php';

// Fetch active resorts from the database and correctly construct banner image paths
$sql = "SELECT r.id, r.resort_name, r.destination_id, r.resort_code, r.resort_slug, r.banner_image,
               CONCAT('assets/resorts/', r.resort_slug, '/', r.banner_image) AS resort_image 
       FROM resorts r 
       WHERE r.is_active = 1";
$stmt = $pdo->query($sql);

$resorts = [];
if ($stmt) {
    while($row = $stmt->fetch()) {
        // If banner_image is empty, use a fallback image
        if (empty($row['banner_image'])) {
            $row['resort_image'] = 'assets/img/normal/cta-img-6.jpg';
        }
        $resorts[] = $row;
    }
}

// Fetch all destinations
$sql = "SELECT id, destination_name, banner_image, 
               CONCAT('assets/destinations/', COALESCE(banner_image, 'default-destination.jpg')) AS destination_image 
       FROM destinations";
$stmt = $pdo->query($sql);

$destinations = [];
if ($stmt) {
    while($row = $stmt->fetch()) {
        // If banner_image is empty, use a fallback image
        if (empty($row['destination_image'])) {
            $row['destination_image'] = 'assets/img/normal/cta-img-6.jpg';
        }
        $destinations[] = $row;
    }
}

// CSRF token already set at the top of the file
$csrf_token = $_SESSION['csrf_token'];
?>

<!-- Add the international telephone input library -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>

<!-- Modern CSS for the enquire page -->
<style>
:root {
    --primary-color: #8b734b;
    --primary-hover: #8b734b;
    --secondary-color: #FF7E50;
    --secondary-hover: #FF6B31;
    --success-color: #10B981;
    --dark-color: #1A202C;
    --gray-color: #4A5568;
    --light-color: #F8FAFC;
    --white: #FFFFFF;
    --muted: #718096;
    --border-color: #E2E8F0;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --font-sans: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --transition: all 0.3s ease;
}

body {
    font-family: var(--font-sans);
    color: var(--dark-color);
    background-color: var(--light-color);
}

/* Breadcrumb */
.breadcumb-wrapper {
    background-image: linear-gradient(rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.75)), url("assets/img/bg/about-bg.webp");
    background-size: cover;
    background-position: center;
    padding: 150px 0;
    position: relative;
    color: var(--white);
    text-align: center;
    margin-bottom: 50px;
}

.breadcumb-content h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 25px;
    text-shadow: 0px 2px 4px rgba(0, 0, 0, 0.5);
    color: #ffffff;
    position: relative;
    z-index: 5;
}

.breadcumb-menu {
    display: flex;
    justify-content: center;
    padding: 0;
    list-style: none;
    gap: 10px;
    position: relative;
    z-index: 5;
}

.breadcumb-menu li {
    position: relative;
    font-size: 1.2rem;
    font-weight: 500;
}

.breadcumb-menu li:not(:first-child)::before {
    content: '/';
    margin-right: 10px;
    color: var(--secondary-color);
}

.breadcumb-menu a {
    color: #ffffff;
    text-decoration: none;
    transition: var(--transition);
}

.breadcumb-menu a:hover {
    color: var(--secondary-color);
    text-decoration: underline;
}

/* Form Area */
.form-body {
    padding: 0 0 80px 0; /* Removed top padding since we added margin to breadcrumb */
    background-color: var(--light-color);
}

.enquiry-container {
    max-width: 1100px;
    margin: 0 auto;
    background: var(--white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.enquiry-header {
    background: linear-gradient(135deg, #8b734b, #8b734b);
    color: var(--white);
    padding: 40px 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.enquiry-header::before,
.enquiry-header::after {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
}

.enquiry-header::before {
    top: -100px;
    right: -100px;
}

.enquiry-header::after {
    bottom: -150px;
    left: -150px;
    width: 350px;
    height: 350px;
}

.enquiry-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 15px;
    position: relative;
    z-index: 1;
    color: #ffffff;
}

.enquiry-header p {
    font-size: 1.2rem;
    margin: 0;
    position: relative;
    z-index: 1;
    opacity: 0.95;
    color: #ffffff;
}

/* Form Layout */
.enquiry-content-wrapper {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 992px) {
    .enquiry-content-wrapper {
    grid-template-columns: 5fr 7fr;
    }
}

.enquiry-sidebar {
    padding: 20px;
    background-color: rgba(30, 95, 116, 0.05);
    border-right: 1px solid var(--border-color);
}

@media (max-width: 991px) {
    .enquiry-sidebar {
        border-right: none;
        border-bottom: 1px solid var(--border-color);
    }
}

.sidebar-progress {
    margin-bottom: 30px;
}

.progress-title {
    display: flex;
    justify-content: space-between;
    font-size: 1rem;
    color: var(--gray-color);
    margin-bottom: 10px;
    font-weight: 500;
}

.progress-bar-container {
    width: 100%;
    height: 10px;
    background-color: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(to right, #8b734b, #FF7E50);
    border-radius: 10px;
    transition: width 0.5s ease;
}

.sidebar-steps {
    margin-top: 40px;
}

.sidebar-step {
    display: flex;
    align-items: flex-start;
    margin-bottom: 25px;
    opacity: 0.7;
    transition: var(--transition);
}

.sidebar-step.active {
    opacity: 1;
}

.sidebar-step.completed .step-icon {
    background-color: var(--success-color);
    border-color: var(--success-color);
    color: var(--white);
}

.step-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background-color: var(--white);
    border: 2px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    margin-right: 15px;
    transition: var(--transition);
    position: relative;
    z-index: 1;
    color: var(--gray-color);
    flex-shrink: 0;
}

.sidebar-step.active .step-icon {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--white);
}

.step-line {
    position: absolute;
    left: 18px;
    top: 38px;
    width: 2px;
    height: calc(100% - 38px);
    background-color: var(--border-color);
    z-index: 0;
}

.sidebar-step:last-child .step-line {
    display: none;
}

.step-content {
    margin-top: 7px;
}

.step-title {
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--dark-color);
    font-size: 1.1rem;
}

.step-desc {
    font-size: 0.95rem;
    color: var(--gray-color);
    line-height: 1.5;
}

.destination-preview {
    margin-top: 30px;
    display: none;
}

.destination-preview.active {
    display: block;
    animation: fadeIn 0.5s ease;
}

.preview-card {
    background-color: var(--white);
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;
    border: 1px solid #E2E8F0;
}

.preview-title {
    font-size: 0.9rem;
    text-transform: uppercase;
    color: var(--gray-color);
    margin-bottom: 12px;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.selected-item {
    display: flex;
    align-items: center;
    background-color: rgba(30, 95, 116, 0.1);
    border-radius: 8px;
    padding: 12px 15px;
}

.selected-icon {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    background-color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    margin-right: 15px;
    flex-shrink: 0;
    font-size: 1.2rem;
}

.selected-details h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 5px;
    color: var(--dark-color);
}

.selected-details p {
    font-size: 0.95rem;
    color: var(--gray-color);
    margin: 0;
}

.enquiry-form-container {
    padding: 20px;
}

@media (min-width: 768px) {
.enquiry-form-container {
    padding: 40px;
    }
}

.form-content {
    display: none;
}

.form-content.active {
    display: block;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-step-title {
    margin-bottom: 30px;
}

.form-step-title h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0 0 10px;
    color: var(--dark-color);
}

.form-step-title p {
    font-size: 1.1rem;
    color: var(--gray-color);
    margin: 0;
}

.destination-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 25px;
}

@media (min-width: 768px) {
    .destination-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

.destination-card {
    background-color: var(--white);
    border: 2px solid var(--border-color);
    border-radius: 10px;
    padding: 15px;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.destination-card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

.destination-card h4 {
    font-size: 1rem;
    margin: 0 0 5px;
    color: var(--dark-color);
}

.destination-card p {
    font-size: 0.9rem;
    color: var(--gray-color);
    margin: 0;
}

.destination-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background-color: var(--primary-color);
    color: var(--white);
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
    z-index: 2;
}

.destination-card.selected .destination-badge {
    display: none;
}

.resort-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 25px;
}

@media (min-width: 768px) {
    .resort-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.resort-card {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    cursor: pointer;
    height: 220px;
    margin-bottom: 0; /* Remove margin-bottom since we're using grid gap */
}

.resort-card:hover, .resort-card.selected {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.resort-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    z-index: 1;
    transition: transform 0.5s ease;
}

.resort-card:hover .resort-image {
    transform: scale(1.05);
}

.resort-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to top, rgba(0,0,0,0.9) 20%, rgba(0,0,0,0.5) 60%, rgba(0,0,0,0.3) 100%);
    z-index: 2;
}

.resort-card.selected .resort-overlay {
    background: linear-gradient(to top, rgba(19, 59, 92, 0.9) 20%, rgba(19, 59, 92, 0.6) 60%, rgba(19, 59, 92, 0.4) 100%);
}

.resort-content {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 20px;
    z-index: 3;
    color: var(--white);
}

.resort-content h4 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 8px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    color: #ffffff;
}

.resort-content p {
    font-size: 0.95rem;
    margin: 0;
    opacity: 0.95;
    color: #ffffff;
    line-height: 1.5;
    max-height: 4.5em;
    overflow: hidden;
    text-shadow: 0 1px 3px rgba(0,0,0,0.4);
}

.resort-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background-color: var(--primary-color);
    color: var(--white);
    font-size: 0.9rem;
    padding: 6px 14px;
    border-radius: 30px;
    z-index: 3;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
    letter-spacing: 0.5px;
}

.resort-card.selected::after {
    content: '✓';
    position: absolute;
    top: 15px;
    left: 15px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: var(--success-color);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 700;
    z-index: 3;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
}

/* Form Controls */
.form-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (min-width: 768px) {
    .form-row {
        grid-template-columns: 1fr 1fr;
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 10px;
    font-weight: 500;
    color: var(--dark-color);
    font-size: 1rem;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(139, 115, 75, 0.1);
    outline: none;
}

.form-control::placeholder {
    color: var(--muted);
}

input[type="date"].form-control {
    padding-right: 10px;
    min-height: 52px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234A5568' viewBox='0 0 16 16'%3E%3Cpath d='M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4V.5zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2zm-3.5-7h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: calc(100% - 15px) center;
}

select.form-control {
    padding-right: 40px;
    min-height: 52px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234A5568' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: calc(100% - 15px) center;
    cursor: pointer;
}

/* Fix the date field appearance on Firefox */
input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: 0;
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    cursor: pointer;
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 30px;
}

@media (min-width: 768px) {
    .form-actions {
        flex-direction: row;
    justify-content: space-between;
    }
}

.btn {
    width: 100%;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

@media (min-width: 768px) {
    .btn {
        width: auto;
    }
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background-color: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background-color: var(--gray-color);
    color: var(--white);
}

.btn-submit {
    background-color: var(--primary-color);
    color: var(--white);
}

.btn-submit:hover {
    background-color: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.field-error {
    color: #E11D48;
    font-size: 0.9rem;
    margin-top: 8px;
    display: none;
    font-weight: 500;
}

/* Updated Contact Section Styling */
.contact-section {
    margin-top: 80px;
    padding: 70px 0;
    background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
    border-radius: 30px;
    position: relative;
    overflow: hidden;
}

.contact-section::before {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: rgba(30, 95, 116, 0.04);
    top: -150px;
    right: -150px;
    z-index: 0;
}

.contact-section::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: rgba(255, 126, 80, 0.04);
    bottom: -200px;
    left: -200px;
    z-index: 0;
}

.section-title {
    text-align: center;
    margin-bottom: 60px;
    position: relative;
    z-index: 1;
}

.section-title h2 {
    font-size: 2.8rem;
    font-weight: 800;
    margin: 0 0 20px;
    color: var(--dark-color);
    position: relative;
    display: inline-block;
}

.section-title h2:after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 70px;
    height: 4px;
    background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
    border-radius: 4px;
}

.section-title p {
    font-size: 1.2rem;
    color: var(--gray-color);
    margin: 20px auto 0;
    max-width: 700px;
    line-height: 1.6;
}

.contact-cards-container {
    position: relative;
    z-index: 1;
}

.contact-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(330px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.contact-card {
    background-color: var(--white);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    transform: translateY(0);
    height: 100%;
    position: relative;
    border: 1px solid rgba(226, 232, 240, 0.6);
}

.contact-card:hover {
    transform: translateY(-15px);
    box-shadow: 0 20px 40px rgba(30, 95, 116, 0.15);
}

.card-header {
    padding: 35px 30px;
    position: relative;
    z-index: 1;
    overflow: hidden;
}

.contact-card:nth-child(1) .card-header {
    background: linear-gradient(135deg, #8b734b, #8b734b);
}

.contact-card:nth-child(2) .card-header {
    background: linear-gradient(135deg, #8b734b, #247691);
}

.contact-card:nth-child(3) .card-header {
    background: linear-gradient(135deg, #8b734b, #8b734b);
}

.card-header::before {
    content: '';
    position: absolute;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.1);
    top: -75px;
    right: -75px;
    z-index: -1;
}

.card-header::after {
    content: '';
    position: absolute;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.15);
    bottom: -40px;
    left: -40px;
    z-index: -1;
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background-color: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    font-size: 1.8rem;
    color: #ffffff;
}

.card-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0 0 8px;
    color: #ffffff;
    letter-spacing: 0.5px;
}

.card-subtitle {
    font-size: 1.1rem;
    margin: 0;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 400;
}

.card-body {
    padding: 30px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.contact-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.contact-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background-color: rgba(30, 95, 116, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    margin-right: 20px;
    flex-shrink: 0;
    font-size: 1.4rem;
    transition: all 0.3s ease;
}

.contact-card:hover .contact-icon {
    background-color: var(--primary-color);
    color: white;
    transform: scale(1.1);
}

.contact-info {
    flex-grow: 1;
}

.contact-info h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 10px;
    color: var(--dark-color);
}

.contact-info p, .contact-info a {
    font-size: 1.15rem;
    color: var(--gray-color);
    margin: 0;
    line-height: 1.5;
    word-break: break-word; /* Allow long email addresses to break */
    overflow-wrap: break-word;
}

.contact-info a {
    color: var(--primary-color);
    text-decoration: none;
    transition: var(--transition);
    font-weight: 600;
    display: inline-block;
}

.contact-info a:hover {
    color: var(--primary-hover);
    transform: translateX(3px);
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .contact-cards {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
    }
    
    .card-header {
        padding: 30px 25px;
    }
    
    .card-body {
        padding: 25px;
    }
}

@media (max-width: 768px) {
    .section-title h2 {
        font-size: 2.2rem;
    }
    
    .contact-cards {
        grid-template-columns: 1fr;
        max-width: 450px;
    }
}

.change-destination-card {
    background-color: var(--white);
    border: 2px dashed var(--border-color);
    border-radius: 10px;
    padding: 20px;
    cursor: pointer;
    transition: var(--transition);
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 15px;
    min-height: 220px;
}

.change-destination-card i {
    font-size: 2rem;
    color: var(--primary-color);
    transition: transform 0.3s ease;
}

.change-destination-card h4 {
    font-size: 1.1rem;
    margin: 0;
    color: var(--primary-color);
    font-weight: 600;
}

.change-destination-card:hover {
    background-color: rgba(139, 115, 75, 0.05);
    border-color: var(--primary-color);
}

.change-destination-card:hover i {
    transform: rotate(180deg);
}

/* Remove old button styles */
.change-destination-btn {
    display: none;
}

/* Update resort grid spacing */
.resort-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 25px;
}

@media (min-width: 768px) {
    .resort-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Update destination grid spacing */
.destination-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 25px;
}

@media (min-width: 768px) {
    .destination-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

/* Custom styling for checkboxes */
.form-check {
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
}

/* Alert styling */
.alert {
    padding: 15px;
    margin: 0 20px 20px;
    border-radius: 5px;
    position: relative;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.spinner-container {
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
}

.spinner {
    width: 40px;
    height: 40px;
    margin: 0 auto 15px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #8b734b;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.form-check-input {
    min-width: 20px !important;
    min-height: 20px !important;
    margin-right: 10px !important;
    position: relative !important;
    top: 3px !important;
    visibility: visible !important;
    opacity: 1 !important;
    cursor: pointer !important;
    appearance: auto !important;
    -webkit-appearance: checkbox !important;
}

.form-check-label {
    font-size: 1rem;
    color: var(--dark-color);
    cursor: pointer;
    user-select: none;
    line-height: 1.5;
}
</style>

<!--banner section start-->
<div class="breadcumb-wrapper">
        <div class="container">
            <div class="breadcumb-content">
                <h1 class="breadcumb-title">Enquire Now</h1>
                <ul class="breadcumb-menu">
                    <li><a href="index.php">Home</a></li>
                    <li>Enquire Now</li>
                </ul>
            </div>
        </div>
    </div>
<!--banner section end-->

<!--form section start-->
<div class="form-body">
<div class="container">
        <div class="enquiry-container">
            <div class="enquiry-header">
                <h2>Start Your Dream Vacation</h2>
                <p>Our luxury destinations await. Complete this form and our team will contact you within 24 hours.</p>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
            <?php endif; ?>

            <div class="enquiry-content-wrapper">
                <!-- Left Sidebar -->
                <div class="enquiry-sidebar">
                    <!-- Progress Bar -->
                    <div class="sidebar-progress">
                        <div class="progress-title">
                            <span>Booking Progress</span>
                            <span id="progress-percentage">0%</span>
                            </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <!-- Sidebar Steps -->
                    <div class="sidebar-steps">
                        <div class="sidebar-step active" id="step1-sidebar">
                            <div class="step-icon">1</div>
                            <div class="step-line"></div>
                            <div class="step-content">
                                <div class="step-title">Choose Destination</div>
                                <div class="step-desc">Select your preferred destination from our collection</div>
                            </div>
                        </div>
                        
                        <div class="sidebar-step" id="step2-sidebar">
                            <div class="step-icon">2</div>
                            <div class="step-line"></div>
                            <div class="step-content">
                                <div class="step-title">Select Resort</div>
                                <div class="step-desc">Choose from our luxury resorts at your selected destination</div>
                            </div>
                        </div>
                        
                        <div class="sidebar-step" id="step3-sidebar">
                            <div class="step-icon">3</div>
                            <div class="step-content">
                                <div class="step-title">Your Details</div>
                                <div class="step-desc">Provide your contact information for booking</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Selected -->
                    <div class="destination-preview" id="destination-preview">
                        <div class="preview-card">
                            <div class="preview-title">Selected Destination</div>
                            <div class="selected-item">
                                <div class="selected-icon">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </div>
                                <div class="selected-details">
                                    <h4 id="selected-destination-name">None Selected</h4>
                                    <p>Luxury Destination</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="destination-preview" id="resort-preview">
                        <div class="preview-card">
                            <div class="preview-title">Selected Resort</div>
                            <div class="selected-item">
                                <div class="selected-icon">
                                    <i class="fa-solid fa-hotel"></i>
                                </div>
                                <div class="selected-details">
                                    <h4 id="selected-resort-name">None Selected</h4>
                                    <p id="selected-destination-subtext">Luxury Resort</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Form Container -->
                <div class="enquiry-form-container">
                    <form id="enquiryForm" action="process_resort_enquiry.php" method="POST" onsubmit="return validateForm()">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="resort_id" id="resort_id" value="">
                        <input type="hidden" name="destination_id" id="destination_id" value="">
                        <input type="hidden" name="resort_name" id="resort_name_hidden" value="">
                        <input type="hidden" name="destination_name" id="destination_name_hidden" value="">
                        <input type="hidden" name="resort_code" id="resort_code_hidden" value="">
                        
                        <!-- Step 1: Choose Destination -->
                        <div class="form-content active" id="step1-content">
                            <div class="form-step-title">
                                <h3>Choose Your Destination</h3>
                                <p>Select from our collection of exclusive destinations around the world.</p>
                            </div>
                            
                            <!-- Visual destination selector -->
                            <div class="destination-grid">
                                <?php foreach ($destinations as $destination): ?>
                                <div class="destination-card" 
                                     data-id="<?php echo htmlspecialchars($destination['id']); ?>"
                                     data-name="<?php echo htmlspecialchars($destination['destination_name']); ?>">
                                    <div class="destination-icon">
                                        <i class="fa-solid fa-map-location-dot"></i>
                                    </div>
                                    <h4><?php echo htmlspecialchars($destination['destination_name']); ?></h4>
                                    <p>Luxury Destination</p>
                                    <?php 
                                    // Count how many resorts are available for this destination
                                    $count = 0;
                                    foreach($resorts as $resort) {
                                        if($resort['destination_id'] == $destination['id']) {
                                            $count++;
                                        }
                                    }
                                    ?>
                                    <span class="destination-badge"><?php echo $count; ?> Resort<?php echo $count != 1 ? 's' : ''; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="field-error" id="destination-error">Please select a destination to continue</div>
                            
                            <div class="form-actions">
                                <div></div> <!-- Empty div for spacing -->
                                <button type="button" class="btn btn-primary" id="step1-next">
                                    <i class="fa-solid fa-arrow-right"></i> Continue to Resorts
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Select Resort -->
                        <div class="form-content" id="step2-content">
                            <div class="form-step-title">
                                <h3>Select Your Resort</h3>
                                <p>Choose from our luxury resorts at <span id="destination-name-display">your selected destination</span>.</p>
                            </div>
                            
                            <!-- Visual resort selector -->
                            <div class="resort-grid" id="resort-grid">
                                <!-- Resort cards will be dynamically populated here -->
                            </div>
                            
                            <div class="field-error" id="resort-error">Please select a resort to continue</div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" id="step2-prev">
                                    <i class="fa-solid fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary" id="step2-next">
                                    <i class="fa-solid fa-arrow-right"></i> Continue to Details
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Your Details -->
                        <div class="form-content" id="step3-content">
                            <div class="form-step-title">
                                <h3><i class="bi bi-person-lines-fill"></i> Step 3: Your Contact Information</h3>
                                <p>Please provide your contact details so we can get in touch regarding your enquiry.</p>
                            </div>
                            
                            <div class="modern-form">
                                <div class="form-row">
                            <div class="form-group">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Your first name" required>
                                        <div class="field-error" id="first_name_error">Please enter your first name</div>
                            </div>
                            <div class="form-group">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Your last name" required>
                                        <div class="field-error" id="last_name_error">Please enter your last name</div>
                                    </div>
                            </div>
                            
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Your email address" required>
                                        <div class="field-error" id="email_error">Please enter a valid email address</div>
                        </div>
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <div class="intl-tel-container">
                                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Your phone number" required>
                                            <input type="hidden" name="full_phone" id="full_phone">
                                        </div>
                                        <div class="field-error" id="phone_error">Please enter a valid phone number</div>
                    </div>
                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="dob" class="form-label">Date of Birth(must be 27 or older) <span class="text-danger">*</span></label>
                                        <div class="date-field-container">
                                            <input type="date" id="dob" name="dob" class="form-control" required placeholder="DD/MM/YYYY">
                                        </div>
                                        <div id="dob-error" class="field-error">You must be at least 27 years old</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="hasPassport" class="form-label">Do you have a valid passport? <span class="text-danger">*</span></label>
                                        <div class="select-container">
                                            <select id="hasPassport" name="has_passport" class="form-control" required>
                                                <option value="">Please select</option>
                                                <option value="yes">Yes</option>
                                                <option value="no">No</option>
                                                <option value="in_process">In Process</option>
                                            </select>
                                </div>
                                        <div id="hasPassport-error" class="field-error">Please select an option</div>
                            </div>
                        </div>
                                
                                <div class="form-group">
                                    <label for="additionalReq" class="form-label">Additional Requirements</label>
                                    <textarea id="additionalReq" name="additional_requirements" class="form-control" placeholder="Tell us about any special requirements or questions you may have"></textarea>
                    </div>

                                <div class="terms-privacy-section">
                                    <!-- Removed the Terms and Conditions checkbox -->
                                    
                                    <div class="form-check mt-3">
                                        <input type="checkbox" class="form-check-input" id="communicationAgree" name="communication_consent" required style="width: 20px; height: 20px; margin-right: 10px; visibility: visible; opacity: 1;">
                                        <label class="form-check-label" for="communicationAgree">
                                            Allow Karma Experience/Karma Group related brands to communicate with me via SMS/Email/Call during and after my submission on this promotional offer.
                                        </label>
                                        <div id="communication-error" class="field-error">This agreement is required</div>
                                    </div>
                                    
                                    <div class="form-check mt-3">
                                        <input type="checkbox" class="form-check-input" id="dndAgree" name="dnd_consent" required style="width: 20px; height: 20px; margin-right: 10px; visibility: visible; opacity: 1;">
                                        <label class="form-check-label" for="dndAgree">
                                            Should I be a registered DND subscriber, I agree that I have requested to be contacted about this contest/promotional offer.
                                        </label>
                                        <div id="dnd-error" class="field-error">This agreement is required</div>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" id="step3-prev">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </button>
                                    <button type="submit" class="btn btn-submit">
                                        <i class="fas fa-check-circle"></i> Submit Enquiry
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    </div>
            </div>
    </div>

        <!-- Contact Section -->
        <div class="contact-section">
            <div class="section-title">
                <h2>Our Offices</h2>
                <p>Get in touch with our global team for personalized assistance with your travel plans</p>
            </div>
            
            <div class="contact-cards-container">
                <div class="contact-cards">
                    <div class="contact-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <h3 class="card-title">Marketing Head Office</h3>
                            <p class="card-subtitle">Bengaluru, India</p>
                        </div>
                        <div class="card-body">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-info">
                                    <h4>Working Hours</h4>
                                    <p>Monday - Friday, 10AM to 6PM</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-globe-asia"></i>
                                </div>
                                <div class="contact-info">
                                    <h4>Regional Offices</h4>
                                    <p>Philippines, United Kingdom, Germany, Bali, Goa</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h3 class="card-title">For Indian Residents</h3>
                            <p class="card-subtitle">Dedicated Support</p>
                        </div>
                        <div class="card-body">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="contact-info">
                                    <h4>New Bookings</h4>
                                    <p><strong>080 - 69588043</strong></p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div class="contact-info">
                                    <h4>Already Booked?</h4>
                                    <p><strong>080 - 66759603</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="contact-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <h3 class="card-title">For Non-Indian Residents</h3>
                            <p class="card-subtitle">Global Assistance</p>
                        </div>
                        <div class="card-body">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-info">
                                    <h4>New Bookings</h4>
                                    <p><a href="mailto:res@karmaexperience.com">res@karmaexperience.com</a></p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div class="contact-info">
                                    <h4>Already Booked?</h4>
                                    <p><a href="mailto:yourholiday@karmaexperience.com">yourholiday@karmaexperience.com</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
</div>
<!--form section end-->

<!-- Contact Information Section End -->

<!-- Hidden Admin Link (only visible to admins, hidden from regular users) -->
<!-- <div style="text-align:center; margin-top:30px; padding:10px; font-size:11px; opacity:0.6;">
    <a href="login.php" style="color:#999; text-decoration:none;">Administrative Access</a>
</div> -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the international phone input
    const phoneInput = window.intlTelInput(document.querySelector("#phone"), {
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        separateDialCode: true,
        preferredCountries: ["in", "us", "gb", "ca", "au"],
        initialCountry: "in",
        autoPlaceholder: "polite"
    });
    
    // Store the phone input instance in the window for access in validation
    window.phoneInput = phoneInput;
    
    // Update the hidden field with the full number when the phone input changes
    document.querySelector("#phone").addEventListener("keyup", function() {
        if (phoneInput.isValidNumber()) {
            document.querySelector("#full_phone").value = phoneInput.getNumber();
        }
    });

    // Set minimum date for DOB to be 27 years ago
    const today = new Date();
    const minAgeDate = new Date();
    minAgeDate.setFullYear(minAgeDate.getFullYear() - 27);
    const minAgeYear = minAgeDate.getFullYear();
    const minAgeMonth = String(minAgeDate.getMonth() + 1).padStart(2, '0');
    const minAgeDay = String(minAgeDate.getDate()).padStart(2, '0');
    
    document.getElementById('dob').max = `${minAgeYear}-${minAgeMonth}-${minAgeDay}`;
    
    // Date of birth validation
    function validateDateOfBirth(input) {
        const dob = new Date(input.value);
        const today = new Date();
        const minAgeDate = new Date();
        minAgeDate.setFullYear(today.getFullYear() - 27);
        
        const error = document.getElementById('dob-error');
        
        if (dob > minAgeDate) {
            error.style.display = 'block';
            input.setCustomValidity('You must be at least 27 years old');
        } else {
            error.style.display = 'none';
            input.setCustomValidity('');
        }
    }

    // Basic form validation 
    window.validateForm = function() {
        let isValid = true;
        
        // Validate First Name
        const firstName = document.getElementById('first_name').value;
        if (!firstName) {
            document.getElementById('first_name_error').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('first_name_error').style.display = 'none';
        }
        
        // Validate Last Name
        const lastName = document.getElementById('last_name').value;
        if (!lastName) {
            document.getElementById('last_name_error').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('last_name_error').style.display = 'none';
        }
        
        // Validate Email
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email)) {
            document.getElementById('email_error').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('email_error').style.display = 'none';
        }
        
        // Validate Phone - using the intlTelInput validation
        if (!phoneInput.isValidNumber()) {
            document.getElementById('phone_error').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('phone_error').style.display = 'none';
            // Set the full international phone number in a hidden field
            document.getElementById('full_phone').value = phoneInput.getNumber();
        }
        
        // Validate Date of Birth
        const dob = document.getElementById('dob').value;
        if (!dob) {
            document.getElementById('dob-error').style.display = 'block';
            isValid = false;
        } else {
            validateDateOfBirth(document.getElementById('dob'));
            if (document.getElementById('dob-error').style.display === 'block') {
                isValid = false;
            }
        }
        
        // Validate Passport
        const hasPassport = document.getElementById('hasPassport').value;
        if (!hasPassport) {
            document.getElementById('hasPassport-error').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('hasPassport-error').style.display = 'none';
        }
        
        // Validate Communication Agreement
        const communicationAgree = document.getElementById('communicationAgree').checked;
        if (!communicationAgree) {
            document.getElementById('communication-error').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('communication-error').style.display = 'none';
        }
        
        // Validate DND Agreement
        const dndAgree = document.getElementById('dndAgree').checked;
        if (!dndAgree) {
            document.getElementById('dnd-error').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('dnd-error').style.display = 'none';
        }
        
        // Make sure hidden fields are set
        if (!resortIdInput.value) {
            alert('Please select a resort before submitting');
            isValid = false;
        }
        
        if (!destinationIdInput.value) {
            alert('Please select a destination before submitting');
            isValid = false;
        }
        
        // Update progress on valid submission
        if (isValid) {
            updateProgress(100);
        }
        
        return isValid;
    };

    // Store all resorts data in JavaScript
    const resortsData = <?php echo json_encode($resorts); ?>;
    
    // Progress tracking
    const progressBar = document.getElementById('progress-bar');
    const progressPercentage = document.getElementById('progress-percentage');
    
    // UI elements
    const step1Content = document.getElementById('step1-content');
    const step2Content = document.getElementById('step2-content');
    const step3Content = document.getElementById('step3-content');
    
    const step1Sidebar = document.getElementById('step1-sidebar');
    const step2Sidebar = document.getElementById('step2-sidebar');
    const step3Sidebar = document.getElementById('step3-sidebar');
    
    const destinationPreview = document.getElementById('destination-preview');
    const resortPreview = document.getElementById('resort-preview');
    const selectedDestinationName = document.getElementById('selected-destination-name');
    const selectedResortName = document.getElementById('selected-resort-name');
    const selectedDestinationSubtext = document.getElementById('selected-destination-subtext');
    const destinationNameDisplay = document.getElementById('destination-name-display');
    
    // Navigation buttons
    const step1Next = document.getElementById('step1-next');
    const step2Prev = document.getElementById('step2-prev');
    const step2Next = document.getElementById('step2-next');
    const step3Prev = document.getElementById('step3-prev');
    
    // Form hidden inputs
    const destinationIdInput = document.getElementById('destination_id');
    const resortIdInput = document.getElementById('resort_id');
    const resortNameHidden = document.getElementById('resort_name_hidden');
    const destinationNameHidden = document.getElementById('destination_name_hidden');
    const resortCodeHidden = document.getElementById('resort_code_hidden');
    
    // Form validation errors
    const destinationError = document.getElementById('destination-error');
    const resortError = document.getElementById('resort-error');
    
    // Initialize resort grid
    const resortGrid = document.getElementById('resort-grid');
    
    // Initialize destination grid
    const destinationGrid = document.querySelector('.destination-grid');
    
    // Initialize destination card selection
    const destinationCards = document.querySelectorAll('.destination-card');
    let selectedDestinationId = null;
    
    destinationCards.forEach(card => {
        card.addEventListener('click', function() {
            // Reset previous selection
            destinationCards.forEach(c => c.classList.remove('selected'));
            
            // Set new selection
            card.classList.add('selected');
            
            // Store destination details
            selectedDestinationId = card.dataset.id;
            const destinationName = card.dataset.name;
            
            // Update hidden inputs
            destinationIdInput.value = selectedDestinationId;
            destinationNameHidden.value = destinationName;
            
            // Update UI
            selectedDestinationName.textContent = destinationName;
            destinationNameDisplay.textContent = destinationName;
            destinationPreview.classList.add('active');
            destinationError.style.display = 'none';
            
            // Hide all destination cards except selected one
            destinationCards.forEach(c => {
                if (c !== card) {
                    c.style.display = 'none';
                }
            });
            
            // Add a "Change Destination" card if it doesn't exist
            if (!document.getElementById('change-destination')) {
                const changeCard = document.createElement('div');
                changeCard.id = 'change-destination';
                changeCard.className = 'change-destination-card';
                changeCard.innerHTML = `
                    <i class="fas fa-exchange-alt"></i>
                    <h4>Change Destination</h4>
                `;
                
                // Insert the card into the destination grid
                destinationGrid.appendChild(changeCard);
                
                changeCard.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Show all destination cards again
                    destinationCards.forEach(c => {
                        c.style.display = 'block';
                        c.classList.remove('selected');
                    });
                    
                    // Remove this card
                    this.remove();
                    
                    // Reset progress
                    updateProgress(0);
                    
                    // Hide destination and resort previews
                    destinationPreview.classList.remove('active');
                    resortPreview.classList.remove('active');
                    
                    // Clear selections
                    destinationIdInput.value = '';
                    destinationNameHidden.value = '';
                    resortIdInput.value = '';
                    resortNameHidden.value = '';
                    resortCodeHidden.value = '';
                    
                    // Reset selectedDestinationId
                    selectedDestinationId = null;
                });
            }
            
            // Load resorts for this destination
            loadResortsForDestination(selectedDestinationId);
            
            // Update progress
            updateProgress(33);
        });
    });
    
    // Navigation between steps
    step1Next.addEventListener('click', function() {
        if (!selectedDestinationId) {
            destinationError.style.display = 'block';
            return;
        }
        
        // Hide current, show next
        step1Content.classList.remove('active');
        step2Content.classList.add('active');
        
        // Update sidebar active state
        step1Sidebar.classList.remove('active');
        step1Sidebar.classList.add('completed');
        step2Sidebar.classList.add('active');
    });
    
    step2Prev.addEventListener('click', function() {
        // Hide current, show previous
        step2Content.classList.remove('active');
        step1Content.classList.add('active');
        
        // Update sidebar active state
        step2Sidebar.classList.remove('active');
        step1Sidebar.classList.remove('completed');
        step1Sidebar.classList.add('active');
    });
    
    step2Next.addEventListener('click', function() {
        if (!resortIdInput.value) {
            resortError.style.display = 'block';
            return;
        }
        
        // Hide current, show next
        step2Content.classList.remove('active');
        step3Content.classList.add('active');
        
        // Update sidebar active state
        step2Sidebar.classList.remove('active');
        step2Sidebar.classList.add('completed');
        step3Sidebar.classList.add('active');
        
        // Update progress
        updateProgress(66);
    });
    
    step3Prev.addEventListener('click', function() {
        // Hide current, show previous
        step3Content.classList.remove('active');
        step2Content.classList.add('active');
        
        // Update sidebar active state
        step3Sidebar.classList.remove('active');
        step2Sidebar.classList.remove('completed');
        step2Sidebar.classList.add('active');
    });
    
    // Load resorts for selected destination
    function loadResortsForDestination(destinationId) {
        // Clear existing resorts
        resortGrid.innerHTML = '';
        
        // Filter resorts for this destination
        const filteredResorts = resortsData.filter(resort => 
            resort.destination_id == destinationId
        );
        
        if (filteredResorts.length > 0) {
            // Add resort cards
            filteredResorts.forEach(resort => {
                const resortCard = document.createElement('div');
                resortCard.className = 'resort-card';
                resortCard.dataset.id = resort.id;
                resortCard.dataset.name = resort.resort_name;
                resortCard.dataset.code = resort.resort_code || generateResortCode(resort.resort_name);
                
                // Get the proper resort image path from the database
                const resortImage = resort.resort_image || 'assets/img/normal/cta-img-6.jpg';
                
                // Use a default description since we don't have one from the database
                const resortDescription = "Experience luxury and comfort in this stunning resort";
                
                resortCard.innerHTML = `
                    <img src="${resortImage}" class="resort-image" alt="${resort.resort_name} - Luxury Resort" onerror="this.src='assets/img/normal/cta-img-6.jpg'">
                    <div class="resort-overlay"></div>
                    <div class="resort-content">
                        <h4>${resort.resort_name}</h4>
                        <p>${resortDescription}</p>
                    </div>
                    <span class="resort-badge">${selectedDestinationName.textContent}</span>
                `;
                
                // Handle resort selection
                resortCard.addEventListener('click', function() {
                    // Reset previous selection
                    const allResortCards = document.querySelectorAll('.resort-card');
                    allResortCards.forEach(c => c.classList.remove('selected'));
                    
                    // Set new selection
                    resortCard.classList.add('selected');
                    
                    // Store resort details
                    const resortId = resortCard.dataset.id;
                    const resortName = resortCard.dataset.name;
                    const resortCode = resortCard.dataset.code;
                    
                    // Update hidden inputs
                    resortIdInput.value = resortId;
                    resortNameHidden.value = resortName;
                    resortCodeHidden.value = resortCode;
                    
                    // Update UI
                    selectedResortName.textContent = resortName;
                    selectedDestinationSubtext.textContent = selectedDestinationName.textContent;
                    resortPreview.classList.add('active');
                    resortError.style.display = 'none';

                    // Hide all resort cards except selected one
                    allResortCards.forEach(c => {
                        if (c !== resortCard) {
                            c.style.display = 'none';
                        }
                    });

                    // Add "Change Resort" card if it doesn't exist
                    if (!document.getElementById('change-resort')) {
                        const changeCard = document.createElement('div');
                        changeCard.id = 'change-resort';
                        changeCard.className = 'change-destination-card';
                        changeCard.innerHTML = `
                            <i class="fas fa-exchange-alt"></i>
                            <h4>Change Resort</h4>
                        `;
                        
                        // Insert the card into the resort grid
                        resortGrid.appendChild(changeCard);
                        
                        // Add click handler for the change card
                        changeCard.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            // Show all resort cards again
                            allResortCards.forEach(c => {
                                c.style.display = 'block';
                                c.classList.remove('selected');
                            });
                            
                            // Reset resort selection
                            resortIdInput.value = '';
                            resortNameHidden.value = '';
                            resortCodeHidden.value = '';
                            resortPreview.classList.remove('active');
                            
                            // Remove the change card
                            this.remove();
                            
                            // Update progress
                            updateProgress(33);
                        });
                    }
                    
                    // Update progress
                    updateProgress(45);
                });
                
                resortGrid.appendChild(resortCard);
            });
        } else {
            // No resorts available
            resortGrid.innerHTML = `
                <div class="no-results">
                    <p>No resorts available for this destination yet. Please select another destination or contact us directly.</p>
                </div>
            `;
        }
    }
    
    // Helper function to generate a resort code if one doesn't exist
    function generateResortCode(resortName) {
        // Simple method - take first letters of each word
        return resortName
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase();
    }
    
    // Update progress bar
    function updateProgress(percentage) {
        progressBar.style.width = `${percentage}%`;
        progressPercentage.textContent = `${percentage}%`;
    }
    
    // Submit form handling
    document.getElementById('enquiryForm').addEventListener('submit', function(e) {
        e.preventDefault();

        if (validateForm()) {
            // Set the full international phone number before submission
            if (phoneInput && phoneInput.isValidNumber()) {
                document.getElementById('full_phone').value = phoneInput.getNumber();
            }

            // Create a loading overlay
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = `
                <div class="spinner-container">
                    <div class="spinner"></div>
                    <p>Submitting your enquiry...</p>
                </div>
            `;
            document.body.appendChild(loadingOverlay);

            // Submit the form programmatically (not using this.submit() to allow proper data validation)
            const formData = new FormData(this);
            
            fetch('process_resort_enquiry.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                // Remove loading overlay
                document.body.removeChild(loadingOverlay);
                
                if (response.redirected) {
                    // If the server redirected us, follow that redirect
                    window.location.href = response.url;
                } else {
                    // If no redirect, parse the response to check for errors
                    return response.text().then(text => {
                        try {
                            // Try to parse as JSON first
                            const data = JSON.parse(text);
                            if (data.status === 'error') {
                                // Display structured error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'alert alert-danger';
                                errorDiv.textContent = data.message || 'An error occurred while processing your request.';
                                document.querySelector('.enquiry-header').after(errorDiv);
                                
                                // Log details for debugging
                                console.error('Form submission error:', data);
                                return;
                            } else if (data.status === 'success') {
                                // Handle success response (non-redirect)
                                window.location.href = data.redirect || 'thank-you.php';
                                return;
                            }
                        } catch (e) {
                            // Not JSON, continue with text processing
                            console.log('Response is not JSON, processing as text');
                        }
                        
                        if (text.includes('error_message')) {
                            // Display error message on the page
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'alert alert-danger';
                            errorDiv.textContent = text;
                            document.querySelector('.enquiry-header').after(errorDiv);
                        } else if (text.includes('success_message')) {
                            // If success message found but no redirect happened
                            window.location.href = 'thank-you.php';
                        } else {
                            // Submit the form traditionally as fallback
                            this.submit();
                        }
                    });
                }
            })
            .catch(error => {
                // Remove loading overlay
                if (document.querySelector('.loading-overlay')) {
                    document.body.removeChild(loadingOverlay);
                }
                
                // Create an error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                errorDiv.textContent = 'An error occurred while submitting your enquiry. Please try again.';
                document.querySelector('.enquiry-header').after(errorDiv);
                
                console.error('Error submitting form:', error);
            });
        }
    });
    
    // Additional validation for the form
    document.getElementById('dob').addEventListener('change', function() {
        validateDateOfBirth(this);
    });
    
    document.getElementById('hasPassport').addEventListener('change', function() {
        const error = document.getElementById('hasPassport-error');
        if (this.value) {
            error.style.display = 'none';
        }
    });

    document.getElementById('communicationAgree').addEventListener('change', function() {
        const error = document.getElementById('communication-error');
        if (this.checked) {
            error.style.display = 'none';
        }
    });
    
    document.getElementById('dndAgree').addEventListener('change', function() {
        const error = document.getElementById('dnd-error');
        if (this.checked) {
            error.style.display = 'none';
        }
    });
});
</script>

<?php
include_once('kfooter.php');
?>
</body>
</html>