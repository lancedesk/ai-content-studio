<?php
/**
 * Integration Compatibility Layer
 *
 * Provides seamless integration with existing content generation workflow
 * without breaking existing functionality. Supports bypass mode and automatic
 * optimization for new content.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class IntegrationCompatibilityLayer
 *
 * Manages integration between multi-pass optimizer and existing workflows
 */
class IntegrationCompatibilityLayer {
    
    /**
     * @var MultiPassSEOOptimizer
     */
    private $optimizer;
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * @var bool Whether optimizer is enabled
     */
    private $optimizerEnabled;
    
    /**
     * @var array Supported content formats
     */
    private $supportedFormats;
    
    /**
     * @var array Integration hooks
     */
    private $hooks;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'enableOptimizer' => true,
            'autoOptimizeNewContent' => true,
            'bypassMode' => false,
            'supportedFormats' => ['post', 'page', 'article', 'blog'],
            'integrationMode' => 'seamless', // seamless, manual, disabled
            'fallbackToOriginal' => true,
            'preserveExistingWorkflow' => true,
            'logLevel' => 'info'
        ], $config);
        
        $this->optimizerEnabled = $this->config['enableOptimizer'] && !$this->config['bypassMode'];
        $this->supportedFormats = $this->config['supportedFormats'];
        $this->hooks = [];
        
        $this->initializeIntegration();
    }
    
    /**
     * Initialize integration with existing workflow
     */
    private function initializeIntegration() {
        if (!$this->config['preserveExistingWorkflow']) {
            return;
        }
        
        // Initialize optimizer if enabled
        if ($this->optimizerEnabled) {
            $optimizerConfig = [
                'maxIterations' => 3, // Reduced for integration performance
                'targetComplianceScore' => 95.0, // Slightly lower for practical use
                'enableEarlyTermination' => true,
                'autoCorrection' => true,
                'logLevel' => $this->config['logLevel']
            ];
            
            $this->optimizer = new MultiPassSEOOptimizer($optimizerConfig);
        }
        
        // Set up integration hooks
        $this->setupIntegrationHooks();
    }
    
    /**
     * Set up WordPress hooks for seamless integration
     */
    private function setupIntegrationHooks() {
        if ($this->config['integrationMode'] === 'disabled') {
            return;
        }
        
        // Hook into content generation workflow
        add_filter('acs_generated_content', [$this, 'processGeneratedContent'], 10, 3);
        
        // Hook into post save for automatic optimization
        if ($this->config['autoOptimizeNewContent']) {
            add_action('save_post', [$this, 'autoOptimizeOnSave'], 20, 2);
        }
        
        // Add admin interface hooks
        add_action('admin_init', [$this, 'initializeAdminInterface']);
        
        $this->hooks = [
            'acs_generated_content' => 'processGeneratedContent',
            'save_post' => 'autoOptimizeOnSave',
            'admin_init' => 'initializeAdminInterface'
        ];
    }
    
    /**
     * Process generated content through optimizer
     *
     * @param array $content Generated content
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return array Processed content
     */
    public function processGeneratedContent($content, $focusKeyword = '', $secondaryKeywords = []) {
        // Bypass mode - return original content unchanged
        if ($this->config['bypassMode'] || !$this->optimizerEnabled) {
            return $this->addBypassMetadata($content);
        }
        
        // Check if content format is supported
        if (!$this->isContentFormatSupported($content)) {
            return $this->addUnsupportedFormatMetadata($content);
        }
        
        try {
            // Validate input content
            if (!$this->validateContentStructure($content)) {
                throw new Exception('Invalid content structure provided');
            }
            
            // Run optimization if in seamless mode
            if ($this->config['integrationMode'] === 'seamless') {
                $optimizationResult = $this->optimizer->optimizeContent($content, $focusKeyword, $secondaryKeywords);
                
                if ($optimizationResult['success']) {
                    return $this->addOptimizationMetadata($optimizationResult['content'], $optimizationResult);
                } else {
                    // Fallback to original content on optimization failure
                    if ($this->config['fallbackToOriginal']) {
                        return $this->addFallbackMetadata($content, $optimizationResult);
                    }
                    throw new Exception('Optimization failed: ' . ($optimizationResult['error'] ?? 'Unknown error'));
                }
            }
            
            // Manual mode - just add metadata indicating optimization is available
            return $this->addManualModeMetadata($content);
            
        } catch (Exception $e) {
            // Log error and return original content if fallback is enabled
            $this->logError('Content processing failed: ' . $e->getMessage());
            
            if ($this->config['fallbackToOriginal']) {
                return $this->addErrorMetadata($content, $e->getMessage());
            }
            
            throw $e;
        }
    }
    
    /**
     * Auto-optimize content on post save
     *
     * @param int $postId Post ID
     * @param WP_Post $post Post object
     */
    public function autoOptimizeOnSave($postId, $post) {
        // Skip if optimizer disabled or in bypass mode
        if (!$this->optimizerEnabled || $this->config['bypassMode']) {
            return;
        }
        
        // Skip auto-saves and revisions
        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }
        
        // Only process supported post types
        if (!in_array($post->post_type, ['post', 'page'])) {
            return;
        }
        
        // Check if post has content to optimize
        if (empty($post->post_content)) {
            return;
        }
        
        try {
            // Extract content for optimization
            $content = $this->extractContentFromPost($post);
            
            // Get SEO metadata - try multiple sources
            $focusKeyword = get_post_meta($postId, '_yoast_wpseo_focuskw', true);
            
            // If no Yoast keyword, try to get from ACS generated content
            if (empty($focusKeyword)) {
                $acsContent = get_post_meta($postId, '_acs_generated_content', true);
                if (!empty($acsContent) && is_array($acsContent)) {
                    $focusKeyword = $acsContent['focus_keyword'] ?? '';
                }
            }
            
            // If still empty, try to extract from title
            if (empty($focusKeyword) && !empty($post->post_title)) {
                // Extract first meaningful word from title as fallback
                $words = explode(' ', $post->post_title);
                $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for'];
                foreach ($words as $word) {
                    $word = strtolower(trim($word));
                    if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                        $focusKeyword = $word;
                        break;
                    }
                }
            }
            
            $secondaryKeywords = [];
            
            // Skip optimization if no focus keyword can be determined
            if (empty($focusKeyword)) {
                $this->logError('Cannot optimize post ' . $postId . ': No focus keyword found');
                return;
            }
            
            // Run optimization
            $optimizationResult = $this->optimizer->optimizeContent($content, $focusKeyword, $secondaryKeywords);
            
            if ($optimizationResult['success']) {
                // Update post content with optimized version
                $this->updatePostWithOptimizedContent($postId, $optimizationResult['content']);
                
                // Store optimization metadata
                update_post_meta($postId, '_acs_optimization_result', $optimizationResult);
                update_post_meta($postId, '_acs_optimization_timestamp', current_time('mysql'));
            }
            
        } catch (Exception $e) {
            $this->logError('Auto-optimization failed for post ' . $postId . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize admin interface integration
     */
    public function initializeAdminInterface() {
        // Add meta box for optimization controls
        add_action('add_meta_boxes', [$this, 'addOptimizationMetaBox']);
        
        // Add settings to admin menu
        add_action('admin_menu', [$this, 'addAdminMenuItems']);
    }
    
    /**
     * Add optimization meta box to post editor
     */
    public function addOptimizationMetaBox() {
        $screens = ['post', 'page'];
        
        foreach ($screens as $screen) {
            add_meta_box(
                'acs-seo-optimization',
                'SEO Optimization',
                [$this, 'renderOptimizationMetaBox'],
                $screen,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render optimization meta box
     *
     * @param WP_Post $post Current post
     */
    public function renderOptimizationMetaBox($post) {
        // Get optimization status
        $optimizationResult = get_post_meta($post->ID, '_acs_optimization_result', true);
        $lastOptimized = get_post_meta($post->ID, '_acs_optimization_timestamp', true);
        
        echo '<div class="acs-optimization-controls">';
        
        if ($optimizationResult) {
            $score = $optimizationResult['optimizationSummary']['finalScore'] ?? 0;
            echo '<p><strong>SEO Score:</strong> ' . number_format($score, 1) . '%</p>';
            
            if ($lastOptimized) {
                echo '<p><strong>Last Optimized:</strong> ' . date('M j, Y g:i A', strtotime($lastOptimized)) . '</p>';
            }
            
            if (isset($optimizationResult['optimizationSummary']['complianceAchieved']) && 
                $optimizationResult['optimizationSummary']['complianceAchieved']) {
                echo '<p style="color: green;"><strong>✓ SEO Compliant</strong></p>';
            } else {
                echo '<p style="color: orange;"><strong>⚠ Needs Optimization</strong></p>';
            }
        } else {
            echo '<p>No optimization data available.</p>';
        }
        
        // Add manual optimization button
        if ($this->optimizerEnabled) {
            wp_nonce_field('acs_manual_optimization', 'acs_optimization_nonce');
            echo '<button type="button" class="button button-secondary" id="acs-manual-optimize">Optimize Now</button>';
        }
        
        echo '</div>';
        
        // Add JavaScript for manual optimization
        $this->addOptimizationScript();
    }
    
    /**
     * Add optimization JavaScript
     */
    private function addOptimizationScript() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#acs-manual-optimize').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Optimizing...');
                
                var data = {
                    action: 'acs_manual_optimize',
                    post_id: $('#post_ID').val(),
                    nonce: $('#acs_optimization_nonce').val()
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Optimization failed: ' + (response.data || 'Unknown error'));
                        button.prop('disabled', false).text('Optimize Now');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Add admin menu items
     */
    public function addAdminMenuItems() {
        add_submenu_page(
            'options-general.php',
            'SEO Optimizer Settings',
            'SEO Optimizer',
            'manage_options',
            'acs-seo-optimizer',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage() {
        ?>
        <div class="wrap">
            <h1>SEO Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acs_seo_optimizer');
                do_settings_sections('acs_seo_optimizer');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Optimizer</th>
                        <td>
                            <input type="checkbox" name="acs_optimizer_enabled" value="1" 
                                   <?php checked($this->config['enableOptimizer']); ?> />
                            <label>Enable automatic SEO optimization</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Auto-Optimize New Content</th>
                        <td>
                            <input type="checkbox" name="acs_auto_optimize" value="1" 
                                   <?php checked($this->config['autoOptimizeNewContent']); ?> />
                            <label>Automatically optimize new posts and pages</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Bypass Mode</th>
                        <td>
                            <input type="checkbox" name="acs_bypass_mode" value="1" 
                                   <?php checked($this->config['bypassMode']); ?> />
                            <label>Bypass optimizer (disable all optimization)</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Check if content format is supported
     *
     * @param array $content Content array
     * @return bool True if supported
     */
    private function isContentFormatSupported($content) {
        $contentType = $content['type'] ?? 'post';
        return in_array($contentType, $this->supportedFormats);
    }
    
    /**
     * Validate content structure
     *
     * @param array $content Content array
     * @return bool True if valid
     */
    private function validateContentStructure($content) {
        // Check required fields
        $requiredFields = ['title', 'content'];
        
        foreach ($requiredFields as $field) {
            if (!isset($content[$field]) || empty($content[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Extract content from WordPress post
     *
     * @param WP_Post $post Post object
     * @return array Content array
     */
    private function extractContentFromPost($post) {
        return [
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'meta_description' => get_post_meta($post->ID, '_yoast_wpseo_metadesc', true) ?: '',
            'type' => $post->post_type
        ];
    }
    
    /**
     * Update post with optimized content
     *
     * @param int $postId Post ID
     * @param array $optimizedContent Optimized content
     */
    private function updatePostWithOptimizedContent($postId, $optimizedContent) {
        // Prevent infinite loops
        remove_action('save_post', [$this, 'autoOptimizeOnSave'], 20);
        
        // Update post content
        $updateData = [
            'ID' => $postId,
            'post_title' => $optimizedContent['title'] ?? '',
            'post_content' => $optimizedContent['content'] ?? '',
            'post_excerpt' => $optimizedContent['excerpt'] ?? ''
        ];
        
        wp_update_post($updateData);
        
        // Update SEO metadata
        if (isset($optimizedContent['meta_description'])) {
            update_post_meta($postId, '_yoast_wpseo_metadesc', $optimizedContent['meta_description']);
        }
        
        // Re-add the hook
        add_action('save_post', [$this, 'autoOptimizeOnSave'], 20, 2);
    }
    
    /**
     * Add bypass metadata to content
     *
     * @param array $content Original content
     * @return array Content with metadata
     */
    private function addBypassMetadata($content) {
        $content['_acs_optimization'] = [
            'status' => 'bypassed',
            'reason' => 'Optimizer in bypass mode',
            'timestamp' => current_time('mysql')
        ];
        
        return $content;
    }
    
    /**
     * Add unsupported format metadata
     *
     * @param array $content Original content
     * @return array Content with metadata
     */
    private function addUnsupportedFormatMetadata($content) {
        $content['_acs_optimization'] = [
            'status' => 'unsupported_format',
            'reason' => 'Content format not supported for optimization',
            'timestamp' => current_time('mysql')
        ];
        
        return $content;
    }
    
    /**
     * Add optimization metadata to content
     *
     * @param array $content Optimized content
     * @param array $result Optimization result
     * @return array Content with metadata
     */
    private function addOptimizationMetadata($content, $result) {
        $content['_acs_optimization'] = [
            'status' => 'optimized',
            'score' => $result['optimizationSummary']['finalScore'] ?? 0,
            'iterations' => $result['optimizationSummary']['iterationsUsed'] ?? 0,
            'compliance_achieved' => $result['optimizationSummary']['complianceAchieved'] ?? false,
            'timestamp' => current_time('mysql'),
            'full_result' => $result
        ];
        
        return $content;
    }
    
    /**
     * Add fallback metadata to content
     *
     * @param array $content Original content
     * @param array $result Failed optimization result
     * @return array Content with metadata
     */
    private function addFallbackMetadata($content, $result) {
        $content['_acs_optimization'] = [
            'status' => 'fallback',
            'reason' => 'Optimization failed, using original content',
            'error' => $result['error'] ?? 'Unknown error',
            'timestamp' => current_time('mysql')
        ];
        
        return $content;
    }
    
    /**
     * Add manual mode metadata
     *
     * @param array $content Original content
     * @return array Content with metadata
     */
    private function addManualModeMetadata($content) {
        $content['_acs_optimization'] = [
            'status' => 'manual_mode',
            'reason' => 'Optimization available but not automatic',
            'timestamp' => current_time('mysql')
        ];
        
        return $content;
    }
    
    /**
     * Add error metadata to content
     *
     * @param array $content Original content
     * @param string $error Error message
     * @return array Content with metadata
     */
    private function addErrorMetadata($content, $error) {
        $content['_acs_optimization'] = [
            'status' => 'error',
            'reason' => 'Optimization error occurred',
            'error' => $error,
            'timestamp' => current_time('mysql')
        ];
        
        return $content;
    }
    
    /**
     * Enable optimizer
     */
    public function enableOptimizer() {
        $this->config['enableOptimizer'] = true;
        $this->config['bypassMode'] = false;
        $this->optimizerEnabled = true;
        
        if (!$this->optimizer) {
            $this->initializeIntegration();
        }
    }
    
    /**
     * Disable optimizer (bypass mode)
     */
    public function disableOptimizer() {
        $this->config['enableOptimizer'] = false;
        $this->config['bypassMode'] = true;
        $this->optimizerEnabled = false;
    }
    
    /**
     * Set integration mode
     *
     * @param string $mode Integration mode (seamless, manual, disabled)
     */
    public function setIntegrationMode($mode) {
        $validModes = ['seamless', 'manual', 'disabled'];
        
        if (in_array($mode, $validModes)) {
            $this->config['integrationMode'] = $mode;
            
            if ($mode === 'disabled') {
                $this->disableOptimizer();
            }
        }
    }
    
    /**
     * Get integration status
     *
     * @return array Status information
     */
    public function getIntegrationStatus() {
        return [
            'optimizer_enabled' => $this->optimizerEnabled,
            'bypass_mode' => $this->config['bypassMode'],
            'integration_mode' => $this->config['integrationMode'],
            'auto_optimize' => $this->config['autoOptimizeNewContent'],
            'supported_formats' => $this->supportedFormats,
            'hooks_registered' => $this->hooks,
            'optimizer_available' => $this->optimizer !== null
        ];
    }
    
    /**
     * Get configuration
     *
     * @return array Current configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Update configuration
     *
     * @param array $newConfig New configuration settings
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        
        // Update optimizer enabled status
        $this->optimizerEnabled = $this->config['enableOptimizer'] && !$this->config['bypassMode'];
        
        // Reinitialize if needed
        if (isset($newConfig['enableOptimizer']) || isset($newConfig['bypassMode'])) {
            $this->initializeIntegration();
        }
    }
    
    /**
     * Test integration compatibility
     *
     * @param array $testContent Test content
     * @return array Test results
     */
    public function testIntegrationCompatibility($testContent) {
        $results = [
            'bypass_mode_test' => false,
            'seamless_integration_test' => false,
            'fallback_test' => false,
            'format_support_test' => false,
            'workflow_preservation_test' => false
        ];
        
        try {
            // Test bypass mode
            $originalMode = $this->config['bypassMode'];
            $this->config['bypassMode'] = true;
            $this->optimizerEnabled = false;
            
            $bypassResult = $this->processGeneratedContent($testContent, 'test');
            $results['bypass_mode_test'] = isset($bypassResult['_acs_optimization']) && 
                                          $bypassResult['_acs_optimization']['status'] === 'bypassed';
            
            // Restore mode
            $this->config['bypassMode'] = $originalMode;
            $this->optimizerEnabled = $this->config['enableOptimizer'] && !$this->config['bypassMode'];
            
            // Test format support
            $results['format_support_test'] = $this->isContentFormatSupported($testContent);
            
            // Test workflow preservation
            $results['workflow_preservation_test'] = $this->validateContentStructure($testContent);
            
            // Test seamless integration (if optimizer available)
            if ($this->optimizer && $this->optimizerEnabled) {
                $seamlessResult = $this->processGeneratedContent($testContent, 'test');
                $results['seamless_integration_test'] = isset($seamlessResult['_acs_optimization']);
            }
            
            // Test fallback mechanism
            $originalFallback = $this->config['fallbackToOriginal'];
            $this->config['fallbackToOriginal'] = true;
            
            // Simulate error condition
            $originalOptimizer = $this->optimizer;
            $this->optimizer = null;
            
            $fallbackResult = $this->processGeneratedContent($testContent, 'test');
            $results['fallback_test'] = isset($fallbackResult['_acs_optimization']) && 
                                       in_array($fallbackResult['_acs_optimization']['status'], ['fallback', 'error']);
            
            // Restore
            $this->optimizer = $originalOptimizer;
            $this->config['fallbackToOriginal'] = $originalFallback;
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Log error message
     *
     * @param string $message Error message
     */
    private function logError($message) {
        if ($this->config['logLevel'] === 'debug' || $this->config['logLevel'] === 'error') {
            error_log("IntegrationCompatibilityLayer: {$message}");
        }
    }
    
    /**
     * Get optimizer instance
     *
     * @return MultiPassSEOOptimizer|null
     */
    public function getOptimizer() {
        return $this->optimizer;
    }
    
    /**
     * Check if optimizer is enabled
     *
     * @return bool True if enabled
     */
    public function isOptimizerEnabled() {
        return $this->optimizerEnabled;
    }
    
    /**
     * Get supported formats
     *
     * @return array Supported content formats
     */
    public function getSupportedFormats() {
        return $this->supportedFormats;
    }
    
    /**
     * Add supported format
     *
     * @param string $format Format to add
     */
    public function addSupportedFormat($format) {
        if (!in_array($format, $this->supportedFormats)) {
            $this->supportedFormats[] = $format;
            $this->config['supportedFormats'] = $this->supportedFormats;
        }
    }
    
    /**
     * Remove supported format
     *
     * @param string $format Format to remove
     */
    public function removeSupportedFormat($format) {
        $key = array_search($format, $this->supportedFormats);
        if ($key !== false) {
            unset($this->supportedFormats[$key]);
            $this->supportedFormats = array_values($this->supportedFormats);
            $this->config['supportedFormats'] = $this->supportedFormats;
        }
    }
}