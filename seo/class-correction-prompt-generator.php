<?php
/**
 * Correction Prompt Generator
 *
 * Creates targeted AI prompts for specific SEO fixes with quantitative calculations,
 * priority-based ordering, and context-aware instruction generation.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Correction Prompt Data Model
 */
class CorrectionPrompt {
    public $issueType;         // Type of issue being corrected
    public $promptText;        // Specific AI instruction
    public $targetLocations;   // Specific content areas to modify
    public $expectedChanges;   // Expected improvements
    public $maxAttempts;       // Maximum retry attempts
    public $priority;          // Correction priority (1-10)
    public $quantitativeTarget; // Specific numeric target
    
    public function __construct($issueType, $promptText, $targetLocations = [], $expectedChanges = [], $maxAttempts = 3, $priority = 5, $quantitativeTarget = null) {
        $this->issueType = $issueType;
        $this->promptText = $promptText;
        $this->targetLocations = $targetLocations;
        $this->expectedChanges = $expectedChanges;
        $this->maxAttempts = $maxAttempts;
        $this->priority = $priority;
        $this->quantitativeTarget = $quantitativeTarget;
    }
    
    public function toArray() {
        return [
            'issueType' => $this->issueType,
            'promptText' => $this->promptText,
            'targetLocations' => $this->targetLocations,
            'expectedChanges' => $this->expectedChanges,
            'maxAttempts' => $this->maxAttempts,
            'priority' => $this->priority,
            'quantitativeTarget' => $this->quantitativeTarget
        ];
    }
}

/**
 * Class CorrectionPromptGenerator
 *
 * Generates targeted correction prompts for specific SEO issues
 */
class CorrectionPromptGenerator {
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * @var array Prompt templates for different issue types
     */
    private $promptTemplates;
    
    /**
     * @var array Effectiveness tracking data
     */
    private $effectivenessTracking;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'enableQuantitativeTargets' => true,
            'enableLocationTracking' => true,
            'maxPromptsPerIssue' => 3,
            'priorityWeights' => [
                'critical' => 10,
                'major' => 7,
                'minor' => 4
            ]
        ], $config);
        
        $this->initializePromptTemplates();
        $this->effectivenessTracking = [];
    }
    
    /**
     * Initialize prompt templates for different issue types
     */
    private function initializePromptTemplates() {
        $this->promptTemplates = [
            'keyword_density_high' => [
                'template' => "Reduce keyword '{keyword}' density from {current}% to {target}% by replacing {count} instances with synonyms or related terms. Focus on locations: {locations}",
                'priority' => 9,
                'quantitative' => true
            ],
            'keyword_density_low' => [
                'template' => "Increase keyword '{keyword}' density from {current}% to at least {target}% by naturally incorporating {count} more instances. Suggested locations: {locations}",
                'priority' => 8,
                'quantitative' => true
            ],
            'meta_description_short' => [
                'template' => "Expand meta description from {current} to {target} characters (add {diff} characters). Include keyword '{keyword}' and compelling call-to-action. Current: '{text}'",
                'priority' => 10,
                'quantitative' => true
            ],
            'meta_description_long' => [
                'template' => "Shorten meta description from {current} to {target} characters (remove {diff} characters). Keep keyword '{keyword}' and main message. Current: '{text}'",
                'priority' => 7,
                'quantitative' => true
            ],
            'meta_description_no_keyword' => [
                'template' => "Add focus keyword '{keyword}' to meta description naturally. Current: '{text}'",
                'priority' => 6,
                'quantitative' => false
            ],
            'passive_voice_high' => [
                'template' => "Convert {count} passive voice sentences to active voice (reduce from {current}% to {target}%). Target sentences: {sentences}",
                'priority' => 5,
                'quantitative' => true
            ],
            'sentence_length_high' => [
                'template' => "Split {count} long sentences to reduce long sentence percentage from {current}% to {target}%. Target sentences: {sentences}",
                'priority' => 3,
                'quantitative' => true
            ],
            'transition_words_low' => [
                'template' => "Add transition words to increase from {current}% to {target}%. Add approximately {count} transition words like 'however', 'therefore', 'additionally', 'furthermore'.",
                'priority' => 2,
                'quantitative' => true
            ],
            'title_too_long' => [
                'template' => "Shorten title from {current} to {target} characters (remove {diff} characters). Keep keyword '{keyword}' and main message. Current: '{text}'",
                'priority' => 7,
                'quantitative' => true
            ],
            'title_no_keyword' => [
                'template' => "Add focus keyword '{keyword}' to title naturally. Keep under 66 characters. Current: '{text}'",
                'priority' => 9,
                'quantitative' => false
            ],
            'subheading_keyword_overuse' => [
                'template' => "Reduce keyword '{keyword}' usage in subheadings from {current}% to {target}%. Modify {count} headings: {headings}",
                'priority' => 4,
                'quantitative' => true
            ],
            'no_images' => [
                'template' => "Add at least one relevant image with descriptive alt text containing keyword '{keyword}'.",
                'priority' => 6,
                'quantitative' => false
            ],
            'alt_text_no_keyword' => [
                'template' => "Update {count} image alt text to include keyword '{keyword}'. Images: {images}",
                'priority' => 3,
                'quantitative' => true
            ]
        ];
    }
    
    /**
     * Generate correction prompts for all detected issues
     *
     * @param array $issues Array of SEOIssue objects
     * @param string $focusKeyword Focus keyword
     * @param array $content Content array for context
     * @return array Array of CorrectionPrompt objects sorted by priority
     */
    public function generatePromptsForIssues($issues, $focusKeyword, $content = []) {
        $prompts = [];
        
        foreach ($issues as $issue) {
            $prompt = $this->generatePromptForIssue($issue, $focusKeyword, $content);
            if ($prompt !== null) {
                $prompts[] = $prompt;
            }
        }
        
        // Sort by priority (highest first)
        return $this->sortPromptsByPriority($prompts);
    }
    
    /**
     * Generate correction prompt for a single issue
     *
     * @param SEOIssue $issue Issue object
     * @param string $focusKeyword Focus keyword
     * @param array $content Content array for context
     * @return CorrectionPrompt|null Correction prompt or null if template not found
     */
    public function generatePromptForIssue($issue, $focusKeyword, $content = []) {
        if (!isset($this->promptTemplates[$issue->type])) {
            return null;
        }
        
        $template = $this->promptTemplates[$issue->type];
        
        // Calculate quantitative targets
        $quantitativeTarget = null;
        if ($template['quantitative']) {
            $quantitativeTarget = $this->calculateQuantitativeTarget($issue);
        }
        
        // Generate prompt text
        $promptText = $this->fillPromptTemplate($template['template'], $issue, $focusKeyword, $content, $quantitativeTarget);
        
        // Extract target locations
        $targetLocations = $this->extractTargetLocations($issue);
        
        // Define expected changes
        $expectedChanges = $this->defineExpectedChanges($issue, $quantitativeTarget);
        
        // Determine priority (use template priority adjusted by severity)
        $priority = $this->calculatePriority($template['priority'], $issue->severity);
        
        return new CorrectionPrompt(
            $issue->type,
            $promptText,
            $targetLocations,
            $expectedChanges,
            3, // maxAttempts
            $priority,
            $quantitativeTarget
        );
    }
    
    /**
     * Calculate quantitative target for issue correction
     *
     * @param SEOIssue $issue Issue object
     * @return array Quantitative target data
     */
    private function calculateQuantitativeTarget($issue) {
        $target = [
            'current' => $issue->currentValue,
            'target' => $issue->targetValue,
            'difference' => 0,
            'count' => 0,
            'action' => ''
        ];
        
        // Determine action and calculate difference
        if ($issue->currentValue > $issue->targetValue) {
            $target['action'] = 'reduce';
            $target['difference'] = $issue->currentValue - $issue->targetValue;
        } else {
            $target['action'] = 'increase';
            $target['difference'] = $issue->targetValue - $issue->currentValue;
        }
        
        // Calculate count of changes needed based on issue type
        $target['count'] = $this->calculateChangeCount($issue, $target['difference']);
        
        return $target;
    }
    
    /**
     * Calculate number of changes needed
     *
     * @param SEOIssue $issue Issue object
     * @param float $difference Difference between current and target
     * @return int Number of changes needed
     */
    private function calculateChangeCount($issue, $difference) {
        switch ($issue->type) {
            case 'keyword_density_high':
            case 'keyword_density_low':
                // Estimate based on typical content length (assume 500 words)
                return max(1, (int)round(($difference / 100) * 500 / 2));
                
            case 'passive_voice_high':
                // Count of passive sentences to convert
                return isset($issue->locations) ? count($issue->locations) : max(1, (int)round($difference / 10));
                
            case 'sentence_length_high':
                // Count of long sentences to split
                return isset($issue->locations) ? count($issue->locations) : max(1, (int)round($difference / 5));
                
            case 'transition_words_low':
                // Estimate transition words needed (assume 30 sentences)
                return max(1, (int)round(($difference / 100) * 30));
                
            case 'subheading_keyword_overuse':
                // Count of headings to modify
                return isset($issue->locations) ? count($issue->locations) : max(1, (int)round($difference / 25));
                
            case 'alt_text_no_keyword':
                // Count of images to update
                return isset($issue->locations) ? count($issue->locations) : 1;
                
            default:
                return 1;
        }
    }
    
    /**
     * Fill prompt template with issue-specific data
     *
     * @param string $template Template string
     * @param SEOIssue $issue Issue object
     * @param string $focusKeyword Focus keyword
     * @param array $content Content array
     * @param array|null $quantitativeTarget Quantitative target data
     * @return string Filled prompt text
     */
    private function fillPromptTemplate($template, $issue, $focusKeyword, $content, $quantitativeTarget) {
        $replacements = [
            '{keyword}' => $focusKeyword,
            '{current}' => number_format($issue->currentValue, 1),
            '{target}' => number_format($issue->targetValue, 1),
            '{diff}' => $quantitativeTarget ? abs((int)$quantitativeTarget['difference']) : 0,
            '{count}' => $quantitativeTarget ? $quantitativeTarget['count'] : 0,
            '{text}' => $this->getContextText($issue, $content),
            '{locations}' => $this->formatLocations($issue->locations),
            '{sentences}' => $this->formatSentences($issue->locations),
            '{headings}' => $this->formatHeadings($issue->locations),
            '{images}' => $this->formatImages($issue->locations)
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Get context text for issue
     *
     * @param SEOIssue $issue Issue object
     * @param array $content Content array
     * @return string Context text
     */
    private function getContextText($issue, $content) {
        switch ($issue->type) {
            case 'meta_description_short':
            case 'meta_description_long':
            case 'meta_description_no_keyword':
                return $content['meta_description'] ?? '';
                
            case 'title_too_long':
            case 'title_no_keyword':
                return $content['title'] ?? '';
                
            default:
                return '';
        }
    }
    
    /**
     * Format locations for prompt
     *
     * @param array $locations Location data
     * @return string Formatted locations
     */
    private function formatLocations($locations) {
        if (empty($locations)) {
            return 'throughout content';
        }
        
        $formatted = [];
        foreach (array_slice($locations, 0, 3) as $location) {
            if (isset($location['context'])) {
                $formatted[] = '"' . substr($location['context'], 0, 50) . '..."';
            } elseif (isset($location['position'])) {
                $formatted[] = 'position ' . $location['position'];
            }
        }
        
        return implode(', ', $formatted);
    }
    
    /**
     * Format sentences for prompt
     *
     * @param array $locations Location data with sentences
     * @return string Formatted sentences
     */
    private function formatSentences($locations) {
        if (empty($locations)) {
            return 'identified sentences';
        }
        
        $formatted = [];
        foreach (array_slice($locations, 0, 3) as $location) {
            if (isset($location['sentence'])) {
                $sentence = substr($location['sentence'], 0, 60);
                $formatted[] = '"' . $sentence . '..."';
            }
        }
        
        $result = implode(', ', $formatted);
        if (count($locations) > 3) {
            $result .= ' and ' . (count($locations) - 3) . ' more';
        }
        
        return $result;
    }
    
    /**
     * Format headings for prompt
     *
     * @param array $locations Location data with headings
     * @return string Formatted headings
     */
    private function formatHeadings($locations) {
        if (empty($locations)) {
            return 'identified headings';
        }
        
        $formatted = [];
        foreach (array_slice($locations, 0, 3) as $location) {
            if (isset($location['heading'])) {
                $formatted[] = '"' . $location['heading'] . '"';
            }
        }
        
        $result = implode(', ', $formatted);
        if (count($locations) > 3) {
            $result .= ' and ' . (count($locations) - 3) . ' more';
        }
        
        return $result;
    }
    
    /**
     * Format images for prompt
     *
     * @param array $locations Location data with images
     * @return string Formatted images
     */
    private function formatImages($locations) {
        if (empty($locations)) {
            return 'identified images';
        }
        
        $formatted = [];
        foreach (array_slice($locations, 0, 3) as $location) {
            if (isset($location['alt_text'])) {
                $formatted[] = 'alt="' . substr($location['alt_text'], 0, 30) . '"';
            } elseif (isset($location['img_tag'])) {
                $formatted[] = 'image at position ' . ($location['position'] ?? 'unknown');
            }
        }
        
        return implode(', ', $formatted);
    }
    
    /**
     * Extract target locations from issue
     *
     * @param SEOIssue $issue Issue object
     * @return array Target locations
     */
    private function extractTargetLocations($issue) {
        if (empty($issue->locations)) {
            return [];
        }
        
        return array_map(function($location) {
            return [
                'position' => $location['position'] ?? null,
                'length' => $location['length'] ?? null,
                'context' => $location['context'] ?? $location['sentence'] ?? $location['heading'] ?? null
            ];
        }, $issue->locations);
    }
    
    /**
     * Define expected changes for issue correction
     *
     * @param SEOIssue $issue Issue object
     * @param array|null $quantitativeTarget Quantitative target data
     * @return array Expected changes
     */
    private function defineExpectedChanges($issue, $quantitativeTarget) {
        $changes = [
            'metric' => $issue->type,
            'currentValue' => $issue->currentValue,
            'targetValue' => $issue->targetValue,
            'expectedImprovement' => abs($issue->targetValue - $issue->currentValue)
        ];
        
        if ($quantitativeTarget) {
            $changes['action'] = $quantitativeTarget['action'];
            $changes['changeCount'] = $quantitativeTarget['count'];
        }
        
        return $changes;
    }
    
    /**
     * Calculate priority based on template priority and issue severity
     *
     * @param int $templatePriority Template base priority
     * @param string $severity Issue severity
     * @return int Calculated priority
     */
    private function calculatePriority($templatePriority, $severity) {
        $severityBoost = [
            'critical' => 2,
            'major' => 1,
            'minor' => 0
        ];
        
        return min(10, $templatePriority + ($severityBoost[$severity] ?? 0));
    }
    
    /**
     * Sort prompts by priority (highest first)
     *
     * @param array $prompts Array of CorrectionPrompt objects
     * @return array Sorted prompts
     */
    public function sortPromptsByPriority($prompts) {
        usort($prompts, function($a, $b) {
            return $b->priority - $a->priority;
        });
        
        return $prompts;
    }
    
    /**
     * Validate prompt effectiveness
     *
     * @param CorrectionPrompt $prompt Prompt object
     * @param array $beforeMetrics Metrics before correction
     * @param array $afterMetrics Metrics after correction
     * @return array Validation result
     */
    public function validatePromptEffectiveness($prompt, $beforeMetrics, $afterMetrics) {
        $issueType = $prompt->issueType;
        
        // Track effectiveness
        if (!isset($this->effectivenessTracking[$issueType])) {
            $this->effectivenessTracking[$issueType] = [
                'attempts' => 0,
                'successes' => 0,
                'improvements' => []
            ];
        }
        
        $this->effectivenessTracking[$issueType]['attempts']++;
        
        // Check if expected changes were achieved
        $expectedChanges = $prompt->expectedChanges;
        $metricKey = $this->getMetricKey($issueType);
        
        $beforeValue = $beforeMetrics[$metricKey] ?? null;
        $afterValue = $afterMetrics[$metricKey] ?? null;
        
        if ($beforeValue === null || $afterValue === null) {
            return [
                'success' => false,
                'reason' => 'missing_metrics',
                'improvement' => 0
            ];
        }
        
        $improvement = abs($afterValue - $beforeValue);
        $expectedImprovement = $expectedChanges['expectedImprovement'] ?? 0;
        
        // Consider successful if improvement is at least 50% of expected
        $success = $improvement >= ($expectedImprovement * 0.5);
        
        if ($success) {
            $this->effectivenessTracking[$issueType]['successes']++;
        }
        
        $this->effectivenessTracking[$issueType]['improvements'][] = $improvement;
        
        return [
            'success' => $success,
            'improvement' => $improvement,
            'expectedImprovement' => $expectedImprovement,
            'effectivenessRate' => $improvement / max(0.1, $expectedImprovement)
        ];
    }
    
    /**
     * Get metric key for issue type
     *
     * @param string $issueType Issue type
     * @return string Metric key
     */
    private function getMetricKey($issueType) {
        $metricMap = [
            'keyword_density_high' => 'keywordDensity',
            'keyword_density_low' => 'keywordDensity',
            'meta_description_short' => 'metaDescriptionLength',
            'meta_description_long' => 'metaDescriptionLength',
            'passive_voice_high' => 'passiveVoicePercentage',
            'sentence_length_high' => 'longSentencePercentage',
            'transition_words_low' => 'transitionWordPercentage',
            'title_too_long' => 'titleLength',
            'subheading_keyword_overuse' => 'subheadingKeywordUsage'
        ];
        
        return $metricMap[$issueType] ?? $issueType;
    }
    
    /**
     * Get effectiveness statistics
     *
     * @return array Effectiveness statistics
     */
    public function getEffectivenessStats() {
        $stats = [];
        
        foreach ($this->effectivenessTracking as $issueType => $data) {
            $successRate = $data['attempts'] > 0 ? ($data['successes'] / $data['attempts']) * 100 : 0;
            $avgImprovement = !empty($data['improvements']) ? array_sum($data['improvements']) / count($data['improvements']) : 0;
            
            $stats[$issueType] = [
                'attempts' => $data['attempts'],
                'successes' => $data['successes'],
                'successRate' => $successRate,
                'averageImprovement' => $avgImprovement
            ];
        }
        
        return $stats;
    }
    
    /**
     * Update configuration
     *
     * @param array $newConfig New configuration settings
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
    }
    
    /**
     * Get current configuration
     *
     * @return array Current configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Clear effectiveness tracking
     */
    public function clearEffectivenessTracking() {
        $this->effectivenessTracking = [];
    }
}
