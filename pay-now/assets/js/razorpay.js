
    // Add a Razorpay event handler on form submit
    $("form#formmain").submit(function(e) {
        e.preventDefault();

        // Check if Razorpay is selected
        if ($("select[name='payment_method']").val() === "razorpay") {
            var options = {
                key: 'rzp_live_01QXUGxWaNQGSK', // Replace with your actual Razorpay key
                amount: $("input[name='price']").val() * 100, // Convert amount to paise
                currency: $("select[name='selectcurrency']").val().toUpperCase(),
                name: 'Karma Group',
                description: 'Karma Rewards',
                image: 'https://your-logo-url.png', // Replace with your logo URL
                handler: function(response) {
                    // Handle Razorpay success response here
                    alert('Payment successful!');
                document.getElementById('formmain').submit()
                },
                prefill: {
                    name: $("input[name='name_first']").val(),
                    email: $("input[name='email']").val(),
                    contact: $("input[name='phonefront']").val()
                },
                theme: {
                    color: '#528FF0'
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        } else {
            // If another payment method is selected, submit the form normally
            $("form#formmain").unbind('submit').submit();
        }
    });

    // Add logic to hide/show Razorpay section based on currency selection
    $("select[name='selectcurrency']").on('change', function () {
        var selectedCurrency = $(this).val();
        if (selectedCurrency === 'inr') {
            $("#razorpaySection").removeClass('d-hide');
        } else {
            $("#razorpaySection").addClass('d-hide');
        }
    });

    // Initial check on page load
    $(document).ready(function () {
        var selectedCurrency = $("select[name='selectcurrency']").val();
        if (selectedCurrency === 'inr') {
            $("#razorpaySection").removeClass('d-hide');
        } else {
            $("#razorpaySection").addClass('d-hide');
        }
    });
