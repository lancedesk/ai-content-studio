<?php
/**
 * SEO Validation Cache Class
 *
 * High-performance caching system for SEO validation results to minimize
 * redundant calculations and AI model calls.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class SEOValidationCache
 *
 * Manages caching for validation results and content analysis
 */
class SEOValidationCache {
    
    /**
     * @var string Cache prefix
     */
    private $cachePrefix = 'acs_seo_cache_';
    
    /**
     * @var int Default cache expiration (1 hour)
     */
    private $defaultExpiration = 3600;
    
    /**
     * @var array In-memory cache for current request
     */
    private $memoryCache = [];
    
    /**
     * @var array Cache statistics
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize cache cleanup hook
        add_action('acs_seo_cache_cleanup', [$this, 'cleanupExpiredCache']);
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('acs_seo_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'acs_seo_cache_cleanup');
        }
    }
    
    /**
     * Get cached validation result
     *
     * @param string $contentHash Hash of content being validated
     * @param string $configHash Hash of validation configuration
     * @return array|false Cached result or false if not found
     */
    public function getValidationResult($contentHash, $configHash) {
        $cacheKey = $this->generateCacheKey('validation', $contentHash, $configHash);
        
        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            $this->stats['hits']++;
            return $this->memoryCache[$cacheKey];
        }
        
        // Check persistent cache
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            $this->memoryCache[$cacheKey] = $cached;
            $this->stats['hits']++;
            return $cached;
        }
        
        $this->stats['misses']++;
        return false;
    }
    
    /**
     * Cache validation result
     *
     * @param string $contentHash Hash of content being validated
     * @param string $configHash Hash of validation configuration
     * @param array $result Validation result to cache
     * @param int $expiration Cache expiration time (default: 1 hour)
     * @return bool Success status
     */
    public function setValidationResult($contentHash, $configHash, $result, $expiration = null) {
        $cacheKey = $this->generateCacheKey('validation', $contentHash, $configHash);
        $expiration = $expiration ?? $this->defaultExpiration;
        
        // Store in memory cache
        $this->memoryCache[$cacheKey] = $result;
        
        // Store in persistent cache
        $success = set_transient($cacheKey, $result, $expiration);
        
        if ($success) {
            $this->stats['sets']++;
        }
        
        return $success;
    }
    
    /**
     * Get cached content metrics
     *
     * @param string $contentHash Hash of content
     * @return array|false Cached metrics or false if not found
     */
    public function getContentMetrics($contentHash) {
        $cacheKey = $this->generateCacheKey('metrics', $contentHash);
        
        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            $this->stats['hits']++;
            return $this->memoryCache[$cacheKey];
        }
        
        // Check persistent cache
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            $this->memoryCache[$cacheKey] = $cached;
            $this->stats['hits']++;
            return $cached;
        }
        
        $this->stats['misses']++;
        return false;
    }
    
    /**
     * Cache content metrics
     *
     * @param string $contentHash Hash of content
     * @param array $metrics Content metrics to cache
     * @param int $expiration Cache expiration time (default: 1 hour)
     * @return bool Success status
     */
    public function setContentMetrics($contentHash, $metrics, $expiration = null) {
        $cacheKey = $this->generateCacheKey('metrics', $contentHash);
        $expiration = $expiration ?? $this->defaultExpiration;
        
        // Store in memory cache
        $this->memoryCache[$cacheKey] = $metrics;
        
        // Store in persistent cache
        $success = set_transient($cacheKey, $metrics, $expiration);
        
        if ($success) {
            $this->stats['sets']++;
        }
        
        return $success;
    }
    
    /**
     * Get cached keyword analysis
     *
     * @param string $contentHash Hash of content
     * @param string $keywordHash Hash of keywords
     * @return array|false Cached analysis or false if not found
     */
    public function getKeywordAnalysis($contentHash, $keywordHash) {
        $cacheKey = $this->generateCacheKey('keywords', $contentHash, $keywordHash);
        
        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            $this->stats['hits']++;
            return $this->memoryCache[$cacheKey];
        }
        
        // Check persistent cache
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            $this->memoryCache[$cacheKey] = $cached;
            $this->stats['hits']++;
            return $cached;
        }
        
        $this->stats['misses']++;
        return false;
    }
    
    /**
     * Cache keyword analysis
     *
     * @param string $contentHash Hash of content
     * @param string $keywordHash Hash of keywords
     * @param array $analysis Keyword analysis to cache
     * @param int $expiration Cache expiration time (default: 2 hours)
     * @return bool Success status
     */
    public function setKeywordAnalysis($contentHash, $keywordHash, $analysis, $expiration = null) {
        $cacheKey = $this->generateCacheKey('keywords', $contentHash, $keywordHash);
        $expiration = $expiration ?? ($this->defaultExpiration * 2);
        
        // Store in memory cache
        $this->memoryCache[$cacheKey] = $analysis;
        
        // Store in persistent cache
        $success = set_transient($cacheKey, $analysis, $expiration);
        
        if ($success) {
            $this->stats['sets']++;
        }
        
        return $success;
    }
    
    /**
     * Get cached readability analysis
     *
     * @param string $contentHash Hash of content
     * @return array|false Cached analysis or false if not found
     */
    public function getReadabilityAnalysis($contentHash) {
        $cacheKey = $this->generateCacheKey('readability', $contentHash);
        
        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            $this->stats['hits']++;
            return $this->memoryCache[$cacheKey];
        }
        
        // Check persistent cache
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            $this->memoryCache[$cacheKey] = $cached;
            $this->stats['hits']++;
            return $cached;
        }
        
        $this->stats['misses']++;
        return false;
    }
    
    /**
     * Cache readability analysis
     *
     * @param string $contentHash Hash of content
     * @param array $analysis Readability analysis to cache
     * @param int $expiration Cache expiration time (default: 2 hours)
     * @return bool Success status
     */
    public function setReadabilityAnalysis($contentHash, $analysis, $expiration = null) {
        $cacheKey = $this->generateCacheKey('readability', $contentHash);
        $expiration = $expiration ?? ($this->defaultExpiration * 2);
        
        // Store in memory cache
        $this->memoryCache[$cacheKey] = $analysis;
        
        // Store in persistent cache
        $success = set_transient($cacheKey, $analysis, $expiration);
        
        if ($success) {
            $this->stats['sets']++;
        }
        
        return $success;
    }
    
    /**
     * Get cached title uniqueness check
     *
     * @param string $titleHash Hash of title
     * @return array|false Cached result or false if not found
     */
    public function getTitleUniqueness($titleHash) {
        $cacheKey = $this->generateCacheKey('title_unique', $titleHash);
        
        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            $this->stats['hits']++;
            return $this->memoryCache[$cacheKey];
        }
        
        // Check persistent cache (shorter expiration for uniqueness checks)
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            $this->memoryCache[$cacheKey] = $cached;
            $this->stats['hits']++;
            return $cached;
        }
        
        $this->stats['misses']++;
        return false;
    }
    
    /**
     * Cache title uniqueness check
     *
     * @param string $titleHash Hash of title
     * @param array $result Uniqueness check result
     * @param int $expiration Cache expiration time (default: 30 minutes)
     * @return bool Success status
     */
    public function setTitleUniqueness($titleHash, $result, $expiration = null) {
        $cacheKey = $this->generateCacheKey('title_unique', $titleHash);
        $expiration = $expiration ?? ($this->defaultExpiration / 2);
        
        // Store in memory cache
        $this->memoryCache[$cacheKey] = $result;
        
        // Store in persistent cache
        $success = set_transient($cacheKey, $result, $expiration);
        
        if ($success) {
            $this->stats['sets']++;
        }
        
        return $success;
    }
    
    /**
     * Invalidate cache for specific content
     *
     * @param string $contentHash Hash of content to invalidate
     * @return bool Success status
     */
    public function invalidateContent($contentHash) {
        $patterns = ['validation', 'metrics', 'keywords', 'readability'];
        $success = true;
        
        foreach ($patterns as $pattern) {
            $cacheKey = $this->generateCacheKey($pattern, $contentHash);
            
            // Remove from memory cache
            unset($this->memoryCache[$cacheKey]);
            
            // Remove from persistent cache
            if (!delete_transient($cacheKey)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Clear all validation cache
     *
     * @return bool Success status
     */
    public function clearAll() {
        global $wpdb;
        
        // Clear memory cache
        $this->memoryCache = [];
        
        // Clear persistent cache
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cachePrefix . '%'
            )
        );
        
        // Also clear timeout entries
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . $this->cachePrefix . '%'
            )
        );
        
        // Reset stats
        $this->stats = ['hits' => 0, 'misses' => 0, 'sets' => 0];
        
        return $result !== false;
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getStats() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;
        
        return array_merge($this->stats, [
            'hit_rate' => round($hitRate, 2),
            'memory_cache_size' => count($this->memoryCache)
        ]);
    }
    
    /**
     * Generate content hash for caching
     *
     * @param array $content Content array
     * @return string Content hash
     */
    public function generateContentHash($content) {
        // Create hash from relevant content fields
        $hashData = [
            'title' => $content['title'] ?? '',
            'content' => $content['content'] ?? '',
            'meta_description' => $content['meta_description'] ?? ''
        ];
        
        return md5(serialize($hashData));
    }
    
    /**
     * Generate configuration hash for caching
     *
     * @param array $config Configuration array
     * @return string Configuration hash
     */
    public function generateConfigHash($config) {
        // Only include validation-relevant config
        $relevantConfig = array_intersect_key($config, [
            'minMetaDescLength' => true,
            'maxMetaDescLength' => true,
            'minKeywordDensity' => true,
            'maxKeywordDensity' => true,
            'maxPassiveVoice' => true,
            'maxLongSentences' => true,
            'minTransitionWords' => true,
            'maxTitleLength' => true,
            'maxSubheadingKeywordUsage' => true
        ]);
        
        return md5(serialize($relevantConfig));
    }
    
    /**
     * Generate keyword hash for caching
     *
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return string Keyword hash
     */
    public function generateKeywordHash($focusKeyword, $secondaryKeywords = []) {
        $keywordData = [
            'focus' => strtolower(trim($focusKeyword)),
            'secondary' => array_map('strtolower', array_map('trim', $secondaryKeywords))
        ];
        
        return md5(serialize($keywordData));
    }
    
    /**
     * Cleanup expired cache entries
     */
    public function cleanupExpiredCache() {
        global $wpdb;
        
        // WordPress automatically handles transient cleanup, but we can force it
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_{$this->cachePrefix}%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        // Clean up corresponding transient entries
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_{$this->cachePrefix}%' 
             AND option_name NOT IN (
                 SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_timeout_{$this->cachePrefix}%'
             )"
        );
    }
    
    /**
     * Generate cache key
     *
     * @param string $type Cache type
     * @param string ...$parts Key parts
     * @return string Cache key
     */
    private function generateCacheKey($type, ...$parts) {
        return $this->cachePrefix . $type . '_' . implode('_', $parts);
    }
    
    /**
     * Get cache size information
     *
     * @return array Cache size information
     */
    public function getCacheSize() {
        global $wpdb;
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as entry_count,
                    SUM(LENGTH(option_value)) as total_size
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE %s",
                '_transient_' . $this->cachePrefix . '%'
            )
        );
        
        return [
            'entry_count' => (int) $result->entry_count,
            'total_size_bytes' => (int) $result->total_size,
            'total_size_mb' => round($result->total_size / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Warm up cache with common validations
     *
     * @param array $commonContent Array of common content to pre-cache
     * @param array $config Validation configuration
     */
    public function warmUpCache($commonContent, $config) {
        foreach ($commonContent as $content) {
            $contentHash = $this->generateContentHash($content);
            $configHash = $this->generateConfigHash($config);
            
            // Check if already cached
            if ($this->getValidationResult($contentHash, $configHash) === false) {
                // Pre-calculate and cache common metrics
                $metrics = new ContentValidationMetrics();
                
                // Cache basic metrics
                $basicMetrics = [
                    'word_count' => str_word_count(strip_tags($content['content'] ?? '')),
                    'meta_length' => strlen($content['meta_description'] ?? ''),
                    'title_length' => strlen($content['title'] ?? '')
                ];
                
                $this->setContentMetrics($contentHash, $basicMetrics);
            }
        }
    }
}