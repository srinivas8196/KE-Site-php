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
<div class="thank-you-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="thank-you-content">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1>Thank You, <?php echo htmlspecialchars($name); ?>!</h1>
                    <p>Your enquiry about <strong><?php echo htmlspecialchars($resortName); ?></strong> has been submitted successfully.</p>
                    
                    <div class="confirmation-details">
                        <p>We've sent a confirmation email to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
                        <p>Our team will contact you shortly to discuss your enquiry.</p>
                    </div>
                    
                    <div class="action-buttons mt-4">
                        <a href="/" class="btn btn-primary mr-3">Back to Home</a>
                        <a href="/our-destinations.php" class="btn btn-outline-primary">Explore More Destinations</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.thank-you-container {
    padding: 80px 0;
    min-height: 60vh;
    display: flex;
    align-items: center;
}

.thank-you-content {
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.success-icon {
    color: #28a745;
    font-size: 4rem;
    margin-bottom: 20px;
    animation: scale-in 0.5s ease-out;
}

@keyframes scale-in {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.thank-you-content h1 {
    color: #2c3e50;
    margin-bottom: 20px;
    animation: fade-in 0.8s ease-out;
}

.thank-you-content p {
    color: #666;
    margin-bottom: 20px;
    font-size: 1.1rem;
    animation: fade-in 1s ease-out;
}

.confirmation-details {
    background-color: rgba(40, 167, 69, 0.1);
    border-left: 4px solid #28a745;
    padding: 15px;
    margin: 20px 0;
    text-align: left;
    animation: slide-in 1.2s ease-out;
}

@keyframes fade-in {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

@keyframes slide-in {
    0% { transform: translateX(-20px); opacity: 0; }
    100% { transform: translateX(0); opacity: 1; }
}

.action-buttons {
    animation: fade-in 1.5s ease-out;
}

.btn-outline-primary {
    border: 1px solid #007bff;
    color: #007bff;
    background: transparent;
    transition: all 0.3s;
}

.btn-outline-primary:hover {
    background: #007bff;
    color: white;
}

.mr-3 {
    margin-right: 1rem;
}

.mt-4 {
    margin-top: 1.5rem;
}
</style>
<?php require 'kfooter.php'; ?>