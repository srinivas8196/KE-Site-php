<?php
include_once('kheader.php');
?>
<style>
.thank-you-container {
    text-align: center;
    padding: 80px 20px;
    max-width: 800px;
    margin: 0 auto;
}

.thank-you-icon {
    font-size: 80px;
    color: #28a745;
    margin-bottom: 30px;
}

.thank-you-title {
    font-size: 36px;
    margin-bottom: 20px;
    color: #333;
}

.thank-you-message {
    font-size: 18px;
    margin-bottom: 40px;
    color: #666;
    line-height: 1.6;
}

.btn-home {
    display: inline-block;
    padding: 12px 30px;
    background-color: #3498db;
    color: white;
    border-radius: 30px;
    text-decoration: none;
    transition: background-color 0.3s;
    font-weight: 500;
    margin-top: 20px;
}

.btn-home:hover {
    background-color: #2980b9;
    color: white;
    text-decoration: none;
}
</style>

<!--banner section start-->
<div class="breadcumb-wrapper" data-bg-src="assets/img/bg/about-bg.webp">
    <div class="container">
        <div class="breadcumb-content">
            <h1 class="breadcumb-title">Thank You</h1>
            <ul class="breadcumb-menu">
                <li><a href="index.php">Home</a></li>
                <li>Thank You</li>
            </ul>
        </div>
    </div>
</div>
<!--banner section end-->

<div class="thank-you-container">
    <div class="thank-you-icon">
        <i class="fa fa-check-circle"></i>
    </div>
    <h1 class="thank-you-title">Thank You for Your Submission!</h1>
    <p class="thank-you-message">
        We have received your inquiry and our team will get back to you shortly. 
        Your interest in Karma Experience is greatly appreciated.
    </p>
    <p class="thank-you-message">
        If you have any urgent questions, please feel free to contact us directly.
    </p>
    <a href="index.php" class="btn-home">Return to Home</a>
</div>

<?php
include_once('kfooter.php');
?>