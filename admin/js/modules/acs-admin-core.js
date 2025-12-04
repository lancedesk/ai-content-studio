/**
 * ACS Admin Core Module
 *
 * Core JavaScript functionality for ACS admin interface with modular architecture
 * and extensibility hooks.
 *
 * @package AI_Content_Studio
 * @subpackage Admin/JavaScript
 * @since 2.0.0
 */

(function($, window, document) {
    'use strict';
    
    // Namespace for ACS admin functionality
    window.ACSAdmin = window.ACSAdmin || {};
    
    /**
     * Core Admin Module
     */
    ACSAdmin.Core = {
        
        /**
         * Module configuration
         */
        config: {
            ajaxUrl: '',
            nonce: '',
            currentPage: '',
            restUrl: '',
            restNonce: '',
            strings: {},
            adminColors: {},
            debug: false
        },
        
        /**
         * Registered modules
         */
        modules: {},
        
        /**
         * Event hooks system
         */
        hooks: {
            actions: {},
            filters: {}
        },
        
        /**
         * Initialize the core module
         */
        init: function(config) {
            this.config = $.extend(this.config, config || {});
            
            this.log('Initializing ACS Admin Core');
            
            // Initialize core functionality
            this.initAccessibility();
            this.initNotifications();
            this.initAjaxHandlers();
            this.initFormValidation();
            this.initKeyboardShortcuts();
            
            // Initialize registered modules
            this.initModules();
            
            // Trigger initialization complete hook
            this.doAction('acs_admin_core_initialized', this);
            
            this.log('ACS Admin Core initialized successfully');
        },
        
        /**
         * Register a module
         */
        registerModule: function(name, module) {
            if (typeof module !== 'object' || typeof module.init !== 'function') {
                this.log('Invalid module: ' + name, 'error');
                return false;
            }
            
            this.modules[name] = module;
            this.log('Module registered: ' + name);
            
            // Initialize immediately if core is already initialized
            if (this.isInitialized()) {
                this.initModule(name);
            }
            
            return true;
        },
        
        /**
         * Initialize all registered modules
         */
        initModules: function() {
            for (var name in this.modules) {
                this.initModule(name);
            }
        },
        
        /**
         * Initialize a specific module
         */
        initModule: function(name) {
            if (!this.modules[name]) {
                this.log('Module not found: ' + name, 'error');
                return false;
            }
            
            try {
                this.modules[name].init(this);
                this.log('Module initialized: ' + name);
                this.doAction('acs_admin_module_initialized', name, this.modules[name]);
                return true;
            } catch (error) {
                this.log('Module initialization failed: ' + name + ' - ' + error.message, 'error');
                return false;
            }
        },
        
        /**
         * Get a registered module
         */
        getModule: function(name) {
            return this.modules[name] || null;
        },
        
        /**
         * Check if core is initialized
         */
        isInitialized: function() {
            return this.config.ajaxUrl !== '';
        },
        
        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add skip links if not present
            if (!$('.skip-links').length) {
                $('body').prepend(
                    '<div class="skip-links">' +
                    '<a class="skip-link screen-reader-text" href="#main">' + this.getString('skip_to_content', 'Skip to main content') + '</a>' +
                    '<a class="skip-link screen-reader-text" href="#adminmenu">' + this.getString('skip_to_navigation', 'Skip to navigation') + '</a>' +
                    '</div>'
                );
            }
            
            // Enhance skip links
            $('.skip-link').on('focus', function() {
                $(this).removeClass('screen-reader-text');
            }).on('blur', function() {
                $(this).addClass('screen-reader-text');
            });
            
            // Add ARIA labels to buttons without text
            this.enhanceButtonAccessibility();
            
            // Manage focus for dynamic content
            this.initFocusManagement();
            
            this.log('Accessibility features initialized');
        },
        
        /**
         * Enhance button accessibility
         */
        enhanceButtonAccessibility: function() {
            $('button:not([aria-label])').each(function() {
                var $button = $(this);
                var $icon = $button.find('.dashicons');
                var text = $button.text().trim();
                
                if (!text && $icon.length) {
                    var iconClass = $icon.attr('class');
                    var label = ACSAdmin.Core.getIconLabel(iconClass);
                    
                    if (label) {
                        $button.attr('aria-label', label);
                    }
                }
            });
        },
        
        /**
         * Get appropriate label for icon
         */
        getIconLabel: function(iconClass) {
            var iconMap = {
                'dashicons-edit': this.getString('edit', 'Edit'),
                'dashicons-trash': this.getString('delete', 'Delete'),
                'dashicons-visibility': this.getString('view', 'View'),
                'dashicons-admin-settings': this.getString('settings', 'Settings'),
                'dashicons-plus': this.getString('add', 'Add'),
                'dashicons-minus': this.getString('remove', 'Remove'),
                'dashicons-yes': this.getString('confirm', 'Confirm'),
                'dashicons-no': this.getString('cancel', 'Cancel')
            };
            
            for (var icon in iconMap) {
                if (iconClass.includes(icon)) {
                    return iconMap[icon];
                }
            }
            
            return '';
        },
        
        /**
         * Initialize focus management
         */
        initFocusManagement: function() {
            var self = this;
            
            // Handle hash changes for focus management
            $(window).on('hashchange', function() {
                var hash = window.location.hash;
                if (hash) {
                    var $target = $(hash);
                    if ($target.length) {
                        $target.focus();
                        self.announceToScreenReader(self.getString('navigated_to', 'Navigated to') + ' ' + ($target.attr('aria-label') || $target.text().trim()));
                    }
                }
            });
            
            // Trap focus in modals
            $(document).on('keydown', '.acs-modal', function(e) {
                if (e.keyCode === 9) { // Tab key
                    self.trapFocus(e, this);
                }
            });
        },
        
        /**
         * Trap focus within an element
         */
        trapFocus: function(event, container) {
            var $container = $(container);
            var $focusableElements = $container.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            var $firstElement = $focusableElements.first();
            var $lastElement = $focusableElements.last();
            
            if (event.shiftKey) {
                if (document.activeElement === $firstElement[0]) {
                    $lastElement.focus();
                    event.preventDefault();
                }
            } else {
                if (document.activeElement === $lastElement[0]) {
                    $firstElement.focus();
                    event.preventDefault();
                }
            }
        },
        
        /**
         * Initialize notification system
         */
        initNotifications: function() {
            // Handle dismissible notices
            $(document).on('click', '.notice-dismiss', function() {
                var $notice = $(this).closest('.notice');
                var noticeId = $notice.data('notice-id');
                
                if (noticeId) {
                    ACSAdmin.Core.dismissNotice(noticeId);
                }
            });
            
            // Auto-dismiss temporary notices
            $('.notice.is-dismissible[data-auto-dismiss]').each(function() {
                var $notice = $(this);
                var delay = parseInt($notice.data('auto-dismiss')) || 5000;
                
                setTimeout(function() {
                    $notice.fadeOut();
                }, delay);
            });
            
            this.log('Notification system initialized');
        },
        
        /**
         * Dismiss a notice
         */
        dismissNotice: function(noticeId) {
            $.post(this.config.ajaxUrl, {
                action: 'acs_dismiss_notice',
                notice_id: noticeId,
                nonce: this.config.nonce
            });
        },
        
        /**
         * Show a notification
         */
        showNotification: function(message, type, options) {
            type = type || 'info';
            options = options || {};
            
            var $notice = $('<div class="notice notice-' + type + (options.dismissible !== false ? ' is-dismissible' : '') + '">' +
                '<p>' + message + '</p>' +
                (options.dismissible !== false ? '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + this.getString('dismiss', 'Dismiss this notice') + '</span></button>' : '') +
                '</div>');
            
            if (options.target) {
                $(options.target).prepend($notice);
            } else {
                $('.wrap').first().prepend($notice);
            }
            
            // Auto-dismiss if specified
            if (options.autoDismiss) {
                setTimeout(function() {
                    $notice.fadeOut();
                }, options.autoDismiss);
            }
            
            // Announce to screen readers
            this.announceToScreenReader(message);
            
            return $notice;
        },
        
        /**
         * Initialize AJAX handlers
         */
        initAjaxHandlers: function() {
            var self = this;
            
            // Global AJAX setup
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    // Add nonce to all ACS AJAX requests
                    if (settings.url.indexOf(self.config.ajaxUrl) !== -1 && settings.data && settings.data.indexOf('action=acs_') !== -1) {
                        if (settings.data.indexOf('nonce=') === -1) {
                            settings.data += '&nonce=' + self.config.nonce;
                        }
                    }
                    
                    self.doAction('acs_ajax_before_send', xhr, settings);
                },
                complete: function(xhr, status) {
                    self.doAction('acs_ajax_complete', xhr, status);
                },
                error: function(xhr, status, error) {
                    self.log('AJAX Error: ' + error, 'error');
                    self.doAction('acs_ajax_error', xhr, status, error);
                }
            });
            
            // Handle form submissions with AJAX
            $(document).on('submit', '.acs-ajax-form', function(e) {
                e.preventDefault();
                self.submitForm($(this));
            });
            
            this.log('AJAX handlers initialized');
        },
        
        /**
         * Submit form via AJAX
         */
        submitForm: function($form) {
            var self = this;
            var $submitButton = $form.find('[type="submit"]');
            var originalText = $submitButton.text();
            
            // Show loading state
            $submitButton.prop('disabled', true).text(this.getString('loading', 'Loading...'));
            
            $.post(this.config.ajaxUrl, $form.serialize())
                .done(function(response) {
                    if (response.success) {
                        self.showNotification(response.data.message || self.getString('success', 'Operation completed successfully'), 'success');
                        self.doAction('acs_form_submit_success', response, $form);
                    } else {
                        self.showNotification(response.data || self.getString('error', 'An error occurred'), 'error');
                        self.doAction('acs_form_submit_error', response, $form);
                    }
                })
                .fail(function() {
                    self.showNotification(self.getString('ajax_error', 'Request failed. Please try again.'), 'error');
                })
                .always(function() {
                    $submitButton.prop('disabled', false).text(originalText);
                });
        },
        
        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            var self = this;
            
            // Real-time validation
            $(document).on('blur', '.acs-form-field input, .acs-form-field textarea, .acs-form-field select', function() {
                self.validateField($(this));
            });
            
            // Form submission validation
            $(document).on('submit', '.acs-form', function(e) {
                if (!self.validateForm($(this))) {
                    e.preventDefault();
                }
            });
            
            this.log('Form validation initialized');
        },
        
        /**
         * Validate a single field
         */
        validateField: function($field) {
            var isValid = true;
            var $fieldWrapper = $field.closest('.acs-form-field');
            var $validationMessage = $fieldWrapper.find('.acs-form-validation');
            
            // Clear previous validation
            $fieldWrapper.removeClass('acs-form-field--error');
            $validationMessage.hide().empty();
            
            // Required field validation
            if ($field.prop('required') && !$field.val().trim()) {
                isValid = false;
                this.showFieldError($field, this.getString('field_required', 'This field is required'));
            }
            
            // Type-specific validation
            if (isValid && $field.val().trim()) {
                var fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
                
                switch (fieldType) {
                    case 'email':
                        if (!this.isValidEmail($field.val())) {
                            isValid = false;
                            this.showFieldError($field, this.getString('invalid_email', 'Please enter a valid email address'));
                        }
                        break;
                    case 'url':
                        if (!this.isValidUrl($field.val())) {
                            isValid = false;
                            this.showFieldError($field, this.getString('invalid_url', 'Please enter a valid URL'));
                        }
                        break;
                    case 'number':
                        if (!this.isValidNumber($field.val())) {
                            isValid = false;
                            this.showFieldError($field, this.getString('invalid_number', 'Please enter a valid number'));
                        }
                        break;
                }
            }
            
            // Custom validation hook
            isValid = this.applyFilters('acs_validate_field', isValid, $field);
            
            return isValid;
        },
        
        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            var $fieldWrapper = $field.closest('.acs-form-field');
            var $validationMessage = $fieldWrapper.find('.acs-form-validation');
            
            $fieldWrapper.addClass('acs-form-field--error');
            $validationMessage.text(message).show();
            
            // Announce to screen readers
            this.announceToScreenReader(message);
        },
        
        /**
         * Validate entire form
         */
        validateForm: function($form) {
            var self = this;
            var isValid = true;
            
            $form.find('input, textarea, select').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });
            
            // Custom form validation hook
            isValid = this.applyFilters('acs_validate_form', isValid, $form);
            
            return isValid;
        },
        
        /**
         * Initialize keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            var self = this;
            
            $(document).on('keydown', function(e) {
                // Alt + D = Dashboard
                if (e.altKey && e.keyCode === 68) {
                    e.preventDefault();
                    window.location.href = self.config.adminUrl + 'admin.php?page=acs-dashboard';
                }
                
                // Alt + G = Generate
                if (e.altKey && e.keyCode === 71) {
                    e.preventDefault();
                    window.location.href = self.config.adminUrl + 'admin.php?page=acs-generate';
                }
                
                // Alt + A = Analytics
                if (e.altKey && e.keyCode === 65) {
                    e.preventDefault();
                    window.location.href = self.config.adminUrl + 'admin.php?page=acs-analytics';
                }
                
                // Alt + S = Settings
                if (e.altKey && e.keyCode === 83) {
                    e.preventDefault();
                    window.location.href = self.config.adminUrl + 'admin.php?page=acs-settings';
                }
                
                // Escape = Close modals
                if (e.keyCode === 27) {
                    $('.acs-modal').hide();
                }
                
                // Custom keyboard shortcuts hook
                self.doAction('acs_keyboard_shortcut', e);
            });
            
            this.log('Keyboard shortcuts initialized');
        },
        
        /**
         * Validation helper methods
         */
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },
        
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },
        
        isValidNumber: function(value) {
            return !isNaN(value) && !isNaN(parseFloat(value));
        },
        
        /**
         * Announce message to screen readers
         */
        announceToScreenReader: function(message, assertive) {
            var $liveRegion = $(assertive ? '#acs-live-region-assertive' : '#acs-live-region');
            if ($liveRegion.length) {
                $liveRegion.text(message);
                
                // Clear after announcement
                setTimeout(function() {
                    $liveRegion.empty();
                }, 1000);
            }
        },
        
        /**
         * Get localized string
         */
        getString: function(key, defaultValue) {
            return this.config.strings[key] || defaultValue || key;
        },
        
        /**
         * Logging utility
         */
        log: function(message, level) {
            if (!this.config.debug) return;
            
            level = level || 'info';
            var prefix = '[ACS Admin] ';
            
            switch (level) {
                case 'error':
                    console.error(prefix + message);
                    break;
                case 'warn':
                    console.warn(prefix + message);
                    break;
                default:
                    console.log(prefix + message);
            }
        },
        
        /**
         * Hook system - Add action
         */
        addAction: function(hook, callback, priority) {
            priority = priority || 10;
            
            if (!this.hooks.actions[hook]) {
                this.hooks.actions[hook] = [];
            }
            
            this.hooks.actions[hook].push({
                callback: callback,
                priority: priority
            });
            
            // Sort by priority
            this.hooks.actions[hook].sort(function(a, b) {
                return a.priority - b.priority;
            });
        },
        
        /**
         * Hook system - Do action
         */
        doAction: function(hook) {
            if (!this.hooks.actions[hook]) return;
            
            var args = Array.prototype.slice.call(arguments, 1);
            
            for (var i = 0; i < this.hooks.actions[hook].length; i++) {
                this.hooks.actions[hook][i].callback.apply(this, args);
            }
        },
        
        /**
         * Hook system - Add filter
         */
        addFilter: function(hook, callback, priority) {
            priority = priority || 10;
            
            if (!this.hooks.filters[hook]) {
                this.hooks.filters[hook] = [];
            }
            
            this.hooks.filters[hook].push({
                callback: callback,
                priority: priority
            });
            
            // Sort by priority
            this.hooks.filters[hook].sort(function(a, b) {
                return a.priority - b.priority;
            });
        },
        
        /**
         * Hook system - Apply filters
         */
        applyFilters: function(hook, value) {
            if (!this.hooks.filters[hook]) return value;
            
            var args = Array.prototype.slice.call(arguments, 1);
            
            for (var i = 0; i < this.hooks.filters[hook].length; i++) {
                args[0] = this.hooks.filters[hook][i].callback.apply(this, args);
            }
            
            return args[0];
        }
    };
    
    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        // Initialize with global config if available
        if (typeof acsAdmin !== 'undefined') {
            ACSAdmin.Core.init(acsAdmin);
        }
    });
    
})(jQuery, window, document);