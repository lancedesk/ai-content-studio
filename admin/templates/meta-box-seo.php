<?php
/**
 * SEO Optimization Meta Box Template
 *
 * @package AI_Content_Studio
 * @subpackage Admin/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get optimization data
$optimization_result = $optimization_result ?? null;
$last_optimized = $last_optimized ?? null;

?>
<div class="acs-seo-metabox">
    <?php if ($optimization_result && isset($optimization_result['optimizationSummary'])) : 
        $summary = $optimization_result['optimizationSummary'];
        $score = $summary['finalScore'] ?? 0;
        $compliant = $summary['complianceAchieved'] ?? false;
    ?>
        <div class="acs-score-display">
            <div class="acs-score-circle <?php echo $compliant ? 'compliant' : 'needs-work'; ?>">
                <span class="score-value"><?php echo number_format($score, 1); ?>%</span>
            </div>
            <div class="acs-score-label">
                <?php if ($compliant) : ?>
                    <span class="status-icon">✓</span>
                    <strong><?php esc_html_e('SEO Compliant', 'ai-content-studio'); ?></strong>
                <?php else : ?>
                    <span class="status-icon">⚠</span>
                    <strong><?php esc_html_e('Needs Optimization', 'ai-content-studio'); ?></strong>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($last_optimized) : ?>
            <p class="acs-last-optimized">
                <small>
                    <?php esc_html_e('Last optimized:', 'ai-content-studio'); ?> 
                    <?php echo human_time_diff(strtotime($last_optimized), current_time('timestamp')); ?> 
                    <?php esc_html_e('ago', 'ai-content-studio'); ?>
                </small>
            </p>
        <?php endif; ?>
        
        <div class="acs-optimization-details">
            <p><strong><?php esc_html_e('Iterations:', 'ai-content-studio'); ?></strong> <?php echo $summary['iterationsUsed'] ?? 0; ?></p>
            <p><strong><?php esc_html_e('Issues Fixed:', 'ai-content-studio'); ?></strong> <?php echo $summary['issuesFixed'] ?? 0; ?></p>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No optimization data available.', 'ai-content-studio'); ?></p>
    <?php endif; ?>
    
    <div class="acs-optimization-actions">
        <button type="button" class="button button-primary button-large acs-button" 
                data-ajax-action="acs_manual_optimize" 
                data-ajax-data='{"post_id": <?php echo $post->ID; ?>}'
                data-loading-text="<?php esc_attr_e('Optimizing...', 'ai-content-studio'); ?>">
            <?php esc_html_e('Optimize Now', 'ai-content-studio'); ?>
        </button>
    </div>
    
    <div id="acs-optimization-progress" style="display:none;">
        <div class="acs-progress acs-progress--linear">
            <div class="acs-progress__track">
                <div class="acs-progress__fill" style="width: 0%"></div>
            </div>
        </div>
        <p class="acs-progress-text"></p>
    </div>
</div>

<style>
.acs-seo-metabox {
    padding: 0;
}

.acs-score-display {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.acs-score-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    font-size: 14px;
}

.acs-score-circle.compliant {
    background: #00a32a;
}

.acs-score-circle.needs-work {
    background: #dba617;
}

.acs-score-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-icon {
    font-size: 1.2em;
}

.acs-last-optimized {
    margin: 0.5rem 0;
    color: #646970;
}

.acs-optimization-details {
    margin: 1rem 0;
    padding: 0.75rem;
    background: #f6f7f7;
    border-radius: 4px;
}

.acs-optimization-details p {
    margin: 0.25rem 0;
    font-size: 13px;
}

.acs-optimization-actions {
    margin-top: 1rem;
}

.acs-progress {
    margin-top: 1rem;
}

.acs-progress__track {
    height: 8px;
    background: #dcdcde;
    border-radius: 4px;
    overflow: hidden;
}

.acs-progress__fill {
    height: 100%;
    background: #0073aa;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.acs-progress-text {
    margin: 0.5rem 0 0 0;
    font-size: 13px;
    color: #646970;
}
</style>