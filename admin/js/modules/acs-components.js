/**
 * ACS Components Module
 *
 * Reusable UI components for ACS admin interface following modular architecture
 * and design system patterns.
 *
 * @package AI_Content_Studio
 * @subpackage Admin/JavaScript
 * @since 2.0.0
 */

(function($, window, document) {
    'use strict';
    
    // Ensure ACSAdmin namespace exists
    window.ACSAdmin = window.ACSAdmin || {};
    
    /**
     * Components Module
     */
    ACSAdmin.Components = {
        
        /**
         * Initialize the components module
         */
        init: function(core) {
            this.core = core;
            this.core.log('Initializing ACS Components Module');
            
            // Initialize component types
            this.initCards();
            this.initTables();
            this.initCharts();
            this.initModals();
            this.initToggles();
            this.initTabs();
            
            this.core.log('ACS Components Module initialized');
        },
        
        /**
         * Initialize card components
         */
        initCards: function() {
            var self = this;
            
            // Handle card actions
            $(document).on('click', '.acs-card__action', function(e) {
                e.preventDefault();
                var $card = $(this).closest('.acs-card');
                var action = $(this).data('action');
                
                self.core.doAction('acs_card_action', action, $card);
            });
            
            // Handle collapsible cards
            $(document).on('click', '.acs-card__toggle', function(e) {
                e.preventDefault();
                var $card = $(this).closest('.acs-card');
                self.toggleCard($card);
            });
        },
        
        /**
         * Create a card component
         */
        createCard: function(options) {
            var defaults = {
                title: '',
                variant: 'default',
                size: 'medium',
                content: '',
                actions: [],
                collapsible: false,
                collapsed: false,
                loading: false
            };
            
            var opts = $.extend({}, defaults, options);
            
            var $card = $('<div class="acs-card acs-card--' + opts.variant + ' acs-card--' + opts.size + '"></div>');
            
            if (opts.collapsible) {
                $card.addClass('acs-card--collapsible');
                if (opts.collapsed) {
                    $card.addClass('acs-card--collapsed');
                }
            }
            
            if (opts.loading) {
                $card.addClass('acs-card--loading');
            }
            
            // Card header
            if (opts.title || opts.actions.length || opts.collapsible) {
                var $header = $('<div class="acs-card__header"></div>');
                
                if (opts.title) {
                    $header.append('<h3 class="acs-card__title">' + opts.title + '</h3>');
                }
                
                if (opts.actions.length || opts.collapsible) {
                    var $actions = $('<div class="acs-card__actions"></div>');
                    
                    opts.actions.forEach(function(action) {
                        $actions.append(
                            '<button class="acs-card__action" data-action="' + action.id + '" aria-label="' + action.label + '">' +
                            (action.icon ? '<span class="dashicons ' + action.icon + '"></span>' : '') +
                            (action.text || '') +
                            '</button>'
                        );
                    });
                    
                    if (opts.collapsible) {
                        $actions.append(
                            '<button class="acs-card__toggle" aria-label="' + (opts.collapsed ? 'Expand' : 'Collapse') + '">' +
                            '<span class="dashicons dashicons-arrow-down-alt2"></span>' +
                            '</button>'
                        );
                    }
                    
                    $header.append($actions);
                }
                
                $card.append($header);
            }
            
            // Card body
            var $body = $('<div class="acs-card__body"></div>');
            if (typeof opts.content === 'string') {
                $body.html(opts.content);
            } else {
                $body.append(opts.content);
            }
            $card.append($body);
            
            // Loading overlay
            if (opts.loading) {
                $card.append('<div class="acs-card__loading"><span class="spinner is-active"></span></div>');
            }
            
            return $card;
        },
        
        /**
         * Toggle card collapsed state
         */
        toggleCard: function($card) {
            $card.toggleClass('acs-card--collapsed');
            var isCollapsed = $card.hasClass('acs-card--collapsed');
            
            var $toggle = $card.find('.acs-card__toggle');
            $toggle.attr('aria-label', isCollapsed ? 'Expand' : 'Collapse');
            
            this.core.doAction('acs_card_toggled', $card, isCollapsed);
        },
        
        /**
         * Initialize table components
         */
        initTables: function() {
            var self = this;
            
            // Handle sortable columns
            $(document).on('click', '.acs-table__header--sortable', function() {
                var $header = $(this);
                var $table = $header.closest('.acs-table');
                var column = $header.data('column');
                
                self.sortTable($table, column);
            });
            
            // Handle bulk actions
            $(document).on('change', '.acs-table__select-all', function() {
                var $table = $(this).closest('.acs-table');
                var checked = $(this).prop('checked');
                
                $table.find('.acs-table__select-row').prop('checked', checked);
                self.updateBulkActions($table);
            });
            
            $(document).on('change', '.acs-table__select-row', function() {
                var $table = $(this).closest('.acs-table');
                self.updateBulkActions($table);
            });
            
            $(document).on('click', '.acs-table__bulk-apply', function(e) {
                e.preventDefault();
                var $table = $(this).closest('.acs-table-wrapper').find('.acs-table');
                var action = $('.acs-table__bulk-action').val();
                var selected = self.getSelectedRows($table);
                
                if (action && selected.length) {
                    self.core.doAction('acs_table_bulk_action', action, selected, $table);
                }
            });
        },
        
        /**
         * Sort table by column
         */
        sortTable: function($table, column) {
            var $headers = $table.find('.acs-table__header');
            var $targetHeader = $headers.filter('[data-column="' + column + '"]');
            var currentOrder = $targetHeader.data('order') || 'none';
            var newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Update header states
            $headers.removeClass('acs-table__header--asc acs-table__header--desc').data('order', 'none');
            $targetHeader.addClass('acs-table__header--' + newOrder).data('order', newOrder);
            
            // Sort rows
            var $rows = $table.find('.acs-table__row').get();
            var columnIndex = $targetHeader.index();
            
            $rows.sort(function(a, b) {
                var aValue = $(a).find('.acs-table__cell').eq(columnIndex).text().trim();
                var bValue = $(b).find('.acs-table__cell').eq(columnIndex).text().trim();
                
                // Try numeric comparison first
                var aNum = parseFloat(aValue);
                var bNum = parseFloat(bValue);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return newOrder === 'asc' ? aNum - bNum : bNum - aNum;
                }
                
                // Fall back to string comparison
                return newOrder === 'asc' 
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            });
            
            $table.find('tbody').append($rows);
            
            this.core.doAction('acs_table_sorted', $table, column, newOrder);
        },
        
        /**
         * Update bulk actions state
         */
        updateBulkActions: function($table) {
            var selected = this.getSelectedRows($table);
            var $bulkActions = $table.closest('.acs-table-wrapper').find('.acs-table__bulk-actions');
            
            if (selected.length > 0) {
                $bulkActions.removeClass('acs-table__bulk-actions--hidden');
            } else {
                $bulkActions.addClass('acs-table__bulk-actions--hidden');
            }
            
            // Update select all checkbox
            var $selectAll = $table.find('.acs-table__select-all');
            var $checkboxes = $table.find('.acs-table__select-row');
            var allChecked = $checkboxes.length === selected.length;
            
            $selectAll.prop('checked', allChecked);
        },
        
        /**
         * Get selected table rows
         */
        getSelectedRows: function($table) {
            var selected = [];
            
            $table.find('.acs-table__select-row:checked').each(function() {
                selected.push($(this).val());
            });
            
            return selected;
        },
        
        /**
         * Initialize chart components
         */
        initCharts: function() {
            var self = this;
            
            // Initialize progress circles
            $('.acs-chart--progress').each(function() {
                self.updateProgressCircle($(this));
            });
        },
        
        /**
         * Create a progress circle chart
         */
        createProgressCircle: function(options) {
            var defaults = {
                value: 0,
                max: 100,
                size: 'medium',
                label: '',
                color: 'primary'
            };
            
            var opts = $.extend({}, defaults, options);
            var percentage = Math.round((opts.value / opts.max) * 100);
            
            var $chart = $('<div class="acs-chart acs-chart--progress acs-chart--' + opts.size + '" data-value="' + opts.value + '" data-max="' + opts.max + '"></div>');
            
            var $circle = $('<svg class="acs-chart__circle" viewBox="0 0 100 100">' +
                '<circle class="acs-chart__circle-bg" cx="50" cy="50" r="45"></circle>' +
                '<circle class="acs-chart__circle-fill acs-chart__circle-fill--' + opts.color + '" cx="50" cy="50" r="45" ' +
                'style="stroke-dasharray: ' + (percentage * 2.827) + ' 282.7"></circle>' +
                '</svg>');
            
            var $value = $('<div class="acs-chart__value">' + percentage + '%</div>');
            
            $chart.append($circle).append($value);
            
            if (opts.label) {
                $chart.append('<div class="acs-chart__label">' + opts.label + '</div>');
            }
            
            return $chart;
        },
        
        /**
         * Update progress circle
         */
        updateProgressCircle: function($chart) {
            var value = parseFloat($chart.data('value')) || 0;
            var max = parseFloat($chart.data('max')) || 100;
            var percentage = Math.round((value / max) * 100);
            
            var $fill = $chart.find('.acs-chart__circle-fill');
            var circumference = 282.7;
            var offset = circumference - (percentage / 100 * circumference);
            
            $fill.css('stroke-dasharray', (circumference - offset) + ' ' + circumference);
            $chart.find('.acs-chart__value').text(percentage + '%');
        },
        
        /**
         * Initialize modal components
         */
        initModals: function() {
            var self = this;
            
            // Open modal
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                var modalId = $(this).data('modal');
                self.openModal(modalId);
            });
            
            // Close modal
            $(document).on('click', '.acs-modal__close, .acs-modal__overlay', function(e) {
                e.preventDefault();
                var $modal = $(this).closest('.acs-modal');
                self.closeModal($modal);
            });
            
            // Prevent closing when clicking inside modal content
            $(document).on('click', '.acs-modal__content', function(e) {
                e.stopPropagation();
            });
        },
        
        /**
         * Open modal
         */
        openModal: function(modalId) {
            var $modal = $('#' + modalId);
            
            if ($modal.length) {
                $modal.addClass('acs-modal--open');
                $('body').addClass('acs-modal-open');
                
                // Focus first focusable element
                var $focusable = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
                $focusable.focus();
                
                this.core.doAction('acs_modal_opened', $modal);
            }
        },
        
        /**
         * Close modal
         */
        closeModal: function($modal) {
            $modal.removeClass('acs-modal--open');
            $('body').removeClass('acs-modal-open');
            
            this.core.doAction('acs_modal_closed', $modal);
        },
        
        /**
         * Create modal
         */
        createModal: function(options) {
            var defaults = {
                id: 'acs-modal-' + Date.now(),
                title: '',
                content: '',
                size: 'medium',
                actions: []
            };
            
            var opts = $.extend({}, defaults, options);
            
            var $modal = $('<div id="' + opts.id + '" class="acs-modal acs-modal--' + opts.size + '"></div>');
            var $overlay = $('<div class="acs-modal__overlay"></div>');
            var $dialog = $('<div class="acs-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="' + opts.id + '-title"></div>');
            var $content = $('<div class="acs-modal__content"></div>');
            
            // Header
            var $header = $('<div class="acs-modal__header">' +
                '<h2 id="' + opts.id + '-title" class="acs-modal__title">' + opts.title + '</h2>' +
                '<button class="acs-modal__close" aria-label="Close modal">' +
                '<span class="dashicons dashicons-no-alt"></span>' +
                '</button>' +
                '</div>');
            
            // Body
            var $body = $('<div class="acs-modal__body"></div>');
            if (typeof opts.content === 'string') {
                $body.html(opts.content);
            } else {
                $body.append(opts.content);
            }
            
            // Footer with actions
            if (opts.actions.length) {
                var $footer = $('<div class="acs-modal__footer"></div>');
                
                opts.actions.forEach(function(action) {
                    var btnClass = 'button' + (action.primary ? ' button-primary' : '');
                    $footer.append(
                        '<button class="' + btnClass + '" data-action="' + action.id + '">' + action.label + '</button>'
                    );
                });
                
                $content.append($footer);
            }
            
            $content.append($header).append($body);
            $dialog.append($content);
            $modal.append($overlay).append($dialog);
            
            return $modal;
        },
        
        /**
         * Initialize toggle components
         */
        initToggles: function() {
            var self = this;
            
            $(document).on('change', '.acs-toggle__input', function() {
                var $toggle = $(this).closest('.acs-toggle');
                var checked = $(this).prop('checked');
                
                $toggle.toggleClass('acs-toggle--on', checked);
                
                self.core.doAction('acs_toggle_changed', $toggle, checked);
            });
        },
        
        /**
         * Create toggle switch
         */
        createToggle: function(options) {
            var defaults = {
                id: 'acs-toggle-' + Date.now(),
                name: '',
                label: '',
                checked: false,
                disabled: false
            };
            
            var opts = $.extend({}, defaults, options);
            
            var $toggle = $('<label class="acs-toggle' + (opts.checked ? ' acs-toggle--on' : '') + '" for="' + opts.id + '"></label>');
            
            var $input = $('<input type="checkbox" class="acs-toggle__input" id="' + opts.id + '" name="' + opts.name + '"' +
                (opts.checked ? ' checked' : '') +
                (opts.disabled ? ' disabled' : '') + '>');
            
            var $slider = $('<span class="acs-toggle__slider"></span>');
            
            $toggle.append($input).append($slider);
            
            if (opts.label) {
                $toggle.append('<span class="acs-toggle__label">' + opts.label + '</span>');
            }
            
            return $toggle;
        },
        
        /**
         * Initialize tab components
         */
        initTabs: function() {
            var self = this;
            
            $(document).on('click', '.acs-tabs__tab', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var $tabGroup = $tab.closest('.acs-tabs');
                var target = $tab.data('tab');
                
                self.switchTab($tabGroup, target);
            });
            
            // Initialize first tab as active if none selected
            $('.acs-tabs').each(function() {
                var $tabGroup = $(this);
                if (!$tabGroup.find('.acs-tabs__tab--active').length) {
                    $tabGroup.find('.acs-tabs__tab').first().addClass('acs-tabs__tab--active');
                    var firstTarget = $tabGroup.find('.acs-tabs__tab').first().data('tab');
                    $tabGroup.find('.acs-tabs__panel[data-tab="' + firstTarget + '"]').addClass('acs-tabs__panel--active');
                }
            });
        },
        
        /**
         * Switch active tab
         */
        switchTab: function($tabGroup, target) {
            // Update tab buttons
            $tabGroup.find('.acs-tabs__tab').removeClass('acs-tabs__tab--active');
            $tabGroup.find('.acs-tabs__tab[data-tab="' + target + '"]').addClass('acs-tabs__tab--active');
            
            // Update panels
            $tabGroup.find('.acs-tabs__panel').removeClass('acs-tabs__panel--active');
            $tabGroup.find('.acs-tabs__panel[data-tab="' + target + '"]').addClass('acs-tabs__panel--active');
            
            this.core.doAction('acs_tab_switched', $tabGroup, target);
        },
        
        /**
         * Create tabs component
         */
        createTabs: function(options) {
            var defaults = {
                tabs: [],
                activeTab: 0
            };
            
            var opts = $.extend({}, defaults, options);
            
            var $tabs = $('<div class="acs-tabs"></div>');
            var $tabList = $('<div class="acs-tabs__list" role="tablist"></div>');
            var $panels = $('<div class="acs-tabs__panels"></div>');
            
            opts.tabs.forEach(function(tab, index) {
                var isActive = index === opts.activeTab;
                
                // Tab button
                var $tab = $('<button class="acs-tabs__tab' + (isActive ? ' acs-tabs__tab--active' : '') + '" ' +
                    'data-tab="' + tab.id + '" role="tab" aria-selected="' + isActive + '">' +
                    (tab.icon ? '<span class="dashicons ' + tab.icon + '"></span>' : '') +
                    tab.label +
                    '</button>');
                
                $tabList.append($tab);
                
                // Tab panel
                var $panel = $('<div class="acs-tabs__panel' + (isActive ? ' acs-tabs__panel--active' : '') + '" ' +
                    'data-tab="' + tab.id + '" role="tabpanel"></div>');
                
                if (typeof tab.content === 'string') {
                    $panel.html(tab.content);
                } else {
                    $panel.append(tab.content);
                }
                
                $panels.append($panel);
            });
            
            $tabs.append($tabList).append($panels);
            
            return $tabs;
        }
    };
    
    // Register module with core when available
    $(document).ready(function() {
        if (window.ACSAdmin && window.ACSAdmin.Core) {
            ACSAdmin.Core.registerModule('components', ACSAdmin.Components);
        }
    });
    
})(jQuery, window, document);
