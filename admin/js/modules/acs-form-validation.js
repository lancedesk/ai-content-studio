/**
 * ACS Form Validation Module
 *
 * Real-time form validation with user-friendly feedback.
 *
 * @package AI_Content_Studio
 * @subpackage Admin/JavaScript
 * @since 2.0.0
 */

(function($, window, document) {
    'use strict';

    // Ensure namespace exists
    window.ACSAdmin = window.ACSAdmin || {};

    /**
     * Form Validation Module
     */
    ACSAdmin.FormValidation = {

        /**
         * Validation rules
         */
        rules: {
            required: {
                test: function(value) {
                    return value.trim().length > 0;
                },
                message: 'This field is required.'
            },
            email: {
                test: function(value) {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                },
                message: 'Please enter a valid email address.'
            },
            url: {
                test: function(value) {
                    try {
                        new URL(value);
                        return true;
                    } catch (e) {
                        return false;
                    }
                },
                message: 'Please enter a valid URL.'
            },
            apiKey: {
                test: function(value) {
                    // API keys are typically alphanumeric with dashes/underscores
                    return /^[a-zA-Z0-9_-]{20,}$/.test(value.trim());
                },
                message: 'Please enter a valid API key (at least 20 characters).'
            },
            minLength: {
                test: function(value, min) {
                    return value.trim().length >= min;
                },
                message: function(min) {
                    return 'Please enter at least ' + min + ' characters.';
                }
            },
            maxLength: {
                test: function(value, max) {
                    return value.trim().length <= max;
                },
                message: function(max) {
                    return 'Please enter no more than ' + max + ' characters.';
                }
            },
            numeric: {
                test: function(value) {
                    return /^\d+$/.test(value);
                },
                message: 'Please enter a valid number.'
            },
            range: {
                test: function(value, min, max) {
                    var num = parseInt(value, 10);
                    return !isNaN(num) && num >= min && num <= max;
                },
                message: function(min, max) {
                    return 'Please enter a value between ' + min + ' and ' + max + '.';
                }
            }
        },

        /**
         * Initialize the module
         */
        init: function() {
            this.initValidationHandlers();
            this.initAPIKeyValidation();
            this.initCharacterCounters();
            
            console.log('[ACS] Form Validation initialized');
        },

        /**
         * Initialize validation handlers
         */
        initValidationHandlers: function() {
            var self = this;

            // Real-time validation on blur
            $(document).on('blur', '[data-validate]', function() {
                self.validateField($(this));
            });

            // Clear error on input
            $(document).on('input', '.acs-field--error [data-validate]', function() {
                var $field = $(this);
                self.clearFieldError($field);
                
                // Re-validate after delay
                clearTimeout($field.data('validate-timeout'));
                $field.data('validate-timeout', setTimeout(function() {
                    self.validateField($field);
                }, 500));
            });

            // Form submission validation
            $(document).on('submit', 'form[data-validate-form]', function(e) {
                var $form = $(this);
                var isValid = self.validateForm($form);
                
                if (!isValid) {
                    e.preventDefault();
                    self.focusFirstError($form);
                }
            });
        },

        /**
         * Initialize API key validation
         */
        initAPIKeyValidation: function() {
            var self = this;

            // Test API key button
            $(document).on('click', '.acs-test-api-key', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $input = $btn.closest('.acs-form-field').find('input[type="text"], input[type="password"]');
                var provider = $btn.data('provider');
                
                if (!$input.val().trim()) {
                    self.showFieldError($input, 'Please enter an API key to test.');
                    return;
                }
                
                self.testAPIKey($input, provider, $btn);
            });

            // Auto-validate API key on paste
            $(document).on('paste', '.acs-api-key-input', function() {
                var $input = $(this);
                setTimeout(function() {
                    if ($input.val().trim().length >= 20) {
                        $input.closest('.acs-form-field').find('.acs-test-api-key').trigger('click');
                    }
                }, 100);
            });
        },

        /**
         * Initialize character counters
         */
        initCharacterCounters: function() {
            var self = this;

            $('[data-max-length]').each(function() {
                var $field = $(this);
                self.addCharacterCounter($field);
            });

            // Update counter on input
            $(document).on('input', '[data-max-length]', function() {
                self.updateCharacterCounter($(this));
            });
        },

        /**
         * Validate a single field
         */
        validateField: function($field) {
            var validations = $field.data('validate');
            if (!validations) return true;

            var value = $field.val();
            var rules = validations.split(' ');
            var isValid = true;
            var errorMessage = '';

            for (var i = 0; i < rules.length; i++) {
                var rule = rules[i];
                var result = this.checkRule(rule, value);
                
                if (!result.valid) {
                    isValid = false;
                    errorMessage = result.message;
                    break;
                }
            }

            if (!isValid) {
                this.showFieldError($field, errorMessage);
            } else {
                this.showFieldSuccess($field);
            }

            return isValid;
        },

        /**
         * Check a single validation rule
         */
        checkRule: function(rule, value) {
            // Parse rule with parameters (e.g., "minLength:10")
            var parts = rule.split(':');
            var ruleName = parts[0];
            var params = parts.slice(1);

            // Special case: skip required check if field is empty and not required
            if (ruleName !== 'required' && value.trim() === '') {
                return { valid: true };
            }

            var ruleConfig = this.rules[ruleName];
            if (!ruleConfig) {
                return { valid: true };
            }

            var testArgs = [value].concat(params.map(function(p) {
                return parseInt(p, 10) || p;
            }));
            
            var valid = ruleConfig.test.apply(null, testArgs);
            var message = typeof ruleConfig.message === 'function' 
                ? ruleConfig.message.apply(null, params) 
                : ruleConfig.message;

            return {
                valid: valid,
                message: message
            };
        },

        /**
         * Validate entire form
         */
        validateForm: function($form) {
            var self = this;
            var isValid = true;

            $form.find('[data-validate]').each(function() {
                var fieldValid = self.validateField($(this));
                if (!fieldValid) {
                    isValid = false;
                }
            });

            return isValid;
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            var $wrapper = $field.closest('.acs-form-field');
            
            // Remove existing feedback
            $wrapper.removeClass('acs-field--success').addClass('acs-field--error');
            $wrapper.find('.acs-validation-message').remove();
            
            // Add error message
            var $message = $('<div class="acs-validation-message acs-validation-message--error">' +
                '<span class="dashicons dashicons-warning"></span> ' + 
                this.escapeHtml(message) + 
                '</div>');
            
            $field.after($message);
            
            // Accessibility
            $field.attr('aria-invalid', 'true');
            var msgId = 'validation-' + ($field.attr('id') || Date.now());
            $message.attr('id', msgId);
            $field.attr('aria-describedby', msgId);
        },

        /**
         * Show field success
         */
        showFieldSuccess: function($field) {
            var $wrapper = $field.closest('.acs-form-field');
            
            // Remove existing feedback
            $wrapper.removeClass('acs-field--error').addClass('acs-field--success');
            $wrapper.find('.acs-validation-message').remove();
            
            // Add success indicator
            var $indicator = $('<div class="acs-validation-message acs-validation-message--success">' +
                '<span class="dashicons dashicons-yes-alt"></span>' +
                '</div>');
            
            $field.after($indicator);
            
            // Accessibility
            $field.attr('aria-invalid', 'false');
            
            // Auto-remove after delay
            setTimeout(function() {
                $indicator.fadeOut(function() {
                    $(this).remove();
                });
            }, 2000);
        },

        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            var $wrapper = $field.closest('.acs-form-field');
            $wrapper.removeClass('acs-field--error acs-field--success');
            $wrapper.find('.acs-validation-message').remove();
            $field.removeAttr('aria-invalid aria-describedby');
        },

        /**
         * Focus first error field
         */
        focusFirstError: function($form) {
            var $firstError = $form.find('.acs-field--error:first input, .acs-field--error:first textarea, .acs-field--error:first select');
            if ($firstError.length) {
                $firstError.focus();
                
                // Scroll into view
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 300);
            }
        },

        /**
         * Test API key connection
         */
        testAPIKey: function($input, provider, $btn) {
            var self = this;
            var apiKey = $input.val().trim();
            var $wrapper = $input.closest('.acs-form-field');

            // Set loading state
            $btn.prop('disabled', true);
            var originalText = $btn.text();
            $btn.html('<span class="acs-spinner acs-spinner--small"></span> Testing...');
            
            // Remove existing status
            $wrapper.find('.acs-api-key-status').remove();

            // Make test request
            $.ajax({
                url: window.acsAdmin ? acsAdmin.ajaxUrl : ajaxurl,
                type: 'POST',
                data: {
                    action: 'acs_test_api_connection',
                    nonce: window.acsAdmin ? acsAdmin.nonce : '',
                    provider: provider,
                    api_key: apiKey
                },
                success: function(response) {
                    $btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        self.showAPIKeyStatus($wrapper, 'valid', 'API key is valid and connected.');
                        self.showFieldSuccess($input);
                    } else {
                        var message = response.data || 'Invalid API key. Please check and try again.';
                        self.showAPIKeyStatus($wrapper, 'invalid', message);
                        self.showFieldError($input, message);
                    }
                },
                error: function(jqXHR) {
                    $btn.prop('disabled', false).text(originalText);
                    self.showAPIKeyStatus($wrapper, 'invalid', 'Connection test failed. Please try again.');
                    self.showFieldError($input, 'Could not verify API key.');
                }
            });
        },

        /**
         * Show API key status
         */
        showAPIKeyStatus: function($wrapper, status, message) {
            var iconClass = status === 'valid' ? 'dashicons-yes-alt' : 'dashicons-warning';
            var $status = $('<div class="acs-api-key-status acs-api-key-status--' + status + '">' +
                '<span class="dashicons ' + iconClass + '"></span> ' +
                this.escapeHtml(message) +
                '</div>');
            
            $wrapper.find('.acs-api-key-status').remove();
            $wrapper.append($status);
        },

        /**
         * Add character counter to field
         */
        addCharacterCounter: function($field) {
            var maxLength = parseInt($field.data('max-length'), 10);
            var current = $field.val().length;
            
            var $counter = $('<div class="acs-char-counter">' +
                '<span class="acs-char-counter__current">' + current + '</span>' +
                ' / ' +
                '<span class="acs-char-counter__max">' + maxLength + '</span>' +
                '</div>');
            
            $field.after($counter);
            this.updateCharacterCounter($field);
        },

        /**
         * Update character counter
         */
        updateCharacterCounter: function($field) {
            var maxLength = parseInt($field.data('max-length'), 10);
            var current = $field.val().length;
            var $counter = $field.siblings('.acs-char-counter');
            var $currentSpan = $counter.find('.acs-char-counter__current');
            
            $currentSpan.text(current);
            
            // Update styling based on usage
            $counter.removeClass('acs-char-counter--warning acs-char-counter--danger');
            
            var percentage = (current / maxLength) * 100;
            if (percentage >= 100) {
                $counter.addClass('acs-char-counter--danger');
            } else if (percentage >= 80) {
                $counter.addClass('acs-char-counter--warning');
            }
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Add custom validation rule
         */
        addRule: function(name, testFn, message) {
            this.rules[name] = {
                test: testFn,
                message: message
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ACSAdmin.FormValidation.init();
    });

})(jQuery, window, document);
