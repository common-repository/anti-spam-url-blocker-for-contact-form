/**
 * 47B CF URL Validator Scripts
 * Version: 1.0.1
 */

(function($) {
    'use strict';

    const B47CFURLValidator = {
        /**
         * Initialize the validator
         */
        init: function() {
            this.initializeFormValidation();
        },

        /**
         * Check if a string contains a URL
         */
        containsURL: function(text) {
            const urlRegex = /(https?:\/\/[^\s]+)|(www\.[^\s]+)/gi;
            return urlRegex.test(text);
        },

        /**
         * Create or get error message div
         */
        getErrorDiv: function($form) {
            let $errorDiv = $form.find('.b47-cf-url-validator-error');
            if ($errorDiv.length === 0) {
                $errorDiv = $('<div>', {
                    class: 'b47-cf-url-validator-error',
                    text: b47CFURLValidator.errorMessage
                });
                $form.find('.wpcf7-response-output').before($errorDiv);
            }
            return $errorDiv;
        },

        /**
         * Validate form fields
         */
        validateFields: function($form) {
            const self = this;
            let hasURL = false;
            const $textInputs = $form.find('input[type="text"], input[type="email"], textarea');
            const $submitButton = $form.find('input[type="submit"]');
            const $errorDiv = this.getErrorDiv($form);

            // Reset previous errors
            $textInputs.removeClass('b47-cf-url-error');
            
            $textInputs.each(function() {
                const $input = $(this);
                const inputValue = $input.val();

                if (inputValue && self.containsURL(inputValue)) {
                    hasURL = true;
                    $input.addClass('b47-cf-url-error');
                }
            });

            if (hasURL) {
                $submitButton.prop('disabled', true);
                $errorDiv.slideDown();
            } else {
                $submitButton.prop('disabled', false);
                $errorDiv.slideUp();
            }

            return !hasURL;
        },

        /**
         * Initialize form validation
         */
        initializeFormValidation: function() {
            const self = this;
            
            $(document).on('wpcf7submit', function(event) {
                const $form = $(event.target);
                if (!self.validateFields($form)) {
                    event.preventDefault();
                }
            });

            $('.wpcf7-form').each(function() {
                const $form = $(this);
                
                // Validate on input change
                $form.on('input.b47cfurlvalidator', 'input[type="text"], input[type="email"], textarea', function() {
                    self.validateFields($form);
                });

                // Validate before form submit
                $form.on('submit.b47cfurlvalidator', function(e) {
                    if (!self.validateFields($form)) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        try {
            B47CFURLValidator.init();
        } catch (error) {
            console.error('47B CF URL Validator Error:', error);
        }
    });

})(jQuery);