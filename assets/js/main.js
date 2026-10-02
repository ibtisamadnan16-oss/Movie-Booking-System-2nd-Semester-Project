
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const forms = document.querySelectorAll('.needs-validation, form[novalidate]');

    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            let isValid = form.checkValidity();

            const passwordInput = form.querySelector('input[name="password"], input[name="new_password"]');
            const confirmInput = form.querySelector('input[name="confirm_password"]');

            if (passwordInput && confirmInput) {
                if (passwordInput.value !== confirmInput.value) {
                    confirmInput.setCustomValidity('Passwords do not match.');
                    confirmInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    confirmInput.setCustomValidity('');
                    confirmInput.classList.remove('is-invalid');
                }
            }

            const emailInputs = form.querySelectorAll('input[type="email"]');
            emailInputs.forEach(function (emailInput) {
                if (emailInput.value && !validateEmail(emailInput.value)) {
                    emailInput.setCustomValidity('Please enter a valid email address.');
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                } else if (emailInput.value) {
                    emailInput.setCustomValidity('');
                    emailInput.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });

function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        return re.test(String(email).toLowerCase());
    }

    const allEmailInputs = document.querySelectorAll('input[type="email"]');
    allEmailInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            if (this.value.length > 0 && !validateEmail(this.value)) {
                this.classList.add('is-invalid');
                let feedback = this.parentElement.querySelector('.invalid-feedback') || this.parentElement.parentElement.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = 'Please enter a valid email address (e.g. user@example.com).';
            } else {
                this.classList.remove('is-invalid');
                if (this.value.length > 0) this.classList.add('is-valid');
            }
        });
    });

    const passwordInputs = document.querySelectorAll('input[name="password"], input[name="new_password"]');
    passwordInputs.forEach(function (passInput) {
        const form = passInput.closest('form');
        if (!form) return;
        const confirmInput = form.querySelector('input[name="confirm_password"]');

        if (confirmInput) {
            const checkMatch = function () {
                if (confirmInput.value.length > 0) {
                    if (passInput.value !== confirmInput.value) {
                        confirmInput.classList.add('is-invalid');
                        confirmInput.classList.remove('is-valid');
                        let fb = confirmInput.parentElement.querySelector('.invalid-feedback') || confirmInput.parentElement.parentElement.querySelector('.invalid-feedback');
                        if (fb) fb.textContent = 'Passwords do not match.';
                    } else {
                        confirmInput.classList.remove('is-invalid');
                        confirmInput.classList.add('is-valid');
                    }
                }
            };

            confirmInput.addEventListener('input', checkMatch);
            passInput.addEventListener('input', checkMatch);
        }

        passInput.addEventListener('input', function () {
            if (this.value.length > 0 && this.value.length < 6) {
                this.classList.add('is-invalid');
                let fb = this.parentElement.querySelector('.invalid-feedback') || this.parentElement.parentElement.querySelector('.invalid-feedback');
                if (fb) fb.textContent = 'Password must be at least 6 characters long.';
            } else if (this.value.length >= 6) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    requiredInputs.forEach(function (input) {
        input.addEventListener('blur', function () {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });

    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            try {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } catch (e) {
            }
        }, 6000);
    });

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    console.log('CinePass Movie Booking System: Phase 18 Validation & UI Handlers Ready.');
});
