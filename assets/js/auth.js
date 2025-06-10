document.addEventListener('DOMContentLoaded', function() {
    // Form validation for register and login
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate required fields
            const inputs = form.querySelectorAll('input[required]');
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = 'red';
                } else {
                    input.style.borderColor = '#ddd';
                }
            });
            
            // Validate password on register
            if (form.id === 'register-form') {
                const password = form.querySelector('#password');
                const confirmPassword = form.querySelector('#confirm_password');
                
                if (password.value !== confirmPassword.value) {
                    isValid = false;
                    alert('Passwords do not match');
                    password.style.borderColor = 'red';
                    confirmPassword.style.borderColor = 'red';
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
});