document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    menuToggle.addEventListener('click', function () {
        mobileMenu.classList.toggle('active');
        menuToggle.classList.toggle('active');
    });

    const menuItems = document.querySelectorAll('.menu-item-has-children > a');
    menuItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('active');
        });
    });
});
