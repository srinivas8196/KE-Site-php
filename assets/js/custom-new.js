document.addEventListener('DOMContentLoaded', function () {
    // Single Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenuIcon = document.getElementById('mobileMenuIcon');
    const mobileMenu = document.getElementById('mobileMenu');

    if (mobileMenuToggle && mobileMenu && mobileMenuIcon) {
        mobileMenuToggle.addEventListener('click', function () {
            mobileMenu.classList.toggle('active');
            // Switch icon from bars to times
            if (mobileMenu.classList.contains('active')) {
                mobileMenuIcon.className = "fas fa-times";
            } else {
                mobileMenuIcon.className = "fas fa-bars";
            }
        });
    }

    // Destinations submenu in mobile
    const mobileDestinations = document.getElementById('mobileDestinations');
    if (mobileDestinations) {
        const destinationsLink = mobileDestinations.querySelector('a');
        destinationsLink.addEventListener('click', function (e) {
            e.preventDefault();
            mobileDestinations.classList.toggle('active');
        });
    }
});
