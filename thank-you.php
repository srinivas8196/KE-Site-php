<?php require 'kheader.php'; ?>
<div class="thank-you-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="thank-you-content">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1>Thank You!</h1>
                    <p>Your enquiry has been submitted successfully. Our team will contact you shortly.</p>
                    <a href="/" class="btn btn-primary">Back to Home</a>
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
}

.thank-you-content h1 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.thank-you-content p {
    color: #666;
    margin-bottom: 30px;
    font-size: 1.1rem;
}
</style>
<?php require 'kfooter.php'; ?>