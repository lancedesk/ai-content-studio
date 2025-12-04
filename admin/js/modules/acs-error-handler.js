/**
 * ACS Error Handler Module
 *
 * Client-side error handling, user feedback, and retry mechanisms.
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
     * Error Handler Module
     */
    ACSAdmin.ErrorHandler = {

        /**
         * Configuration
         */
        config: {
            maxRetries: 3,
            baseRetryDelay: 1000,
            showStackTrace: false
        },

        /**
         * Error type icons
         */
        icons: {
            error: 'dashicons-warning',
            warning: 'dashicons-info',
            info: 'dashicons-info-outline',
            success: 'dashicons-yes-alt'
        },

        /**
         * Initialize the module
         */
        init: function() {
            this.initGlobalErrorHandler();
            this.initAjaxErrorHandler();
            this.initFormErrorHandler();
            this.initRetryMechanism();
            
            console.log('[ACS] Error Handler initialized');
        },

        // =========================================================================
        // Global Error Handling
        // =========================================================================

        /**
         * Initialize global JavaScript error handler
         */
        initGlobalErrorHandler: function() {
            var self = this;

            // Catch unhandled errors
            window.onerror = function(message, source, lineno, colno, error) {
                // Only handle our plugin's errors
                if (source && source.indexOf('ai-content-studio') !== -1) {
                    self.logError('javascript_error', message, {
                        source: source,
                        line: lineno,
                        column: colno,
                        stack: error ? error.stack : ''
                    });
                }
                return false;
            };

            // Catch unhandled promise rejections
            window.onunhandledrejection = function(event) {
                self.logError('promise_rejection', event.reason ? event.reason.message : 'Unknown promise rejection', {
                    reason: event.reason
                });
            };
        },

        /**
         * Initialize AJAX error handling
         */
        initAjaxErrorHandler: function() {
            var self = this;

            // Global AJAX error handler
            $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
                // Skip if error was already handled
                if (settings.suppressErrors) return;

                var errorData = self.parseAjaxError(jqXHR, thrownError);
                
                // Don't show error for aborted requests
                if (errorData.code === 'aborted') return;

                // Show error notification
                self.showError(errorData.message, {
                    code: errorData.code,
                    retryable: errorData.retryable,
                    retryCallback: errorData.retryable ? function() {
                        $.ajax(settings);
                    } : null
                });
            });

            // Global AJAX success handler for error responses
            $(document).ajaxSuccess(function(event, jqXHR, settings) {
                try {
                    var response = JSON.parse(jqXHR.responseText);
                    if (response && response.success === false && response.data) {
                        // Handle WordPress AJAX error response
                        var errorData = response.data;
                        if (typeof errorData === 'object' && errorData.message) {
                            // Error was returned but HTTP was 200
                            // Don't auto-show, let individual handlers decide
                        }
                    }
                } catch (e) {
                    // Not JSON response, ignore
                }
            });
        },

        /**
         * Initialize form error handling
         */
        initFormErrorHandler: function() {
            var self = this;

            // Handle form validation errors
            $(document).on('invalid', 'form input, form textarea, form select', function(e) {
                var $field = $(this);
                var message = this.validationMessage || 'This field is invalid';
                
                self.showFieldError($field, message);
            });

            // Clear field errors on input
            $(document).on('input change', '.acs-form-field--error input, .acs-form-field--error textarea, .acs-form-field--error select', function() {
                var $field = $(this);
                self.clearFieldError($field);
            });

            // Handle form submission errors
            $(document).on('submit', 'form.acs-form', function(e) {
                var $form = $(this);
                var isValid = true;

                // Clear all previous errors
                self.clearFormErrors($form);

                // Validate required fields
                $form.find('[required]').each(function() {
                    var $field = $(this);
                    if (!$field.val().trim()) {
                        self.showFieldError($field, 'This field is required');
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    
                    // Focus first error field
                    $form.find('.acs-form-field--error:first input, .acs-form-field--error:first textarea, .acs-form-field--error:first select').focus();
                    
                    // Announce to screen readers
                    self.announceError('Please fix the errors in the form before submitting.');
                }
            });
        },

        /**
         * Initialize retry mechanism
         */
        initRetryMechanism: function() {
            var self = this;

            // Handle retry button clicks
            $(document).on('click', '.acs-retry-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var callback = $btn.data('retry-callback');
                
                if (callback && typeof window[callback] === 'function') {
                    self.executeWithRetry(window[callback], [], $btn);
                }
            });
        },

        // =========================================================================
        // Error Display
        // =========================================================================

        /**
         * Show error notification
         */
        showError: function(message, options) {
            options = $.extend({
                type: 'error',
                code: '',
                duration: 0, // 0 = persistent
                retryable: false,
                retryCallback: null,
                dismissible: true
            }, options);

            return this.showNotification(message, options);
        },

        /**
         * Show warning notification
         */
        showWarning: function(message, options) {
            options = $.extend({
                type: 'warning',
                duration: 8000,
                dismissible: true
            }, options);

            return this.showNotification(message, options);
        },

        /**
         * Show success notification
         */
        showSuccess: function(message, options) {
            options = $.extend({
                type: 'success',
                duration: 5000,
                dismissible: true
            }, options);

            return this.showNotification(message, options);
        },

        /**
         * Show info notification
         */
        showInfo: function(message, options) {
            options = $.extend({
                type: 'info',
                duration: 5000,
                dismissible: true
            }, options);

            return this.showNotification(message, options);
        },

        /**
         * Show notification
         */
        showNotification: function(message, options) {
            var self = this;
            options = options || {};

            var $container = this.getNotificationContainer();
            var id = 'acs-notification-' + Date.now();

            var html = '<div id="' + id + '" class="acs-notification acs-notification--' + (options.type || 'info') + '" role="alert" aria-live="assertive">' +
                '<span class="acs-notification__icon dashicons ' + this.icons[options.type || 'info'] + '"></span>' +
                '<div class="acs-notification__content">' +
                '<p class="acs-notification__message">' + this.escapeHtml(message) + '</p>';

            // Add error code if provided
            if (options.code) {
                html += '<p class="acs-notification__code">Error code: ' + this.escapeHtml(options.code) + '</p>';
            }

            // Add retry button if retryable
            if (options.retryable && options.retryCallback) {
                var callbackName = 'acsRetryCallback_' + Date.now();
                window[callbackName] = options.retryCallback;
                html += '<button type="button" class="acs-button acs-button--small acs-retry-btn" data-retry-callback="' + callbackName + '">' +
                    '<span class="dashicons dashicons-update"></span> Retry' +
                    '</button>';
            }

            html += '</div>';

            // Add dismiss button
            if (options.dismissible) {
                html += '<button type="button" class="acs-notification__dismiss" aria-label="Dismiss notification">' +
                    '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>';
            }

            html += '</div>';

            var $notification = $(html);
            $container.append($notification);

            // Animate in
            setTimeout(function() {
                $notification.addClass('acs-notification--visible');
            }, 10);

            // Auto-dismiss
            var timeout = null;
            if (options.duration > 0) {
                timeout = setTimeout(function() {
                    self.dismissNotification($notification);
                }, options.duration);
            }

            // Dismiss button handler
            $notification.find('.acs-notification__dismiss').on('click', function() {
                if (timeout) clearTimeout(timeout);
                self.dismissNotification($notification);
            });

            // Announce to screen readers
            this.announceError(message);

            return $notification;
        },

        /**
         * Get or create notification container
         */
        getNotificationContainer: function() {
            var $container = $('#acs-notification-container');
            if ($container.length === 0) {
                $container = $('<div id="acs-notification-container" class="acs-notification-container" aria-live="polite"></div>');
                $('body').append($container);
            }
            return $container;
        },

        /**
         * Dismiss notification
         */
        dismissNotification: function($notification) {
            $notification.removeClass('acs-notification--visible');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            var $formField = $field.closest('.acs-form-field');
            if ($formField.length === 0) {
                $formField = $field.closest('.form-field, .acs-form-group');
            }

            // Remove existing error
            this.clearFieldError($field);

            // Add error class
            $formField.addClass('acs-form-field--error');
            $field.attr('aria-invalid', 'true');

            // Add error message
            var $error = $('<div class="acs-field-error" role="alert">' +
                '<span class="dashicons dashicons-warning"></span> ' +
                this.escapeHtml(message) +
                '</div>');
            
            $field.after($error);

            // Link error to field for accessibility
            var errorId = 'error-' + ($field.attr('id') || Date.now());
            $error.attr('id', errorId);
            $field.attr('aria-describedby', errorId);
        },

        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            var $formField = $field.closest('.acs-form-field, .form-field, .acs-form-group');
            
            $formField.removeClass('acs-form-field--error');
            $field.attr('aria-invalid', 'false').removeAttr('aria-describedby');
            $formField.find('.acs-field-error').remove();
        },

        /**
         * Clear all form errors
         */
        clearFormErrors: function($form) {
            $form.find('.acs-form-field--error').removeClass('acs-form-field--error');
            $form.find('.acs-field-error').remove();
            $form.find('[aria-invalid]').attr('aria-invalid', 'false').removeAttr('aria-describedby');
        },

        /**
         * Show inline error in element
         */
        showInlineError: function($element, message) {
            var $error = $('<div class="acs-inline-error">' +
                '<span class="dashicons dashicons-warning"></span> ' +
                this.escapeHtml(message) +
                '</div>');
            
            $element.empty().append($error);
        },

        // =========================================================================
        // Retry Mechanism
        // =========================================================================

        /**
         * Execute function with retry logic
         */
        executeWithRetry: function(fn, args, $btn, attempt) {
            var self = this;
            attempt = attempt || 1;
            args = args || [];

            // Show loading state on button
            if ($btn) {
                $btn.prop('disabled', true);
                $btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update acs-spin');
            }

            try {
                var result = fn.apply(null, args);

                // Handle Promise
                if (result && typeof result.then === 'function') {
                    result.then(function(response) {
                        self.resetRetryButton($btn);
                        
                        if (response.success === false) {
                            self.handleRetryFailure(fn, args, $btn, attempt, response.data);
                        }
                    }).catch(function(error) {
                        self.handleRetryFailure(fn, args, $btn, attempt, error);
                    });
                } else {
                    self.resetRetryButton($btn);
                }

            } catch (error) {
                self.handleRetryFailure(fn, args, $btn, attempt, error);
            }
        },

        /**
         * Handle retry failure
         */
        handleRetryFailure: function(fn, args, $btn, attempt, error) {
            var self = this;
            var maxRetries = this.config.maxRetries;
            var retryable = error && error.retryable !== false;

            if (retryable && attempt < maxRetries) {
                var delay = this.calculateRetryDelay(attempt);
                
                // Show countdown on button
                if ($btn) {
                    var countdown = Math.ceil(delay / 1000);
                    $btn.text('Retrying in ' + countdown + 's...');
                    
                    var countdownInterval = setInterval(function() {
                        countdown--;
                        if (countdown > 0) {
                            $btn.text('Retrying in ' + countdown + 's...');
                        } else {
                            clearInterval(countdownInterval);
                        }
                    }, 1000);
                }

                setTimeout(function() {
                    self.executeWithRetry(fn, args, $btn, attempt + 1);
                }, delay);

            } else {
                this.resetRetryButton($btn);
                
                var message = error && error.message ? error.message : 'Operation failed after multiple attempts';
                this.showError(message, {
                    code: error && error.code,
                    retryable: retryable,
                    retryCallback: retryable ? function() {
                        self.executeWithRetry(fn, args, $btn, 1);
                    } : null
                });
            }
        },

        /**
         * Reset retry button state
         */
        resetRetryButton: function($btn) {
            if ($btn) {
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('acs-spin');
                $btn.html('<span class="dashicons dashicons-update"></span> Retry');
            }
        },

        /**
         * Calculate retry delay with exponential backoff
         */
        calculateRetryDelay: function(attempt) {
            return this.config.baseRetryDelay * Math.pow(2, attempt - 1);
        },

        // =========================================================================
        // Error Parsing
        // =========================================================================

        /**
         * Parse AJAX error response
         */
        parseAjaxError: function(jqXHR, thrownError) {
            var code = 'unknown_error';
            var message = 'An unexpected error occurred';
            var retryable = false;

            // Handle different HTTP status codes
            switch (jqXHR.status) {
                case 0:
                    if (jqXHR.statusText === 'abort') {
                        code = 'aborted';
                        message = 'Request was cancelled';
                    } else {
                        code = 'connection_error';
                        message = 'Unable to connect to the server. Please check your internet connection.';
                        retryable = true;
                    }
                    break;
                case 400:
                    code = 'bad_request';
                    message = 'Invalid request. Please check your input.';
                    break;
                case 401:
                    code = 'unauthorized';
                    message = 'Your session has expired. Please refresh the page and try again.';
                    break;
                case 403:
                    code = 'forbidden';
                    message = 'You do not have permission to perform this action.';
                    break;
                case 404:
                    code = 'not_found';
                    message = 'The requested resource was not found.';
                    break;
                case 408:
                    code = 'timeout';
                    message = 'The request timed out. Please try again.';
                    retryable = true;
                    break;
                case 429:
                    code = 'rate_limited';
                    message = 'Too many requests. Please wait a moment and try again.';
                    retryable = true;
                    break;
                case 500:
                case 502:
                case 503:
                case 504:
                    code = 'server_error';
                    message = 'The server encountered an error. Please try again later.';
                    retryable = true;
                    break;
            }

            // Try to get more specific error from response
            try {
                var response = JSON.parse(jqXHR.responseText);
                if (response.data) {
                    if (response.data.message) {
                        message = response.data.message;
                    }
                    if (response.data.code) {
                        code = response.data.code;
                    }
                    if (typeof response.data.retryable !== 'undefined') {
                        retryable = response.data.retryable;
                    }
                }
            } catch (e) {
                // Not JSON, use default message
            }

            return {
                code: code,
                message: message,
                retryable: retryable,
                status: jqXHR.status
            };
        },

        // =========================================================================
        // Accessibility
        // =========================================================================

        /**
         * Announce error to screen readers
         */
        announceError: function(message) {
            var $announcer = $('#acs-error-announcer');
            if ($announcer.length === 0) {
                $announcer = $('<div id="acs-error-announcer" class="screen-reader-text" role="alert" aria-live="assertive" aria-atomic="true"></div>');
                $('body').append($announcer);
            }

            $announcer.text(message);

            // Clear after announcement
            setTimeout(function() {
                $announcer.text('');
            }, 1000);
        },

        // =========================================================================
        // Utilities
        // =========================================================================

        /**
         * Escape HTML special characters
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Log error to console and optionally to server
         */
        logError: function(code, message, data) {
            console.error('[ACS Error]', code, message, data);

            // Send to server if logging is enabled
            if (window.acsAdmin && acsAdmin.logErrors) {
                $.post(acsAdmin.ajaxUrl, {
                    action: 'acs_log_client_error',
                    nonce: acsAdmin.nonce,
                    code: code,
                    message: message,
                    data: JSON.stringify(data),
                    url: window.location.href,
                    userAgent: navigator.userAgent
                });
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ACSAdmin.ErrorHandler.init();
    });

})(jQuery, window, document);
