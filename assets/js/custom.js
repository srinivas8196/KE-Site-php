// document.addEventListener('DOMContentLoaded', function () {
//     const menuToggle = document.getElementById('mobileMenuToggle');
//     const mobileMenu = document.getElementById('mobileMenu');

//     menuToggle.addEventListener('click', function () {
//         mobileMenu.classList.toggle('active');
//         menuToggle.classList.toggle('active');
//     });

//     const menuItems = document.querySelectorAll('.menu-item-has-children > a');
//     menuItems.forEach(item => {
//         item.addEventListener('click', function (e) {
//             e.preventDefault();
//             const parent = this.parentElement;
//             parent.classList.toggle('active');
//         });
//     });
// });


document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("mobileMenuToggle");
    const mobileMenu = document.getElementById("mobileMenu");

    menuToggle.addEventListener("click", function () {
        if (mobileMenu.classList.contains("opacity-0")) {
            mobileMenu.classList.remove("opacity-0", "invisible", "scale-95");
            mobileMenu.classList.add("opacity-100", "visible", "scale-100");
        } else {
            mobileMenu.classList.remove("opacity-100", "visible", "scale-100");
            mobileMenu.classList.add("opacity-0", "invisible", "scale-95");
        }
    });
});






