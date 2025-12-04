<?php
/**
 * SEO Error Handler Class
 *
 * Comprehensive error handling and logging system for SEO validation failures.
 * Provides detailed error logging, manual override capabilities, and adaptive rule updating.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class SEOErrorHandler
 *
 * Handles SEO validation errors with logging and recovery mechanisms
 */
class SEOErrorHandler {
    
    /**
     * @var string Log file path
     */
    private $logFile;
    
    /**
     * @var array Error statistics
     */
    private $errorStats;
    
    /**
     * @var array Manual overrides
     */
    private $manualOverrides;
    
    /**
     * @var array Adaptive rules
     */
    private $adaptiveRules;
    
    /**
     * @var array Error classification categories
     */
    private $errorCategories;
    
    /**
     * @var array Recovery strategies
     */
    private $recoveryStrategies;
    
    /**
     * @var array Fallback strategies
     */
    private $fallbackStrategies;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Use WordPress content directory or fallback
        $content_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : (dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-content');
        $this->logFile = $content_dir . '/acs_seo_errors.log';
        
        // Initialize with safe defaults if WordPress functions aren't available
        $this->errorStats = function_exists('get_option') ? get_option('acs_seo_error_stats', []) : [];
        $this->manualOverrides = function_exists('get_option') ? get_option('acs_seo_manual_overrides', []) : [];
        $this->adaptiveRules = function_exists('get_option') ? get_option('acs_seo_adaptive_rules', $this->getDefaultRules()) : $this->getDefaultRules();
        
        // Initialize error classification
        $this->errorCategories = $this->initializeErrorCategories();
        
        // Initialize recovery strategies
        $this->recoveryStrategies = $this->initializeRecoveryStrategies();
        
        // Initialize fallback strategies
        $this->fallbackStrategies = $this->initializeFallbackStrategies();
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            if (function_exists('wp_mkdir_p')) {
                wp_mkdir_p($logDir);
            } else {
                // Fallback to standard PHP
                @mkdir($logDir, 0755, true);
            }
        }
    }
    
    /**
     * Log validation failure with detailed context
     *
     * @param string $component Component that failed
     * @param string $error Error message
     * @param array $context Additional context data
     * @param string $severity Error severity (error, warning, info)
     */
    public function logValidationFailure($component, $error, $context = [], $severity = 'error') {
        $timestamp = function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'component' => $component,
            'error' => $error,
            'severity' => $severity,
            'context' => $context,
            'session_id' => $this->getSessionId(),
            'user_id' => function_exists('get_current_user_id') ? get_current_user_id() : 0,
            'site_url' => function_exists('home_url') ? home_url() : 'localhost'
        ];
        
        // Write to log file
        $this->writeToLogFile($logEntry);
        
        // Update error statistics
        $this->updateErrorStats($component, $error, $severity);
        
        // Check if manual override exists
        $overrideKey = $this->generateOverrideKey($component, $error);
        if (isset($this->manualOverrides[$overrideKey])) {
            $logEntry['manual_override'] = $this->manualOverrides[$overrideKey];
        }
        
        // Trigger adaptive rule update if needed
        $this->checkAdaptiveRuleUpdate($component, $error, $context);
        
        // Log to WordPress error log for critical errors
        if ($severity === 'error') {
            error_log('[ACS][SEO_ERROR] ' . $component . ': ' . $error);
        }
        
        return $logEntry;
    }
    
    /**
     * Add manual override for persistent validation issues
     *
     * @param string $component Component name
     * @param string $error Error message
     * @param array $override Override configuration
     * @return bool Success status
     */
    public function addManualOverride($component, $error, $override) {
        $overrideKey = $this->generateOverrideKey($component, $error);
        
        $this->manualOverrides[$overrideKey] = [
            'component' => $component,
            'error' => $error,
            'override' => $override,
            'created_at' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
            'created_by' => function_exists('get_current_user_id') ? get_current_user_id() : 0,
            'active' => true
        ];
        
        // Save to database
        $saved = function_exists('update_option') ? update_option('acs_seo_manual_overrides', $this->manualOverrides) : true;
        
        // Log override creation
        $this->logValidationFailure('manual_override', 'Override created for: ' . $error, [
            'component' => $component,
            'override_key' => $overrideKey,
            'override_config' => $override
        ], 'info');
        
        return $saved;
    }
    
    /**
     * Check if manual override exists for error
     *
     * @param string $component Component name
     * @param string $error Error message
     * @return array|false Override configuration or false
     */
    public function getManualOverride($component, $error) {
        $overrideKey = $this->generateOverrideKey($component, $error);
        
        if (isset($this->manualOverrides[$overrideKey]) && $this->manualOverrides[$overrideKey]['active']) {
            return $this->manualOverrides[$overrideKey]['override'];
        }
        
        return false;
    }
    
    /**
     * Remove manual override
     *
     * @param string $component Component name
     * @param string $error Error message
     * @return bool Success status
     */
    public function removeManualOverride($component, $error) {
        $overrideKey = $this->generateOverrideKey($component, $error);
        
        if (isset($this->manualOverrides[$overrideKey])) {
            $this->manualOverrides[$overrideKey]['active'] = false;
            $this->manualOverrides[$overrideKey]['removed_at'] = function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s');
            
            return function_exists('update_option') ? update_option('acs_seo_manual_overrides', $this->manualOverrides) : true;
        }
        
        return false;
    }
    
    /**
     * Update adaptive validation rules based on error patterns
     *
     * @param array $newRules New rule configuration
     * @return bool Success status
     */
    public function updateAdaptiveRules($newRules) {
        $oldRules = $this->adaptiveRules;
        $this->adaptiveRules = array_merge($this->adaptiveRules, $newRules);
        
        // Save to database
        $saved = function_exists('update_option') ? update_option('acs_seo_adaptive_rules', $this->adaptiveRules) : true;
        
        // Log rule update
        $this->logValidationFailure('adaptive_rules', 'Rules updated', [
            'old_rules' => $oldRules,
            'new_rules' => $newRules,
            'merged_rules' => $this->adaptiveRules
        ], 'info');
        
        return $saved;
    }
    
    /**
     * Get current adaptive rules
     *
     * @return array Current adaptive rules
     */
    public function getAdaptiveRules() {
        return $this->adaptiveRules;
    }
    
    /**
     * Get error statistics
     *
     * @param string $component Optional component filter
     * @param int $days Number of days to include (default: 30)
     * @return array Error statistics
     */
    public function getErrorStats($component = null, $days = 30) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $filteredStats = [];
        
        foreach ($this->errorStats as $key => $stat) {
            if ($stat['last_occurrence'] >= $cutoffDate) {
                if ($component === null || $stat['component'] === $component) {
                    $filteredStats[$key] = $stat;
                }
            }
        }
        
        // Sort by frequency
        uasort($filteredStats, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return $filteredStats;
    }
    
    /**
     * Get recent error log entries
     *
     * @param int $limit Number of entries to return
     * @param string $component Optional component filter
     * @return array Recent log entries
     */
    public function getRecentErrors($limit = 50, $component = null) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entries = [];
        
        // Parse log entries (newest first)
        $lines = array_reverse($lines);
        
        foreach ($lines as $line) {
            if (count($entries) >= $limit) {
                break;
            }
            
            $entry = json_decode($line, true);
            if ($entry && ($component === null || $entry['component'] === $component)) {
                $entries[] = $entry;
            }
        }
        
        return $entries;
    }
    
    /**
     * Clear old log entries
     *
     * @param int $days Keep entries newer than this many days
     * @return bool Success status
     */
    public function clearOldLogs($days = 90) {
        if (!file_exists($this->logFile)) {
            return true;
        }
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $keptLines = [];
        
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if ($entry && $entry['timestamp'] >= $cutoffDate) {
                $keptLines[] = $line;
            }
        }
        
        // Write back filtered lines
        return file_put_contents($this->logFile, implode("\n", $keptLines) . "\n", LOCK_EX) !== false;
    }
    
    /**
     * Export error logs for analysis
     *
     * @param string $format Export format (json, csv)
     * @param int $days Number of days to include
     * @return string|false Exported data or false on failure
     */
    public function exportLogs($format = 'json', $days = 30) {
        $entries = $this->getRecentErrors(1000); // Get up to 1000 entries
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Filter by date
        $filteredEntries = array_filter($entries, function($entry) use ($cutoffDate) {
            return $entry['timestamp'] >= $cutoffDate;
        });
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($filteredEntries);
            case 'json':
            default:
                return json_encode($filteredEntries, JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Write log entry to file
     *
     * @param array $logEntry Log entry data
     */
    private function writeToLogFile($logEntry) {
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Update error statistics
     *
     * @param string $component Component name
     * @param string $error Error message
     * @param string $severity Error severity
     */
    private function updateErrorStats($component, $error, $severity) {
        $key = md5($component . '|' . $error);
        
        if (!isset($this->errorStats[$key])) {
            $this->errorStats[$key] = [
                'component' => $component,
                'error' => $error,
                'severity' => $severity,
                'count' => 0,
                'first_occurrence' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
                'last_occurrence' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s')
            ];
        }
        
        $this->errorStats[$key]['count']++;
        $this->errorStats[$key]['last_occurrence'] = function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s');
        
        // Save to database periodically (every 10 errors)
        if ($this->errorStats[$key]['count'] % 10 === 0) {
            if (function_exists('update_option')) {
                update_option('acs_seo_error_stats', $this->errorStats);
            }
        }
    }
    
    /**
     * Generate override key for component and error
     *
     * @param string $component Component name
     * @param string $error Error message
     * @return string Override key
     */
    private function generateOverrideKey($component, $error) {
        return md5($component . '|' . $error);
    }
    
    /**
     * Check if adaptive rule update is needed
     *
     * @param string $component Component name
     * @param string $error Error message
     * @param array $context Error context
     */
    private function checkAdaptiveRuleUpdate($component, $error, $context) {
        $key = md5($component . '|' . $error);
        
        // If error occurs frequently, consider rule adaptation
        if (isset($this->errorStats[$key]) && $this->errorStats[$key]['count'] >= 10) {
            $this->suggestRuleAdaptation($component, $error, $context);
        }
    }
    
    /**
     * Suggest rule adaptation for frequent errors
     *
     * @param string $component Component name
     * @param string $error Error message
     * @param array $context Error context
     */
    private function suggestRuleAdaptation($component, $error, $context) {
        $suggestions = [];
        
        // Analyze error patterns and suggest rule changes
        switch ($component) {
            case 'meta_description':
                if (strpos($error, 'too short') !== false) {
                    $suggestions['minMetaDescLength'] = max(100, $this->adaptiveRules['minMetaDescLength'] - 10);
                } elseif (strpos($error, 'too long') !== false) {
                    $suggestions['maxMetaDescLength'] = min(200, $this->adaptiveRules['maxMetaDescLength'] + 10);
                }
                break;
                
            case 'keyword_density':
                if (strpos($error, 'too low') !== false) {
                    $suggestions['minKeywordDensity'] = max(0.1, $this->adaptiveRules['minKeywordDensity'] - 0.1);
                } elseif (strpos($error, 'too high') !== false) {
                    $suggestions['maxKeywordDensity'] = min(5.0, $this->adaptiveRules['maxKeywordDensity'] + 0.5);
                }
                break;
                
            case 'readability':
                if (strpos($error, 'passive voice') !== false) {
                    $suggestions['maxPassiveVoice'] = min(20.0, $this->adaptiveRules['maxPassiveVoice'] + 2.0);
                } elseif (strpos($error, 'long sentences') !== false) {
                    $suggestions['maxLongSentences'] = min(40.0, $this->adaptiveRules['maxLongSentences'] + 5.0);
                } elseif (strpos($error, 'transition words') !== false) {
                    $suggestions['minTransitionWords'] = max(10.0, $this->adaptiveRules['minTransitionWords'] - 5.0);
                }
                break;
        }
        
        if (!empty($suggestions)) {
            // Log suggestion
            $this->logValidationFailure('adaptive_suggestion', 'Rule adaptation suggested', [
                'component' => $component,
                'error' => $error,
                'suggested_changes' => $suggestions,
                'current_rules' => $this->adaptiveRules
            ], 'info');
            
            // Auto-apply if enabled (could be a setting)
            $autoApply = function_exists('get_option') ? get_option('acs_seo_auto_adapt_rules', false) : false;
            if ($autoApply) {
                $this->updateAdaptiveRules($suggestions);
            }
        }
    }
    
    /**
     * Get default validation rules
     *
     * @return array Default rules
     */
    private function getDefaultRules() {
        return [
            'minMetaDescLength' => 120,
            'maxMetaDescLength' => 156,
            'minKeywordDensity' => 0.5,
            'maxKeywordDensity' => 2.5,
            'maxPassiveVoice' => 10.0,
            'maxLongSentences' => 25.0,
            'minTransitionWords' => 30.0,
            'maxTitleLength' => 66,
            'maxSubheadingKeywordUsage' => 75.0
        ];
    }
    
    /**
     * Get unique session ID for tracking
     *
     * @return string Session ID
     */
    private function getSessionId() {
        if (!session_id()) {
            return uniqid('acs_', true);
        }
        return session_id();
    }
    
    /**
     * Export entries to CSV format
     *
     * @param array $entries Log entries
     * @return string CSV data
     */
    private function exportToCsv($entries) {
        if (empty($entries)) {
            return '';
        }
        
        $csv = "Timestamp,Component,Error,Severity,User ID,Session ID\n";
        
        foreach ($entries as $entry) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $entry['timestamp'],
                $entry['component'],
                str_replace('"', '""', $entry['error']),
                $entry['severity'],
                $entry['user_id'] ?? '',
                $entry['session_id'] ?? ''
            );
        }
        
        return $csv;
    }
    
    /**
     * Initialize error classification categories
     *
     * @return array Error categories with classification rules
     */
    private function initializeErrorCategories() {
        return [
            'critical' => [
                'description' => 'Errors that prevent optimization from continuing',
                'patterns' => ['fatal', 'exception', 'crash', 'cannot continue'],
                'requires_immediate_action' => true,
                'enable_fallback' => true
            ],
            'recoverable' => [
                'description' => 'Errors that can be recovered through retry or alternative approach',
                'patterns' => ['timeout', 'rate limit', 'temporary', 'retry'],
                'requires_immediate_action' => false,
                'enable_fallback' => true
            ],
            'degraded' => [
                'description' => 'Errors that allow partial functionality',
                'patterns' => ['partial', 'incomplete', 'degraded'],
                'requires_immediate_action' => false,
                'enable_fallback' => true
            ],
            'informational' => [
                'description' => 'Non-critical issues for monitoring',
                'patterns' => ['warning', 'notice', 'info'],
                'requires_immediate_action' => false,
                'enable_fallback' => false
            ]
        ];
    }
    
    /**
     * Initialize recovery strategies
     *
     * @return array Recovery strategies by error type
     */
    private function initializeRecoveryStrategies() {
        return [
            'ai_provider_failure' => [
                'strategy' => 'provider_failover',
                'steps' => ['switch_provider', 'retry_request', 'use_cached_result'],
                'max_attempts' => 3,
                'backoff_multiplier' => 2
            ],
            'validation_timeout' => [
                'strategy' => 'simplified_validation',
                'steps' => ['reduce_validation_scope', 'use_cached_validation', 'skip_non_critical'],
                'max_attempts' => 2,
                'backoff_multiplier' => 1.5
            ],
            'correction_failure' => [
                'strategy' => 'alternative_correction',
                'steps' => ['simplify_prompt', 'use_template', 'manual_fallback'],
                'max_attempts' => 3,
                'backoff_multiplier' => 1
            ],
            'rate_limit_exceeded' => [
                'strategy' => 'exponential_backoff',
                'steps' => ['wait_and_retry', 'switch_provider', 'queue_for_later'],
                'max_attempts' => 5,
                'backoff_multiplier' => 2
            ],
            'network_error' => [
                'strategy' => 'retry_with_backoff',
                'steps' => ['retry_immediately', 'retry_with_delay', 'use_cached_result'],
                'max_attempts' => 3,
                'backoff_multiplier' => 2
            ]
        ];
    }
    
    /**
     * Initialize fallback strategies
     *
     * @return array Fallback strategies by component
     */
    private function initializeFallbackStrategies() {
        return [
            'ai_correction' => [
                'primary' => 'use_ai_provider',
                'fallback_1' => 'use_alternative_provider',
                'fallback_2' => 'use_template_based_correction',
                'fallback_3' => 'return_original_content',
                'graceful_degradation' => true
            ],
            'validation' => [
                'primary' => 'full_validation',
                'fallback_1' => 'critical_validation_only',
                'fallback_2' => 'cached_validation',
                'fallback_3' => 'skip_validation',
                'graceful_degradation' => true
            ],
            'optimization_loop' => [
                'primary' => 'continue_optimization',
                'fallback_1' => 'reduce_iteration_count',
                'fallback_2' => 'return_best_result',
                'fallback_3' => 'return_original_content',
                'graceful_degradation' => true
            ]
        ];
    }
    
    /**
     * Classify error by category
     *
     * @param string $error Error message
     * @param string $component Component name
     * @return string Error category (critical, recoverable, degraded, informational)
     */
    public function classifyError($error, $component) {
        $errorLower = strtolower($error);
        
        // Check each category
        foreach ($this->errorCategories as $category => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (strpos($errorLower, $pattern) !== false) {
                    return $category;
                }
            }
        }
        
        // Default to recoverable if no match
        return 'recoverable';
    }
    
    /**
     * Handle error with automatic recovery
     *
     * @param string $errorType Type of error
     * @param string $component Component that failed
     * @param string $error Error message
     * @param array $context Error context
     * @param int $attemptNumber Current attempt number
     * @return array Recovery result with strategy and next action
     */
    public function handleErrorWithRecovery($errorType, $component, $error, $context = [], $attemptNumber = 1) {
        // Classify error
        $category = $this->classifyError($error, $component);
        
        // Log error with classification
        $this->logValidationFailure($component, $error, array_merge($context, [
            'error_type' => $errorType,
            'category' => $category,
            'attempt_number' => $attemptNumber
        ]), $category === 'critical' ? 'error' : 'warning');
        
        // Get recovery strategy
        $strategy = $this->recoveryStrategies[$errorType] ?? null;
        
        if (!$strategy) {
            // No specific strategy, use generic fallback
            return $this->applyGenericFallback($component, $error, $context);
        }
        
        // Check if max attempts reached
        if ($attemptNumber >= $strategy['max_attempts']) {
            return [
                'success' => false,
                'action' => 'max_attempts_reached',
                'fallback' => $this->getFallbackStrategy($component),
                'message' => "Maximum recovery attempts ({$strategy['max_attempts']}) reached for {$errorType}"
            ];
        }
        
        // Determine next recovery step
        $stepIndex = min($attemptNumber - 1, count($strategy['steps']) - 1);
        $nextStep = $strategy['steps'][$stepIndex];
        
        // Calculate backoff delay
        $backoffDelay = $this->calculateBackoffDelay($attemptNumber, $strategy['backoff_multiplier']);
        
        return [
            'success' => true,
            'action' => 'retry',
            'strategy' => $strategy['strategy'],
            'next_step' => $nextStep,
            'backoff_delay' => $backoffDelay,
            'attempt_number' => $attemptNumber + 1,
            'message' => "Applying recovery strategy: {$strategy['strategy']}, step: {$nextStep}"
        ];
    }
    
    /**
     * Apply graceful degradation for partial failures
     *
     * @param string $component Component with failure
     * @param array $partialResults Partial results achieved
     * @param array $failures Failed operations
     * @return array Degraded result
     */
    public function applyGracefulDegradation($component, $partialResults, $failures) {
        $fallbackStrategy = $this->fallbackStrategies[$component] ?? null;
        
        if (!$fallbackStrategy || !$fallbackStrategy['graceful_degradation']) {
            // No graceful degradation available
            return [
                'success' => false,
                'degraded' => false,
                'results' => $partialResults,
                'failures' => $failures,
                'message' => 'Graceful degradation not available for ' . $component
            ];
        }
        
        // Log degradation
        $this->logValidationFailure($component, 'Applying graceful degradation', [
            'partial_results_count' => count($partialResults),
            'failures_count' => count($failures),
            'fallback_strategy' => $fallbackStrategy
        ], 'warning');
        
        // Determine degradation level
        $successRate = count($partialResults) / (count($partialResults) + count($failures));
        
        if ($successRate >= 0.7) {
            $degradationLevel = 'minor';
        } elseif ($successRate >= 0.4) {
            $degradationLevel = 'moderate';
        } else {
            $degradationLevel = 'severe';
        }
        
        return [
            'success' => true,
            'degraded' => true,
            'degradation_level' => $degradationLevel,
            'results' => $partialResults,
            'failures' => $failures,
            'success_rate' => $successRate * 100,
            'message' => "Operating in degraded mode ({$degradationLevel}) with {$successRate}% success rate"
        ];
    }
    
    /**
     * Get fallback strategy for component
     *
     * @param string $component Component name
     * @return array Fallback strategy
     */
    public function getFallbackStrategy($component) {
        return $this->fallbackStrategies[$component] ?? [
            'primary' => 'default_operation',
            'fallback_1' => 'return_original',
            'fallback_2' => 'skip_operation',
            'fallback_3' => 'log_and_continue',
            'graceful_degradation' => false
        ];
    }
    
    /**
     * Apply generic fallback when no specific strategy exists
     *
     * @param string $component Component name
     * @param string $error Error message
     * @param array $context Error context
     * @return array Fallback result
     */
    private function applyGenericFallback($component, $error, $context) {
        $fallback = $this->getFallbackStrategy($component);
        
        $this->logValidationFailure($component, 'Applying generic fallback', [
            'error' => $error,
            'fallback_strategy' => $fallback,
            'context' => $context
        ], 'warning');
        
        return [
            'success' => false,
            'action' => 'fallback',
            'fallback_strategy' => $fallback,
            'message' => "Applied generic fallback for {$component}: {$error}"
        ];
    }
    
    /**
     * Calculate exponential backoff delay
     *
     * @param int $attemptNumber Current attempt number
     * @param float $multiplier Backoff multiplier
     * @return float Delay in seconds
     */
    private function calculateBackoffDelay($attemptNumber, $multiplier) {
        // Base delay of 1 second
        $baseDelay = 1.0;
        
        // Exponential backoff: baseDelay * (multiplier ^ (attemptNumber - 1))
        $delay = $baseDelay * pow($multiplier, $attemptNumber - 1);
        
        // Cap at 30 seconds
        return min($delay, 30.0);
    }
    
    /**
     * Execute recovery strategy with automatic retry
     *
     * @param callable $operation Operation to execute
     * @param string $errorType Error type for recovery strategy
     * @param string $component Component name
     * @param array $context Operation context
     * @return array Operation result
     */
    public function executeWithRecovery($operation, $errorType, $component, $context = []) {
        $strategy = $this->recoveryStrategies[$errorType] ?? null;
        $maxAttempts = $strategy ? $strategy['max_attempts'] : 3;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Execute operation
                $result = call_user_func($operation, $attempt, $context);
                
                // Check if successful
                if (is_array($result) && isset($result['success']) && $result['success']) {
                    // Log successful recovery if not first attempt
                    if ($attempt > 1) {
                        $this->logValidationFailure($component, "Operation succeeded after {$attempt} attempts", [
                            'error_type' => $errorType,
                            'attempts' => $attempt,
                            'context' => $context
                        ], 'info');
                    }
                    
                    return $result;
                }
                
                // Operation returned failure result
                $error = $result['error'] ?? 'Operation failed';
                
                // Handle error with recovery
                $recoveryResult = $this->handleErrorWithRecovery(
                    $errorType,
                    $component,
                    $error,
                    $context,
                    $attempt
                );
                
                if ($recoveryResult['action'] === 'max_attempts_reached') {
                    // Apply fallback
                    return array_merge($result, [
                        'fallback_applied' => true,
                        'fallback_strategy' => $recoveryResult['fallback']
                    ]);
                }
                
                // Apply backoff delay before retry
                if (isset($recoveryResult['backoff_delay']) && $recoveryResult['backoff_delay'] > 0) {
                    usleep($recoveryResult['backoff_delay'] * 1000000);
                }
                
            } catch (Exception $e) {
                // Handle exception
                $recoveryResult = $this->handleErrorWithRecovery(
                    $errorType,
                    $component,
                    $e->getMessage(),
                    array_merge($context, ['exception' => get_class($e)]),
                    $attempt
                );
                
                if ($recoveryResult['action'] === 'max_attempts_reached') {
                    // Return failure with fallback
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                        'fallback_applied' => true,
                        'fallback_strategy' => $recoveryResult['fallback']
                    ];
                }
                
                // Apply backoff delay before retry
                if (isset($recoveryResult['backoff_delay']) && $recoveryResult['backoff_delay'] > 0) {
                    usleep($recoveryResult['backoff_delay'] * 1000000);
                }
            }
        }
        
        // All attempts failed
        return [
            'success' => false,
            'error' => 'All recovery attempts failed',
            'attempts' => $maxAttempts,
            'fallback_applied' => true,
            'fallback_strategy' => $this->getFallbackStrategy($component)
        ];
    }
    
    /**
     * Generate user-friendly error report
     *
     * @param array $errors Array of errors
     * @return array User-friendly error report
     */
    public function generateUserFriendlyReport($errors) {
        $report = [
            'summary' => '',
            'details' => [],
            'recommendations' => [],
            'severity' => 'info'
        ];
        
        if (empty($errors)) {
            $report['summary'] = 'No errors detected';
            return $report;
        }
        
        // Classify errors
        $classified = [];
        foreach ($errors as $error) {
            $category = $this->classifyError($error['message'] ?? $error, $error['component'] ?? 'unknown');
            $classified[$category][] = $error;
        }
        
        // Determine overall severity
        if (!empty($classified['critical'])) {
            $report['severity'] = 'critical';
            $report['summary'] = count($classified['critical']) . ' critical error(s) detected';
        } elseif (!empty($classified['recoverable'])) {
            $report['severity'] = 'warning';
            $report['summary'] = count($classified['recoverable']) . ' recoverable error(s) detected';
        } elseif (!empty($classified['degraded'])) {
            $report['severity'] = 'warning';
            $report['summary'] = 'System operating in degraded mode';
        } else {
            $report['severity'] = 'info';
            $report['summary'] = 'Minor issues detected';
        }
        
        // Generate details
        foreach ($classified as $category => $categoryErrors) {
            $report['details'][$category] = [
                'count' => count($categoryErrors),
                'errors' => array_map(function($error) {
                    return [
                        'component' => $error['component'] ?? 'unknown',
                        'message' => $this->simplifyErrorMessage($error['message'] ?? $error),
                        'timestamp' => $error['timestamp'] ?? (function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'))
                    ];
                }, $categoryErrors)
            ];
        }
        
        // Generate recommendations
        $report['recommendations'] = $this->generateRecommendations($classified);
        
        return $report;
    }
    
    /**
     * Simplify error message for user display
     *
     * @param string $error Technical error message
     * @return string User-friendly message
     */
    private function simplifyErrorMessage($error) {
        $simplifications = [
            '/timeout/i' => 'The operation took too long to complete',
            '/rate limit/i' => 'Too many requests - please wait a moment',
            '/connection/i' => 'Unable to connect to the service',
            '/authentication/i' => 'Authentication failed - please check your API keys',
            '/not found/i' => 'The requested resource was not found',
            '/permission/i' => 'You do not have permission to perform this action',
            '/invalid/i' => 'The provided data is invalid',
            '/exception/i' => 'An unexpected error occurred'
        ];
        
        foreach ($simplifications as $pattern => $message) {
            if (preg_match($pattern, $error)) {
                return $message;
            }
        }
        
        return $error;
    }
    
    /**
     * Generate recommendations based on errors
     *
     * @param array $classifiedErrors Errors classified by category
     * @return array Recommendations
     */
    private function generateRecommendations($classifiedErrors) {
        $recommendations = [];
        
        if (!empty($classifiedErrors['critical'])) {
            $recommendations[] = 'Critical errors detected - immediate action required';
            $recommendations[] = 'Check system logs for detailed error information';
            $recommendations[] = 'Verify API keys and service connectivity';
        }
        
        if (!empty($classifiedErrors['recoverable'])) {
            $recommendations[] = 'Some operations failed but can be retried';
            $recommendations[] = 'Consider increasing timeout values if errors persist';
        }
        
        if (!empty($classifiedErrors['degraded'])) {
            $recommendations[] = 'System is operating with reduced functionality';
            $recommendations[] = 'Some features may not be available';
        }
        
        return $recommendations;
    }
    
    /**
     * Get error categories configuration
     *
     * @return array Error categories
     */
    public function getErrorCategories() {
        return $this->errorCategories;
    }
    
    /**
     * Get recovery strategies configuration
     *
     * @return array Recovery strategies
     */
    public function getRecoveryStrategies() {
        return $this->recoveryStrategies;
    }
    
    /**
     * Get fallback strategies configuration
     *
     * @return array Fallback strategies
     */
    public function getFallbackStrategies() {
        return $this->fallbackStrategies;
    }
}