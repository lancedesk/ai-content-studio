/**
 * ACS Interactions Module
 *
 * Enhanced JavaScript interactions including micro-animations,
 * smooth transitions, AJAX loading states, and keyboard shortcuts.
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
     * Interactions Module
     */
    ACSAdmin.Interactions = {

        /**
         * Configuration
         */
        config: {
            animationDuration: 300,
            debounceDelay: 250,
            keyboardShortcutsEnabled: true
        },

        /**
         * Keyboard shortcuts registry
         */
        shortcuts: {},

        /**
         * Initialize the module
         */
        init: function() {
            this.initMicroAnimations();
            this.initLoadingStates();
            this.initKeyboardShortcuts();
            this.initSmoothTransitions();
            this.initInteractiveComponents();
            this.initProgressIndicators();
            this.initTooltips();
            this.initContextMenus();
            
            console.log('[ACS] Interactions module initialized');
        },

        // =========================================================================
        // Micro Animations
        // =========================================================================

        /**
         * Initialize micro-animations for UI feedback
         */
        initMicroAnimations: function() {
            var self = this;

            // Button ripple effect
            $(document).on('click', '.acs-button', function(e) {
                self.createRippleEffect($(this), e);
            });

            // Card hover lift effect
            $(document).on('mouseenter', '.acs-card--hoverable', function() {
                $(this).addClass('acs-card--lifted');
            }).on('mouseleave', '.acs-card--hoverable', function() {
                $(this).removeClass('acs-card--lifted');
            });

            // Success checkmark animation
            $(document).on('acs:success', function(e, $element) {
                self.showSuccessAnimation($element);
            });

            // Error shake animation
            $(document).on('acs:error', function(e, $element) {
                self.showErrorAnimation($element);
            });

            // Toggle switch animation
            $(document).on('change', '.acs-toggle-input', function() {
                var $toggle = $(this).closest('.acs-toggle');
                $toggle.addClass('acs-toggle--animating');
                setTimeout(function() {
                    $toggle.removeClass('acs-toggle--animating');
                }, 300);
            });

            // Number counter animation
            this.initCounterAnimations();
        },

        /**
         * Create ripple effect on button click
         */
        createRippleEffect: function($button, e) {
            // Remove existing ripples
            $button.find('.acs-ripple').remove();

            var offset = $button.offset();
            var x = e.pageX - offset.left;
            var y = e.pageY - offset.top;

            var $ripple = $('<span class="acs-ripple"></span>');
            $ripple.css({
                left: x + 'px',
                top: y + 'px'
            });

            $button.append($ripple);

            // Remove ripple after animation
            setTimeout(function() {
                $ripple.remove();
            }, 600);
        },

        /**
         * Show success animation
         */
        showSuccessAnimation: function($element) {
            $element = $element || $('body');
            
            var $check = $('<div class="acs-success-check"><svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="25" fill="none"/><path fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg></div>');
            
            $element.append($check);
            
            setTimeout(function() {
                $check.addClass('acs-success-check--active');
            }, 10);
            
            setTimeout(function() {
                $check.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 1500);
        },

        /**
         * Show error shake animation
         */
        showErrorAnimation: function($element) {
            $element.addClass('acs-shake');
            setTimeout(function() {
                $element.removeClass('acs-shake');
            }, 500);
        },

        /**
         * Initialize counter animations for statistics
         */
        initCounterAnimations: function() {
            var self = this;

            // Animate numbers when they come into view
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        self.animateCounter($(entry.target));
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            $('.acs-stat-value[data-count]').each(function() {
                observer.observe(this);
            });
        },

        /**
         * Animate a counter from 0 to target value
         */
        animateCounter: function($element) {
            var target = parseInt($element.data('count'), 10) || 0;
            var duration = 1000;
            var start = 0;
            var startTime = null;

            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var current = Math.floor(progress * target);
                
                $element.text(current.toLocaleString());
                
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    $element.text(target.toLocaleString());
                }
            }

            window.requestAnimationFrame(step);
        },

        // =========================================================================
        // Loading States
        // =========================================================================

        /**
         * Initialize AJAX loading states
         */
        initLoadingStates: function() {
            var self = this;

            // Global AJAX loading indicator
            $(document).ajaxStart(function() {
                self.showGlobalLoader();
            }).ajaxStop(function() {
                self.hideGlobalLoader();
            });

            // Button loading states
            $(document).on('click', '.acs-button[data-loading-text]', function() {
                var $btn = $(this);
                if (!$btn.hasClass('acs-button--loading')) {
                    self.setButtonLoading($btn, true);
                }
            });

            // Form submission loading
            $(document).on('submit', 'form.acs-form', function() {
                var $form = $(this);
                var $submitBtn = $form.find('[type="submit"]');
                self.setButtonLoading($submitBtn, true);
            });

            // Card loading states
            $(document).on('acs:card:loading', function(e, $card) {
                self.setCardLoading($card, true);
            }).on('acs:card:loaded', function(e, $card) {
                self.setCardLoading($card, false);
            });
        },

        /**
         * Show global loading indicator
         */
        showGlobalLoader: function() {
            if ($('#acs-global-loader').length === 0) {
                var $loader = $('<div id="acs-global-loader" class="acs-global-loader"><div class="acs-global-loader__bar"></div></div>');
                $('body').append($loader);
            }
            $('#acs-global-loader').addClass('acs-global-loader--active');
        },

        /**
         * Hide global loading indicator
         */
        hideGlobalLoader: function() {
            $('#acs-global-loader').removeClass('acs-global-loader--active');
        },

        /**
         * Set button loading state
         */
        setButtonLoading: function($btn, loading) {
            if (loading) {
                var loadingText = $btn.data('loading-text') || 'Loading...';
                $btn.data('original-html', $btn.html());
                $btn.addClass('acs-button--loading')
                    .prop('disabled', true)
                    .html('<span class="acs-spinner acs-spinner--small"></span> ' + loadingText);
            } else {
                var originalHtml = $btn.data('original-html');
                $btn.removeClass('acs-button--loading')
                    .prop('disabled', false);
                if (originalHtml) {
                    $btn.html(originalHtml);
                }
            }
        },

        /**
         * Set card loading state with skeleton
         */
        setCardLoading: function($card, loading) {
            if (loading) {
                $card.addClass('acs-card--loading');
                var $content = $card.find('.acs-card__content');
                $content.data('original-html', $content.html());
                $content.html(this.getSkeletonHTML());
            } else {
                $card.removeClass('acs-card--loading');
                var $content = $card.find('.acs-card__content');
                var originalHtml = $content.data('original-html');
                if (originalHtml) {
                    $content.html(originalHtml);
                }
            }
        },

        /**
         * Generate skeleton loading HTML
         */
        getSkeletonHTML: function() {
            return '<div class="acs-skeleton">' +
                '<div class="acs-skeleton__line acs-skeleton__line--title"></div>' +
                '<div class="acs-skeleton__line"></div>' +
                '<div class="acs-skeleton__line"></div>' +
                '<div class="acs-skeleton__line acs-skeleton__line--short"></div>' +
                '</div>';
        },

        // =========================================================================
        // Keyboard Shortcuts
        // =========================================================================

        /**
         * Initialize keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            var self = this;

            // Register default shortcuts
            this.registerShortcut('ctrl+s', 'Save', function(e) {
                e.preventDefault();
                var $form = $('form.acs-form:visible').first();
                if ($form.length) {
                    $form.submit();
                }
            });

            this.registerShortcut('ctrl+shift+g', 'Generate Content', function(e) {
                e.preventDefault();
                window.location.href = 'admin.php?page=acs-generate';
            });

            this.registerShortcut('ctrl+shift+d', 'Dashboard', function(e) {
                e.preventDefault();
                window.location.href = 'admin.php?page=acs-dashboard';
            });

            this.registerShortcut('ctrl+shift+a', 'Analytics', function(e) {
                e.preventDefault();
                window.location.href = 'admin.php?page=acs-analytics';
            });

            this.registerShortcut('ctrl+shift+s', 'Settings', function(e) {
                e.preventDefault();
                window.location.href = 'admin.php?page=acs-settings';
            });

            this.registerShortcut('?', 'Show Keyboard Shortcuts', function(e) {
                if (!$(e.target).is('input, textarea, select')) {
                    e.preventDefault();
                    self.showShortcutsModal();
                }
            });

            this.registerShortcut('escape', 'Close Modal/Menu', function(e) {
                // Close any open modals
                if ($('.acs-modal--open').length) {
                    $('.acs-modal--open').removeClass('acs-modal--open');
                    $('body').removeClass('acs-modal-open');
                }
                // Close mobile nav
                if ($('.acs-mobile-nav.active').length) {
                    $('.acs-mobile-nav').removeClass('active');
                    $('body').removeClass('acs-mobile-nav-open');
                }
            });

            // Global keyboard listener
            $(document).on('keydown', function(e) {
                self.handleKeydown(e);
            });
        },

        /**
         * Register a keyboard shortcut
         */
        registerShortcut: function(keys, description, callback) {
            this.shortcuts[keys.toLowerCase()] = {
                keys: keys,
                description: description,
                callback: callback
            };
        },

        /**
         * Handle keydown events
         */
        handleKeydown: function(e) {
            if (!this.config.keyboardShortcutsEnabled) return;

            var key = this.getKeyCombo(e);
            var shortcut = this.shortcuts[key];

            if (shortcut && typeof shortcut.callback === 'function') {
                shortcut.callback(e);
            }
        },

        /**
         * Get key combination string from event
         */
        getKeyCombo: function(e) {
            var parts = [];
            
            if (e.ctrlKey || e.metaKey) parts.push('ctrl');
            if (e.shiftKey) parts.push('shift');
            if (e.altKey) parts.push('alt');
            
            var key = e.key.toLowerCase();
            if (key === ' ') key = 'space';
            if (!['control', 'shift', 'alt', 'meta'].includes(key)) {
                parts.push(key);
            }
            
            return parts.join('+');
        },

        /**
         * Show keyboard shortcuts modal
         */
        showShortcutsModal: function() {
            var self = this;
            var $modal = $('#acs-shortcuts-modal');

            if ($modal.length === 0) {
                var html = '<div id="acs-shortcuts-modal" class="acs-modal">' +
                    '<div class="acs-modal__backdrop"></div>' +
                    '<div class="acs-modal__content">' +
                    '<div class="acs-modal__header">' +
                    '<h2>Keyboard Shortcuts</h2>' +
                    '<button class="acs-modal__close" aria-label="Close"><span class="dashicons dashicons-no-alt"></span></button>' +
                    '</div>' +
                    '<div class="acs-modal__body">' +
                    '<table class="acs-shortcuts-table">' +
                    '<thead><tr><th>Shortcut</th><th>Action</th></tr></thead>' +
                    '<tbody></tbody>' +
                    '</table>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                $modal = $(html);
                $('body').append($modal);

                // Populate shortcuts table
                var $tbody = $modal.find('tbody');
                for (var key in this.shortcuts) {
                    var shortcut = this.shortcuts[key];
                    var displayKey = shortcut.keys.replace('ctrl', '⌘/Ctrl').replace('shift', '⇧').replace('alt', '⌥');
                    $tbody.append('<tr><td><kbd>' + displayKey + '</kbd></td><td>' + shortcut.description + '</td></tr>');
                }

                // Close handlers
                $modal.on('click', '.acs-modal__backdrop, .acs-modal__close', function() {
                    self.closeShortcutsModal();
                });
            }

            $modal.addClass('acs-modal--open');
            $('body').addClass('acs-modal-open');
        },

        /**
         * Close shortcuts modal
         */
        closeShortcutsModal: function() {
            $('#acs-shortcuts-modal').removeClass('acs-modal--open');
            $('body').removeClass('acs-modal-open');
        },

        // =========================================================================
        // Smooth Transitions
        // =========================================================================

        /**
         * Initialize smooth page transitions
         */
        initSmoothTransitions: function() {
            var self = this;

            // Tab transitions
            $(document).on('click', '.acs-tab', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var targetId = $tab.data('tab');
                
                self.switchTab($tab, targetId);
            });

            // Accordion transitions
            $(document).on('click', '.acs-accordion__header', function() {
                var $item = $(this).closest('.acs-accordion__item');
                self.toggleAccordion($item);
            });

            // Smooth scroll for anchor links
            $(document).on('click', 'a[href^="#"]', function(e) {
                var href = $(this).attr('href');
                if (href.length > 1) {
                    var $target = $(href);
                    if ($target.length) {
                        e.preventDefault();
                        self.smoothScrollTo($target);
                    }
                }
            });
        },

        /**
         * Switch tab with animation
         */
        switchTab: function($tab, targetId) {
            var $container = $tab.closest('.acs-tabs');
            var $tabs = $container.find('.acs-tab');
            var $contents = $container.find('.acs-tab-content');
            var $target = $container.find('#' + targetId);

            // Update tab states
            $tabs.removeClass('active').attr('aria-selected', 'false');
            $tab.addClass('active').attr('aria-selected', 'true');

            // Animate content switch
            $contents.removeClass('active').hide();
            $target.fadeIn(this.config.animationDuration).addClass('active');

            // Announce to screen readers
            if (window.ACSAdmin && ACSAdmin.announceToScreenReader) {
                ACSAdmin.announceToScreenReader('Switched to ' + $tab.text() + ' tab');
            }
        },

        /**
         * Toggle accordion item
         */
        toggleAccordion: function($item) {
            var $content = $item.find('.acs-accordion__content');
            var isOpen = $item.hasClass('acs-accordion__item--open');

            if (isOpen) {
                $content.slideUp(this.config.animationDuration);
                $item.removeClass('acs-accordion__item--open');
            } else {
                // Close other items in exclusive mode
                var $accordion = $item.closest('.acs-accordion');
                if ($accordion.hasClass('acs-accordion--exclusive')) {
                    $accordion.find('.acs-accordion__item--open').each(function() {
                        $(this).removeClass('acs-accordion__item--open')
                            .find('.acs-accordion__content').slideUp();
                    });
                }
                
                $content.slideDown(this.config.animationDuration);
                $item.addClass('acs-accordion__item--open');
            }
        },

        /**
         * Smooth scroll to element
         */
        smoothScrollTo: function($target, offset) {
            offset = offset || 50;
            $('html, body').animate({
                scrollTop: $target.offset().top - offset
            }, this.config.animationDuration);
        },

        // =========================================================================
        // Interactive Components
        // =========================================================================

        /**
         * Initialize interactive component enhancements
         */
        initInteractiveComponents: function() {
            this.initDropdowns();
            this.initToasts();
            this.initCopyToClipboard();
            this.initAutoSave();
        },

        /**
         * Initialize dropdown components
         */
        initDropdowns: function() {
            var self = this;

            $(document).on('click', '.acs-dropdown__trigger', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $dropdown = $(this).closest('.acs-dropdown');
                var isOpen = $dropdown.hasClass('acs-dropdown--open');

                // Close all other dropdowns
                $('.acs-dropdown--open').not($dropdown).removeClass('acs-dropdown--open');

                // Toggle this dropdown
                $dropdown.toggleClass('acs-dropdown--open', !isOpen);
            });

            // Close dropdowns when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.acs-dropdown').length) {
                    $('.acs-dropdown--open').removeClass('acs-dropdown--open');
                }
            });

            // Keyboard navigation for dropdowns
            $(document).on('keydown', '.acs-dropdown--open', function(e) {
                var $dropdown = $(this);
                var $items = $dropdown.find('.acs-dropdown__item');
                var $focused = $items.filter(':focus');
                var index = $items.index($focused);

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    $items.eq(Math.min(index + 1, $items.length - 1)).focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    $items.eq(Math.max(index - 1, 0)).focus();
                } else if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $focused.click();
                }
            });
        },

        /**
         * Initialize toast notifications
         */
        initToasts: function() {
            // Create toast container if it doesn't exist
            if ($('#acs-toast-container').length === 0) {
                $('body').append('<div id="acs-toast-container" class="acs-toast-container" aria-live="polite"></div>');
            }
        },

        /**
         * Show a toast notification
         */
        showToast: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;

            var icons = {
                success: 'dashicons-yes-alt',
                error: 'dashicons-warning',
                warning: 'dashicons-info',
                info: 'dashicons-info-outline'
            };

            var $toast = $('<div class="acs-toast acs-toast--' + type + '">' +
                '<span class="dashicons ' + icons[type] + '"></span>' +
                '<span class="acs-toast__message">' + message + '</span>' +
                '<button class="acs-toast__close" aria-label="Dismiss"><span class="dashicons dashicons-no-alt"></span></button>' +
                '</div>');

            $('#acs-toast-container').append($toast);

            // Animate in
            setTimeout(function() {
                $toast.addClass('acs-toast--visible');
            }, 10);

            // Auto dismiss
            var timeout = setTimeout(function() {
                $toast.removeClass('acs-toast--visible');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, duration);

            // Manual dismiss
            $toast.find('.acs-toast__close').on('click', function() {
                clearTimeout(timeout);
                $toast.removeClass('acs-toast--visible');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            });

            return $toast;
        },

        /**
         * Initialize copy to clipboard functionality
         */
        initCopyToClipboard: function() {
            var self = this;

            $(document).on('click', '[data-copy]', function() {
                var $btn = $(this);
                var text = $btn.data('copy');
                
                self.copyToClipboard(text).then(function() {
                    var originalText = $btn.text();
                    $btn.text('Copied!').addClass('acs-button--success');
                    
                    setTimeout(function() {
                        $btn.text(originalText).removeClass('acs-button--success');
                    }, 2000);
                    
                    self.showToast('Copied to clipboard', 'success', 2000);
                }).catch(function() {
                    self.showToast('Failed to copy', 'error');
                });
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text);
            }

            // Fallback for older browsers
            return new Promise(function(resolve, reject) {
                var $temp = $('<textarea>');
                $temp.val(text).css({
                    position: 'fixed',
                    left: '-9999px'
                }).appendTo('body').focus().select();

                try {
                    var success = document.execCommand('copy');
                    $temp.remove();
                    if (success) {
                        resolve();
                    } else {
                        reject();
                    }
                } catch (err) {
                    $temp.remove();
                    reject(err);
                }
            });
        },

        /**
         * Initialize auto-save functionality
         */
        initAutoSave: function() {
            var self = this;
            var saveTimeout = null;

            $(document).on('input change', 'form[data-autosave] input, form[data-autosave] textarea, form[data-autosave] select', function() {
                var $form = $(this).closest('form');
                
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    self.autoSaveForm($form);
                }, 2000);
            });
        },

        /**
         * Auto-save form data
         */
        autoSaveForm: function($form) {
            var formData = $form.serialize();
            var action = $form.data('autosave-action') || 'acs_autosave';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData + '&action=' + action + '&nonce=' + (window.acsAdmin ? acsAdmin.nonce : ''),
                success: function(response) {
                    if (response.success) {
                        ACSAdmin.Interactions.showToast('Auto-saved', 'success', 2000);
                    }
                }
            });
        },

        // =========================================================================
        // Progress Indicators
        // =========================================================================

        /**
         * Initialize progress indicators
         */
        initProgressIndicators: function() {
            // Circular progress animation
            $('.acs-progress--circular').each(function() {
                var $progress = $(this);
                var value = parseFloat($progress.data('value')) || 0;
                var max = parseFloat($progress.data('max')) || 100;
                
                ACSAdmin.Interactions.updateCircularProgress($progress, value, max);
            });
        },

        /**
         * Update circular progress indicator
         */
        updateCircularProgress: function($progress, value, max) {
            var percentage = (value / max) * 100;
            var $circle = $progress.find('.acs-progress__circle');
            var radius = parseFloat($circle.attr('r')) || 45;
            var circumference = 2 * Math.PI * radius;
            var offset = circumference - (percentage / 100) * circumference;

            $circle.css({
                'stroke-dasharray': circumference,
                'stroke-dashoffset': offset
            });

            $progress.find('.acs-progress__value').text(Math.round(percentage) + '%');
        },

        // =========================================================================
        // Tooltips
        // =========================================================================

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $(document).on('mouseenter focus', '[data-tooltip]', function() {
                var $trigger = $(this);
                var text = $trigger.data('tooltip');
                var position = $trigger.data('tooltip-position') || 'top';
                
                ACSAdmin.Interactions.showTooltip($trigger, text, position);
            }).on('mouseleave blur', '[data-tooltip]', function() {
                ACSAdmin.Interactions.hideTooltip();
            });
        },

        /**
         * Show tooltip
         */
        showTooltip: function($trigger, text, position) {
            this.hideTooltip();

            var $tooltip = $('<div class="acs-tooltip acs-tooltip--' + position + '">' + text + '</div>');
            $('body').append($tooltip);

            var triggerOffset = $trigger.offset();
            var triggerWidth = $trigger.outerWidth();
            var triggerHeight = $trigger.outerHeight();
            var tooltipWidth = $tooltip.outerWidth();
            var tooltipHeight = $tooltip.outerHeight();

            var top, left;

            switch (position) {
                case 'top':
                    top = triggerOffset.top - tooltipHeight - 8;
                    left = triggerOffset.left + (triggerWidth / 2) - (tooltipWidth / 2);
                    break;
                case 'bottom':
                    top = triggerOffset.top + triggerHeight + 8;
                    left = triggerOffset.left + (triggerWidth / 2) - (tooltipWidth / 2);
                    break;
                case 'left':
                    top = triggerOffset.top + (triggerHeight / 2) - (tooltipHeight / 2);
                    left = triggerOffset.left - tooltipWidth - 8;
                    break;
                case 'right':
                    top = triggerOffset.top + (triggerHeight / 2) - (tooltipHeight / 2);
                    left = triggerOffset.left + triggerWidth + 8;
                    break;
            }

            $tooltip.css({ top: top, left: left }).addClass('acs-tooltip--visible');
        },

        /**
         * Hide tooltip
         */
        hideTooltip: function() {
            $('.acs-tooltip').remove();
        },

        // =========================================================================
        // Context Menus
        // =========================================================================

        /**
         * Initialize context menus
         */
        initContextMenus: function() {
            var self = this;

            $(document).on('contextmenu', '[data-context-menu]', function(e) {
                e.preventDefault();
                var menuId = $(this).data('context-menu');
                self.showContextMenu(menuId, e.pageX, e.pageY);
            });

            // Hide context menu on click outside
            $(document).on('click', function() {
                self.hideContextMenu();
            });
        },

        /**
         * Show context menu
         */
        showContextMenu: function(menuId, x, y) {
            this.hideContextMenu();

            var $menu = $('#' + menuId);
            if ($menu.length === 0) return;

            $menu.css({
                top: y + 'px',
                left: x + 'px'
            }).addClass('acs-context-menu--visible');

            // Adjust position if menu goes off screen
            var menuWidth = $menu.outerWidth();
            var menuHeight = $menu.outerHeight();
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();

            if (x + menuWidth > windowWidth) {
                $menu.css('left', (x - menuWidth) + 'px');
            }
            if (y + menuHeight > windowHeight) {
                $menu.css('top', (y - menuHeight) + 'px');
            }
        },

        /**
         * Hide context menu
         */
        hideContextMenu: function() {
            $('.acs-context-menu--visible').removeClass('acs-context-menu--visible');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ACSAdmin.Interactions.init();
    });

})(jQuery, window, document);
