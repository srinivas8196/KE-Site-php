<?php
// Include header
include 'kheader.php';
?>

<section class="error-area" style="padding: 150px 0; background-color: #f8f9fa;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-8">
                <div class="error-wrapper text-center">
                    <div class="error-icon">
                        <img src="assets/images/logo/K-logo.png" alt="Karma Experience Logo" style="max-width: 120px; margin-bottom: 30px;">
                    </div>
                    <div class="error-number" style="font-size: 120px; font-weight: 700; line-height: 1; color: #B4975A; margin-bottom: 20px;">404</div>
                    <h2 class="error-title" style="font-size: 36px; margin-bottom: 20px; color: #222;">Resort Not Available</h2>
                    <p class="error-text" style="font-size: 18px; color: #555; margin-bottom: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">
                        The resort you are looking for is currently inactive or does not exist. Please browse our other available resorts.
                    </p>
                    <div class="error-btn" style="display: flex; justify-content: center; gap: 15px;">
                        <a href="index.php" class="th-btn" style="background-color: #B4975A; color: white; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                            Back to Home <i class="fas fa-home" style="margin-left: 8px;"></i>
                        </a>
                        <a href="javascript:history.back()" class="th-btn style2" style="background-color: #f0f0f0; color: #333; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                            Go Back <i class="fas fa-arrow-left" style="margin-left: 8px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'kfooter.php';
?>
