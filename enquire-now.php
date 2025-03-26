<?php
include_once('kheader.php');
?>
<!--banner section start-->
<div class="breadcumb-wrapper" data-bg-src="assets/img/bg/about-bg.webp">
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
    <div class="row"> 
                <div class="en-form">
                    <div class="row">
                        
                        <div class="col-lg-6">
                            <div class="en-bg">
                                <img src="assets/img/bg/contact.svg" alt="booking">
                            </div>
                        </div>
                        <div class="col-lg-6">
                        <?php
                            include_once('destination-form.php');
                            ?>
                        </div>
                    </div>
                </div>

                <!-- <div class="contact details text-center">
                    <h3>Marketing Head Office - Bengaluru</h3>
                    <p>Working Hours: <strong>Monday-Friday 10AM to 6PM </strong></p>
                    <p><strong>Regional Offices:</strong> Philippines, United Kingdom, Germany, Bali, Goa</p>
                </div> -->
                <!-- <div class="row">
                    <div class="col-lg-6">
                        <div class="ofc-details">

                            <div class="align-items-center card card-body h-100 text-center">
                                <div class="icon-lg bg-opacity-10 text-info rounded-circle mb-2"><i class="bi bi-geo-alt"></i></div>
                                <h5>For Indian Residents</h5>
                                <div class="d-grid gap-3 d-sm-block">
                                    <p>New Bookings: <strong>080 - 69588043</strong></p>
                                    <p>Already booked: <strong>080 - 66759603</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="ofc-details">

                            <div class="align-items-center card card-body h-100 text-center">
                                <div class="icon-lg bg-opacity-10 text-info rounded-circle mb-2"><i class="bi bi-geo-alt"></i></div>
                                <h5>For Non-Indian Residents</h5>
                                <div class="d-grid gap-3 d-sm-block">
                                <p>Already booked: <strong>yourholiday@karmaexperience.com</strong></p>
                                <p>New Bookings: <strong>res@karmaexperience.com</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
    </div>


    <div class="position-relative overflow-hidden space">
        <div class="cta-sec6 bg-title position-relative overflow-hidden shape-mockup-wrap">
            <div class="container"><div class="row">
                <div class="col-lg-6">
                    <div class="cta-area6 space en-space">
                        <div class="d-flex">
                    <i class="align-items-center d-flex fa-location-dot fa-solid justify-content-center"></i>
                    <div>
                        <h3>Marketing Head Office - Bengaluru</h3>
                        <span>Working Hours: Monday-Friday 10AM to 6PM</span><br/>
                        <span>Regional Offices: Philippines, United Kingdom, Germany, Bali, Goa</span>
                        </div>
                    </div>
                        <hr>
                        <div class="title-area mb-30 en-cta">
                        
                            <h5>For Indian Residents</h5>
                            <p>New Bookings: <strong>080 - 69588043</strong></p>
                            <p>Already booked: <strong>080 - 66759603</strong></p>
                            <h5>For Non-Indian Residents</h5>
                            <p>Already booked: <strong>yourholiday@karmaexperience.com</strong></p>
                            <p>New Bookings: <strong>res@karmaexperience.com</strong></p>
                        </div>
                        <div class="cta-shape">    
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="shape-mockup" style="right: -2%; bottom: 0%;">
            <img src="assets/img/normal/cta-img-6.jpg" alt="">
        </div>
    </div>
</div>


</div>
</div>
<!--form section end-->

<script src="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.min.js"></script>
<?php
include_once('kfooter.php');
?>