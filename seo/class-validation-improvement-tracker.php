<?php
/**
 * Validation and Improvement Measurement System
 *
 * Implements re-validation logic for corrected content, improvement calculation,
 * validation result aggregation, progress measurement, and validation caching.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-result.php';
require_once ACS_PLUGIN_PATH . 'seo/class-content-validation-metrics.php';
require_once ACS_PLUGIN_PATH . 'seo/class-seo-issue-detector.php';

/**
 * Class ValidationImprovementTracker
 *
 * Tracks validation results and measures improvements between optimization passes
 */
class ValidationImprovementTracker {
    
    /**
     * @var SEOIssueDetector
     */
    private $issueDetector;
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * @var array Validation cache for performance optimization
     */
    private $validationCache;
    
    /**
     * @var array Pass history tracking
     */
    private $passHistory;
    
    /**
     * @var array Improvement trends
     */
    private $improvementTrends;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'enableCaching' => true,
            'cacheExpiration' => 3600, // 1 hour
            'trackTrends' => true,
            'detailedMetrics' => true
        ], $config);
        
        $this->issueDetector = new SEOIssueDetector();
        $this->validationCache = [];
        $this->passHistory = [];
        $this->improvementTrends = [];
    }
    
    /**
     * Re-validate corrected content and measure improvements
     *
     * @param array $originalContent Original content before correction
     * @param array $correctedContent Content after correction
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param int $passNumber Current pass number
     * @return array Validation and improvement result
     */
    public function validateAndMeasureImprovement($originalContent, $correctedContent, $focusKeyword, $secondaryKeywords = [], $passNumber = 1) {
        // Validate original content (use cache if available)
        $originalValidation = $this->validateContent($originalContent, $focusKeyword, $secondaryKeywords, 'original');
        
        // Validate corrected content
        $correctedValidation = $this->validateContent($correctedContent, $focusKeyword, $secondaryKeywords, 'corrected');
        
        // Calculate improvements
        $improvements = $this->calculateImprovements($originalValidation, $correctedValidation);
        
        // Aggregate validation results
        $aggregatedResult = $this->aggregateValidationResults($originalValidation, $correctedValidation, $improvements);
        
        // Track progress for this pass
        $this->trackPassProgress($passNumber, $originalValidation, $correctedValidation, $improvements);
        
        // Analyze trends if enabled
        if ($this->config['trackTrends']) {
            $trends = $this->analyzeTrends();
            $aggregatedResult['trends'] = $trends;
        }
        
        return $aggregatedResult;
    }
    
    /**
     * Validate content with caching support
     *
     * @param array $content Content to validate
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param string $cacheKey Cache key identifier
     * @return array Validation result
     */
    private function validateContent($content, $focusKeyword, $secondaryKeywords, $cacheKey) {
        // Generate cache key
        $contentHash = $this->generateContentHash($content, $focusKeyword, $secondaryKeywords);
        $fullCacheKey = $cacheKey . '_' . $contentHash;
        
        // Check cache if enabled
        if ($this->config['enableCaching'] && isset($this->validationCache[$fullCacheKey])) {
            $cached = $this->validationCache[$fullCacheKey];
            if (time() - $cached['timestamp'] < $this->config['cacheExpiration']) {
                return $cached['result'];
            }
        }
        
        // Perform validation
        $validationResult = $this->issueDetector->detectAllIssues($content, $focusKeyword, $secondaryKeywords);
        
        // Cache result if enabled
        if ($this->config['enableCaching']) {
            $this->validationCache[$fullCacheKey] = [
                'result' => $validationResult,
                'timestamp' => time()
            ];
        }
        
        return $validationResult;
    }
    
    /**
     * Calculate improvements between original and corrected content
     *
     * @param array $originalValidation Original validation result
     * @param array $correctedValidation Corrected validation result
     * @return array Improvement metrics
     */
    private function calculateImprovements($originalValidation, $correctedValidation) {
        $improvements = [
            'scoreImprovement' => $correctedValidation['complianceScore'] - $originalValidation['complianceScore'],
            'issuesResolved' => $originalValidation['totalIssues'] - $correctedValidation['totalIssues'],
            'criticalIssuesResolved' => $originalValidation['criticalIssues'] - $correctedValidation['criticalIssues'],
            'majorIssuesResolved' => $originalValidation['majorIssues'] - $correctedValidation['majorIssues'],
            'minorIssuesResolved' => $originalValidation['minorIssues'] - $correctedValidation['minorIssues'],
            'percentageImprovement' => 0,
            'resolvedIssueTypes' => [],
            'newIssues' => [],
            'persistentIssues' => []
        ];
        
        // Calculate percentage improvement
        if ($originalValidation['complianceScore'] < 100) {
            $improvements['percentageImprovement'] = 
                ($improvements['scoreImprovement'] / (100 - $originalValidation['complianceScore'])) * 100;
        }
        
        // Identify resolved, new, and persistent issues
        $originalIssueTypes = array_map(function($issue) { return $issue->type; }, $originalValidation['issues']);
        $correctedIssueTypes = array_map(function($issue) { return $issue->type; }, $correctedValidation['issues']);
        
        $improvements['resolvedIssueTypes'] = array_diff($originalIssueTypes, $correctedIssueTypes);
        $improvements['newIssues'] = array_diff($correctedIssueTypes, $originalIssueTypes);
        $improvements['persistentIssues'] = array_intersect($originalIssueTypes, $correctedIssueTypes);
        
        // Calculate metric-specific improvements if detailed metrics enabled
        if ($this->config['detailedMetrics']) {
            $improvements['metricImprovements'] = $this->calculateMetricImprovements(
                $originalValidation['metrics'],
                $correctedValidation['metrics']
            );
        }
        
        return $improvements;
    }
    
    /**
     * Calculate improvements for specific metrics
     *
     * @param array $originalMetrics Original metrics
     * @param array $correctedMetrics Corrected metrics
     * @return array Metric-specific improvements
     */
    private function calculateMetricImprovements($originalMetrics, $correctedMetrics) {
        $metricImprovements = [];
        
        $metricsToCompare = [
            'keywordDensity',
            'metaDescriptionLength',
            'passiveVoicePercentage',
            'longSentencePercentage',
            'transitionWordPercentage'
        ];
        
        foreach ($metricsToCompare as $metric) {
            if (isset($originalMetrics[$metric]) && isset($correctedMetrics[$metric])) {
                $metricImprovements[$metric] = [
                    'original' => $originalMetrics[$metric],
                    'corrected' => $correctedMetrics[$metric],
                    'change' => $correctedMetrics[$metric] - $originalMetrics[$metric],
                    'percentChange' => $originalMetrics[$metric] != 0 ? 
                        (($correctedMetrics[$metric] - $originalMetrics[$metric]) / $originalMetrics[$metric]) * 100 : 0
                ];
            }
        }
        
        return $metricImprovements;
    }
    
    /**
     * Aggregate validation results into comprehensive report
     *
     * @param array $originalValidation Original validation result
     * @param array $correctedValidation Corrected validation result
     * @param array $improvements Improvement metrics
     * @return array Aggregated result
     */
    private function aggregateValidationResults($originalValidation, $correctedValidation, $improvements) {
        return [
            'original' => [
                'complianceScore' => $originalValidation['complianceScore'],
                'totalIssues' => $originalValidation['totalIssues'],
                'criticalIssues' => $originalValidation['criticalIssues'],
                'majorIssues' => $originalValidation['majorIssues'],
                'minorIssues' => $originalValidation['minorIssues'],
                'isCompliant' => $originalValidation['isCompliant'],
                'metrics' => $originalValidation['metrics']
            ],
            'corrected' => [
                'complianceScore' => $correctedValidation['complianceScore'],
                'totalIssues' => $correctedValidation['totalIssues'],
                'criticalIssues' => $correctedValidation['criticalIssues'],
                'majorIssues' => $correctedValidation['majorIssues'],
                'minorIssues' => $correctedValidation['minorIssues'],
                'isCompliant' => $correctedValidation['isCompliant'],
                'metrics' => $correctedValidation['metrics']
            ],
            'improvements' => $improvements,
            'summary' => [
                'improved' => $improvements['scoreImprovement'] > 0,
                'complianceAchieved' => $correctedValidation['isCompliant'],
                'significantImprovement' => $improvements['scoreImprovement'] >= 10,
                'allIssuesResolved' => $correctedValidation['totalIssues'] === 0
            ]
        ];
    }
    
    /**
     * Track progress for current pass
     *
     * @param int $passNumber Pass number
     * @param array $originalValidation Original validation
     * @param array $correctedValidation Corrected validation
     * @param array $improvements Improvements
     */
    private function trackPassProgress($passNumber, $originalValidation, $correctedValidation, $improvements) {
        $this->passHistory[$passNumber] = [
            'passNumber' => $passNumber,
            'timestamp' => current_time('mysql'),
            'originalScore' => $originalValidation['complianceScore'],
            'correctedScore' => $correctedValidation['complianceScore'],
            'scoreImprovement' => $improvements['scoreImprovement'],
            'issuesResolved' => $improvements['issuesResolved'],
            'resolvedIssueTypes' => $improvements['resolvedIssueTypes'],
            'newIssues' => $improvements['newIssues'],
            'persistentIssues' => $improvements['persistentIssues']
        ];
    }
    
    /**
     * Analyze improvement trends across passes
     *
     * @return array Trend analysis
     */
    private function analyzeTrends() {
        if (count($this->passHistory) < 2) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Need at least 2 passes for trend analysis'
            ];
        }
        
        $scores = array_column($this->passHistory, 'correctedScore');
        $improvements = array_column($this->passHistory, 'scoreImprovement');
        
        // Calculate trend direction
        $trendDirection = 'stable';
        $recentImprovements = array_slice($improvements, -3);
        $avgRecentImprovement = array_sum($recentImprovements) / count($recentImprovements);
        
        if ($avgRecentImprovement > 5) {
            $trendDirection = 'improving';
        } elseif ($avgRecentImprovement < -2) {
            $trendDirection = 'declining';
        } elseif ($avgRecentImprovement < 1) {
            $trendDirection = 'stagnating';
        }
        
        // Calculate velocity (rate of improvement)
        $velocity = count($improvements) > 1 ? 
            array_sum($improvements) / count($improvements) : 0;
        
        // Predict passes needed to reach 100% (if not already there)
        $currentScore = end($scores);
        $passesNeeded = 0;
        if ($currentScore < 100 && $velocity > 0) {
            $passesNeeded = ceil((100 - $currentScore) / $velocity);
        }
        
        return [
            'status' => 'available',
            'trendDirection' => $trendDirection,
            'averageImprovement' => array_sum($improvements) / count($improvements),
            'velocity' => $velocity,
            'currentScore' => $currentScore,
            'passesNeeded' => $passesNeeded,
            'consistentImprovement' => min($improvements) > 0,
            'scoreProgression' => $scores
        ];
    }
    
    /**
     * Get pass history
     *
     * @return array Pass history
     */
    public function getPassHistory() {
        return $this->passHistory;
    }
    
    /**
     * Get improvement trends
     *
     * @return array Improvement trends
     */
    public function getImprovementTrends() {
        return $this->improvementTrends;
    }
    
    /**
     * Clear validation cache
     */
    public function clearCache() {
        $this->validationCache = [];
    }
    
    /**
     * Clear pass history
     */
    public function clearHistory() {
        $this->passHistory = [];
        $this->improvementTrends = [];
    }
    
    /**
     * Generate content hash for caching
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return string Content hash
     */
    private function generateContentHash($content, $focusKeyword, $secondaryKeywords) {
        $hashData = [
            'title' => $content['title'] ?? '',
            'content' => $content['content'] ?? '',
            'meta_description' => $content['meta_description'] ?? '',
            'focus_keyword' => $focusKeyword,
            'secondary_keywords' => implode(',', $secondaryKeywords)
        ];
        
        return md5(json_encode($hashData));
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getCacheStats() {
        $totalEntries = count($this->validationCache);
        $expiredEntries = 0;
        
        foreach ($this->validationCache as $entry) {
            if (time() - $entry['timestamp'] >= $this->config['cacheExpiration']) {
                $expiredEntries++;
            }
        }
        
        return [
            'totalEntries' => $totalEntries,
            'activeEntries' => $totalEntries - $expiredEntries,
            'expiredEntries' => $expiredEntries,
            'cacheEnabled' => $this->config['enableCaching']
        ];
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
}
