/**
 * ACS Unified Admin Interface JavaScript
 * 
 * Modern, modular JavaScript for enhanced user interactions
 * with progressive enhancement and accessibility support
 */

(function($) {
    'use strict';

    // Main ACS Admin object
    window.ACSAdmin = {
        // Configuration
        config: {
            ajaxUrl: acsAdmin.ajaxUrl || '',
            nonce: acsAdmin.nonce || '',
            strings: acsAdmin.strings || {}
        },

        // Initialize all components
        init: function() {
            this.initNavigation();
            this.initResponsive();
            this.initComponents();
            this.initEventListeners();
            this.initAccessibility();
            this.initWordPressIntegration();
        },

        // Initialize WordPress-specific integrations
        initWordPressIntegration: function() {
            this.initAdminNotices();
            this.initFormValidation();
            this.initAjaxHandling();
        },

        // Initialize WordPress admin notices
        initAdminNotices: function() {
            // Handle dismissible notices using WordPress patterns
            $(document).on('click', '.notice-dismiss', function(e) {
                var $notice = $(this).closest('.notice');
                var noticeId = $notice.data('notice-id');
                
                if (noticeId) {
                    // Send AJAX request to dismiss notice
                    $.post(acsAdmin.config.ajaxUrl, {
                        action: 'acs_dismiss_notice',
                        notice_id: noticeId,
                        nonce: acsAdmin.config.nonce
                    });
                }
                
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto-dismiss temporary notices
            $('.notice.is-dismissible[data-auto-dismiss]').each(function() {
                var $notice = $(this);
                var delay = parseInt($notice.data('auto-dismiss')) || 5000;
                
                setTimeout(function() {
                    $notice.find('.notice-dismiss').trigger('click');
                }, delay);
            });
        },

        // Initialize WordPress form validation
        initFormValidation: function() {
            // WordPress-compatible form validation
            $('form[data-acs-validate]').on('submit', function(e) {
                var $form = $(this);
                var isValid = true;
                
                // Clear previous validation messages
                $form.find('.form-invalid').hide().empty();
                
                // Validate required fields
                $form.find('[required]').each(function() {
                    var $field = $(this);
                    var value = $field.val().trim();
                    
                    if (!value) {
                        isValid = false;
                        ACSAdmin.showFieldError($field, acsAdmin.strings.required || 'This field is required.');
                    }
                });
                
                // Validate email fields
                $form.find('input[type="email"]').each(function() {
                    var $field = $(this);
                    var value = $field.val().trim();
                    
                    if (value && !ACSAdmin.isValidEmail(value)) {
                        isValid = false;
                        ACSAdmin.showFieldError($field, 'Please enter a valid email address.');
                    }
                });
                
                // Validate URL fields
                $form.find('input[type="url"]').each(function() {
                    var $field = $(this);
                    var value = $field.val().trim();
                    
                    if (value && !ACSAdmin.isValidUrl(value)) {
                        isValid = false;
                        ACSAdmin.showFieldError($field, 'Please enter a valid URL.');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    
                    // Focus first invalid field
                    var $firstError = $form.find('.form-invalid:visible').first().prev().find('input, select, textarea').first();
                    if ($firstError.length) {
                        $firstError.focus();
                    }
                }
            });
        },

        // Show field validation error
        showFieldError: function($field, message) {
            var $errorContainer = $field.closest('.form-field, .acs-form-field').find('.form-invalid, .acs-form-validation');
            $errorContainer.html('<p>' + message + '</p>').show();
            $field.addClass('error').attr('aria-invalid', 'true');
        },

        // Email validation
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        // URL validation
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        // Initialize WordPress AJAX handling
        initAjaxHandling: function() {
            // Set up WordPress AJAX defaults
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    // Add nonce to all AJAX requests
                    if (settings.data && settings.data.indexOf('action=acs_') !== -1) {
                        if (settings.data.indexOf('nonce=') === -1) {
                            settings.data += '&nonce=' + acsAdmin.config.nonce;
                        }
                    }
                }
            });
            
            // Global AJAX error handler
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                if (settings.url === acsAdmin.config.ajaxUrl) {
                    console.error('ACS AJAX Error:', thrownError);
                    
                    // Show user-friendly error message
                    ACSAdmin.showNotice(
                        acsAdmin.strings.error || 'An error occurred. Please try again.',
                        'error'
                    );
                }
            });
        },

        // Show WordPress-style notice
        showNotice: function(message, type, dismissible) {
            type = type || 'info';
            dismissible = dismissible !== false;
            
            var classes = ['notice', 'notice-' + type];
            if (dismissible) {
                classes.push('is-dismissible');
            }
            
            var $notice = $('<div class="' + classes.join(' ') + '"><p>' + message + '</p></div>');
            
            if (dismissible) {
                $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
            }
            
            // Insert notice at the top of the page
            if ($('.wrap h1').length) {
                $notice.insertAfter('.wrap h1');
            } else {
                $notice.prependTo('.wrap');
            }
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success' && dismissible) {
                setTimeout(function() {
                    $notice.find('.notice-dismiss').trigger('click');
                }, 5000);
            }
        },

        // Initialize navigation system
        initNavigation: function() {
            this.highlightActiveMenuItem();
            this.initBreadcrumbNavigation();
            this.initNavigationHelpers();
        },

        // Highlight active menu item based on current page
        highlightActiveMenuItem: function() {
            // Get current page from URL
            var urlParams = new URLSearchParams(window.location.search);
            var currentPage = urlParams.get('page');
            
            if (!currentPage) return;
            
            // Find and highlight the active menu item
            $('#adminmenu a').each(function() {
                var $link = $(this);
                var href = $link.attr('href');
                
                if (href && href.indexOf('page=' + currentPage) !== -1) {
                    // Add active class to the menu item
                    $link.closest('li').addClass('current');
                    $link.addClass('current');
                    $link.parent().addClass('current');
                    
                    // Expand parent menu if it's a submenu item
                    var $parentMenu = $link.closest('.wp-submenu').prev('a');
                    if ($parentMenu.length) {
                        $parentMenu.addClass('wp-has-current-submenu wp-menu-open');
                        $parentMenu.parent().addClass('wp-has-current-submenu wp-menu-open');
                    }
                    
                    // Add ARIA current attribute for accessibility
                    $link.attr('aria-current', 'page');
                }
            });
            
            // Highlight ACS submenu items specifically
            $('#adminmenu .wp-submenu a').each(function() {
                var $link = $(this);
                var href = $link.attr('href');
                
                if (href && href.indexOf('page=' + currentPage) !== -1) {
                    $link.addClass('acs-menu-active');
                    $link.parent().addClass('acs-menu-active');
                }
            });
        },

        // Initialize breadcrumb navigation
        initBreadcrumbNavigation: function() {
            var $breadcrumbs = $('.acs-breadcrumbs');
            if ($breadcrumbs.length === 0) return;
            
            // Add keyboard navigation to breadcrumbs
            $breadcrumbs.find('a').on('keydown', function(e) {
                var $links = $breadcrumbs.find('a');
                var currentIndex = $links.index(this);
                
                // Arrow key navigation
                if (e.key === 'ArrowRight' && currentIndex < $links.length - 1) {
                    e.preventDefault();
                    $links.eq(currentIndex + 1).focus();
                } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                    e.preventDefault();
                    $links.eq(currentIndex - 1).focus();
                }
            });
        },

        // Initialize navigation helpers
        initNavigationHelpers: function() {
            // WordPress admin keyboard shortcuts
            this.initKeyboardShortcuts();
            
            // WordPress postbox functionality
            if (typeof postboxes !== 'undefined') {
                postboxes.add_postbox_toggles(pagenow);
            }
            
            // WordPress screen options
            this.initScreenOptions();
        },

        // Initialize WordPress-compatible keyboard shortcuts
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Alt + D for Dashboard
                if (e.altKey && e.key === 'd') {
                    e.preventDefault();
                    window.location.href = acsAdmin.config.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=acs-dashboard');
                }
                
                // Alt + G for Generate
                if (e.altKey && e.key === 'g') {
                    e.preventDefault();
                    window.location.href = acsAdmin.config.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=acs-generate');
                }
                
                // Alt + A for Analytics
                if (e.altKey && e.key === 'a') {
                    e.preventDefault();
                    window.location.href = acsAdmin.config.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=acs-analytics');
                }
                
                // Alt + S for Settings
                if (e.altKey && e.key === 's') {
                    e.preventDefault();
                    window.location.href = acsAdmin.config.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=acs-settings');
                }
            });
        },

        // Initialize WordPress screen options
        initScreenOptions: function() {
            // Add screen options for ACS pages
            if (typeof screen !== 'undefined' && screen.id && screen.id.indexOf('acs-') !== -1) {
                // Add columns screen option for list tables
                $('.acs-table').each(function() {
                    var $table = $(this);
                    var columns = $table.find('thead th').length;
                    
                    // Add to screen options if available
                    if ($('#screen-options-wrap').length) {
                        // WordPress will handle this automatically for wp-list-table
                    }
                });
            }
        },

        // Initialize navigation helpers
        initNavigationHelpers: function() {
            // Add back button functionality
            this.initBackButton();
            
            // Add navigation shortcuts
            this.initNavigationShortcuts();
            
            // Add page transition indicators
            this.initPageTransitions();
        },

        // Initialize responsive features
        initResponsive: function() {
            this.initMobileNavigation();
            this.initCollapsibleSections();
            this.initTouchInteractions();
            this.handleViewportChanges();
        },

        // Initialize mobile navigation
        initMobileNavigation: function() {
            var self = this;
            
            // Create mobile navigation if it doesn't exist
            if ($('.acs-mobile-nav').length === 0 && $(window).width() < 640) {
                this.createMobileNavigation();
            }
            
            // Mobile menu toggle
            $(document).on('click', '.acs-mobile-menu-toggle', function(e) {
                e.preventDefault();
                self.toggleMobileNav();
            });
            
            // Close mobile nav on backdrop click
            $(document).on('click', '.acs-mobile-nav', function(e) {
                if ($(e.target).hasClass('acs-mobile-nav')) {
                    self.closeMobileNav();
                }
            });
            
            // Close mobile nav button
            $(document).on('click', '.acs-mobile-nav__close', function(e) {
                e.preventDefault();
                self.closeMobileNav();
            });
            
            // Close mobile nav on link click
            $(document).on('click', '.acs-mobile-nav__link', function() {
                self.closeMobileNav();
            });
            
            // Handle escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('.acs-mobile-nav').hasClass('active')) {
                    self.closeMobileNav();
                }
            });
        },

        // Create mobile navigation structure
        createMobileNavigation: function() {
            var menuItems = this.getMobileMenuItems();
            var currentPage = new URLSearchParams(window.location.search).get('page');
            
            var navHtml = '<div class="acs-mobile-nav">' +
                '<div class="acs-mobile-nav__panel">' +
                '<div class="acs-mobile-nav__header">' +
                '<h3 class="acs-mobile-nav__title">AI Content Studio</h3>' +
                '<button class="acs-mobile-nav__close" aria-label="Close menu">' +
                '<span class="dashicons dashicons-no-alt"></span>' +
                '</button>' +
                '</div>' +
                '<ul class="acs-mobile-nav__menu">';
            
            menuItems.forEach(function(item) {
                var isActive = currentPage === item.page ? ' active' : '';
                navHtml += '<li class="acs-mobile-nav__item">' +
                    '<a href="' + item.url + '" class="acs-mobile-nav__link' + isActive + '">' +
                    '<span class="acs-mobile-nav__icon"><span class="dashicons ' + item.icon + '"></span></span>' +
                    '<span>' + item.label + '</span>' +
                    '</a>' +
                    '</li>';
            });
            
            navHtml += '</ul></div></div>';
            
            // Add mobile nav to body
            $('body').append(navHtml);
            
            // Add mobile menu toggle button
            if ($('.acs-mobile-menu-toggle').length === 0) {
                $('body').append(
                    '<button class="acs-mobile-menu-toggle" aria-label="Open menu">' +
                    '<span class="dashicons dashicons-menu"></span>' +
                    '</button>'
                );
            }
        },

        // Get mobile menu items
        getMobileMenuItems: function() {
            return [
                { label: 'Dashboard', page: 'acs-dashboard', url: 'admin.php?page=acs-dashboard', icon: 'dashicons-dashboard' },
                { label: 'Generate Content', page: 'acs-generate', url: 'admin.php?page=acs-generate', icon: 'dashicons-edit' },
                { label: 'SEO Optimizer', page: 'acs-seo-optimizer', url: 'admin.php?page=acs-seo-optimizer', icon: 'dashicons-chart-line' },
                { label: 'Analytics', page: 'acs-analytics', url: 'admin.php?page=acs-analytics', icon: 'dashicons-chart-bar' },
                { label: 'Generation Logs', page: 'acs-logs', url: 'admin.php?page=acs-logs', icon: 'dashicons-list-view' },
                { label: 'Settings', page: 'acs-settings', url: 'admin.php?page=acs-settings', icon: 'dashicons-admin-settings' }
            ];
        },

        // Toggle mobile navigation
        toggleMobileNav: function() {
            var $nav = $('.acs-mobile-nav');
            if ($nav.hasClass('active')) {
                this.closeMobileNav();
            } else {
                this.openMobileNav();
            }
        },

        // Open mobile navigation
        openMobileNav: function() {
            $('.acs-mobile-nav').addClass('active');
            $('body').addClass('acs-mobile-nav-open');
            
            // Prevent body scroll
            $('body').css('overflow', 'hidden');
            
            // Focus first menu item
            setTimeout(function() {
                $('.acs-mobile-nav__link').first().focus();
            }, 300);
        },

        // Close mobile navigation
        closeMobileNav: function() {
            $('.acs-mobile-nav').removeClass('active');
            $('body').removeClass('acs-mobile-nav-open');
            
            // Restore body scroll
            $('body').css('overflow', '');
            
            // Return focus to toggle button
            $('.acs-mobile-menu-toggle').focus();
        },

        // Initialize collapsible sections
        initCollapsibleSections: function() {
            var self = this;
            
            // Create collapsible sections on mobile
            if ($(window).width() < 768) {
                this.makeCardsSectionsCollapsible();
            }
            
            // Handle collapsible toggle
            $(document).on('click', '.acs-collapsible__header', function() {
                var $collapsible = $(this).closest('.acs-collapsible');
                self.toggleCollapsible($collapsible);
            });
        },

        // Make card sections collapsible on mobile
        makeCardsSectionsCollapsible: function() {
            $('.acs-card[data-collapsible="true"]').each(function() {
                var $card = $(this);
                if ($card.hasClass('acs-collapsible')) return;
                
                var $header = $card.find('.acs-card__header');
                var $content = $card.find('.acs-card__content');
                
                if ($header.length && $content.length) {
                    $header.addClass('acs-collapsible__header');
                    $header.append('<span class="acs-collapsible__icon dashicons dashicons-arrow-down"></span>');
                    
                    $content.wrap('<div class="acs-collapsible__content"><div class="acs-collapsible__body"></div></div>');
                    
                    $card.addClass('acs-collapsible expanded');
                }
            });
        },

        // Toggle collapsible section
        toggleCollapsible: function($collapsible) {
            $collapsible.toggleClass('expanded');
            
            var $content = $collapsible.find('.acs-collapsible__content');
            var isExpanded = $collapsible.hasClass('expanded');
            
            // Update ARIA attributes
            $collapsible.find('.acs-collapsible__header').attr('aria-expanded', isExpanded);
            
            // Announce to screen readers
            var announcement = isExpanded ? 'Section expanded' : 'Section collapsed';
            this.announceToScreenReader(announcement);
        },

        // Initialize touch interactions
        initTouchInteractions: function() {
            // Add touch-friendly swipe gestures for mobile nav
            if ('ontouchstart' in window) {
                this.initSwipeGestures();
            }
            
            // Enhance tap targets
            this.enhanceTapTargets();
            
            // Add touch feedback
            this.addTouchFeedback();
        },

        // Initialize swipe gestures
        initSwipeGestures: function() {
            var startX = 0;
            var startY = 0;
            var self = this;
            
            $(document).on('touchstart', function(e) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            });
            
            $(document).on('touchend', function(e) {
                if (!e.changedTouches || !e.changedTouches[0]) return;
                
                var endX = e.changedTouches[0].clientX;
                var endY = e.changedTouches[0].clientY;
                
                var diffX = endX - startX;
                var diffY = endY - startY;
                
                // Horizontal swipe detection
                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                    if (diffX > 0 && startX < 50) {
                        // Swipe right from left edge - open menu
                        self.openMobileNav();
                    } else if (diffX < 0 && $('.acs-mobile-nav').hasClass('active')) {
                        // Swipe left - close menu
                        self.closeMobileNav();
                    }
                }
            });
        },

        // Enhance tap targets for touch devices
        enhanceTapTargets: function() {
            if (!('ontouchstart' in window)) return;
            
            // Add touch-target class to interactive elements
            $('.acs-button, .acs-breadcrumbs__link, .acs-nav-helper, .acs-table__header--sortable').addClass('acs-touch-target');
        },

        // Add touch feedback
        addTouchFeedback: function() {
            if (!('ontouchstart' in window)) return;
            
            $(document).on('touchstart', '.acs-button, .acs-card--action, .acs-mobile-nav__link', function() {
                $(this).addClass('acs-touch-active');
            });
            
            $(document).on('touchend touchcancel', '.acs-button, .acs-card--action, .acs-mobile-nav__link', function() {
                var $el = $(this);
                setTimeout(function() {
                    $el.removeClass('acs-touch-active');
                }, 150);
            });
        },

        // Handle viewport changes
        handleViewportChanges: function() {
            var self = this;
            var currentWidth = $(window).width();
            
            $(window).on('resize', this.debounce(function() {
                var newWidth = $(window).width();
                
                // Detect breakpoint changes
                if ((currentWidth < 640 && newWidth >= 640) || (currentWidth >= 640 && newWidth < 640)) {
                    self.handleBreakpointChange(newWidth);
                }
                
                currentWidth = newWidth;
            }, 250));
            
            // Handle orientation change
            $(window).on('orientationchange', function() {
                setTimeout(function() {
                    self.handleOrientationChange();
                }, 100);
            });
        },

        // Handle breakpoint changes
        handleBreakpointChange: function(width) {
            if (width < 640) {
                // Switched to mobile
                this.createMobileNavigation();
                this.makeCardsSectionsCollapsible();
            } else {
                // Switched to desktop
                this.closeMobileNav();
                $('.acs-mobile-nav, .acs-mobile-menu-toggle').remove();
            }
        },

        // Handle orientation changes
        handleOrientationChange: function() {
            // Close mobile nav on orientation change
            if ($('.acs-mobile-nav').hasClass('active')) {
                this.closeMobileNav();
            }
            
            // Recalculate collapsible heights
            $('.acs-collapsible.expanded').each(function() {
                var $content = $(this).find('.acs-collapsible__content');
                var height = $content.find('.acs-collapsible__body').outerHeight();
                $content.css('max-height', height + 'px');
            });
        },

        // Announce to screen reader
        announceToScreenReader: function(message) {
            var $announcement = $('#acs-sr-announcement');
            if ($announcement.length === 0) {
                $announcement = $('<div id="acs-sr-announcement" class="acs-sr-only" role="status" aria-live="polite" aria-atomic="true"></div>');
                $('body').append($announcement);
            }
            
            $announcement.text(message);
            
            // Clear after announcement
            setTimeout(function() {
                $announcement.text('');
            }, 1000);
        },

        // Initialize back button
        initBackButton: function() {
            $(document).on('click', '[data-acs-back]', function(e) {
                e.preventDefault();
                
                var backUrl = $(this).data('acs-back');
                if (backUrl) {
                    window.location.href = backUrl;
                } else if (window.history.length > 1) {
                    window.history.back();
                } else {
                    // Fallback to dashboard
                    window.location.href = 'admin.php?page=acs-dashboard';
                }
            });
        },

        // Initialize navigation keyboard shortcuts
        initNavigationShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Only trigger if not in an input field
                if ($(e.target).is('input, textarea, select')) return;
                
                // Alt + D: Go to Dashboard
                if (e.altKey && e.key === 'd') {
                    e.preventDefault();
                    window.location.href = 'admin.php?page=acs-dashboard';
                }
                
                // Alt + G: Go to Generate
                if (e.altKey && e.key === 'g') {
                    e.preventDefault();
                    window.location.href = 'admin.php?page=acs-generate';
                }
                
                // Alt + S: Go to Settings
                if (e.altKey && e.key === 's') {
                    e.preventDefault();
                    window.location.href = 'admin.php?page=acs-settings';
                }
                
                // Alt + A: Go to Analytics
                if (e.altKey && e.key === 'a') {
                    e.preventDefault();
                    window.location.href = 'admin.php?page=acs-analytics';
                }
            });
        },

        // Initialize page transition indicators
        initPageTransitions: function() {
            // Add loading indicator for page navigation
            $(document).on('click', 'a[href*="page=acs-"]', function(e) {
                var $link = $(this);
                
                // Skip if it's an external link or has special handling
                if ($link.attr('target') === '_blank' || $link.data('no-transition')) {
                    return;
                }
                
                // Show loading indicator
                ACSAdmin.showPageTransition();
            });
            
            // Handle browser back/forward buttons
            $(window).on('popstate', function() {
                ACSAdmin.showPageTransition();
            });
        },

        // Show page transition loading indicator
        showPageTransition: function() {
            if ($('#acs-page-transition').length === 0) {
                $('body').append('<div id="acs-page-transition" class="acs-page-transition"><div class="acs-spinner"></div></div>');
            }
            $('#acs-page-transition').fadeIn(150);
        },

        // Hide page transition loading indicator
        hidePageTransition: function() {
            $('#acs-page-transition').fadeOut(150, function() {
                $(this).remove();
            });
        },

        // Initialize UI components
        initComponents: function() {
            this.initCards();
            this.initTables();
            this.initButtons();
            this.initAlerts();
            this.initProgressBars();
            this.initModals();
            this.initForms();
        },

        // Initialize event listeners
        initEventListeners: function() {
            $(document).on('click', '.acs-button', this.handleButtonClick.bind(this));
            $(document).on('click', '.acs-alert__dismiss', this.handleAlertDismiss.bind(this));
            $(document).on('change', '.acs-table__select-all', this.handleSelectAll.bind(this));
            $(document).on('change', '.acs-table__select-item', this.handleSelectItem.bind(this));
            $(document).on('click', '.acs-table__header--sortable', this.handleTableSort.bind(this));
            $(document).on('input', '.acs-table__search', this.debounce(this.handleTableSearch.bind(this), 300));
        },

        // Initialize accessibility features
        initAccessibility: function() {
            this.initKeyboardNavigation();
            this.initAriaLiveRegions();
            this.initFocusManagement();
            this.initScreenReaderSupport();
            this.initSemanticStructure();
            this.initWCAGCompliance();
        },

        // Initialize focus management
        initFocusManagement: function() {
            // Trap focus in modals
            $(document).on('keydown', '.acs-modal', function(e) {
                if (e.key === 'Escape') {
                    ACSAdmin.closeModal($(this));
                }
                
                if (e.key === 'Tab') {
                    ACSAdmin.trapFocus(e, $(this));
                }
            });
        },

        // Initialize card components
        initCards: function() {
            $('.acs-card--action').on('click', function(e) {
                if (!$(e.target).closest('.acs-card__actions').length) {
                    var href = $(this).data('href');
                    if (href) {
                        window.location.href = href;
                    }
                }
            });
        },

        // Initialize table components
        initTables: function() {
            // Initialize sortable tables
            $('.acs-table').each(function() {
                var $table = $(this);
                $table.data('sort-column', null);
                $table.data('sort-direction', 'asc');
            });

            // Initialize bulk actions
            $('.acs-table__bulk-apply').on('click', this.handleBulkAction.bind(this));
        },

        // Initialize button components
        initButtons: function() {
            // Add loading state support
            $('.acs-button[data-loading]').each(function() {
                var $btn = $(this);
                $btn.data('original-text', $btn.find('.acs-button__text').text());
            });
        },

        // Initialize alert components
        initAlerts: function() {
            // Auto-dismiss alerts after timeout
            $('.acs-alert[data-timeout]').each(function() {
                var $alert = $(this);
                var timeout = parseInt($alert.data('timeout'), 10);
                if (timeout > 0) {
                    setTimeout(function() {
                        ACSAdmin.dismissAlert($alert);
                    }, timeout);
                }
            });
        },

        // Initialize progress bars
        initProgressBars: function() {
            $('.acs-progress').each(function() {
                var $progress = $(this);
                var value = parseFloat($progress.data('value') || 0);
                var max = parseFloat($progress.data('max') || 100);
                
                ACSAdmin.updateProgress($progress, value, max);
            });
        },

        // Initialize modal components
        initModals: function() {
            // Close modal on backdrop click
            $(document).on('click', '.acs-modal__backdrop', function(e) {
                if (e.target === this) {
                    ACSAdmin.closeModal($(this).closest('.acs-modal'));
                }
            });

            // Close modal on close button click
            $(document).on('click', '.acs-modal__close', function() {
                ACSAdmin.closeModal($(this).closest('.acs-modal'));
            });
        },

        // Initialize form components
        initForms: function() {
            // Initialize form validation
            this.initFormValidation();
            
            // Initialize file inputs
            this.initFileInputs();
            
            // Initialize form interactions
            this.initFormInteractions();
        },

        // Initialize form validation
        initFormValidation: function() {
            var self = this;
            
            // Real-time validation on input
            $(document).on('input blur', '.acs-form-input, .acs-form-textarea, .acs-form-select', function() {
                self.validateField($(this));
            });
            
            // Validation on form submit
            $(document).on('submit', 'form[data-acs-validate]', function(e) {
                if (!self.validateForm($(this))) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Custom validation rules
            this.setupValidationRules();
        },

        // Setup validation rules
        setupValidationRules: function() {
            this.validationRules = {
                required: function(value) {
                    return value.trim() !== '';
                },
                email: function(value) {
                    if (!value) return true; // Allow empty unless required
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return emailRegex.test(value);
                },
                url: function(value) {
                    if (!value) return true; // Allow empty unless required
                    try {
                        new URL(value);
                        return true;
                    } catch {
                        return false;
                    }
                },
                minLength: function(value, min) {
                    return value.length >= parseInt(min, 10);
                },
                maxLength: function(value, max) {
                    return value.length <= parseInt(max, 10);
                },
                number: function(value) {
                    if (!value) return true; // Allow empty unless required
                    return !isNaN(parseFloat(value)) && isFinite(value);
                },
                min: function(value, min) {
                    if (!value) return true; // Allow empty unless required
                    return parseFloat(value) >= parseFloat(min);
                },
                max: function(value, max) {
                    if (!value) return true; // Allow empty unless required
                    return parseFloat(value) <= parseFloat(max);
                }
            };
        },

        // Validate individual field
        validateField: function($field) {
            var $formField = $field.closest('.acs-form-field');
            var value = $field.val();
            var isValid = true;
            var errorMessage = '';
            
            // Clear previous validation state
            $formField.removeClass('acs-form-field--error acs-form-field--success');
            
            // Check required
            if ($field.prop('required') && !this.validationRules.required(value)) {
                isValid = false;
                errorMessage = 'This field is required.';
            }
            
            // Check other validation rules
            if (isValid && value) {
                var rules = $field.data('validate') || '';
                var ruleArray = rules.split('|');
                
                for (var i = 0; i < ruleArray.length; i++) {
                    var rule = ruleArray[i].trim();
                    if (!rule) continue;
                    
                    var ruleParts = rule.split(':');
                    var ruleName = ruleParts[0];
                    var ruleParam = ruleParts[1];
                    
                    if (this.validationRules[ruleName]) {
                        if (!this.validationRules[ruleName](value, ruleParam)) {
                            isValid = false;
                            errorMessage = this.getValidationMessage(ruleName, ruleParam);
                            break;
                        }
                    }
                }
            }
            
            // Update field state
            if (isValid) {
                $formField.addClass('acs-form-field--success');
            } else {
                $formField.addClass('acs-form-field--error');
                $formField.find('.acs-form-validation').text(errorMessage);
            }
            
            return isValid;
        },

        // Get validation error message
        getValidationMessage: function(rule, param) {
            var messages = {
                required: 'This field is required.',
                email: 'Please enter a valid email address.',
                url: 'Please enter a valid URL.',
                minLength: 'Must be at least ' + param + ' characters long.',
                maxLength: 'Must be no more than ' + param + ' characters long.',
                number: 'Please enter a valid number.',
                min: 'Value must be at least ' + param + '.',
                max: 'Value must be no more than ' + param + '.'
            };
            
            return messages[rule] || 'Invalid value.';
        },

        // Validate entire form
        validateForm: function($form) {
            var isValid = true;
            var self = this;
            
            $form.find('.acs-form-input, .acs-form-textarea, .acs-form-select').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });
            
            // Focus first invalid field
            if (!isValid) {
                var $firstError = $form.find('.acs-form-field--error').first().find('.acs-form-input, .acs-form-textarea, .acs-form-select');
                if ($firstError.length) {
                    $firstError.focus();
                }
            }
            
            return isValid;
        },

        // Initialize file inputs
        initFileInputs: function() {
            $(document).on('change', '.acs-form-file', function() {
                var $input = $(this);
                var $label = $input.siblings('.acs-file-label');
                var $fileName = $label.find('.acs-file-name');
                
                if ($input[0].files && $input[0].files.length > 0) {
                    var fileName = $input[0].files[0].name;
                    $fileName.text(fileName);
                } else {
                    $fileName.text('No file chosen');
                }
            });
        },

        // Initialize form interactions
        initFormInteractions: function() {
            // Auto-resize textareas
            $(document).on('input', '.acs-form-textarea[data-auto-resize]', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            // Character counter
            $(document).on('input', '.acs-form-input[data-max-length], .acs-form-textarea[data-max-length]', function() {
                var $field = $(this);
                var maxLength = parseInt($field.data('max-length'), 10);
                var currentLength = $field.val().length;
                var $counter = $field.siblings('.acs-character-counter');
                
                if ($counter.length === 0) {
                    $counter = $('<div class="acs-character-counter"></div>');
                    $field.after($counter);
                }
                
                $counter.text(currentLength + ' / ' + maxLength);
                
                if (currentLength > maxLength) {
                    $counter.addClass('acs-character-counter--over');
                } else {
                    $counter.removeClass('acs-character-counter--over');
                }
            });
            
            // Password visibility toggle
            $(document).on('click', '.acs-password-toggle', function() {
                var $toggle = $(this);
                var $input = $toggle.siblings('.acs-form-input[type="password"], .acs-form-input[type="text"]');
                
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $toggle.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
                } else {
                    $input.attr('type', 'password');
                    $toggle.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
                }
            });
        },

        // Handle button clicks
        handleButtonClick: function(e) {
            var $btn = $(e.currentTarget);
            
            // Prevent double clicks
            if ($btn.hasClass('acs-button--loading') || $btn.prop('disabled')) {
                e.preventDefault();
                return false;
            }

            // Handle AJAX buttons
            if ($btn.data('ajax-action')) {
                e.preventDefault();
                this.handleAjaxButton($btn);
            }

            // Handle confirmation buttons
            if ($btn.data('confirm')) {
                if (!confirm($btn.data('confirm'))) {
                    e.preventDefault();
                    return false;
                }
            }
        },

        // Handle AJAX button actions
        handleAjaxButton: function($btn) {
            var action = $btn.data('ajax-action');
            var data = $btn.data('ajax-data') || {};
            
            this.setButtonLoading($btn, true);
            
            var ajaxData = $.extend({
                action: action,
                nonce: this.config.nonce
            }, data);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    ACSAdmin.setButtonLoading($btn, false);
                    
                    if (response.success) {
                        ACSAdmin.showNotification(response.data.message || ACSAdmin.config.strings.success, 'success');
                        
                        // Handle custom success callback
                        var callback = $btn.data('success-callback');
                        if (callback && typeof window[callback] === 'function') {
                            window[callback](response.data, $btn);
                        }
                    } else {
                        ACSAdmin.showNotification(response.data || ACSAdmin.config.strings.error, 'error');
                    }
                },
                error: function() {
                    ACSAdmin.setButtonLoading($btn, false);
                    ACSAdmin.showNotification(ACSAdmin.config.strings.error, 'error');
                }
            });
        },

        // Set button loading state
        setButtonLoading: function($btn, loading) {
            if (loading) {
                $btn.addClass('acs-button--loading').prop('disabled', true);
                var loadingText = $btn.data('loading-text');
                if (loadingText) {
                    $btn.find('.acs-button__text').text(loadingText);
                }
            } else {
                $btn.removeClass('acs-button--loading').prop('disabled', false);
                var originalText = $btn.data('original-text');
                if (originalText) {
                    $btn.find('.acs-button__text').text(originalText);
                }
            }
        },

        // Handle alert dismissal
        handleAlertDismiss: function(e) {
            e.preventDefault();
            var $alert = $(e.currentTarget).closest('.acs-alert');
            this.dismissAlert($alert);
        },

        // Dismiss alert with animation
        dismissAlert: function($alert) {
            $alert.fadeOut(300, function() {
                $(this).remove();
            });
        },

        // Handle select all checkbox
        handleSelectAll: function(e) {
            var $checkbox = $(e.currentTarget);
            var $table = $checkbox.closest('.acs-table');
            var checked = $checkbox.prop('checked');
            
            $table.find('.acs-table__select-item').prop('checked', checked);
            this.updateBulkActionState($table);
        },

        // Handle individual item selection
        handleSelectItem: function(e) {
            var $checkbox = $(e.currentTarget);
            var $table = $checkbox.closest('.acs-table');
            
            // Update select all checkbox state
            var $selectAll = $table.find('.acs-table__select-all');
            var $items = $table.find('.acs-table__select-item');
            var checkedItems = $items.filter(':checked').length;
            
            $selectAll.prop('checked', checkedItems === $items.length);
            $selectAll.prop('indeterminate', checkedItems > 0 && checkedItems < $items.length);
            
            this.updateBulkActionState($table);
        },

        // Update bulk action button state
        updateBulkActionState: function($table) {
            var $bulkActions = $table.closest('.acs-table-wrapper').find('.acs-table__bulk-actions');
            var checkedItems = $table.find('.acs-table__select-item:checked').length;
            
            $bulkActions.find('.acs-table__bulk-apply').prop('disabled', checkedItems === 0);
            
            var countText = checkedItems + ' ' + (checkedItems === 1 ? 'item' : 'items') + ' selected';
            $bulkActions.find('.acs-selected-count').text(countText);
        },

        // Handle table sorting
        handleTableSort: function(e) {
            var $header = $(e.currentTarget);
            var $table = $header.closest('.acs-table');
            var column = $header.data('key');
            
            if (!column) return;
            
            var currentColumn = $table.data('sort-column');
            var currentDirection = $table.data('sort-direction');
            var newDirection = 'asc';
            
            if (currentColumn === column && currentDirection === 'asc') {
                newDirection = 'desc';
            }
            
            // Update visual indicators
            $table.find('.acs-table__header').removeClass('acs-table__header--sorted-asc acs-table__header--sorted-desc');
            $header.addClass('acs-table__header--sorted-' + newDirection);
            
            // Store sort state
            $table.data('sort-column', column);
            $table.data('sort-direction', newDirection);
            
            // Perform sort
            this.sortTable($table, column, newDirection);
        },

        // Sort table data
        sortTable: function($table, column, direction) {
            var $tbody = $table.find('tbody');
            var $rows = $tbody.find('tr').get();
            
            $rows.sort(function(a, b) {
                var aVal = $(a).find('[data-sort-value]').data('sort-value') || $(a).find('td').eq(column).text();
                var bVal = $(b).find('[data-sort-value]').data('sort-value') || $(b).find('td').eq(column).text();
                
                // Try to parse as numbers
                var aNum = parseFloat(aVal);
                var bNum = parseFloat(bVal);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return direction === 'asc' ? aNum - bNum : bNum - aNum;
                }
                
                // String comparison
                aVal = aVal.toString().toLowerCase();
                bVal = bVal.toString().toLowerCase();
                
                if (direction === 'asc') {
                    return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
                } else {
                    return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
                }
            });
            
            $.each($rows, function(index, row) {
                $tbody.append(row);
            });
        },

        // Handle table search
        handleTableSearch: function(e) {
            var $input = $(e.currentTarget);
            var $table = $input.closest('.acs-table-wrapper').find('.acs-table');
            var query = $input.val().toLowerCase();
            
            $table.find('tbody tr').each(function() {
                var $row = $(this);
                var text = $row.text().toLowerCase();
                
                if (text.indexOf(query) === -1) {
                    $row.hide();
                } else {
                    $row.show();
                }
            });
        },

        // Handle bulk actions
        handleBulkAction: function(e) {
            var $btn = $(e.currentTarget);
            var $wrapper = $btn.closest('.acs-table-wrapper');
            var $select = $wrapper.find('.acs-table__bulk-select');
            var action = $select.val();
            
            if (!action) return;
            
            var $table = $wrapper.find('.acs-table');
            var selectedIds = [];
            
            $table.find('.acs-table__select-item:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) return;
            
            // Confirm action
            var confirmMessage = 'Are you sure you want to perform this action on ' + selectedIds.length + ' items?';
            if (!confirm(confirmMessage)) return;
            
            // Perform bulk action
            this.performBulkAction(action, selectedIds, $btn);
        },

        // Perform bulk action via AJAX
        performBulkAction: function(action, ids, $btn) {
            this.setButtonLoading($btn, true);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acs_bulk_action',
                    bulk_action: action,
                    ids: ids,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    ACSAdmin.setButtonLoading($btn, false);
                    
                    if (response.success) {
                        ACSAdmin.showNotification(response.data.message || 'Bulk action completed successfully', 'success');
                        // Reload page or update table
                        location.reload();
                    } else {
                        ACSAdmin.showNotification(response.data || 'Bulk action failed', 'error');
                    }
                },
                error: function() {
                    ACSAdmin.setButtonLoading($btn, false);
                    ACSAdmin.showNotification('An error occurred while performing the bulk action', 'error');
                }
            });
        },

        // Update progress bar
        updateProgress: function($progress, value, max) {
            max = max || 100;
            var percentage = Math.min(Math.max((value / max) * 100, 0), 100);
            
            if ($progress.hasClass('acs-progress--circular')) {
                var $circle = $progress.find('.acs-progress__fill-circle');
                var radius = parseFloat($circle.attr('r'));
                var circumference = 2 * Math.PI * radius;
                var offset = circumference - (percentage / 100) * circumference;
                
                $circle.css('stroke-dashoffset', offset);
            } else {
                $progress.find('.acs-progress__fill').css('width', percentage + '%');
            }
            
            $progress.find('.acs-progress__percentage').text(percentage.toFixed(1) + '%');
            $progress.attr('aria-valuenow', value);
            $progress.attr('aria-valuemax', max);
        },

        // Show notification
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="acs-alert acs-alert--' + type + ' acs-alert--dismissible">')
                .html('<div class="acs-alert__content"><p>' + message + '</p></div>')
                .append('<button type="button" class="acs-alert__dismiss" aria-label="Dismiss"><span class="dashicons dashicons-dismiss"></span></button>');
            
            // Insert at top of page
            $('.wrap').prepend($notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                ACSAdmin.dismissAlert($notification);
            }, 5000);
        },

        // Open modal
        openModal: function(modalId) {
            var $modal = $('#' + modalId);
            if ($modal.length === 0) return;
            
            $modal.addClass('acs-modal--open');
            $('body').addClass('acs-modal-open');
            
            // Focus first focusable element
            var $focusable = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
            if ($focusable.length) {
                $focusable.focus();
            }
        },

        // Close modal
        closeModal: function($modal) {
            $modal.removeClass('acs-modal--open');
            $('body').removeClass('acs-modal-open');
        },

        // Trap focus within element
        trapFocus: function(e, $container) {
            var $focusable = $container.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            var $first = $focusable.first();
            var $last = $focusable.last();
            
            if (e.shiftKey) {
                if (document.activeElement === $first[0]) {
                    e.preventDefault();
                    $last.focus();
                }
            } else {
                if (document.activeElement === $last[0]) {
                    e.preventDefault();
                    $first.focus();
                }
            }
        },

        // Debounce function
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Utility: Format number
        formatNumber: function(num, decimals) {
            decimals = decimals || 0;
            return parseFloat(num).toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },

        // Utility: Format bytes
        formatBytes: function(bytes, decimals) {
            if (bytes === 0) return '0 Bytes';
            
            var k = 1024;
            var dm = decimals || 2;
            var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        // Utility: Time ago
        timeAgo: function(date) {
            var now = new Date();
            var diff = now - new Date(date);
            var seconds = Math.floor(diff / 1000);
            
            if (seconds < 60) return 'just now';
            
            var minutes = Math.floor(seconds / 60);
            if (minutes < 60) return minutes + ' minute' + (minutes === 1 ? '' : 's') + ' ago';
            
            var hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + ' hour' + (hours === 1 ? '' : 's') + ' ago';
            
            var days = Math.floor(hours / 24);
            return days + ' day' + (days === 1 ? '' : 's') + ' ago';
        },

        // Initialize semantic HTML structure
        initSemanticStructure: function() {
            // Ensure proper heading hierarchy
            this.validateHeadingHierarchy();
            
            // Add semantic roles where needed
            this.addSemanticRoles();
            
            // Enhance landmark navigation
            this.enhanceLandmarks();
        },

        // Validate and fix heading hierarchy
        validateHeadingHierarchy: function() {
            var $headings = $('.acs-admin-page h1, .acs-admin-page h2, .acs-admin-page h3, .acs-admin-page h4, .acs-admin-page h5, .acs-admin-page h6');
            var currentLevel = 0;
            
            $headings.each(function() {
                var $heading = $(this);
                var level = parseInt($heading.prop('tagName').charAt(1));
                
                // Check for proper hierarchy
                if (currentLevel === 0) {
                    currentLevel = level;
                } else if (level > currentLevel + 1) {
                    // Skip level detected - add warning for developers
                    console.warn('Heading hierarchy skip detected: h' + currentLevel + ' to h' + level);
                }
                
                currentLevel = level;
                
                // Add ARIA level if needed
                if (!$heading.attr('aria-level')) {
                    $heading.attr('aria-level', level);
                }
            });
        },

        // Add semantic roles to improve structure
        addSemanticRoles: function() {
            // Add main content role
            if (!$('.acs-admin-page main, .acs-admin-page [role="main"]').length) {
                $('.acs-admin-page .wrap').attr('role', 'main');
            }
            
            // Add navigation roles
            $('.acs-breadcrumbs').attr('role', 'navigation').attr('aria-label', 'Breadcrumb');
            $('.acs-mobile-nav__menu').attr('role', 'navigation').attr('aria-label', 'Mobile menu');
            
            // Add complementary roles for sidebars
            $('.acs-sidebar, .postbox').attr('role', 'complementary');
            
            // Add banner role for headers
            $('.acs-page-header').attr('role', 'banner');
            
            // Add contentinfo role for footers
            $('.acs-page-footer').attr('role', 'contentinfo');
            
            // Add region roles for major sections
            $('.acs-dashboard-section').attr('role', 'region');
        },

        // Enhance landmark navigation
        enhanceLandmarks: function() {
            // Add skip links if they don't exist
            if (!$('.skip-link').length) {
                var skipLinks = '<div class="skip-links">' +
                    '<a class="skip-link screen-reader-text" href="#main">' + (acsAdmin.strings.skipToContent || 'Skip to main content') + '</a>' +
                    '<a class="skip-link screen-reader-text" href="#adminmenu">' + (acsAdmin.strings.skipToNavigation || 'Skip to navigation') + '</a>' +
                    '</div>';
                $('body').prepend(skipLinks);
            }
            
            // Ensure main content has proper ID
            if (!$('#main').length) {
                $('.acs-admin-page .wrap').attr('id', 'main');
            }
        },

        // Initialize WCAG 2.1 AA compliance features
        initWCAGCompliance: function() {
            // Color contrast validation
            this.validateColorContrast();
            
            // Focus visibility enhancement
            this.enhanceFocusVisibility();
            
            // Touch target size validation
            this.validateTouchTargets();
            
            // Text spacing compliance
            this.ensureTextSpacing();
        },

        // Validate color contrast ratios
        validateColorContrast: function() {
            // This is a simplified check - in production, you'd use a proper contrast checking library
            var contrastIssues = [];
            
            // Check common color combinations
            var colorTests = [
                { bg: 'var(--acs-color-primary)', fg: 'white', element: '.acs-button--primary' },
                { bg: 'var(--acs-color-success)', fg: 'white', element: '.acs-badge--success' },
                { bg: 'var(--acs-color-warning)', fg: 'white', element: '.acs-badge--warning' },
                { bg: 'var(--acs-color-error)', fg: 'white', element: '.acs-badge--error' }
            ];
            
            // Log any potential contrast issues for developers
            colorTests.forEach(function(test) {
                if ($(test.element).length > 0) {
                    // In a real implementation, you'd calculate actual contrast ratios here
                    console.log('Color contrast check for ' + test.element + ': Using ' + test.bg + ' background with ' + test.fg + ' text');
                }
            });
        },

        // Enhance focus visibility for WCAG compliance
        enhanceFocusVisibility: function() {
            // Add high-contrast focus indicators
            var focusStyle = '<style id="acs-focus-enhancement">' +
                '.acs-admin-page *:focus { outline: 2px solid var(--acs-color-primary) !important; outline-offset: 2px !important; }' +
                '.acs-admin-page *:focus-visible { box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.3) !important; }' +
                '@media (prefers-contrast: high) { .acs-admin-page *:focus { outline-width: 3px !important; } }' +
                '</style>';
            
            if (!$('#acs-focus-enhancement').length) {
                $('head').append(focusStyle);
            }
        },

        // Validate touch target sizes for mobile accessibility
        validateTouchTargets: function() {
            var minTouchSize = 44; // 44px minimum as per WCAG
            
            $('.acs-button, .acs-card--action, a, button').each(function() {
                var $element = $(this);
                var width = $element.outerWidth();
                var height = $element.outerHeight();
                
                if (width < minTouchSize || height < minTouchSize) {
                    // Add touch-friendly padding
                    var paddingNeeded = Math.max(0, (minTouchSize - Math.min(width, height)) / 2);
                    if (paddingNeeded > 0) {
                        $element.css({
                            'min-width': minTouchSize + 'px',
                            'min-height': minTouchSize + 'px',
                            'display': 'inline-flex',
                            'align-items': 'center',
                            'justify-content': 'center'
                        });
                    }
                }
            });
        },

        // Ensure proper text spacing for readability
        ensureTextSpacing: function() {
            // Add CSS for proper text spacing if not already present
            var textSpacingStyle = '<style id="acs-text-spacing">' +
                '.acs-admin-page { line-height: 1.5 !important; }' +
                '.acs-admin-page p { margin-bottom: 1em !important; }' +
                '.acs-admin-page h1, .acs-admin-page h2, .acs-admin-page h3, .acs-admin-page h4, .acs-admin-page h5, .acs-admin-page h6 { margin-bottom: 0.5em !important; margin-top: 1em !important; }' +
                '.acs-admin-page h1:first-child, .acs-admin-page h2:first-child, .acs-admin-page h3:first-child { margin-top: 0 !important; }' +
                '</style>';
            
            if (!$('#acs-text-spacing').length) {
                $('head').append(textSpacingStyle);
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ACSAdmin.init();
    });

    // Expose to global scope for external access
    window.ACSAdmin = ACSAdmin;

})(jQuery);   
