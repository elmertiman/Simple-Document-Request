(function($) {
    'use strict';
    
    let currentEmail = '';
    let currentProductId = 0;
    
    $(document).ready(function() {
        // Open modal when button is clicked
        $('.sdr-request-btn').on('click', function() {
            currentProductId = $(this).data('product-id');
            $('#sdr-modal').fadeIn();
            resetModal();
        });
        
        // Close modal
        $('.sdr-modal-close, .sdr-modal-overlay').on('click', function() {
            $('#sdr-modal').fadeOut();
            resetModal();
        });
        
        // Email form submission
        $('#sdr-email-form').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('#sdr-email-input').val().trim();
            const messageDiv = $('#sdr-email-message');
            
            if (!email || !isValidEmail(email)) {
                showMessage(messageDiv, 'Please enter a valid email address.', 'error');
                return;
            }
            
            currentEmail = email;
            
            // Disable submit button
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Sending...');
            
            // Send AJAX request
            $.ajax({
                url: sdrData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sdr_request_otp',
                    nonce: sdrData.nonce,
                    email: email,
                    product_id: currentProductId
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(messageDiv, response.data.message, 'success');
                        // Show OTP step after a short delay
                        setTimeout(function() {
                            $('#sdr-step-email').hide();
                            $('#sdr-step-otp').show();
                            $('#sdr-otp-input').focus();
                        }, 1500);
                    } else {
                        showMessage(messageDiv, response.data.message, 'error');
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // OTP form submission
        $('#sdr-otp-form').on('submit', function(e) {
            e.preventDefault();
            
            const otpCode = $('#sdr-otp-input').val().trim();
            const messageDiv = $('#sdr-otp-message');
            
            if (!otpCode || otpCode.length !== 6) {
                showMessage(messageDiv, 'Please enter a valid 6-digit OTP code.', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Verifying...');
            
            // Send AJAX request
            $.ajax({
                url: sdrData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sdr_verify_otp',
                    nonce: sdrData.nonce,
                    email: currentEmail,
                    product_id: currentProductId,
                    otp_code: otpCode
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(messageDiv, response.data.message, 'success');
                        
                        // Show PDF after a short delay
                        setTimeout(function() {
                            if (response.data.pdf_url) {
                                $('#sdr-pdf-viewer').attr('src', response.data.pdf_url);
                                $('#sdr-step-otp').hide();
                                $('#sdr-step-pdf').show();
                            } else {
                                showMessage(messageDiv, 'PDF document not found for this product.', 'error');
                                submitBtn.prop('disabled', false).text(originalText);
                            }
                        }, 1000);
                    } else {
                        showMessage(messageDiv, response.data.message, 'error');
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Resend OTP
        $('#sdr-resend-otp').on('click', function(e) {
            e.preventDefault();
            
            // Trigger email form submission again
            $('#sdr-email-form').trigger('submit');
        });
        
        // Close PDF step
        $('#sdr-close-pdf').on('click', function() {
            $('#sdr-modal').fadeOut();
            resetModal();
        });
        
        // Allow only numbers in OTP input
        $('#sdr-otp-input').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
    
    function resetModal() {
        $('#sdr-step-email').show();
        $('#sdr-step-otp').hide();
        $('#sdr-step-pdf').hide();
        $('#sdr-email-form')[0].reset();
        $('#sdr-otp-form')[0].reset();
        $('#sdr-email-message').empty().removeClass('success error');
        $('#sdr-otp-message').empty().removeClass('success error');
        currentEmail = '';
        currentProductId = 0;
    }
    
    function showMessage(element, message, type) {
        element.text(message)
            .removeClass('success error')
            .addClass(type)
            .show();
        
        // Auto-hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                element.fadeOut();
            }, 5000);
        }
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
})(jQuery);
