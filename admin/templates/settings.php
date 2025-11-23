<?php
/**
 * Settings template for AI Content Studio
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
                            <label>
                                <input type="checkbox" name="acs_settings[providers][groq][enabled]" value="1" 
                                    <?php checked(!empty($settings['providers']['groq']['enabled']), true); ?>>
                                <?php esc_html_e('Enable Groq provider', 'ai-content-studio'); ?>
                            </label>
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
                            <label>
                                <input type="checkbox" name="acs_settings[providers][openai][enabled]" value="1" 
                                    <?php checked(!empty($settings['providers']['openai']['enabled']), true); ?>>
                                <?php esc_html_e('Enable OpenAI provider', 'ai-content-studio'); ?>
                            </label>
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
                            <label>
                                <input type="checkbox" name="acs_settings[providers][anthropic][enabled]" value="1" 
                                    <?php checked(!empty($settings['providers']['anthropic']['enabled']), true); ?>>
                                <?php esc_html_e('Enable Anthropic provider', 'ai-content-studio'); ?>
                            </label>
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
                
                <div class="acs-checkbox-group">
                    <label>
                        <input type="checkbox" name="acs_settings[content][auto_publish]" value="1" 
                            <?php checked(!empty($settings['content']['auto_publish']), true); ?>>
                        <?php esc_html_e('Auto-publish generated content', 'ai-content-studio'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="acs_settings[content][include_images]" value="1" 
                            <?php checked(!empty($settings['content']['include_images']), true); ?>>
                        <?php esc_html_e('Generate image suggestions by default', 'ai-content-studio'); ?>
                    </label>
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
                
                <div class="acs-checkbox-group">
                    <label>
                        <input type="checkbox" name="acs_settings[seo][auto_optimize]" value="1" 
                            <?php checked(!empty($settings['seo']['auto_optimize']), true); ?>>
                        <?php esc_html_e('Auto-optimize content for SEO', 'ai-content-studio'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="acs_settings[seo][internal_linking]" value="1" 
                            <?php checked(!empty($settings['seo']['internal_linking']), true); ?>>
                        <?php esc_html_e('Suggest internal links automatically', 'ai-content-studio'); ?>
                    </label>
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
                
                <div class="acs-checkbox-group">
                    <label>
                        <input type="checkbox" name="acs_settings[advanced][rate_limiting]" value="1" 
                            <?php checked(!empty($settings['advanced']['rate_limiting']), true); ?>>
                        <?php esc_html_e('Enable rate limiting protection', 'ai-content-studio'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="acs_settings[advanced][cost_tracking]" value="1" 
                            <?php checked(!empty($settings['advanced']['cost_tracking']), true); ?>>
                        <?php esc_html_e('Track API usage costs', 'ai-content-studio'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="acs_settings[advanced][backup_providers]" value="1" 
                            <?php checked(!empty($settings['advanced']['backup_providers']), true); ?>>
                        <?php esc_html_e('Auto-fallback to backup providers', 'ai-content-studio'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="acs_settings[advanced][enable_logging]" value="1" 
                            <?php checked(!empty($settings['advanced']['enable_logging']), true); ?>>
                        <?php esc_html_e('Enable generation logging (write logs to plugin logs)', 'ai-content-studio'); ?>
                    </label>
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
    
});
</script>