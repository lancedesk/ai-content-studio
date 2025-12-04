<?php
/**
 * Smart Retry Manager Class
 *
 * Intelligent retry system that minimizes AI model calls through
 * progressive correction strategies and failure pattern analysis.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class SmartRetryManager
 *
 * Manages intelligent retry strategies for content generation and validation
 */
class SmartRetryManager {
    
    /**
     * @var array Retry configuration
     */
    private $config;
    
    /**
     * @var array Failure patterns and their solutions
     */
    private $failurePatterns;
    
    /**
     * @var array Retry statistics
     */
    private $retryStats;
    
    /**
     * @var SEOValidationCache Cache instance
     */
    private $cache;
    
    /**
     * Constructor
     *
     * @param array $config Retry configuration
     * @param SEOValidationCache $cache Cache instance
     */
    public function __construct($config = [], $cache = null) {
        $this->config = array_merge([
            'maxRetries' => 3,
            'baseDelay' => 1, // seconds
            'maxDelay' => 30, // seconds
            'backoffMultiplier' => 2,
            'enableSmartCorrection' => true,
            'enablePatternLearning' => true,
            'minSuccessRate' => 0.7
        ], $config);
        
        $this->cache = $cache ?? new SEOValidationCache();
        $this->failurePatterns = get_option('acs_retry_failure_patterns', $this->getDefaultPatterns());
        $this->retryStats = get_option('acs_retry_stats', []);
        
        // Initialize pattern learning
        if ($this->config['enablePatternLearning']) {
            add_action('acs_validation_failure', [$this, 'learnFromFailure'], 10, 3);
            add_action('acs_validation_success', [$this, 'learnFromSuccess'], 10, 3);
        }
    }
    
    /**
     * Execute validation with smart retry logic
     *
     * @param callable $validationCallback Validation function to execute
     * @param array $content Content to validate
     * @param array $context Validation context (keywords, config, etc.)
     * @return array Validation result with retry information
     */
    public function executeWithRetry($validationCallback, $content, $context) {
        $attempt = 0;
        $lastError = null;
        $retryHistory = [];
        $startTime = microtime(true);
        
        // Generate cache key for retry strategy
        $retryKey = $this->generateRetryKey($content, $context);
        
        // Check if we have a cached successful strategy
        $cachedStrategy = $this->getCachedStrategy($retryKey);
        if ($cachedStrategy) {
            $content = $this->applyStrategy($content, $cachedStrategy);
        }
        
        while ($attempt < $this->config['maxRetries']) {
            $attempt++;
            $attemptStart = microtime(true);
            
            try {
                // Execute validation
                $result = call_user_func($validationCallback, $content, $context);
                
                // Record successful attempt
                $attemptTime = microtime(true) - $attemptStart;
                $retryHistory[] = [
                    'attempt' => $attempt,
                    'success' => true,
                    'time' => $attemptTime,
                    'strategy' => $cachedStrategy ?? 'none'
                ];
                
                // Cache successful strategy if it worked
                if ($attempt > 1 && $cachedStrategy) {
                    $this->cacheSuccessfulStrategy($retryKey, $cachedStrategy);
                }
                
                // Update success statistics
                $this->updateRetryStats($retryKey, true, $attempt, microtime(true) - $startTime);
                
                // Trigger success learning
                do_action('acs_validation_success', $content, $context, $retryHistory);
                
                return [
                    'success' => true,
                    'result' => $result,
                    'attempts' => $attempt,
                    'total_time' => microtime(true) - $startTime,
                    'retry_history' => $retryHistory
                ];
                
            } catch (Exception $e) {
                $lastError = $e;
                $attemptTime = microtime(true) - $attemptStart;
                
                // Record failed attempt
                $retryHistory[] = [
                    'attempt' => $attempt,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'time' => $attemptTime,
                    'strategy' => $cachedStrategy ?? 'none'
                ];
                
                // Trigger failure learning
                do_action('acs_validation_failure', $content, $context, $e);
                
                // If not the last attempt, apply smart correction
                if ($attempt < $this->config['maxRetries'] && $this->config['enableSmartCorrection']) {
                    $correctionStrategy = $this->analyzeFailureAndGetStrategy($e, $content, $context, $attempt);
                    if ($correctionStrategy) {
                        $content = $this->applyStrategy($content, $correctionStrategy);
                        $cachedStrategy = $correctionStrategy;
                    }
                    
                    // Apply exponential backoff
                    $delay = min(
                        $this->config['baseDelay'] * pow($this->config['backoffMultiplier'], $attempt - 1),
                        $this->config['maxDelay']
                    );
                    sleep($delay);
                }
            }
        }
        
        // All retries failed
        $this->updateRetryStats($retryKey, false, $attempt, microtime(true) - $startTime);
        
        return [
            'success' => false,
            'error' => $lastError ? $lastError->getMessage() : 'Unknown error',
            'attempts' => $attempt,
            'total_time' => microtime(true) - $startTime,
            'retry_history' => $retryHistory
        ];
    }
    
    /**
     * Analyze failure and determine correction strategy
     *
     * @param Exception $error The error that occurred
     * @param array $content Content that failed validation
     * @param array $context Validation context
     * @param int $attempt Current attempt number
     * @return array|null Correction strategy or null if none found
     */
    private function analyzeFailureAndGetStrategy($error, $content, $context, $attempt) {
        $errorMessage = $error->getMessage();
        $errorType = $this->classifyError($errorMessage);
        
        // Check learned patterns first
        $learnedStrategy = $this->getLearnedStrategy($errorType, $content, $context);
        if ($learnedStrategy) {
            return $learnedStrategy;
        }
        
        // Apply built-in patterns
        foreach ($this->failurePatterns as $pattern => $strategy) {
            if (preg_match($pattern, $errorMessage)) {
                return $this->adaptStrategyToAttempt($strategy, $attempt);
            }
        }
        
        // Fallback strategies based on error type
        return $this->getFallbackStrategy($errorType, $attempt);
    }
    
    /**
     * Apply correction strategy to content
     *
     * @param array $content Content to modify
     * @param array $strategy Correction strategy
     * @return array Modified content
     */
    private function applyStrategy($content, $strategy) {
        $modifiedContent = $content;
        
        foreach ($strategy as $action => $params) {
            switch ($action) {
                case 'adjust_meta_length':
                    $modifiedContent = $this->adjustMetaDescriptionLength($modifiedContent, $params);
                    break;
                    
                case 'reduce_keyword_density':
                    $modifiedContent = $this->reduceKeywordDensity($modifiedContent, $params);
                    break;
                    
                case 'increase_keyword_density':
                    $modifiedContent = $this->increaseKeywordDensity($modifiedContent, $params);
                    break;
                    
                case 'improve_readability':
                    $modifiedContent = $this->improveReadability($modifiedContent, $params);
                    break;
                    
                case 'shorten_title':
                    $modifiedContent = $this->shortenTitle($modifiedContent, $params);
                    break;
                    
                case 'add_images':
                    $modifiedContent = $this->addImages($modifiedContent, $params);
                    break;
                    
                case 'relax_constraints':
                    // This would modify validation parameters rather than content
                    break;
            }
        }
        
        return $modifiedContent;
    }
    
    /**
     * Adjust meta description length
     *
     * @param array $content Content array
     * @param array $params Adjustment parameters
     * @return array Modified content
     */
    private function adjustMetaDescriptionLength($content, $params) {
        $metaDesc = $content['meta_description'] ?? '';
        $targetLength = $params['target_length'] ?? 140;
        $currentLength = strlen($metaDesc);
        
        if ($currentLength < $targetLength) {
            // Extend meta description
            $extension = $params['extension_text'] ?? ' Learn more about this topic.';
            $content['meta_description'] = $metaDesc . $extension;
        } elseif ($currentLength > $targetLength) {
            // Trim meta description
            $content['meta_description'] = substr($metaDesc, 0, $targetLength - 3) . '...';
        }
        
        return $content;
    }
    
    /**
     * Reduce keyword density in content
     *
     * @param array $content Content array
     * @param array $params Reduction parameters
     * @return array Modified content
     */
    private function reduceKeywordDensity($content, $params) {
        $contentText = $content['content'] ?? '';
        $keyword = $params['keyword'] ?? '';
        $targetReduction = $params['reduction_percentage'] ?? 0.5;
        
        if (empty($keyword)) {
            return $content;
        }
        
        // Simple keyword replacement with synonyms
        $synonyms = $params['synonyms'] ?? ['it', 'this', 'that'];
        $keywordCount = substr_count(strtolower($contentText), strtolower($keyword));
        $reductionCount = (int) ($keywordCount * $targetReduction);
        
        for ($i = 0; $i < $reductionCount; $i++) {
            $synonym = $synonyms[array_rand($synonyms)];
            $contentText = preg_replace(
                '/\b' . preg_quote($keyword, '/') . '\b/i',
                $synonym,
                $contentText,
                1
            );
        }
        
        $content['content'] = $contentText;
        return $content;
    }
    
    /**
     * Increase keyword density in content
     *
     * @param array $content Content array
     * @param array $params Increase parameters
     * @return array Modified content
     */
    private function increaseKeywordDensity($content, $params) {
        $contentText = $content['content'] ?? '';
        $keyword = $params['keyword'] ?? '';
        $targetIncrease = $params['increase_count'] ?? 2;
        
        if (empty($keyword)) {
            return $content;
        }
        
        // Add keyword in natural positions (after periods)
        $sentences = explode('.', $contentText);
        $addedCount = 0;
        
        for ($i = 0; $i < count($sentences) && $addedCount < $targetIncrease; $i++) {
            if (stripos($sentences[$i], $keyword) === false && strlen(trim($sentences[$i])) > 50) {
                $sentences[$i] = trim($sentences[$i]) . ' ' . $keyword;
                $addedCount++;
            }
        }
        
        $content['content'] = implode('.', $sentences);
        return $content;
    }
    
    /**
     * Improve content readability
     *
     * @param array $content Content array
     * @param array $params Improvement parameters
     * @return array Modified content
     */
    private function improveReadability($content, $params) {
        $contentText = $content['content'] ?? '';
        
        // Split long sentences
        if (isset($params['split_long_sentences']) && $params['split_long_sentences']) {
            $contentText = $this->splitLongSentences($contentText);
        }
        
        // Add transition words
        if (isset($params['add_transitions']) && $params['add_transitions']) {
            $contentText = $this->addTransitionWords($contentText);
        }
        
        // Convert passive voice (simplified)
        if (isset($params['reduce_passive_voice']) && $params['reduce_passive_voice']) {
            $contentText = $this->reducePassiveVoice($contentText);
        }
        
        $content['content'] = $contentText;
        return $content;
    }
    
    /**
     * Shorten title to meet length requirements
     *
     * @param array $content Content array
     * @param array $params Shortening parameters
     * @return array Modified content
     */
    private function shortenTitle($content, $params) {
        $title = $content['title'] ?? '';
        $maxLength = $params['max_length'] ?? 60;
        
        if (strlen($title) > $maxLength) {
            // Try to shorten at word boundaries
            $words = explode(' ', $title);
            $shortenedTitle = '';
            
            foreach ($words as $word) {
                if (strlen($shortenedTitle . ' ' . $word) <= $maxLength - 3) {
                    $shortenedTitle .= ($shortenedTitle ? ' ' : '') . $word;
                } else {
                    break;
                }
            }
            
            $content['title'] = $shortenedTitle . '...';
        }
        
        return $content;
    }
    
    /**
     * Add images to content
     *
     * @param array $content Content array
     * @param array $params Image parameters
     * @return array Modified content
     */
    private function addImages($content, $params) {
        if (empty($content['image_prompts'])) {
            $content['image_prompts'] = [];
        }
        
        $imageCount = $params['count'] ?? 1;
        $keyword = $params['keyword'] ?? '';
        
        for ($i = 0; $i < $imageCount; $i++) {
            $content['image_prompts'][] = [
                'prompt' => "Image related to " . ($content['title'] ?? 'the topic'),
                'alt' => "Image showing " . $keyword . " concept"
            ];
        }
        
        return $content;
    }
    
    /**
     * Learn from validation failure
     *
     * @param array $content Content that failed
     * @param array $context Validation context
     * @param Exception $error Error that occurred
     */
    public function learnFromFailure($content, $context, $error) {
        if (!$this->config['enablePatternLearning']) {
            return;
        }
        
        $errorType = $this->classifyError($error->getMessage());
        $contentSignature = $this->generateContentSignature($content);
        
        // Record failure pattern
        $failureKey = $errorType . '_' . $contentSignature;
        
        if (!isset($this->retryStats[$failureKey])) {
            $this->retryStats[$failureKey] = [
                'failures' => 0,
                'successes' => 0,
                'strategies_tried' => [],
                'successful_strategies' => []
            ];
        }
        
        $this->retryStats[$failureKey]['failures']++;
        
        // Save updated stats
        update_option('acs_retry_stats', $this->retryStats);
    }
    
    /**
     * Learn from validation success
     *
     * @param array $content Content that succeeded
     * @param array $context Validation context
     * @param array $retryHistory History of retry attempts
     */
    public function learnFromSuccess($content, $context, $retryHistory) {
        if (!$this->config['enablePatternLearning']) {
            return;
        }
        
        $contentSignature = $this->generateContentSignature($content);
        
        // Find the strategy that led to success
        $successfulStrategy = null;
        foreach (array_reverse($retryHistory) as $attempt) {
            if ($attempt['success'] && isset($attempt['strategy']) && $attempt['strategy'] !== 'none') {
                $successfulStrategy = $attempt['strategy'];
                break;
            }
        }
        
        if ($successfulStrategy) {
            $successKey = 'success_' . $contentSignature;
            
            if (!isset($this->retryStats[$successKey])) {
                $this->retryStats[$successKey] = [
                    'failures' => 0,
                    'successes' => 0,
                    'strategies_tried' => [],
                    'successful_strategies' => []
                ];
            }
            
            $this->retryStats[$successKey]['successes']++;
            $this->retryStats[$successKey]['successful_strategies'][] = $successfulStrategy;
            
            // Save updated stats
            update_option('acs_retry_stats', $this->retryStats);
        }
    }
    
    /**
     * Get cached successful strategy
     *
     * @param string $retryKey Retry key
     * @return array|null Cached strategy or null
     */
    private function getCachedStrategy($retryKey) {
        return $this->cache->get('retry_strategy_' . $retryKey);
    }
    
    /**
     * Cache successful strategy
     *
     * @param string $retryKey Retry key
     * @param array $strategy Successful strategy
     */
    private function cacheSuccessfulStrategy($retryKey, $strategy) {
        $this->cache->set('retry_strategy_' . $retryKey, $strategy, 3600); // 1 hour
    }
    
    /**
     * Generate retry key for caching
     *
     * @param array $content Content array
     * @param array $context Validation context
     * @return string Retry key
     */
    private function generateRetryKey($content, $context) {
        $keyData = [
            'content_hash' => md5(serialize($content)),
            'context_hash' => md5(serialize($context))
        ];
        
        return md5(serialize($keyData));
    }
    
    /**
     * Generate content signature for pattern learning
     *
     * @param array $content Content array
     * @return string Content signature
     */
    private function generateContentSignature($content) {
        $signature = [
            'title_length' => strlen($content['title'] ?? ''),
            'content_length' => strlen($content['content'] ?? ''),
            'meta_length' => strlen($content['meta_description'] ?? ''),
            'has_images' => !empty($content['image_prompts'])
        ];
        
        return md5(serialize($signature));
    }
    
    /**
     * Classify error type
     *
     * @param string $errorMessage Error message
     * @return string Error type
     */
    private function classifyError($errorMessage) {
        $errorMessage = strtolower($errorMessage);
        
        if (strpos($errorMessage, 'meta description') !== false) {
            return 'meta_description';
        } elseif (strpos($errorMessage, 'keyword density') !== false) {
            return 'keyword_density';
        } elseif (strpos($errorMessage, 'readability') !== false || strpos($errorMessage, 'passive voice') !== false) {
            return 'readability';
        } elseif (strpos($errorMessage, 'title') !== false) {
            return 'title';
        } elseif (strpos($errorMessage, 'image') !== false) {
            return 'images';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Get default failure patterns
     *
     * @return array Default patterns
     */
    private function getDefaultPatterns() {
        return [
            '/meta description.*too short/i' => [
                'adjust_meta_length' => ['target_length' => 140, 'extension_text' => ' Learn more.']
            ],
            '/meta description.*too long/i' => [
                'adjust_meta_length' => ['target_length' => 150]
            ],
            '/keyword density.*too high/i' => [
                'reduce_keyword_density' => ['reduction_percentage' => 0.3]
            ],
            '/keyword density.*too low/i' => [
                'increase_keyword_density' => ['increase_count' => 2]
            ],
            '/passive voice/i' => [
                'improve_readability' => ['reduce_passive_voice' => true]
            ],
            '/long sentences/i' => [
                'improve_readability' => ['split_long_sentences' => true]
            ],
            '/transition words/i' => [
                'improve_readability' => ['add_transitions' => true]
            ],
            '/title.*too long/i' => [
                'shorten_title' => ['max_length' => 60]
            ],
            '/image/i' => [
                'add_images' => ['count' => 1]
            ]
        ];
    }
    
    /**
     * Get learned strategy for error type
     *
     * @param string $errorType Error type
     * @param array $content Content array
     * @param array $context Validation context
     * @return array|null Learned strategy or null
     */
    private function getLearnedStrategy($errorType, $content, $context) {
        $contentSignature = $this->generateContentSignature($content);
        $successKey = 'success_' . $contentSignature;
        
        if (isset($this->retryStats[$successKey]) && !empty($this->retryStats[$successKey]['successful_strategies'])) {
            // Return most recent successful strategy
            return end($this->retryStats[$successKey]['successful_strategies']);
        }
        
        return null;
    }
    
    /**
     * Adapt strategy based on attempt number
     *
     * @param array $strategy Base strategy
     * @param int $attempt Attempt number
     * @return array Adapted strategy
     */
    private function adaptStrategyToAttempt($strategy, $attempt) {
        $adaptedStrategy = $strategy;
        
        // Make strategies more aggressive with each attempt
        foreach ($adaptedStrategy as $action => &$params) {
            switch ($action) {
                case 'adjust_meta_length':
                    if (isset($params['target_length'])) {
                        $params['target_length'] += ($attempt - 1) * 5;
                    }
                    break;
                    
                case 'reduce_keyword_density':
                    if (isset($params['reduction_percentage'])) {
                        $params['reduction_percentage'] = min(0.8, $params['reduction_percentage'] + ($attempt - 1) * 0.1);
                    }
                    break;
                    
                case 'increase_keyword_density':
                    if (isset($params['increase_count'])) {
                        $params['increase_count'] += $attempt - 1;
                    }
                    break;
            }
        }
        
        return $adaptedStrategy;
    }
    
    /**
     * Get fallback strategy for error type
     *
     * @param string $errorType Error type
     * @param int $attempt Attempt number
     * @return array|null Fallback strategy or null
     */
    private function getFallbackStrategy($errorType, $attempt) {
        $fallbackStrategies = [
            'meta_description' => [
                'adjust_meta_length' => ['target_length' => 140]
            ],
            'keyword_density' => [
                'reduce_keyword_density' => ['reduction_percentage' => 0.2]
            ],
            'readability' => [
                'improve_readability' => ['split_long_sentences' => true, 'add_transitions' => true]
            ],
            'title' => [
                'shorten_title' => ['max_length' => 60]
            ],
            'images' => [
                'add_images' => ['count' => 1]
            ]
        ];
        
        if (isset($fallbackStrategies[$errorType])) {
            return $this->adaptStrategyToAttempt($fallbackStrategies[$errorType], $attempt);
        }
        
        return null;
    }
    
    /**
     * Update retry statistics
     *
     * @param string $retryKey Retry key
     * @param bool $success Whether the retry was successful
     * @param int $attempts Number of attempts made
     * @param float $totalTime Total time taken
     */
    private function updateRetryStats($retryKey, $success, $attempts, $totalTime) {
        $statsKey = 'global_retry_stats';
        $globalStats = get_option($statsKey, [
            'total_retries' => 0,
            'successful_retries' => 0,
            'average_attempts' => 0,
            'average_time' => 0
        ]);
        
        $globalStats['total_retries']++;
        if ($success) {
            $globalStats['successful_retries']++;
        }
        
        // Update averages
        $globalStats['average_attempts'] = (
            ($globalStats['average_attempts'] * ($globalStats['total_retries'] - 1)) + $attempts
        ) / $globalStats['total_retries'];
        
        $globalStats['average_time'] = (
            ($globalStats['average_time'] * ($globalStats['total_retries'] - 1)) + $totalTime
        ) / $globalStats['total_retries'];
        
        update_option($statsKey, $globalStats);
    }
    
    /**
     * Get retry statistics
     *
     * @return array Retry statistics
     */
    public function getRetryStats() {
        $globalStats = get_option('global_retry_stats', []);
        $successRate = 0;
        
        if (isset($globalStats['total_retries']) && $globalStats['total_retries'] > 0) {
            $successRate = ($globalStats['successful_retries'] / $globalStats['total_retries']) * 100;
        }
        
        return array_merge($globalStats, [
            'success_rate' => round($successRate, 2),
            'pattern_count' => count($this->failurePatterns),
            'learned_patterns' => count($this->retryStats)
        ]);
    }
    
    /**
     * Simple helper methods for content modification
     */
    
    private function splitLongSentences($text) {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $result = [];
        
        foreach ($sentences as $sentence) {
            if (str_word_count($sentence) > 20) {
                // Split at conjunctions
                $parts = preg_split('/\s+(and|but|or|because|since|while|although)\s+/i', $sentence, 2);
                if (count($parts) > 1) {
                    $result[] = trim($parts[0]) . '.';
                    $result[] = trim($parts[1]);
                } else {
                    $result[] = $sentence;
                }
            } else {
                $result[] = $sentence;
            }
        }
        
        return implode(' ', $result);
    }
    
    private function addTransitionWords($text) {
        $transitions = ['Furthermore', 'Additionally', 'Moreover', 'However', 'Therefore', 'Consequently'];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        
        for ($i = 1; $i < count($sentences); $i += 2) {
            if (rand(0, 1)) {
                $transition = $transitions[array_rand($transitions)];
                $sentences[$i] = $transition . ', ' . lcfirst($sentences[$i]);
            }
        }
        
        return implode(' ', $sentences);
    }
    
    private function reducePassiveVoice($text) {
        // Simple passive voice reduction (basic patterns)
        $passivePatterns = [
            '/(\w+) was (\w+ed) by/' => '$1 $2',
            '/(\w+) were (\w+ed) by/' => '$1 $2',
            '/is (\w+ed) by/' => '$1',
            '/are (\w+ed) by/' => '$1'
        ];
        
        foreach ($passivePatterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        return $text;
    }
}