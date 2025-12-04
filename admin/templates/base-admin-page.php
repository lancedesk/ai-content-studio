<?php
/**
 * Base Admin Page Template
 *
 * Standardized template for all ACS admin pages with consistent structure,
 * accessibility features, and extensibility hooks.
 *
 * @package AI_Content_Studio
 * @subpackage Admin/Templates
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Template variables that should be set before including this template:
// $page_title - The page title
// $page_description - Optional page description
// $breadcrumbs - Optional breadcrumb array
// $page_actions - Optional array of page-level actions
// $content_callback - Callback function to render main content
// $sidebar_callback - Optional callback function to render sidebar content
// $page_slug - Current page slug for styling and hooks
// $page_capability - Required capability for this page

// Ensure required variables are set
$page_title = $page_title ?? __('ACS Admin Page', 'ai-content-studio');
$page_slug = $page_slug ?? 'acs-admin-page';
$page_capability = $page_capability ?? 'manage_options';

// Security check
if (!current_user_can($page_capability)) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'ai-content-studio'));
}

// Get component renderer
if (!class_exists('ACS_Component_Renderer')) {
    require_once ACS_PLUGIN_PATH . 'admin/class-acs-component-renderer.php';
}
$renderer = new ACS_Component_Renderer();

?>
<div class="wrap acs-admin-page acs-admin-page--<?php echo esc_attr($page_slug); ?>" role="main" id="main">
    
    <?php
    /**
     * Hook: acs_admin_page_before_header
     * 
     * Allows plugins to add content before the page header.
     * 
     * @param string $page_slug Current page slug
     */
    do_action('acs_admin_page_before_header', $page_slug);
    ?>
    
    <!-- Page Header -->
    <header class="acs-admin-header" role="banner">
        
        <?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
            <?php echo $renderer->render_breadcrumbs($breadcrumbs); ?>
        <?php endif; ?>
        
        <div class="acs-admin-header__content">
            <div class="acs-admin-header__title-section">
                <h1 class="acs-admin-header__title" aria-level="1">
                    <?php echo esc_html($page_title); ?>
                </h1>
                
                <?php if (!empty($page_description)): ?>
                    <p class="acs-admin-header__description">
                        <?php echo wp_kses_post($page_description); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($page_actions) && is_array($page_actions)): ?>
                <div class="acs-admin-header__actions">
                    <?php foreach ($page_actions as $action): ?>
                        <?php echo $renderer->render_button($action); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php
        /**
         * Hook: acs_admin_page_header_after_content
         * 
         * Allows plugins to add content after the header content.
         * 
         * @param string $page_slug Current page slug
         */
        do_action('acs_admin_page_header_after_content', $page_slug);
        ?>
        
    </header>
    
    <?php
    /**
     * Hook: acs_admin_page_after_header
     * 
     * Allows plugins to add content after the page header.
     * 
     * @param string $page_slug Current page slug
     */
    do_action('acs_admin_page_after_header', $page_slug);
    ?>
    
    <!-- Main Content Area -->
    <div class="acs-admin-content" role="main">
        
        <?php if (isset($sidebar_callback) && is_callable($sidebar_callback)): ?>
            <!-- Two-column layout with sidebar -->
            <div class="acs-admin-content__grid acs-admin-content__grid--with-sidebar">
                
                <main class="acs-admin-content__main" role="main">
                    <?php
                    /**
                     * Hook: acs_admin_page_before_main_content
                     * 
                     * Allows plugins to add content before the main content area.
                     * 
                     * @param string $page_slug Current page slug
                     */
                    do_action('acs_admin_page_before_main_content', $page_slug);
                    ?>
                    
                    <?php if (isset($content_callback) && is_callable($content_callback)): ?>
                        <?php call_user_func($content_callback); ?>
                    <?php else: ?>
                        <div class="notice notice-error">
                            <p><?php _e('Content callback not defined for this page.', 'ai-content-studio'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    /**
                     * Hook: acs_admin_page_after_main_content
                     * 
                     * Allows plugins to add content after the main content area.
                     * 
                     * @param string $page_slug Current page slug
                     */
                    do_action('acs_admin_page_after_main_content', $page_slug);
                    ?>
                </main>
                
                <aside class="acs-admin-content__sidebar" role="complementary" aria-label="<?php esc_attr_e('Page sidebar', 'ai-content-studio'); ?>">
                    <?php
                    /**
                     * Hook: acs_admin_page_before_sidebar
                     * 
                     * Allows plugins to add content before the sidebar.
                     * 
                     * @param string $page_slug Current page slug
                     */
                    do_action('acs_admin_page_before_sidebar', $page_slug);
                    ?>
                    
                    <?php call_user_func($sidebar_callback); ?>
                    
                    <?php
                    /**
                     * Hook: acs_admin_page_after_sidebar
                     * 
                     * Allows plugins to add content after the sidebar.
                     * 
                     * @param string $page_slug Current page slug
                     */
                    do_action('acs_admin_page_after_sidebar', $page_slug);
                    ?>
                </aside>
                
            </div>
        <?php else: ?>
            <!-- Single-column layout -->
            <main class="acs-admin-content__main acs-admin-content__main--full-width" role="main">
                <?php
                /**
                 * Hook: acs_admin_page_before_main_content
                 * 
                 * Allows plugins to add content before the main content area.
                 * 
                 * @param string $page_slug Current page slug
                 */
                do_action('acs_admin_page_before_main_content', $page_slug);
                ?>
                
                <?php if (isset($content_callback) && is_callable($content_callback)): ?>
                    <?php call_user_func($content_callback); ?>
                <?php else: ?>
                    <div class="notice notice-error">
                        <p><?php _e('Content callback not defined for this page.', 'ai-content-studio'); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php
                /**
                 * Hook: acs_admin_page_after_main_content
                 * 
                 * Allows plugins to add content after the main content area.
                 * 
                 * @param string $page_slug Current page slug
                 */
                do_action('acs_admin_page_after_main_content', $page_slug);
                ?>
            </main>
        <?php endif; ?>
        
    </div>
    
    <?php
    /**
     * Hook: acs_admin_page_before_footer
     * 
     * Allows plugins to add content before the page footer.
     * 
     * @param string $page_slug Current page slug
     */
    do_action('acs_admin_page_before_footer', $page_slug);
    ?>
    
    <!-- Page Footer -->
    <footer class="acs-admin-footer" role="contentinfo">
        <?php
        /**
         * Hook: acs_admin_page_footer
         * 
         * Allows plugins to add content to the page footer.
         * 
         * @param string $page_slug Current page slug
         */
        do_action('acs_admin_page_footer', $page_slug);
        ?>
    </footer>
    
</div>

<!-- Accessibility enhancements -->
<div id="acs-live-region" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>
<div id="acs-live-region-assertive" class="screen-reader-text" aria-live="assertive" aria-atomic="true"></div>

<?php
/**
 * Hook: acs_admin_page_after_content
 * 
 * Allows plugins to add content after the entire page content.
 * 
 * @param string $page_slug Current page slug
 */
do_action('acs_admin_page_after_content', $page_slug);
?>

<script type="text/javascript">
// Accessibility enhancements
jQuery(document).ready(function($) {
    // Add skip links if not already present
    if (!$('.skip-links').length) {
        $('body').prepend(
            '<div class="skip-links">' +
            '<a class="skip-link screen-reader-text" href="#main"><?php echo esc_js(__('Skip to main content', 'ai-content-studio')); ?></a>' +
            '<a class="skip-link screen-reader-text" href="#adminmenu"><?php echo esc_js(__('Skip to navigation', 'ai-content-studio')); ?></a>' +
            '</div>'
        );
    }
    
    // Enhance focus management
    $('.skip-link').on('focus', function() {
        $(this).removeClass('screen-reader-text');
    }).on('blur', function() {
        $(this).addClass('screen-reader-text');
    });
    
    // Add ARIA labels to buttons without text
    $('button:not([aria-label])').each(function() {
        var $button = $(this);
        var $icon = $button.find('.dashicons');
        var text = $button.text().trim();
        
        if (!text && $icon.length) {
            var iconClass = $icon.attr('class');
            var label = '';
            
            if (iconClass.includes('dashicons-edit')) {
                label = '<?php echo esc_js(__('Edit', 'ai-content-studio')); ?>';
            } else if (iconClass.includes('dashicons-trash')) {
                label = '<?php echo esc_js(__('Delete', 'ai-content-studio')); ?>';
            } else if (iconClass.includes('dashicons-visibility')) {
                label = '<?php echo esc_js(__('View', 'ai-content-studio')); ?>';
            } else if (iconClass.includes('dashicons-admin-settings')) {
                label = '<?php echo esc_js(__('Settings', 'ai-content-studio')); ?>';
            }
            
            if (label) {
                $button.attr('aria-label', label);
            }
        }
    });
    
    // Announce page changes to screen readers
    if (window.location.hash) {
        var $target = $(window.location.hash);
        if ($target.length) {
            $target.focus();
        }
    }
    
    <?php
    /**
     * Hook: acs_admin_page_footer_script
     * 
     * Allows plugins to add JavaScript to the page footer.
     * 
     * @param string $page_slug Current page slug
     */
    do_action('acs_admin_page_footer_script', $page_slug);
    ?>
});
</script>