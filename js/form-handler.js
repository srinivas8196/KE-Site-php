document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('enquiryForm');
    const submitButton = form.querySelector('button[type="submit"]');
    let phoneInput;

    // Initialize international phone input
    if (document.querySelector("#phone")) {
        phoneInput = window.intlTelInput(document.querySelector("#phone"), {
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
            separateDialCode: true,
            preferredCountries: ["in", "ae", "gb", "us"]
        });
    }

    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate phone number if it exists
        if (phoneInput && !phoneInput.isValidNumber()) {
            alert('Please enter a valid phone number');
            return false;
        }

        // Set loading state
        submitButton.classList.add('loading');
        submitButton.disabled = true;

        try {
            // Set the full phone number with country code
            if (phoneInput) {
                document.querySelector("#phone").value = phoneInput.getNumber();
            }

            // Submit form data
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Redirect to thank you page
                window.location.href = result.redirect;
            } else {
                throw new Error(result.message || 'Form submission failed');
            }
        } catch (error) {
            alert(error.message || 'An error occurred. Please try again.');
            // Reset button state
            submitButton.classList.remove('loading');
            submitButton.disabled = false;
        }
    });

    // Real-time validation
    const inputs = form.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {
        input.addEventListener('invalid', (e) => {
            e.preventDefault();
            input.classList.add('invalid');
        });

        input.addEventListener('input', () => {
            if (input.validity.valid) {
                input.classList.remove('invalid');
            }
        });
    });
});