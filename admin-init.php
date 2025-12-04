<?php
/**
 * Initialize SEO Optimizer Admin Interface
 *
 * Note: Menu registration is now handled by ACS_Unified_Admin to keep 
 * all plugin menus under a single "AI Content Studio" parent.
 *
 * @package AI_Content_Studio
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize admin interface if in admin area
if (is_admin()) {
    // Load all required SEO classes (in dependency order)
    $seo_classes = [
        'seo/class-seo-validation-result.php',
        'seo/class-content-validation-metrics.php', 
        'seo/class-seo-prompt-configuration.php',
        'seo/class-meta-description-validator.php',
        'seo/class-meta-description-corrector.php',
        'seo/class-keyword-density-calculator.php',
        'seo/class-keyword-density-optimizer.php',
        'seo/class-passive-voice-analyzer.php',
        'seo/class-sentence-length-analyzer.php',
        'seo/class-transition-word-analyzer.php',
        'seo/class-readability-corrector.php',
        'seo/class-title-uniqueness-validator.php',
        'seo/class-title-optimization-engine.php',
        'seo/class-image-prompt-generator.php',
        'seo/class-alt-text-accessibility-optimizer.php',
        'seo/class-seo-validation-cache.php',
        'seo/class-smart-retry-manager.php',
        'seo/class-seo-error-handler.php',
        'seo/class-seo-validation-pipeline.php',
        'seo/class-seo-issue-detector.php',
        'seo/class-correction-prompt-generator.php',
        'seo/class-multi-pass-seo-optimizer.php',
        'seo/class-integration-compatibility-layer.php'
    ];
    
    foreach ($seo_classes as $class_file) {
        $file_path = ACS_PLUGIN_PATH . $class_file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    // Load SEO Optimizer admin class (for AJAX handlers and functionality)
    // But DON'T initialize it - menu registration is handled by ACS_Unified_Admin
    require_once ACS_PLUGIN_PATH . 'admin/class-seo-optimizer-admin.php';
    
    // Only initialize for AJAX handlers, not for menu registration
    // The menu is now registered by ACS_Unified_Admin
    add_action('admin_init', function() {
        global $acs_seo_admin;
        if (!isset($acs_seo_admin)) {
            $acs_seo_admin = new SEOOptimizerAdmin();
            // Remove the menu registration to avoid duplicate menus
            remove_action('admin_menu', [$acs_seo_admin, 'add_admin_menu']);
        }
    }, 5);
}

// Add default options on activation
register_activation_hook(ACS_PLUGIN_PATH . 'ai-content-studio.php', function() {
    // Set default optimizer settings
    add_option('acs_optimizer_enabled', true);
    add_option('acs_auto_optimize', true);
    add_option('acs_bypass_mode', false);
    add_option('acs_integration_mode', 'seamless');
    add_option('acs_fallback_enabled', true);
    add_option('acs_max_iterations', 3);
    add_option('acs_target_score', 95);
    add_option('acs_log_level', 'info');
});
