<?php
/**
 * Content Generator template for AI Content Studio
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = get_option('acs_settings', array());
// Available AI providers for the dropdown
$providers = array(
    'groq' => array('name' => 'Groq', 'models' => array('mixtral-8x7b-32768', 'llama2-70b-4096', 'gemma-7b-it')),
    'openai' => array('name' => 'OpenAI', 'models' => array('gpt-4', 'gpt-3.5-turbo')),
    'anthropic' => array('name' => 'Anthropic', 'models' => array('claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'))
);
$default_provider = isset($settings['default_provider']) ? $settings['default_provider'] : 'groq';
// Determine whether any provider is enabled with a non-empty API key
$providers_valid = false;
if ( ! empty( $settings['providers'] ) && is_array( $settings['providers'] ) ) {
    foreach ( $settings['providers'] as $p ) {
        $enabled = isset( $p['enabled'] ) && ( $p['enabled'] === true || $p['enabled'] === '1' || $p['enabled'] === 1 );
        $api_key = isset( $p['api_key'] ) ? trim( (string) $p['api_key'] ) : '';
        if ( $enabled && $api_key !== '' ) {
            $providers_valid = true;
            break;
        }
    }
}
?>

<div class="wrap acs-admin-page">
    <h1><?php esc_html_e('Content Generator', 'ai-content-studio'); ?></h1>
    
    <!-- Notices -->
    <div class="acs-notices"></div>
    
    <!-- Generation Form -->
    <div class="acs-generator-container">
        <form id="acs-generate-form" class="acs-form">
            <?php wp_nonce_field( 'acs_ajax_nonce', 'nonce', false ); ?>
            
            <!-- Content Type Selection -->
            <div class="acs-form-group">
                <label for="acs-content-type"><?php esc_html_e('Content Type', 'ai-content-studio'); ?></label>
                <select id="acs-content-type" name="content_type" class="acs-select">
                    <option value="blog_post"><?php esc_html_e('Blog Post', 'ai-content-studio'); ?></option>
                    <option value="article"><?php esc_html_e('Article', 'ai-content-studio'); ?></option>
                    <option value="product_review"><?php esc_html_e('Product Review', 'ai-content-studio'); ?></option>
                    <option value="how_to_guide"><?php esc_html_e('How-to Guide', 'ai-content-studio'); ?></option>
                    <option value="listicle"><?php esc_html_e('Listicle', 'ai-content-studio'); ?></option>
                    <option value="comparison"><?php esc_html_e('Comparison', 'ai-content-studio'); ?></option>
                    <option value="news"><?php esc_html_e('News Article', 'ai-content-studio'); ?></option>
                    <option value="opinion"><?php esc_html_e('Opinion Piece', 'ai-content-studio'); ?></option>
                </select>
            </div>
            
            <!-- Topic/Prompt -->
            <div class="acs-form-group">
                <label for="acs-prompt"><?php esc_html_e('Topic/Prompt', 'ai-content-studio'); ?></label>
                <textarea id="acs-prompt" name="prompt" class="acs-textarea" rows="4" 
                    placeholder="<?php esc_attr_e('Enter the main topic or idea for your content...', 'ai-content-studio'); ?>" required></textarea>
                <p class="description"><?php esc_html_e('Be specific about what you want to write about. Include target audience, angle, or key points to cover.', 'ai-content-studio'); ?></p>
            </div>
            
            <!-- Keywords -->
            <div class="acs-form-group">
                <label for="acs-keywords"><?php esc_html_e('Target Keywords', 'ai-content-studio'); ?></label>
                <div class="acs-input-group">
                    <input type="text" id="acs-keywords" name="keywords" class="acs-input" 
                        placeholder="<?php esc_attr_e('keyword 1, keyword 2, keyword 3...', 'ai-content-studio'); ?>">
                    <button type="button" class="acs-button secondary acs-get-keywords">
                        <?php esc_html_e('Get Suggestions', 'ai-content-studio'); ?>
                    </button>
                </div>
                <p class="description"><?php esc_html_e('Enter comma-separated keywords to optimize for SEO.', 'ai-content-studio'); ?></p>
                <div class="acs-keyword-results"></div>
            </div>
            
            <!-- Content Settings -->
            <div class="acs-form-row">
                <div class="acs-form-group">
                    <label for="acs-word-count"><?php esc_html_e('Target Word Count', 'ai-content-studio'); ?></label>
                    <select id="acs-word-count" name="word_count" class="acs-select">
                        <option value="500-750"><?php esc_html_e('Short (500-750 words)', 'ai-content-studio'); ?></option>
                        <option value="750-1500" selected><?php esc_html_e('Medium (750-1500 words)', 'ai-content-studio'); ?></option>
                        <option value="1500-2500"><?php esc_html_e('Long (1500-2500 words)', 'ai-content-studio'); ?></option>
                        <option value="2500-4000"><?php esc_html_e('Very Long (2500-4000 words)', 'ai-content-studio'); ?></option>
                    </select>
                </div>
                
                <div class="acs-form-group">
                    <label for="acs-tone"><?php esc_html_e('Writing Tone', 'ai-content-studio'); ?></label>
                    <select id="acs-tone" name="tone" class="acs-select">
                        <option value="professional"><?php esc_html_e('Professional', 'ai-content-studio'); ?></option>
                        <option value="casual"><?php esc_html_e('Casual', 'ai-content-studio'); ?></option>
                        <option value="friendly"><?php esc_html_e('Friendly', 'ai-content-studio'); ?></option>
                        <option value="authoritative"><?php esc_html_e('Authoritative', 'ai-content-studio'); ?></option>
                        <option value="conversational"><?php esc_html_e('Conversational', 'ai-content-studio'); ?></option>
                        <option value="formal"><?php esc_html_e('Formal', 'ai-content-studio'); ?></option>
                    </select>
                </div>
            </div>
            
            <!-- Advanced Settings -->
            <details class="acs-advanced-settings">
                <summary><?php esc_html_e('Advanced Settings', 'ai-content-studio'); ?></summary>
                
                <div class="acs-form-row">
                    <div class="acs-form-group">
                        <label for="acs-provider"><?php esc_html_e('AI Provider', 'ai-content-studio'); ?></label>
                        <select id="acs-provider" name="provider" class="acs-select">
                            <?php foreach ($providers as $provider_name => $provider) : ?>
                                <option value="<?php echo esc_attr($provider_name); ?>" 
                                    <?php selected($default_provider, $provider_name); ?>>
                                    <?php echo esc_html(ucfirst($provider_name)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="acs-form-group">
                        <label for="acs-model"><?php esc_html_e('Model', 'ai-content-studio'); ?></label>
                        <select id="acs-model" name="model" class="acs-select">
                            <option value="default"><?php esc_html_e('Default', 'ai-content-studio'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="acs-form-group">
                    <label for="acs-language"><?php esc_html_e('Language', 'ai-content-studio'); ?></label>
                    <select id="acs-language" name="language" class="acs-select">
                        <option value="en"><?php esc_html_e('English', 'ai-content-studio'); ?></option>
                        <option value="es"><?php esc_html_e('Spanish', 'ai-content-studio'); ?></option>
                        <option value="fr"><?php esc_html_e('French', 'ai-content-studio'); ?></option>
                        <option value="de"><?php esc_html_e('German', 'ai-content-studio'); ?></option>
                        <option value="it"><?php esc_html_e('Italian', 'ai-content-studio'); ?></option>
                        <option value="pt"><?php esc_html_e('Portuguese', 'ai-content-studio'); ?></option>
                        <option value="nl"><?php esc_html_e('Dutch', 'ai-content-studio'); ?></option>
                        <option value="pl"><?php esc_html_e('Polish', 'ai-content-studio'); ?></option>
                    </select>
                </div>
                
                <div class="acs-form-group">
                    <label for="acs-audience"><?php esc_html_e('Target Audience', 'ai-content-studio'); ?></label>
                    <input type="text" id="acs-audience" name="audience" class="acs-input" 
                        placeholder="<?php esc_attr_e('e.g., beginners, professionals, parents...', 'ai-content-studio'); ?>">
                </div>
                
                <div class="acs-form-group">
                    <label for="acs-structure"><?php esc_html_e('Content Structure', 'ai-content-studio'); ?></label>
                    <textarea id="acs-structure" name="structure" class="acs-textarea" rows="3" 
                        placeholder="<?php esc_attr_e('Specify headings, sections, or outline (optional)...', 'ai-content-studio'); ?>"></textarea>
                </div>
                
                <div class="acs-checkbox-group">
                    <label>
                        <input type="checkbox" name="include_images" value="1" checked>
                        <?php esc_html_e('Generate image suggestions', 'ai-content-studio'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="include_faq" value="1">
                        <?php esc_html_e('Include FAQ section', 'ai-content-studio'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="include_conclusion" value="1" checked>
                        <?php esc_html_e('Include conclusion', 'ai-content-studio'); ?>
                    </label>
                </div>
                
            </details>
            
            <!-- Submit Button -->
            <div class="acs-form-actions">
                <button type="submit" class="acs-button primary">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Generate Content', 'ai-content-studio'); ?>
                </button>
            </div>
            
            <!-- Progress Indicator -->
            <div class="acs-generation-progress" style="display: none;">
                <div class="acs-progress-bar-container">
                    <div class="acs-progress-bar"></div>
                </div>
                <p class="acs-progress-text"><?php esc_html_e('Generating your content...', 'ai-content-studio'); ?></p>
            </div>
            
        </form>
        
        <!-- Results -->
        <div class="acs-generation-results" style="display: none;"></div>
        
    </div>
    
    <!-- Templates -->
    <div class="acs-templates-section">
        <h2><?php esc_html_e('Content Templates', 'ai-content-studio'); ?></h2>
        <p class="description"><?php esc_html_e('Use these pre-configured templates for quick content generation.', 'ai-content-studio'); ?></p>
        
        <div class="acs-template-grid">
            <div class="acs-template-card" data-template="product-review">
                <h3><?php esc_html_e('Product Review', 'ai-content-studio'); ?></h3>
                <p><?php esc_html_e('Comprehensive product analysis with pros, cons, and verdict.', 'ai-content-studio'); ?></p>
                <button class="acs-button secondary acs-use-template"><?php esc_html_e('Use Template', 'ai-content-studio'); ?></button>
            </div>
            
            <div class="acs-template-card" data-template="how-to-guide">
                <h3><?php esc_html_e('How-to Guide', 'ai-content-studio'); ?></h3>
                <p><?php esc_html_e('Step-by-step tutorial with clear instructions.', 'ai-content-studio'); ?></p>
                <button class="acs-button secondary acs-use-template"><?php esc_html_e('Use Template', 'ai-content-studio'); ?></button>
            </div>
            
            <div class="acs-template-card" data-template="listicle">
                <h3><?php esc_html_e('Listicle', 'ai-content-studio'); ?></h3>
                <p><?php esc_html_e('Numbered or bulleted list-based article.', 'ai-content-studio'); ?></p>
                <button class="acs-button secondary acs-use-template"><?php esc_html_e('Use Template', 'ai-content-studio'); ?></button>
            </div>
            
            <div class="acs-template-card" data-template="comparison">
                <h3><?php esc_html_e('Comparison', 'ai-content-studio'); ?></h3>
                <p><?php esc_html_e('Side-by-side comparison of products or services.', 'ai-content-studio'); ?></p>
                <button class="acs-button secondary acs-use-template"><?php esc_html_e('Use Template', 'ai-content-studio'); ?></button>
            </div>
        </div>
    </div>
    
</div>

<script>
// Expose provider configuration status to admin JS
var acs_providers_ok = <?php echo $providers_valid ? 'true' : 'false'; ?>;
jQuery(document).ready(function($) {
    
    // Template usage
    $('.acs-use-template').on('click', function(e) {
        e.preventDefault();
        
        var template = $(this).closest('.acs-template-card').data('template');
        var templates = {
            'product-review': {
                content_type: 'product_review',
                prompt: 'Write a comprehensive review of [Product Name]. Include features, performance, pros and cons, user experience, value for money, and final verdict.',
                word_count: '1500-2500',
                tone: 'professional',
                structure: 'Introduction\nProduct Overview\nKey Features\nPerformance Analysis\nPros and Cons\nUser Experience\nValue for Money\nFinal Verdict'
            },
            'how-to-guide': {
                content_type: 'how_to_guide',
                prompt: 'Create a step-by-step guide on how to [Task/Process]. Make it beginner-friendly with clear instructions.',
                word_count: '1500-2500',
                tone: 'friendly',
                structure: 'Introduction\nPrerequisites\nStep-by-step Instructions\nTips and Troubleshooting\nConclusion'
            },
            'listicle': {
                content_type: 'listicle',
                prompt: 'Write a list article about [Topic]. Include [number] items with detailed explanations.',
                word_count: '1500-2500',
                tone: 'conversational',
                structure: 'Introduction\n[Number] Main Points\nConclusion'
            },
            'comparison': {
                content_type: 'comparison',
                prompt: 'Compare [Product/Service A] vs [Product/Service B]. Analyze features, pricing, pros and cons.',
                word_count: '1500-2500',
                tone: 'professional',
                structure: 'Introduction\nOverview of Options\nFeature Comparison\nPricing Comparison\nPros and Cons\nRecommendation'
            }
        };
        
        if (templates[template]) {
            var templateData = templates[template];
            
            $('#acs-content-type').val(templateData.content_type);
            $('#acs-prompt').val(templateData.prompt);
            $('#acs-word-count').val(templateData.word_count);
            $('#acs-tone').val(templateData.tone);
            $('#acs-structure').val(templateData.structure);
            
            // Scroll to form
            $('html, body').animate({
                scrollTop: $('#acs-generate-form').offset().top - 50
            }, 500);
        }
    });
    
    // Update model options based on provider
    $('#acs-provider').on('change', function() {
        var provider = $(this).val();
        var models = {
            'groq': [
                {value: 'mixtral-8x7b-32768', text: 'Mixtral 8x7B'},
                {value: 'llama2-70b-4096', text: 'Llama 2 70B'},
                {value: 'gemma-7b-it', text: 'Gemma 7B'}
            ],
            'openai': [
                {value: 'gpt-4', text: 'GPT-4'},
                {value: 'gpt-3.5-turbo', text: 'GPT-3.5 Turbo'}
            ],
            'anthropic': [
                {value: 'claude-3-opus', text: 'Claude 3 Opus'},
                {value: 'claude-3-sonnet', text: 'Claude 3 Sonnet'},
                {value: 'claude-3-haiku', text: 'Claude 3 Haiku'}
            ]
        };
        
        var modelSelect = $('#acs-model');
        modelSelect.empty();
        
        if (models[provider]) {
            $.each(models[provider], function(index, model) {
                modelSelect.append('<option value="' + model.value + '">' + model.text + '</option>');
            });
        } else {
            modelSelect.append('<option value="default">Default</option>');
        }
    });
    
    // Trigger provider change on load
    $('#acs-provider').trigger('change');
    
});
</script>