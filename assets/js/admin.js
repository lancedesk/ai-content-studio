/**
 * Admin JavaScript for AI Content Studio
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize tabs
        initializeTabs();
        
        // Initialize API testing
        initializeApiTesting();
        
        // Initialize content generation
        initializeContentGeneration();
        
        // Initialize keyword suggestions
        initializeKeywordSuggestions();
        
        // Initialize settings save
        initializeSettingsSave();
        
    });

    /**
     * Initialize tab functionality
     */
    function initializeTabs() {
        $('.acs-tab').on('click', function(e) {
            e.preventDefault();
            
            var tabId = $(this).data('tab');
            
            // Remove active class from all tabs and content
            $('.acs-tab').removeClass('active');
            $('.acs-tab-content').removeClass('active');
            
            // Add active class to clicked tab and corresponding content
            $(this).addClass('active');
            $('#' + tabId).addClass('active');
        });
    }

    /**
     * Initialize API connection testing
     */
    function initializeApiTesting() {
        $('.acs-api-test').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var provider = button.data('provider');
            var apiKeyField = $('input[name="acs_settings[providers][' + provider + '][api_key]"]');
            var apiKey = apiKeyField.val();
            
            if (!apiKey) {
                showNotice('error', 'Please enter an API key first.');
                return;
            }
            
            button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: acs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'acs_test_api_connection',
                    provider: provider,
                    api_key: apiKey,
                    nonce: acs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data);
                        button.siblings('.acs-provider-status').removeClass('disconnected').addClass('connected').text('Connected');
                    } else {
                        showNotice('error', response.data);
                        button.siblings('.acs-provider-status').removeClass('connected').addClass('disconnected').text('Disconnected');
                    }
                },
                error: function() {
                    showNotice('error', 'Connection test failed.');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Connection');
                }
            });
        });
    }

    /**
     * Initialize content generation
     */
    function initializeContentGeneration() {
        // If providers are not configured server-side, disable the Generate button and show a notice
        try {
            var providersOk = (typeof acs_providers_ok !== 'undefined') ? acs_providers_ok : true;
        } catch (ex) {
            var providersOk = true;
        }
        if (!providersOk) {
            var form = $('#acs-generate-form');
            var submitButton = form.find('button[type="submit"]');
            submitButton.prop('disabled', true).attr('title', 'No AI provider configured. Configure API keys in Settings.');
            var settingsUrl = (typeof acs_ajax !== 'undefined' && acs_ajax.settings_url) ? acs_ajax.settings_url : '/wp-admin/admin.php?page=acs-settings';
            if ($('.acs-notices .acs-provider-warning').length === 0) {
                $('.acs-notices').prepend('<div class="notice notice-warning acs-provider-warning"><p>' + 'AI Content Studio: No AI provider configured. <a href="' + settingsUrl + '">Configure settings</a> to enable content generation.' + '</p></div>');
            }
            // Do not bind the submit handler when providers are not available
            return;
        }

        $('#acs-generate-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');
            // Build payload for REST generate endpoint
            var payload = {
                api_key: $('[name="groq_api_key"]').val() || $('[name="acs_settings[providers][groq][api_key]"]').val(),
                topic: $('#content_topic').val() || $('#acs-prompt').val(),
                keywords: $('#keywords').val() || $('#acs-keywords').val(),
                word_count: $('#word_count').val() || 'medium'
            };

            submitButton.prop('disabled', true).text(acs_ajax.strings.generating);
            $('.acs-generation-progress').show();

            var progress = 0;
            var progressInterval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                $('.acs-progress-bar').css('width', progress + '%');
            }, 1000);

            fetch( window.location.origin + '/wp-json/acs/v1/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': acs_ajax.rest_nonce
                },
                body: JSON.stringify( payload )
            } ).then(function(resp) {
                return resp.json();
            }).then(function(data) {
                clearInterval(progressInterval);
                $('.acs-progress-bar').css('width', '100%');
                if ( data && ! data.code ) {
                    showNotice('success', acs_ajax.strings.success);
                    displayGeneratedContent(data);
                } else {
                    showNotice('error', (data && data.message) ? data.message : acs_ajax.strings.error);
                }
            }).catch(function() {
                clearInterval(progressInterval);
                showNotice('error', acs_ajax.strings.error);
            }).finally(function() {
                submitButton.prop('disabled', false).text('Generate Content');
                $('.acs-generation-progress').hide();
                $('.acs-progress-bar').css('width', '0%');
            });
        });
    }

    /**
     * Initialize keyword suggestions
     */
    function initializeKeywordSuggestions() {
        $('.acs-get-keywords').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var topic = $('#acs-prompt').val();
            
            if (!topic) {
                showNotice('error', 'Please enter a topic first.');
                return;
            }
            
            button.prop('disabled', true).text('Getting suggestions...');
            
            $.ajax({
                url: acs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'acs_get_keyword_suggestions',
                    topic: topic,
                    nonce: acs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        displayKeywordSuggestions(response.data);
                    } else {
                        showNotice('error', response.data);
                    }
                },
                error: function() {
                    showNotice('error', 'Failed to get keyword suggestions.');
                },
                complete: function() {
                    button.prop('disabled', false).text('Get Keywords');
                }
            });
        });
    }

    /**
     * Initialize settings save
     */
    function initializeSettingsSave() {
        $('#acs-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');
            var formData = {
                action: 'acs_save_settings',
                nonce: acs_ajax.nonce,
                settings: form.serializeObject()
            };
            
            submitButton.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: acs_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // response.data may be a string or an object with message/providers_ok
                        var message = '';
                        var providersOk = false;
                        if ( typeof response.data === 'object' ) {
                            message = response.data.message || '';
                            providersOk = !!response.data.providers_ok;
                        } else {
                            message = response.data;
                        }
                        showNotice('success', message || acs_ajax.strings.success);

                        // If providers are now valid, enable Generate UI if present
                        if ( providersOk ) {
                            try {
                                window.acs_providers_ok = true;
                            } catch (ex) {}
                            var genBtn = $('#acs-generate-form button[type="submit"]');
                            if ( genBtn.length ) {
                                genBtn.prop('disabled', false).removeAttr('title');
                                $('.acs-provider-warning').remove();
                            }
                        }
                    } else {
                        showNotice('error', response.data);
                    }
                },
                error: function() {
                    showNotice('error', 'Failed to save settings.');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Save Settings');
                }
            });
        });
    }

    /**
     * Initialize meta box actions (Revalidate / Retry)
     */
    function initializeMetaBoxActions() {
        $(document).on('click', '.acs-revalidate-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var post_id = btn.data('post-id');
            // show spinner and disable both buttons for this post
            var container = btn.closest('.inside');
            var retryBtn = container.find('.acs-retry-btn');
            btn.prop('disabled', true).text('Validating...');
            retryBtn.prop('disabled', true);
            var spinner = $('<span class="acs-spinner" style="margin-left:8px;"></span>');
            btn.after(spinner);

            $.post( acs_ajax.ajax_url, {
                action: 'acs_revalidate_generation',
                nonce: acs_ajax.nonce,
                post_id: post_id
            }, function(resp) {
                if ( resp && resp.success ) {
                    if ( resp.data.valid ) {
                        showNotice('success', resp.data.message || 'Post content is valid.');
                        $('#acs-generation-report-ajax-' + post_id).html('<div class="notice notice-success"><p>' + (resp.data.message || 'Valid') + '</p></div>');
                    } else {
                        showNotice('error', 'Validation issues found.');
                        var html = '<div class="notice notice-warning"><strong>Validation issues:</strong><ul>';
                        if ( resp.data.errors && resp.data.errors.length ) {
                            resp.data.errors.forEach(function(err){ html += '<li>' + err + '</li>'; });
                        }
                        html += '</ul></div>';
                        $('#acs-generation-report-ajax-' + post_id).html(html);
                    }
                } else {
                    showNotice('error', (resp && resp.data) ? resp.data : 'Validation failed');
                }
            }).fail(function(){
                showNotice('error', 'Validation request failed.');
            }).always(function(){
                btn.prop('disabled', false).text('Revalidate');
                retryBtn.prop('disabled', false);
                spinner.remove();
            });
        });

        $(document).on('click', '.acs-retry-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var post_id = btn.data('post-id');
            if ( ! confirm('Are you sure you want to retry generation and update this post?') ) {
                return;
            }
            // show spinner and disable both buttons for this post
            var container = btn.closest('.inside');
            var revalidateBtn = container.find('.acs-revalidate-btn');
            btn.prop('disabled', true).text('Retrying...');
            revalidateBtn.prop('disabled', true);
            var spinner = $('<span class="acs-spinner" style="margin-left:8px;"></span>');
            btn.after(spinner);
            $.post( acs_ajax.ajax_url, {
                action: 'acs_retry_generation',
                nonce: acs_ajax.nonce,
                post_id: post_id
            }, function(resp) {
                if ( resp && resp.success ) {
                    showNotice('success', 'Retry completed. Report updated.');
                    var report = resp.data.report || {};
                    $('#acs-generation-report-ajax-' + post_id).html('<pre style="white-space:pre-wrap;">' + JSON.stringify(report, null, 2) + '</pre>');
                    if ( resp.data.edit_link ) {
                        // Optional: show link to edit
                        $('#acs-generation-report-ajax-' + post_id).append('<p><a href="' + resp.data.edit_link + '" target="_blank">Open post</a></p>');
                    }
                } else {
                    showNotice('error', (resp && resp.data) ? resp.data : 'Retry failed');
                }
            }).fail(function(){
                showNotice('error', 'Retry request failed.');
            }).always(function(){
                btn.prop('disabled', false).text('Retry');
                revalidateBtn.prop('disabled', false);
                spinner.remove();
            });
        });

        $(document).on('click', '.acs-autofix-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var post_id = btn.data('post-id');
            var container = btn.closest('.inside');
            var spinner = $('<span class="acs-spinner" style="margin-left:8px;"></span>');
            btn.prop('disabled', true).text('Auto-Fixing...');
            btn.after(spinner);
            $.post(acs_ajax.ajax_url, {
                action: 'acs_autofix_generation',
                nonce: acs_ajax.nonce,
                post_id: post_id
            }, function(resp) {
                if (resp && resp.success) {
                    var html = '<div class="notice notice-success"><strong>Auto-fix applied.</strong>';
                    if (resp.data.changed) {
                        html += '<p>Post content and meta updated.</p>';
                    } else {
                        html += '<p>No changes were needed.</p>';
                    }
                    if (resp.data.report) {
                        html += '<pre>' + JSON.stringify(resp.data.report, null, 2) + '</pre>';
                    }
                    html += '</div>';
                    $('#acs-generation-report-ajax-' + post_id).html(html);
                } else {
                    var msg = (resp && resp.data) ? resp.data : 'Auto-fix failed.';
                    $('#acs-generation-report-ajax-' + post_id).html('<div class="notice notice-error"><p>' + msg + '</p></div>');
                }
            }).fail(function(){
                $('#acs-generation-report-ajax-' + post_id).html('<div class="notice notice-error"><p>Auto-fix request failed.</p></div>');
            }).always(function(){
                btn.prop('disabled', false).text('Auto-Fix & Update');
                spinner.remove();
            });
        });
    }

    // Initialize meta box actions
    $(document).ready(function(){
        initializeMetaBoxActions();
    });

    /**
     * Display generated content
     */
    function displayGeneratedContent(data) {
        var contentHtml = `
            <div class="acs-generated-result">
                <h3>Generated Content</h3>
                <div class="acs-content-preview">
                    <h4>Title:</h4>
                    <p><strong>${data.title}</strong></p>
                    
                    <h4>Meta Description:</h4>
                    <p>${data.meta_description}</p>
                    
                    <h4>Content Preview:</h4>
                    <div class="acs-content-body">${data.content.substring(0, 500)}...</div>
                    
                    <div class="acs-content-actions">
                        <button class="acs-button" onclick="createPost(${JSON.stringify(data).replace(/"/g, '&quot;')})">
                            Create Post
                        </button>
                        <button class="acs-button secondary" onclick="copyToClipboard('${data.content.replace(/'/g, "\\'")}')">
                            Copy Content
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('.acs-generation-results').html(contentHtml).show();
    }

    /**
     * Display keyword suggestions
     */
    function displayKeywordSuggestions(keywords) {
        var keywordHtml = '<div class="acs-keyword-suggestions">';
        keywordHtml += '<h4>Suggested Keywords:</h4>';
        keywordHtml += '<ul>';
        
        keywords.forEach(function(keyword) {
            keywordHtml += `<li><a href="#" class="acs-keyword-suggestion" data-keyword="${keyword}">${keyword}</a></li>`;
        });
        
        keywordHtml += '</ul></div>';
        
        $('.acs-keyword-results').html(keywordHtml).show();
        
        // Handle keyword suggestion clicks
        $('.acs-keyword-suggestion').on('click', function(e) {
            e.preventDefault();
            var keyword = $(this).data('keyword');
            $('#acs-keywords').val($('#acs-keywords').val() + (($('#acs-keywords').val() ? ', ' : '') + keyword));
        });
    }

    /**
     * Show notice message
     */
    function showNotice(type, message) {
        var notice = $('<div class="acs-notice ' + type + '">' + message + '</div>');
        $('.acs-notices').empty().append(notice);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }

    /**
     * Serialize form to object
     */
    $.fn.serializeObject = function() {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

})(jQuery);

/**
 * Global functions for content actions
 */

function createPost(data) {
    // Create a new post with the generated content
    var form = document.createElement('form');
    form.method = 'post';
    form.action = acs_ajax.ajax_url;
    
    // Add form fields
    var fields = {
        'action': 'acs_create_post',
        'nonce': acs_ajax.nonce,
        'title': data.title,
        'content': data.content,
        'meta_description': data.meta_description,
        'slug': data.slug,
        'focus_keyword': data.focus_keyword,
        'tags': data.tags.join(',')
    };
    
    for (var field in fields) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = field;
        input.value = fields[field];
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Content copied to clipboard!');
        });
    } else {
        // Fallback for older browsers
        var textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Content copied to clipboard!');
    }
}