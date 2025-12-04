/**
 * ACS Lazy Loading Module
 *
 * Handles lazy loading of images, components, and async data fetching.
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
     * Lazy Loading Module
     */
    ACSAdmin.LazyLoad = {

        /**
         * Intersection Observer instance
         */
        observer: null,

        /**
         * Loading queue
         */
        queue: [],

        /**
         * Configuration
         */
        config: {
            rootMargin: '100px',
            threshold: 0.1,
            loadDelay: 100
        },

        /**
         * Initialize the module
         */
        init: function() {
            this.initIntersectionObserver();
            this.observeElements();
            this.initDataLoading();
            
            console.log('[ACS] Lazy Loading initialized');
        },

        /**
         * Initialize Intersection Observer
         */
        initIntersectionObserver: function() {
            var self = this;

            if (!('IntersectionObserver' in window)) {
                // Fallback for older browsers
                this.loadAllVisible();
                return;
            }

            this.observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        self.loadElement(entry.target);
                        self.observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: this.config.rootMargin,
                threshold: this.config.threshold
            });
        },

        /**
         * Observe lazy elements
         */
        observeElements: function() {
            var self = this;

            $('[data-lazy="true"]').each(function() {
                if (self.observer) {
                    self.observer.observe(this);
                }
            });

            // Also observe dynamically added elements
            $(document).on('DOMNodeInserted', function(e) {
                var $target = $(e.target);
                if ($target.attr('data-lazy') === 'true' && self.observer) {
                    self.observer.observe(e.target);
                }
                $target.find('[data-lazy="true"]').each(function() {
                    if (self.observer) {
                        self.observer.observe(this);
                    }
                });
            });
        },

        /**
         * Load element content
         */
        loadElement: function(element) {
            var $el = $(element);
            var loadType = $el.data('lazy-type') || this.detectType($el);

            switch (loadType) {
                case 'image':
                    this.loadImage($el);
                    break;
                case 'iframe':
                    this.loadIframe($el);
                    break;
                case 'ajax':
                    this.loadAjax($el);
                    break;
                case 'chart':
                    this.loadChart($el);
                    break;
                default:
                    this.loadDefault($el);
            }
        },

        /**
         * Detect element type
         */
        detectType: function($el) {
            if ($el.hasClass('acs-lazy-image')) return 'image';
            if ($el.hasClass('acs-lazy-iframe')) return 'iframe';
            if ($el.hasClass('acs-lazy-chart')) return 'chart';
            if ($el.data('ajax-url')) return 'ajax';
            return 'default';
        },

        /**
         * Load lazy image
         */
        loadImage: function($el) {
            var src = $el.data('src');
            var srcset = $el.data('srcset');

            if (!src) return;

            var img = new Image();
            
            img.onload = function() {
                if ($el.is('img')) {
                    $el.attr('src', src);
                    if (srcset) {
                        $el.attr('srcset', srcset);
                    }
                } else {
                    $el.css('background-image', 'url(' + src + ')');
                }
                
                $el.removeClass('acs-skeleton__image')
                   .addClass('acs-lazy-loaded')
                   .removeAttr('data-lazy');
            };

            img.onerror = function() {
                $el.addClass('acs-lazy-error');
            };

            img.src = src;
        },

        /**
         * Load lazy iframe
         */
        loadIframe: function($el) {
            var src = $el.data('src');
            
            if (!src) return;

            var $iframe = $('<iframe>')
                .attr('src', src)
                .attr('frameborder', '0')
                .attr('allowfullscreen', 'true');

            $el.replaceWith($iframe);
        },

        /**
         * Load content via AJAX
         */
        loadAjax: function($el) {
            var url = $el.data('ajax-url');
            var action = $el.data('ajax-action');
            var params = $el.data('ajax-params') || {};

            if (!url && !action) return;

            // Show loading state
            $el.addClass('acs-loading');

            var ajaxOptions = {
                type: 'POST',
                data: params
            };

            if (action) {
                ajaxOptions.url = window.acsAdmin ? acsAdmin.ajaxUrl : ajaxurl;
                ajaxOptions.data.action = action;
                ajaxOptions.data.nonce = window.acsAdmin ? acsAdmin.nonce : '';
            } else {
                ajaxOptions.url = url;
            }

            $.ajax(ajaxOptions)
                .done(function(response) {
                    var content = response;
                    
                    if (response.success !== undefined) {
                        content = response.success ? response.data : '';
                    }

                    if (typeof content === 'object') {
                        content = response.data.html || JSON.stringify(content);
                    }

                    $el.html(content)
                       .removeClass('acs-loading acs-skeleton')
                       .addClass('acs-lazy-loaded')
                       .removeAttr('data-lazy');

                    // Trigger event for post-processing
                    $(document).trigger('acs:lazy:loaded', [$el, response]);
                })
                .fail(function() {
                    $el.addClass('acs-lazy-error')
                       .removeClass('acs-loading');
                });
        },

        /**
         * Load chart component
         */
        loadChart: function($el) {
            var chartType = $el.data('chart-type') || 'bar';
            var dataUrl = $el.data('chart-data');
            var chartId = $el.attr('id') || 'acs-chart-' + Date.now();

            if (!dataUrl) {
                $el.removeClass('acs-skeleton-chart')
                   .addClass('acs-lazy-loaded')
                   .removeAttr('data-lazy');
                return;
            }

            // Create canvas for Chart.js
            var $canvas = $('<canvas id="' + chartId + '"></canvas>');
            $el.empty().append($canvas);

            // Fetch chart data
            $.get(dataUrl, function(data) {
                if (window.Chart) {
                    new Chart($canvas[0].getContext('2d'), {
                        type: chartType,
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }

                $el.removeClass('acs-skeleton-chart')
                   .addClass('acs-lazy-loaded')
                   .removeAttr('data-lazy');
            });
        },

        /**
         * Default load handler
         */
        loadDefault: function($el) {
            $el.removeClass('acs-skeleton')
               .addClass('acs-lazy-loaded')
               .removeAttr('data-lazy');
        },

        /**
         * Fallback: load all visible elements
         */
        loadAllVisible: function() {
            var self = this;
            
            $('[data-lazy="true"]').each(function() {
                var $el = $(this);
                if (self.isInViewport($el)) {
                    self.loadElement(this);
                }
            });
        },

        /**
         * Check if element is in viewport
         */
        isInViewport: function($el) {
            var rect = $el[0].getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        /**
         * Initialize data loading for dashboard widgets
         */
        initDataLoading: function() {
            var self = this;

            // Load dashboard metrics asynchronously
            $('[data-load-metrics]').each(function() {
                var $widget = $(this);
                self.loadMetrics($widget);
            });
        },

        /**
         * Load dashboard metrics
         */
        loadMetrics: function($widget) {
            var endpoint = $widget.data('load-metrics');
            var cacheKey = $widget.data('cache-key');

            // Check session storage cache first
            if (cacheKey && sessionStorage.getItem(cacheKey)) {
                try {
                    var cached = JSON.parse(sessionStorage.getItem(cacheKey));
                    if (cached.expiry > Date.now()) {
                        this.renderMetrics($widget, cached.data);
                        return;
                    }
                } catch (e) {
                    // Invalid cache, continue with fetch
                }
            }

            // Show loading state
            $widget.addClass('acs-loading');

            $.ajax({
                url: window.acsAdmin ? acsAdmin.ajaxUrl : ajaxurl,
                type: 'POST',
                data: {
                    action: endpoint,
                    nonce: window.acsAdmin ? acsAdmin.nonce : ''
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Cache in session storage
                        if (cacheKey) {
                            sessionStorage.setItem(cacheKey, JSON.stringify({
                                data: response.data,
                                expiry: Date.now() + 300000 // 5 minutes
                            }));
                        }

                        ACSAdmin.LazyLoad.renderMetrics($widget, response.data);
                    }
                },
                complete: function() {
                    $widget.removeClass('acs-loading');
                }
            });
        },

        /**
         * Render metrics into widget
         */
        renderMetrics: function($widget, data) {
            // Update stat values
            Object.keys(data).forEach(function(key) {
                var $stat = $widget.find('[data-metric="' + key + '"]');
                if ($stat.length) {
                    $stat.text(data[key]);
                    $stat.addClass('acs-metric-loaded');
                }
            });

            // Remove skeleton states
            $widget.find('.acs-skeleton-stat__value, .acs-skeleton-stat__label')
                   .removeClass('acs-skeleton-stat__value acs-skeleton-stat__label');
        },

        /**
         * Preload images
         */
        preloadImages: function(urls) {
            urls.forEach(function(url) {
                var img = new Image();
                img.src = url;
            });
        },

        /**
         * Destroy observer
         */
        destroy: function() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ACSAdmin.LazyLoad.init();
    });

})(jQuery, window, document);
