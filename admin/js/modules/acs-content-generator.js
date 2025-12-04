/**
 * ACS Content Generator Module
 * 
 * Enhanced content generation interface with live preview,
 * progress indicators, and improved user feedback
 */

(function($) {
    'use strict';

    window.ACSContentGenerator = {
        // Configuration
        config: {
            ajaxUrl: acsAdmin?.ajaxUrl || '',
            nonce: acsAdmin?.nonce || '',
            strings: acsAdmin?.strings || {},
            previewUpdateDelay: 500
        },

        // State
        state: {
            isGenerating: false,
            currentGeneration: null,
            previewTimeout: null,
            formData: {}
        },

        // Initialize the content generator
        init: function() {
            this.bindEvents();
            this.initLivePreview();
            this.initProgressIndicators();
            this.initFormEnhancements();
            this.restoreFormState();
        },

        // Bind event listeners
        bindEvents: function() {
            var self = this;

            // Form submission
            $('#acs-generate-form').on('submit', function(e) {
                e.preventDefault();
                self.handleGeneration();
            });

            // Form field changes for live preview
            $('#acs-generate-form').on('change input', 'input, select, textarea', function() {
                self.updateFormState();
                self.schedulePreviewUpdate();
            });

            // Cancel generation
            $(document).on('click', '.acs-cancel-generation', function(e) {
                e.preventDefault();
                self.cancelGeneration();
            });

            // Retry generation
            $(document).on('click', '.acs-retry-generation', function(e) {
                e.preventDefault();
                self.retryGeneration();
            });

            // Create post from result
            $(document).on('click', '.acs-create-post', function(e) {
                e.preventDefault();
                var generationId = $(this).data('generation-id');
                self.createPost(generationId);
            });

            // Copy to clipboard
            $(document).on('click', '.acs-copy-content', function(e) {
                e.preventDefault();
                self.copyToClipboard($(this));
            });

            // Preview toggle
            $(document).on('click', '.acs-toggle-preview', function(e) {
                e.preventDefault();
                self.togglePreview();
            });
        },

        // Initialize live preview functionality
        initLivePreview: function() {
            // Create preview container if it doesn't exist
            if ($('.acs-live-preview').length === 0) {
                var previewHtml = '<div class="acs-live-preview" style="display: none;">' +
                    '<div class="acs-live-preview__header">' +
                    '<h3>' + (acsAdmin.strings.livePreview || 'Live Preview') + '</h3>' +
                    '<button type="button" class="acs-toggle-preview button button-secondary">' +
                    '<span class="dashicons dashicons-visibility"></span>' +
                    '</button>' +
                    '</div>' +
                    '<div class="acs-live-preview__content">' +
                    '<div class="acs-live-preview__placeholder">' +
                    '<p>' + (acsAdmin.strings.previewPlaceholder || 'Fill in the form to see a preview of your content structure') + '</p>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                $('.acs-generator-container').append(previewHtml);
            }
        },

        // Initialize progress indicators
        initProgressIndicators: function() {
            // Enhance existing progress indicator
            var $progress = $('.acs-generation-progress');
            if ($progress.length) {
                // Add detailed progress steps
                var stepsHtml = '<div class="acs-progress-steps">' +
                    '<div class="acs-progress-step" data-step="analyzing">' +
                    '<span class="acs-progress-step__icon"><span class="dashicons dashicons-search"></span></span>' +
                    '<span class="acs-progress-step__label">' + (acsAdmin.strings.analyzing || 'Analyzing') + '</span>' +
                    '</div>' +
                    '<div class="acs-progress-step" data-step="generating">' +
                    '<span class="acs-progress-step__icon"><span class="dashicons dashicons-admin-generic"></span></span>' +
                    '<span class="acs-progress-step__label">' + (acsAdmin.strings.generating || 'Generating') + '</span>' +
                    '</div>' +
                    '<div class="acs-progress-step" data-step="optimizing">' +
                    '<span class="acs-progress-step__icon"><span class="dashicons dashicons-chart-line"></span></span>' +
                    '<span class="acs-progress-step__label">' + (acsAdmin.strings.optimizing || 'Optimizing') + '</span>' +
                    '</div>' +
                    '<div class="acs-progress-step" data-step="finalizing">' +
                    '<span class="acs-progress-step__icon"><span class="dashicons dashicons-yes"></span></span>' +
                    '<span class="acs-progress-step__label">' + (acsAdmin.strings.finalizing || 'Finalizing') + '</span>' +
                    '</div>' +
                    '</div>';
                
                $progress.find('.acs-progress-text').after(stepsHtml);
            }
        },

        // Initialize form enhancements
        initFormEnhancements: function() {
            // Add character counters to text fields
            this.addCharacterCounters();
            
            // Add input validation
            this.addInputValidation();
            
            // Add helpful tooltips
            this.addTooltips();
        },

        // Add character counters
        addCharacterCounters: function() {
            $('#acs-prompt, #acs-structure').each(function() {
                var $field = $(this);
                var maxLength = $field.attr('maxlength') || 2000;
                var currentLength = $field.val().length;
                
                var counterHtml = '<div class="acs-character-counter">' +
                    '<span class="acs-character-counter__current">' + currentLength + '</span>' +
                    ' / ' +
                    '<span class="acs-character-counter__max">' + maxLength + '</span>' +
                    '</div>';
                
                $field.after(counterHtml);
                
                $field.on('input', function() {
                    var length = $(this).val().length;
                    $(this).next('.acs-character-counter').find('.acs-character-counter__current').text(length);
                });
            });
        },

        // Add input validation
        addInputValidation: function() {
            var self = this;
            
            // Real-time validation for required fields
            $('#acs-generate-form [required]').on('blur', function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    self.showFieldError($field, acsAdmin.strings.required || 'This field is required');
                } else {
                    self.clearFieldError($field);
                }
            });
        },

        // Add tooltips
        addTooltips: function() {
            // Add WordPress-style help tooltips
            $('.acs-form-group label').each(function() {
                var $label = $(this);
                var helpText = $label.data('help');
                
                if (helpText) {
                    $label.append(' <span class="dashicons dashicons-editor-help acs-tooltip-trigger" title="' + helpText + '"></span>');
                }
            });
        },

        // Handle content generation
        handleGeneration: function() {
            if (this.state.isGenerating) {
                return;
            }

            // Validate form
            if (!this.validateForm()) {
                return;
            }

            // Collect form data
            var formData = this.collectFormData();
            
            // Show progress
            this.showProgress();
            
            // Start generation
            this.startGeneration(formData);
        },

        // Validate form
        validateForm: function() {
            var isValid = true;
            var self = this;
            
            // Clear previous errors
            $('.acs-form-validation').hide().empty();
            $('.error').removeClass('error');
            
            // Validate required fields
            $('#acs-generate-form [required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    self.showFieldError($field, acsAdmin.strings.required || 'This field is required');
                }
            });
            
            // Validate prompt length
            var prompt = $('#acs-prompt').val().trim();
            if (prompt && prompt.length < 10) {
                isValid = false;
                this.showFieldError($('#acs-prompt'), 'Please provide a more detailed prompt (at least 10 characters)');
            }
            
            return isValid;
        },

        // Show field error
        showFieldError: function($field, message) {
            var $validation = $field.closest('.acs-form-group').find('.acs-form-validation');
            if ($validation.length === 0) {
                $validation = $('<div class="acs-form-validation"></div>');
                $field.after($validation);
            }
            
            $validation.html('<p class="error-message">' + message + '</p>').show();
            $field.addClass('error').attr('aria-invalid', 'true');
            
            // Announce to screen readers
            this.announceToScreenReader(message);
        },

        // Clear field error
        clearFieldError: function($field) {
            $field.removeClass('error').removeAttr('aria-invalid');
            $field.closest('.acs-form-group').find('.acs-form-validation').hide().empty();
        },

        // Collect form data
        collectFormData: function() {
            return {
                action: 'acs_generate_content',
                nonce: this.config.nonce,
                content_type: $('#acs-content-type').val(),
                prompt: $('#acs-prompt').val(),
                keywords: $('#acs-keywords').val(),
                word_count: $('#acs-word-count').val(),
                tone: $('#acs-tone').val(),
                provider: $('#acs-provider').val(),
                model: $('#acs-model').val(),
                language: $('#acs-language').val(),
                audience: $('#acs-audience').val(),
                structure: $('#acs-structure').val(),
                include_images: $('input[name="include_images"]').is(':checked') ? 1 : 0,
                include_faq: $('input[name="include_faq"]').is(':checked') ? 1 : 0,
                include_conclusion: $('input[name="include_conclusion"]').is(':checked') ? 1 : 0
            };
        },

        // Show progress indicator
        showProgress: function() {
            $('.acs-generation-progress').show();
            $('.acs-generation-results').hide();
            $('#acs-generate-form').find('button[type="submit"]').prop('disabled', true);
            
            // Reset progress steps
            $('.acs-progress-step').removeClass('active complete');
            
            // Start progress animation
            this.animateProgress();
        },

        // Animate progress through steps
        animateProgress: function() {
            var steps = ['analyzing', 'generating', 'optimizing', 'finalizing'];
            var currentStep = 0;
            var self = this;
            
            function nextStep() {
                if (currentStep > 0) {
                    $('.acs-progress-step[data-step="' + steps[currentStep - 1] + '"]').removeClass('active').addClass('complete');
                }
                
                if (currentStep < steps.length && self.state.isGenerating) {
                    $('.acs-progress-step[data-step="' + steps[currentStep] + '"]').addClass('active');
                    currentStep++;
                    setTimeout(nextStep, 2000);
                }
            }
            
            nextStep();
        },

        // Start generation
        startGeneration: function(formData) {
            var self = this;
            this.state.isGenerating = true;
            this.state.formData = formData;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    self.handleGenerationSuccess(response);
                },
                error: function(xhr, status, error) {
                    self.handleGenerationError(xhr, status, error);
                },
                complete: function() {
                    self.state.isGenerating = false;
                }
            });
        },

        // Handle generation success
        handleGenerationSuccess: function(response) {
            this.hideProgress();
            
            if (response.success && response.data) {
                this.showSuccessMessage(response.data);
                this.displayGenerationResult(response.data);
                
                // Save to form state
                this.saveFormState();
            } else {
                var errorMessage = response.data?.message || acsAdmin.strings.error || 'Generation failed';
                this.showErrorMessage(errorMessage);
            }
        },

        // Handle generation error
        handleGenerationError: function(xhr, status, error) {
            this.hideProgress();
            
            var errorMessage = 'An error occurred during generation. Please try again.';
            
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                errorMessage = xhr.responseJSON.data.message;
            } else if (error) {
                errorMessage = error;
            }
            
            this.showErrorMessage(errorMessage);
        },

        // Hide progress indicator
        hideProgress: function() {
            $('.acs-generation-progress').hide();
            $('#acs-generate-form').find('button[type="submit"]').prop('disabled', false);
            $('.acs-progress-step').removeClass('active');
        },

        // Show success message
        showSuccessMessage: function(data) {
            var message = acsAdmin.strings.success || 'Content generated successfully!';
            
            var $notice = $('<div class="notice notice-success is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>');
            
            $('.acs-notices').html($notice);
            
            // Announce to screen readers
            this.announceToScreenReader(message);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Show error message
        showErrorMessage: function(message) {
            var $notice = $('<div class="notice notice-error is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>');
            
            $('.acs-notices').html($notice);
            
            // Announce to screen readers
            this.announceToScreenReader('Error: ' + message);
        },

        // Display generation result
        displayGenerationResult: function(data) {
            var resultHtml = '<div class="acs-generation-result">' +
                '<div class="acs-generation-result__header">' +
                '<h2>' + (data.title || 'Generated Content') + '</h2>' +
                '<div class="acs-generation-result__actions">' +
                '<button type="button" class="button button-primary acs-create-post" data-generation-id="' + (data.id || '') + '">' +
                '<span class="dashicons dashicons-plus"></span> Create Post' +
                '</button>' +
                '<button type="button" class="button button-secondary acs-copy-content">' +
                '<span class="dashicons dashicons-clipboard"></span> Copy Content' +
                '</button>' +
                '<button type="button" class="button button-secondary acs-retry-generation">' +
                '<span class="dashicons dashicons-update"></span> Regenerate' +
                '</button>' +
                '</div>' +
                '</div>' +
                '<div class="acs-generation-result__content">' +
                '<div class="acs-generation-result__preview">' +
                (data.content || '') +
                '</div>' +
                '</div>' +
                '</div>';
            
            $('.acs-generation-results').html(resultHtml).show();
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $('.acs-generation-results').offset().top - 50
            }, 500);
        },

        // Cancel generation
        cancelGeneration: function() {
            this.state.isGenerating = false;
            this.hideProgress();
            this.announceToScreenReader('Generation cancelled');
        },

        // Retry generation
        retryGeneration: function() {
            if (this.state.formData) {
                this.showProgress();
                this.startGeneration(this.state.formData);
            }
        },

        // Create post from generation
        createPost: function(generationId) {
            var self = this;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acs_create_post',
                    nonce: this.config.nonce,
                    generation_id: generationId
                },
                success: function(response) {
                    if (response.success && response.data && response.data.post_id) {
                        var editUrl = response.data.edit_url;
                        self.showSuccessMessage('Post created successfully!');
                        
                        // Redirect to edit page
                        setTimeout(function() {
                            window.location.href = editUrl;
                        }, 1000);
                    } else {
                        self.showErrorMessage(response.data?.message || 'Failed to create post');
                    }
                },
                error: function() {
                    self.showErrorMessage('Failed to create post. Please try again.');
                }
            });
        },

        // Copy content to clipboard
        copyToClipboard: function($button) {
            var content = $('.acs-generation-result__preview').html();
            
            // Create temporary element
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(content).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Update button text
            var originalText = $button.html();
            $button.html('<span class="dashicons dashicons-yes"></span> Copied!');
            
            setTimeout(function() {
                $button.html(originalText);
            }, 2000);
            
            this.announceToScreenReader('Content copied to clipboard');
        },

        // Toggle preview
        togglePreview: function() {
            var $preview = $('.acs-live-preview');
            $preview.toggle();
            
            if ($preview.is(':visible')) {
                this.updatePreview();
            }
        },

        // Schedule preview update
        schedulePreviewUpdate: function() {
            var self = this;
            
            clearTimeout(this.state.previewTimeout);
            this.state.previewTimeout = setTimeout(function() {
                self.updatePreview();
            }, this.config.previewUpdateDelay);
        },

        // Update live preview
        updatePreview: function() {
            var formData = this.collectFormData();
            var previewHtml = '';
            
            if (formData.prompt) {
                previewHtml += '<div class="acs-preview-section">' +
                    '<h4>Topic</h4>' +
                    '<p>' + this.escapeHtml(formData.prompt) + '</p>' +
                    '</div>';
            }
            
            if (formData.keywords) {
                var keywords = formData.keywords.split(',').map(function(k) { return k.trim(); });
                previewHtml += '<div class="acs-preview-section">' +
                    '<h4>Keywords</h4>' +
                    '<div class="acs-preview-keywords">';
                keywords.forEach(function(keyword) {
                    previewHtml += '<span class="acs-preview-keyword">' + keyword + '</span>';
                });
                previewHtml += '</div></div>';
            }
            
            if (formData.structure) {
                previewHtml += '<div class="acs-preview-section">' +
                    '<h4>Structure</h4>' +
                    '<pre>' + this.escapeHtml(formData.structure) + '</pre>' +
                    '</div>';
            }
            
            if (previewHtml) {
                $('.acs-live-preview__content').html(previewHtml);
            } else {
                $('.acs-live-preview__content').html($('.acs-live-preview__placeholder').clone());
            }
        },

        // Update form state
        updateFormState: function() {
            this.state.formData = this.collectFormData();
        },

        // Save form state to localStorage
        saveFormState: function() {
            try {
                localStorage.setItem('acs_generator_state', JSON.stringify(this.state.formData));
            } catch (e) {
                console.warn('Failed to save form state:', e);
            }
        },

        // Restore form state from localStorage
        restoreFormState: function() {
            try {
                var savedState = localStorage.getItem('acs_generator_state');
                if (savedState) {
                    var formData = JSON.parse(savedState);
                    
                    // Restore form values
                    Object.keys(formData).forEach(function(key) {
                        var $field = $('#acs-' + key.replace(/_/g, '-'));
                        if ($field.length) {
                            if ($field.is(':checkbox')) {
                                $field.prop('checked', formData[key] == 1);
                            } else {
                                $field.val(formData[key]);
                            }
                        }
                    });
                }
            } catch (e) {
                console.warn('Failed to restore form state:', e);
            }
        },

        // Announce to screen reader
        announceToScreenReader: function(message) {
            var $liveRegion = $('#acs-live-region');
            if ($liveRegion.length === 0) {
                $liveRegion = $('<div id="acs-live-region" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>');
                $('body').append($liveRegion);
            }
            
            $liveRegion.text(message);
            
            setTimeout(function() {
                $liveRegion.text('');
            }, 1000);
        },

        // Escape HTML
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#acs-generate-form').length) {
            ACSContentGenerator.init();
        }
    });

})(jQuery);
