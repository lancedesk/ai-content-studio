<?php
/**
 * Settings template for AI Content Studio
 * Modern settings interface with toggle switches, import/export, and validation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = get_option('acs_settings', array());

// Default settings structure
$default_settings = array(
    'providers' => array(),
    'default_provider' => 'groq',
    'content' => array(
        'default_word_count' => '750-1500',
        'default_tone' => 'professional',
        'auto_publish' => false,
        'include_images' => true,
        'humanization_level' => 'medium'
    ),
    'seo' => array(
        'auto_optimize' => true,
        'meta_description_length' => 155,
        'focus_keyword_density' => '1-2',
        'internal_linking' => true
    ),
    'advanced' => array(
        'rate_limiting' => true,
        'cost_tracking' => true,
        'backup_providers' => true,
        'logging_level' => 'standard'
    )
);

// Merge with current settings
$settings = array_merge_recursive($default_settings, $settings);

/**
 * Helper function to render a toggle switch
 */
function acs_render_toggle( $name, $checked, $label, $description = '', $disabled = false ) {
    $id = sanitize_title( str_replace( array('[', ']'), '-', $name ) );
    $disabled_class = $disabled ? 'acs-toggle-wrapper--disabled' : '';
    $disabled_attr = $disabled ? 'disabled' : '';
    ?>
    <label class="acs-toggle-wrapper <?php echo esc_attr( $disabled_class ); ?>">
        <span class="acs-toggle">
            <input type="checkbox" 
                   class="acs-toggle-input" 
                   id="<?php echo esc_attr( $id ); ?>" 
                   name="<?php echo esc_attr( $name ); ?>" 
                   value="1" 
                   <?php checked( $checked, true ); ?> 
                   <?php echo esc_attr( $disabled_attr ); ?>>
            <span class="acs-toggle-slider"></span>
        </span>
        <span class="acs-toggle-label">
            <?php echo esc_html( $label ); ?>
            <?php if ( $description ) : ?>
                <span class="acs-toggle-label-description"><?php echo esc_html( $description ); ?></span>
            <?php endif; ?>
        </span>
    </label>
    <?php
}
?>

<div class="wrap acs-admin-page">
    <h1><?php esc_html_e('AI Content Studio Settings', 'ai-content-studio'); ?></h1>
    
    <!-- Settings Tabs -->
    <div class="acs-tabs">
        <div class="acs-tab-nav">
            <button class="acs-tab active" data-tab="providers">
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php esc_html_e('AI Providers', 'ai-content-studio'); ?>
            </button>
            <button class="acs-tab" data-tab="content">
                <span class="dashicons dashicons-edit-large"></span>
                <?php esc_html_e('Content Settings', 'ai-content-studio'); ?>
            </button>
            <button class="acs-tab" data-tab="seo">
                <span class="dashicons dashicons-chart-line"></span>
                <?php esc_html_e('SEO Settings', 'ai-content-studio'); ?>
            </button>
            <button class="acs-tab" data-tab="advanced">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Advanced', 'ai-content-studio'); ?>
            </button>
        </div>
        
        <form id="acs-settings-form" class="acs-form" method="post">
            <?php wp_nonce_field( 'acs_settings_action', 'acs_settings_nonce' ); ?>
            
            <!-- AI Providers Tab -->
            <div id="providers" class="acs-tab-content active">
                <h2><?php esc_html_e('AI Provider Configuration', 'ai-content-studio'); ?></h2>
                <p class="description"><?php esc_html_e('Configure your AI service providers. At least one provider must be configured for the plugin to work.', 'ai-content-studio'); ?></p>
                
                <!-- Groq Settings -->
                <div class="acs-provider-section">
                    <h3>
                        <span class="acs-provider-icon">🚀</span>
                        <?php esc_html_e('Groq (Recommended)', 'ai-content-studio'); ?>
                        <span class="acs-provider-status <?php echo !empty($settings['providers']['groq']['api_key']) ? 'connected' : 'disconnected'; ?>">
                            <?php echo !empty($settings['providers']['groq']['api_key']) ? esc_html__('Connected', 'ai-content-studio') : esc_html__('Disconnected', 'ai-content-studio'); ?>
                        </span>
                    </h3>
                    
                    <div class="acs-form-row">
                        <div class="acs-form-group">
                            <label for="groq-api-key"><?php esc_html_e('API Key', 'ai-content-studio'); ?></label>
                            <input type="password" id="groq-api-key" 
                                name="acs_settings[providers][groq][api_key]" 
                                class="acs-input" 
                                value="<?php echo esc_attr($settings['providers']['groq']['api_key'] ?? ''); ?>"
                                placeholder="gsk_...">
                            <p class="description">
                                <?php printf(
                                    esc_html__('Get your API key from %s', 'ai-content-studio'),
                                    '<a href="https://console.groq.com/keys" target="_blank">Groq Console</a>'
                                ); ?>
                            </p>
                        </div>
                        <div class="acs-form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="acs-button secondary acs-api-test" data-provider="groq">
                                <?php esc_html_e('Test Connection', 'ai-content-studio'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="acs-form-row">
                        <div class="acs-form-group">
                            <?php 
                            acs_render_toggle(
                                'acs_settings[providers][groq][enabled]',
                                !empty($settings['providers']['groq']['enabled']),
                                __('Enable Groq provider', 'ai-content-studio'),
                                __('Use Groq for AI content generation', 'ai-content-studio')
                            );
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- OpenAI Settings -->
                <div class="acs-provider-section">
                    <h3>
                        <span class="acs-provider-icon">🤖</span>
                        <?php esc_html_e('OpenAI', 'ai-content-studio'); ?>
                        <span class="acs-provider-status <?php echo !empty($settings['providers']['openai']['api_key']) ? 'connected' : 'disconnected'; ?>">
                            <?php echo !empty($settings['providers']['openai']['api_key']) ? esc_html__('Connected', 'ai-content-studio') : esc_html__('Disconnected', 'ai-content-studio'); ?>
                        </span>
                    </h3>
                    
                    <div class="acs-form-row">
                        <div class="acs-form-group">
                            <label for="openai-api-key"><?php esc_html_e('API Key', 'ai-content-studio'); ?></label>
                            <input type="password" id="openai-api-key" 
                                name="acs_settings[providers][openai][api_key]" 
                                class="acs-input" 
                                value="<?php echo esc_attr($settings['providers']['openai']['api_key'] ?? ''); ?>"
                                placeholder="sk-...">
                            <p class="description">
                                <?php printf(
                                    esc_html__('Get your API key from %s', 'ai-content-studio'),
                                    '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>'
                                ); ?>
                            </p>
                        </div>
                        <div class="acs-form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="acs-button secondary acs-api-test" data-provider="openai">
                                <?php esc_html_e('Test Connection', 'ai-content-studio'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="acs-form-row">
                        <div class="acs-form-group">
                            <?php 
                            acs_render_toggle(
                                'acs_settings[providers][openai][enabled]',
                                !empty($settings['providers']['openai']['enabled']),
                                __('Enable OpenAI provider', 'ai-content-studio'),
                                __('Use OpenAI GPT models for content generation', 'ai-content-studio')
                            );
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Anthropic Settings -->
                <div class="acs-provider-section">
                    <h3>
                        <span class="acs-provider-icon">🧠</span>
                        <?php esc_html_e('Anthropic (Claude)', 'ai-content-studio'); ?>
                        <span class="acs-provider-status <?php echo !empty($settings['providers']['anthropic']['api_key']) ? 'connected' : 'disconnected'; ?>">
                            <?php echo !empty($settings['providers']['anthropic']['api_key']) ? esc_html__('Connected', 'ai-content-studio') : esc_html__('Disconnected', 'ai-content-studio'); ?>
                        </span>
                    </h3>
                    
                    <div class="acs-form-row">
                        <div class="acs-form-group">
                            <label for="anthropic-api-key"><?php esc_html_e('API Key', 'ai-content-studio'); ?></label>
                            <input type="password" id="anthropic-api-key" 
                                name="acs_settings[providers][anthropic][api_key]" 
                                class="acs-input" 
                                value="<?php echo esc_attr($settings['providers']['anthropic']['api_key'] ?? ''); ?>"
                                placeholder="sk-ant-...">
                            <p class="description">
                                <?php printf(
                                    esc_html__('Get your API key from %s', 'ai-content-studio'),
                                    '<a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a>'
                                ); ?>
                            </p>
                        </div>
                        <div class="acs-form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="acs-button secondary acs-api-test" data-provider="anthropic">
                                <?php esc_html_e('Test Connection', 'ai-content-studio'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="acs-form-row">
                        <div class="acs-form-group">
                            <?php 
                            acs_render_toggle(
                                'acs_settings[providers][anthropic][enabled]',
                                !empty($settings['providers']['anthropic']['enabled']),
                                __('Enable Anthropic provider', 'ai-content-studio'),
                                __('Use Claude models for content generation', 'ai-content-studio')
                            );
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Default Provider -->
                <div class="acs-form-group">
                    <label for="default-provider"><?php esc_html_e('Default Provider', 'ai-content-studio'); ?></label>
                    <select id="default-provider" name="acs_settings[default_provider]" class="acs-select">
                        <option value="groq" <?php selected($settings['default_provider'], 'groq'); ?>><?php esc_html_e('Groq', 'ai-content-studio'); ?></option>
                        <option value="openai" <?php selected($settings['default_provider'], 'openai'); ?>><?php esc_html_e('OpenAI', 'ai-content-studio'); ?></option>
                        <option value="anthropic" <?php selected($settings['default_provider'], 'anthropic'); ?>><?php esc_html_e('Anthropic', 'ai-content-studio'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('The primary AI provider to use for content generation.', 'ai-content-studio'); ?></p>
                </div>

                <!-- Image Provider Selector -->
                <div class="acs-form-group">
                    <label for="image-provider"><?php esc_html_e('Image Provider', 'ai-content-studio'); ?></label>
                    <select id="image-provider" name="acs_settings[image_provider]" class="acs-select">
                        <option value="openai" <?php selected($settings['image_provider'] ?? '', 'openai'); ?>><?php esc_html_e('OpenAI (DALL·E)', 'ai-content-studio'); ?></option>
                        <option value="stable_diffusion" <?php selected($settings['image_provider'] ?? '', 'stable_diffusion'); ?>><?php esc_html_e('Stable Diffusion (External)', 'ai-content-studio'); ?></option>
                        <option value="unsplash" <?php selected($settings['image_provider'] ?? '', 'unsplash'); ?>><?php esc_html_e('Unsplash (Fallback)', 'ai-content-studio'); ?></option>
                        <option value="none" <?php selected($settings['image_provider'] ?? '', 'none'); ?>><?php esc_html_e('None', 'ai-content-studio'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Provider used for featured image generation. OpenAI will be used if configured.', 'ai-content-studio'); ?></p>
                </div>

                <!-- Backup Providers (checkboxes) -->
                <div class="acs-form-group">
                    <label><?php esc_html_e('Backup Providers', 'ai-content-studio'); ?></label>
                    <p class="description"><?php esc_html_e('Select providers to use as backups if the default fails.', 'ai-content-studio'); ?></p>
                    <label><input type="checkbox" name="acs_settings[backup_providers][]" value="groq" <?php echo in_array('groq', $settings['backup_providers'] ?? array()) ? 'checked' : ''; ?>> <?php esc_html_e('Groq', 'ai-content-studio'); ?></label>
                    <label><input type="checkbox" name="acs_settings[backup_providers][]" value="openai" <?php echo in_array('openai', $settings['backup_providers'] ?? array()) ? 'checked' : ''; ?>> <?php esc_html_e('OpenAI', 'ai-content-studio'); ?></label>
                    <label><input type="checkbox" name="acs_settings[backup_providers][]" value="anthropic" <?php echo in_array('anthropic', $settings['backup_providers'] ?? array()) ? 'checked' : ''; ?>> <?php esc_html_e('Anthropic', 'ai-content-studio'); ?></label>
                </div>
            </div>
            
            <!-- Content Settings Tab -->
            <div id="content" class="acs-tab-content">
                <h2><?php esc_html_e('Content Generation Settings', 'ai-content-studio'); ?></h2>
                
                <div class="acs-form-row">
                    <div class="acs-form-group">
                        <label for="default-word-count"><?php esc_html_e('Default Word Count', 'ai-content-studio'); ?></label>
                        <select id="default-word-count" name="acs_settings[content][default_word_count]" class="acs-select">
                            <option value="500-750" <?php selected($settings['content']['default_word_count'], '500-750'); ?>><?php esc_html_e('Short (500-750)', 'ai-content-studio'); ?></option>
                            <option value="750-1500" <?php selected($settings['content']['default_word_count'], '750-1500'); ?>><?php esc_html_e('Medium (750-1500)', 'ai-content-studio'); ?></option>
                            <option value="1500-2500" <?php selected($settings['content']['default_word_count'], '1500-2500'); ?>><?php esc_html_e('Long (1500-2500)', 'ai-content-studio'); ?></option>
                            <option value="2500-4000" <?php selected($settings['content']['default_word_count'], '2500-4000'); ?>><?php esc_html_e('Very Long (2500-4000)', 'ai-content-studio'); ?></option>
                        </select>
                    </div>
                    
                    <div class="acs-form-group">
                        <label for="default-tone"><?php esc_html_e('Default Writing Tone', 'ai-content-studio'); ?></label>
                        <select id="default-tone" name="acs_settings[content][default_tone]" class="acs-select">
                            <option value="professional" <?php selected($settings['content']['default_tone'], 'professional'); ?>><?php esc_html_e('Professional', 'ai-content-studio'); ?></option>
                            <option value="casual" <?php selected($settings['content']['default_tone'], 'casual'); ?>><?php esc_html_e('Casual', 'ai-content-studio'); ?></option>
                            <option value="friendly" <?php selected($settings['content']['default_tone'], 'friendly'); ?>><?php esc_html_e('Friendly', 'ai-content-studio'); ?></option>
                            <option value="authoritative" <?php selected($settings['content']['default_tone'], 'authoritative'); ?>><?php esc_html_e('Authoritative', 'ai-content-studio'); ?></option>
                            <option value="conversational" <?php selected($settings['content']['default_tone'], 'conversational'); ?>><?php esc_html_e('Conversational', 'ai-content-studio'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="acs-form-group">
                    <label for="humanization-level"><?php esc_html_e('Content Humanization Level', 'ai-content-studio'); ?></label>
                    <select id="humanization-level" name="acs_settings[content][humanization_level]" class="acs-select">
                        <option value="light" <?php selected($settings['content']['humanization_level'], 'light'); ?>><?php esc_html_e('Light', 'ai-content-studio'); ?></option>
                        <option value="medium" <?php selected($settings['content']['humanization_level'], 'medium'); ?>><?php esc_html_e('Medium', 'ai-content-studio'); ?></option>
                        <option value="heavy" <?php selected($settings['content']['humanization_level'], 'heavy'); ?>><?php esc_html_e('Heavy', 'ai-content-studio'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Higher levels add more human-like variations and reduce AI detection.', 'ai-content-studio'); ?></p>
                </div>
                
                <div class="acs-checkbox-group" style="display: flex; flex-direction: column; gap: var(--acs-spacing-md);">
                    <?php 
                    acs_render_toggle(
                        'acs_settings[content][auto_publish]',
                        !empty($settings['content']['auto_publish']),
                        __('Auto-publish generated content', 'ai-content-studio'),
                        __('Automatically publish posts after generation completes', 'ai-content-studio')
                    );
                    acs_render_toggle(
                        'acs_settings[content][include_images]',
                        !empty($settings['content']['include_images']),
                        __('Generate image suggestions by default', 'ai-content-studio'),
                        __('Include AI-generated image suggestions with content', 'ai-content-studio')
                    );
                    ?>
                </div>
            </div>
            
            <!-- SEO Settings Tab -->
            <div id="seo" class="acs-tab-content">
                <h2><?php esc_html_e('SEO Optimization Settings', 'ai-content-studio'); ?></h2>
                
                <div class="acs-form-row">
                    <div class="acs-form-group">
                        <label for="meta-description-length"><?php esc_html_e('Meta Description Length', 'ai-content-studio'); ?></label>
                        <input type="number" id="meta-description-length" 
                            name="acs_settings[seo][meta_description_length]" 
                            class="acs-input" 
                            value="<?php echo esc_attr($settings['seo']['meta_description_length']); ?>"
                            min="120" max="160">
                        <p class="description"><?php esc_html_e('Recommended: 155 characters', 'ai-content-studio'); ?></p>
                    </div>
                    
                    <div class="acs-form-group">
                        <label for="keyword-density"><?php esc_html_e('Focus Keyword Density (%)', 'ai-content-studio'); ?></label>
                        <select id="keyword-density" name="acs_settings[seo][focus_keyword_density]" class="acs-select">
                            <option value="0.5-1" <?php selected($settings['seo']['focus_keyword_density'], '0.5-1'); ?>><?php esc_html_e('Light (0.5-1%)', 'ai-content-studio'); ?></option>
                            <option value="1-2" <?php selected($settings['seo']['focus_keyword_density'], '1-2'); ?>><?php esc_html_e('Normal (1-2%)', 'ai-content-studio'); ?></option>
                            <option value="2-3" <?php selected($settings['seo']['focus_keyword_density'], '2-3'); ?>><?php esc_html_e('High (2-3%)', 'ai-content-studio'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="acs-form-row">
                    <div class="acs-form-group">
                        <label for="max-passive-voice"><?php esc_html_e('Maximum Passive Voice (%)', 'ai-content-studio'); ?></label>
                        <input type="number" id="max-passive-voice" 
                            name="acs_settings[seo][max_passive_voice]" 
                            class="acs-input" 
                            value="<?php echo esc_attr($settings['seo']['max_passive_voice'] ?? 10); ?>"
                            min="5" max="20" step="0.5">
                        <p class="description"><?php esc_html_e('Recommended: 10% or less', 'ai-content-studio'); ?></p>
                    </div>
                    
                    <div class="acs-form-group">
                        <label for="min-transition-words"><?php esc_html_e('Minimum Transition Words (%)', 'ai-content-studio'); ?></label>
                        <input type="number" id="min-transition-words" 
                            name="acs_settings[seo][min_transition_words]" 
                            class="acs-input" 
                            value="<?php echo esc_attr($settings['seo']['min_transition_words'] ?? 30); ?>"
                            min="20" max="50" step="1">
                        <p class="description"><?php esc_html_e('Recommended: 30% or more', 'ai-content-studio'); ?></p>
                    </div>
                </div>
                
                <div class="acs-form-row">
                    <div class="acs-form-group">
                        <label for="max-long-sentences"><?php esc_html_e('Maximum Long Sentences (%)', 'ai-content-studio'); ?></label>
                        <input type="number" id="max-long-sentences" 
                            name="acs_settings[seo][max_long_sentences]" 
                            class="acs-input" 
                            value="<?php echo esc_attr($settings['seo']['max_long_sentences'] ?? 25); ?>"
                            min="15" max="40" step="1">
                        <p class="description"><?php esc_html_e('Sentences over 20 words. Recommended: 25% or less', 'ai-content-studio'); ?></p>
                    </div>
                    
                    <div class="acs-form-group">
                        <label for="max-title-length"><?php esc_html_e('Maximum Title Length', 'ai-content-studio'); ?></label>
                        <input type="number" id="max-title-length" 
                            name="acs_settings[seo][max_title_length]" 
                            class="acs-input" 
                            value="<?php echo esc_attr($settings['seo']['max_title_length'] ?? 66); ?>"
                            min="50" max="70">
                        <p class="description"><?php esc_html_e('Recommended: 66 characters or less', 'ai-content-studio'); ?></p>
                    </div>
                </div>
                
                <div class="acs-checkbox-group" style="display: flex; flex-direction: column; gap: var(--acs-spacing-md);">
                    <?php 
                    acs_render_toggle(
                        'acs_settings[seo][auto_optimize]',
                        !empty($settings['seo']['auto_optimize']),
                        __('Auto-optimize content for SEO', 'ai-content-studio'),
                        __('Automatically apply SEO optimizations during generation', 'ai-content-studio')
                    );
                    acs_render_toggle(
                        'acs_settings[seo][internal_linking]',
                        !empty($settings['seo']['internal_linking']),
                        __('Suggest internal links automatically', 'ai-content-studio'),
                        __('AI will suggest relevant internal links from your existing content', 'ai-content-studio')
                    );
                    acs_render_toggle(
                        'acs_settings[seo][adaptive_rules]',
                        !empty($settings['seo']['adaptive_rules']),
                        __('Enable adaptive validation rules', 'ai-content-studio'),
                        __('Automatically adjust validation rules based on error patterns', 'ai-content-studio')
                    );
                    acs_render_toggle(
                        'acs_settings[seo][comprehensive_validation]',
                        !empty($settings['seo']['comprehensive_validation']),
                        __('Enable comprehensive SEO validation', 'ai-content-studio'),
                        __('Apply full SEO validation pipeline to all generated content', 'ai-content-studio')
                    );
                    ?>
                </div>
            </div>
            
            <!-- Advanced Settings Tab -->
            <div id="advanced" class="acs-tab-content">
                <h2><?php esc_html_e('Advanced Settings', 'ai-content-studio'); ?></h2>
                
                <div class="acs-form-group">
                    <label for="logging-level"><?php esc_html_e('Logging Level', 'ai-content-studio'); ?></label>
                    <select id="logging-level" name="acs_settings[advanced][logging_level]" class="acs-select">
                        <option value="minimal" <?php selected($settings['advanced']['logging_level'], 'minimal'); ?>><?php esc_html_e('Minimal', 'ai-content-studio'); ?></option>
                        <option value="standard" <?php selected($settings['advanced']['logging_level'], 'standard'); ?>><?php esc_html_e('Standard', 'ai-content-studio'); ?></option>
                        <option value="detailed" <?php selected($settings['advanced']['logging_level'], 'detailed'); ?>><?php esc_html_e('Detailed', 'ai-content-studio'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Higher levels provide more debugging information but may impact performance.', 'ai-content-studio'); ?></p>
                </div>
                
                <div class="acs-checkbox-group" style="display: flex; flex-direction: column; gap: var(--acs-spacing-md);">
                    <?php 
                    acs_render_toggle(
                        'acs_settings[advanced][rate_limiting]',
                        !empty($settings['advanced']['rate_limiting']),
                        __('Enable rate limiting protection', 'ai-content-studio'),
                        __('Prevent excessive API calls and avoid rate limit errors', 'ai-content-studio')
                    );
                    acs_render_toggle(
                        'acs_settings[advanced][cost_tracking]',
                        !empty($settings['advanced']['cost_tracking']),
                        __('Track API usage costs', 'ai-content-studio'),
                        __('Monitor token usage and estimated costs in analytics', 'ai-content-studio')
                    );
                    acs_render_toggle(
                        'acs_settings[advanced][backup_providers]',
                        !empty($settings['advanced']['backup_providers']),
                        __('Auto-fallback to backup providers', 'ai-content-studio'),
                        __('Automatically switch to backup provider if primary fails', 'ai-content-studio')
                    );
                    acs_render_toggle(
                        'acs_settings[advanced][enable_logging]',
                        !empty($settings['advanced']['enable_logging']),
                        __('Enable generation logging', 'ai-content-studio'),
                        __('Write detailed logs for debugging (stored in wp-content/uploads/acs-logs)', 'ai-content-studio')
                    );
                    ?>
                </div>
                
                <!-- Import/Export Settings -->
                <div class="acs-import-export-section">
                    <h3><?php esc_html_e('Import/Export Settings', 'ai-content-studio'); ?></h3>
                    <p class="description" style="width: 100%;"><?php esc_html_e('Export your settings to a file or import settings from another installation.', 'ai-content-studio'); ?></p>
                    
                    <button type="button" class="acs-button secondary acs-export-btn" id="acs-export-settings">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export Settings', 'ai-content-studio'); ?>
                    </button>
                    
                    <label class="acs-button secondary acs-import-btn">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Import Settings', 'ai-content-studio'); ?>
                        <input type="file" class="acs-import-file-input" id="acs-import-file" accept=".json">
                    </label>
                    <span class="acs-import-filename" id="acs-import-filename"></span>
                </div>
                
                <!-- Reset Settings -->
                <div class="acs-danger-zone">
                    <h3><?php esc_html_e('Danger Zone', 'ai-content-studio'); ?></h3>
                    <p class="description"><?php esc_html_e('These actions cannot be undone.', 'ai-content-studio'); ?></p>
                    <button type="button" class="acs-button danger" id="acs-reset-settings">
                        <?php esc_html_e('Reset All Settings', 'ai-content-studio'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="acs-form-actions">
                <button type="submit" name="submit" value="1" class="acs-button primary">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Save Settings', 'ai-content-studio'); ?>
                </button>
            </div>
            
        </form>
    </div>
    
    <!-- Notices -->
    <div class="acs-notices"></div>
    
</div>

<script>
jQuery(document).ready(function($) {
    
    // Reset settings confirmation
    $('#acs-reset-settings').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('<?php esc_html_e('Are you sure you want to reset all settings? This cannot be undone.', 'ai-content-studio'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'acs_reset_settings',
                    nonce: '<?php echo wp_create_nonce('acs_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Error resetting settings');
                    }
                }
            });
        }
    });
    
    // Export settings
    $('#acs-export-settings').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'acs_export_settings',
                nonce: '<?php echo wp_create_nonce('acs_ajax_nonce'); ?>'
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Create download
                    var dataStr = JSON.stringify(response.data, null, 2);
                    var blob = new Blob([dataStr], {type: 'application/json'});
                    var url = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.download = 'acs-settings-' + new Date().toISOString().slice(0,10) + '.json';
                    link.href = url;
                    link.click();
                    URL.revokeObjectURL(url);
                } else {
                    alert(response.data || '<?php esc_html_e('Error exporting settings', 'ai-content-studio'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e('Error exporting settings', 'ai-content-studio'); ?>');
            }
        });
    });
    
    // Import settings - file selection
    $('#acs-import-file').on('change', function(e) {
        var file = this.files[0];
        if (!file) {
            $('#acs-import-filename').text('');
            return;
        }
        
        $('#acs-import-filename').text(file.name);
        
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var settings = JSON.parse(e.target.result);
                
                if (confirm('<?php esc_html_e('Are you sure you want to import these settings? Your current settings will be overwritten.', 'ai-content-studio'); ?>')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'acs_import_settings',
                            nonce: '<?php echo wp_create_nonce('acs_ajax_nonce'); ?>',
                            settings: JSON.stringify(settings)
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php esc_html_e('Settings imported successfully!', 'ai-content-studio'); ?>');
                                location.reload();
                            } else {
                                alert(response.data || '<?php esc_html_e('Error importing settings', 'ai-content-studio'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php esc_html_e('Error importing settings', 'ai-content-studio'); ?>');
                        }
                    });
                }
            } catch (err) {
                alert('<?php esc_html_e('Invalid settings file. Please select a valid JSON file.', 'ai-content-studio'); ?>');
            }
        };
        reader.readAsText(file);
    });

    // Inline validation for number inputs
    $('input[type="number"]').on('change', function() {
        var $input = $(this);
        var val = parseFloat($input.val());
        var min = parseFloat($input.attr('min'));
        var max = parseFloat($input.attr('max'));
        var $feedback = $input.siblings('.acs-field-validation');
        
        // Remove existing feedback
        $feedback.remove();
        
        if (isNaN(val)) {
            $input.after('<div class="acs-field-validation acs-field-validation--error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Please enter a valid number', 'ai-content-studio'); ?></div>');
        } else if (!isNaN(min) && val < min) {
            $input.val(min);
            $input.after('<div class="acs-field-validation acs-field-validation--warning"><span class="dashicons dashicons-info"></span> <?php esc_html_e('Value adjusted to minimum', 'ai-content-studio'); ?>: ' + min + '</div>');
        } else if (!isNaN(max) && val > max) {
            $input.val(max);
            $input.after('<div class="acs-field-validation acs-field-validation--warning"><span class="dashicons dashicons-info"></span> <?php esc_html_e('Value adjusted to maximum', 'ai-content-studio'); ?>: ' + max + '</div>');
        }
    });
    
});
</script>