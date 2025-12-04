<?php
/**
 * Content Structure Preserver
 *
 * Analyzes and preserves content structure during SEO optimization.
 * Implements formatting maintenance, content integrity validation,
 * checksums, and rollback capability for content corruption scenarios.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class ContentStructurePreserver
 *
 * Ensures content structure and formatting are maintained during optimization
 */
class ContentStructurePreserver {
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * @var array Content snapshots for rollback
     */
    private $contentSnapshots;
    
    /**
     * @var array Structure analysis cache
     */
    private $structureCache;
    
    /**
     * @var array Integrity checksums
     */
    private $checksums;
    
    /**
     * @var array Error log
     */
    private $errorLog;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'enableRollback' => true,
            'maxSnapshots' => 10,
            'enableChecksums' => true,
            'preserveFormatting' => true,
            'preserveStructure' => true,
            'preserveIntent' => true,
            'strictValidation' => true,
            'logLevel' => 'info'
        ], $config);
        
        $this->contentSnapshots = [];
        $this->structureCache = [];
        $this->checksums = [];
        $this->errorLog = [];
    }
    
    /**
     * Analyze content structure before optimization
     *
     * @param array $content Content to analyze
     * @return array Structure analysis
     */
    public function analyzeStructure($content) {
        $this->logMessage("Analyzing content structure", 'info');
        
        $structure = [
            'title' => [
                'length' => strlen($content['title'] ?? ''),
                'hasKeyword' => false,
                'format' => 'plain_text'
            ],
            'meta_description' => [
                'length' => strlen($content['meta_description'] ?? ''),
                'hasKeyword' => false,
                'format' => 'plain_text'
            ],
            'content' => [
                'length' => strlen($content['content'] ?? ''),
                'htmlStructure' => $this->analyzeHTMLStructure($content['content'] ?? ''),
                'paragraphCount' => $this->countElements($content['content'] ?? '', 'p'),
                'headingCount' => $this->countHeadings($content['content'] ?? ''),
                'imageCount' => $this->countElements($content['content'] ?? '', 'img'),
                'listCount' => $this->countElements($content['content'] ?? '', 'ul') + 
                              $this->countElements($content['content'] ?? '', 'ol'),
                'linkCount' => $this->countElements($content['content'] ?? '', 'a'),
                'format' => 'html'
            ],
            'timestamp' => current_time('mysql'),
            'checksum' => $this->generateChecksum($content)
        ];
        
        // Cache structure analysis
        $contentHash = md5(serialize($content));
        $this->structureCache[$contentHash] = $structure;
        
        return $structure;
    }
    
    /**
     * Analyze HTML structure in detail
     *
     * @param string $html HTML content
     * @return array HTML structure details
     */
    private function analyzeHTMLStructure($html) {
        $structure = [
            'tags' => [],
            'hierarchy' => [],
            'attributes' => []
        ];
        
        // Extract all HTML tags
        if (preg_match_all('/<([a-z][a-z0-9]*)\b[^>]*>/i', $html, $matches)) {
            $structure['tags'] = array_count_values($matches[1]);
        }
        
        // Extract heading hierarchy
        if (preg_match_all('/<(h[1-6])[^>]*>(.*?)<\/\1>/is', $html, $matches)) {
            foreach ($matches[1] as $index => $tag) {
                $structure['hierarchy'][] = [
                    'tag' => $tag,
                    'content' => strip_tags($matches[2][$index])
                ];
            }
        }
        
        // Extract image attributes
        if (preg_match_all('/<img\s+([^>]+)>/i', $html, $matches)) {
            foreach ($matches[1] as $attrs) {
                $imgAttrs = [];
                if (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $srcMatch)) {
                    $imgAttrs['src'] = $srcMatch[1];
                }
                if (preg_match('/alt=["\']([^"\']+)["\']/i', $attrs, $altMatch)) {
                    $imgAttrs['alt'] = $altMatch[1];
                }
                $structure['attributes'][] = $imgAttrs;
            }
        }
        
        return $structure;
    }
    
    /**
     * Count specific HTML elements
     *
     * @param string $html HTML content
     * @param string $tag Tag name to count
     * @return int Element count
     */
    private function countElements($html, $tag) {
        return preg_match_all('/<' . preg_quote($tag, '/') . '\b[^>]*>/i', $html);
    }
    
    /**
     * Count all heading elements
     *
     * @param string $html HTML content
     * @return array Heading counts by level
     */
    private function countHeadings($html) {
        $counts = [
            'h1' => 0, 'h2' => 0, 'h3' => 0, 
            'h4' => 0, 'h5' => 0, 'h6' => 0
        ];
        
        foreach (array_keys($counts) as $tag) {
            $counts[$tag] = $this->countElements($html, $tag);
        }
        
        return $counts;
    }
    
    /**
     * Generate checksum for content integrity
     *
     * @param array $content Content to checksum
     * @return string Checksum hash
     */
    public function generateChecksum($content) {
        // Create normalized representation for checksumming
        $normalized = [
            'title' => trim($content['title'] ?? ''),
            'meta_description' => trim($content['meta_description'] ?? ''),
            'content' => $this->normalizeHTML($content['content'] ?? '')
        ];
        
        return hash('sha256', serialize($normalized));
    }
    
    /**
     * Normalize HTML for comparison
     *
     * @param string $html HTML content
     * @return string Normalized HTML
     */
    private function normalizeHTML($html) {
        // Remove extra whitespace while preserving structure
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        return trim($html);
    }
    
    /**
     * Create snapshot of content for rollback
     *
     * @param array $content Content to snapshot
     * @param string $label Snapshot label
     * @return string Snapshot ID
     */
    public function createSnapshot($content, $label = '') {
        $snapshotId = uniqid('snapshot_', true);
        
        $snapshot = [
            'id' => $snapshotId,
            'label' => $label,
            'content' => $content,
            'structure' => $this->analyzeStructure($content),
            'checksum' => $this->generateChecksum($content),
            'timestamp' => current_time('mysql')
        ];
        
        $this->contentSnapshots[$snapshotId] = $snapshot;
        
        // Limit snapshot count
        if (count($this->contentSnapshots) > $this->config['maxSnapshots']) {
            // Remove oldest snapshot
            $oldestKey = array_key_first($this->contentSnapshots);
            unset($this->contentSnapshots[$oldestKey]);
        }
        
        $this->logMessage("Created snapshot: {$snapshotId} ({$label})", 'info');
        
        return $snapshotId;
    }
    
    /**
     * Validate content integrity against original structure
     *
     * @param array $originalContent Original content
     * @param array $modifiedContent Modified content
     * @return array Validation result
     */
    public function validateIntegrity($originalContent, $modifiedContent) {
        $this->logMessage("Validating content integrity", 'info');
        
        $originalStructure = $this->analyzeStructure($originalContent);
        $modifiedStructure = $this->analyzeStructure($modifiedContent);
        
        $violations = [];
        $warnings = [];
        
        // Check if HTML structure is preserved
        if ($this->config['preserveStructure']) {
            $structureViolations = $this->checkStructurePreservation(
                $originalStructure['content']['htmlStructure'],
                $modifiedStructure['content']['htmlStructure']
            );
            
            $violations = array_merge($violations, $structureViolations);
        }
        
        // Check if formatting is maintained
        if ($this->config['preserveFormatting']) {
            $formattingViolations = $this->checkFormattingPreservation(
                $originalStructure,
                $modifiedStructure
            );
            
            $violations = array_merge($violations, $formattingViolations);
        }
        
        // Check content intent preservation
        if ($this->config['preserveIntent']) {
            $intentWarnings = $this->checkIntentPreservation(
                $originalContent,
                $modifiedContent
            );
            
            $warnings = array_merge($warnings, $intentWarnings);
        }
        
        // Verify checksums if enabled
        if ($this->config['enableChecksums']) {
            $originalChecksum = $this->generateChecksum($originalContent);
            $modifiedChecksum = $this->generateChecksum($modifiedContent);
            
            // Store checksums
            $this->checksums[] = [
                'original' => $originalChecksum,
                'modified' => $modifiedChecksum,
                'timestamp' => current_time('mysql')
            ];
        }
        
        $isValid = empty($violations);
        
        return [
            'isValid' => $isValid,
            'violations' => $violations,
            'warnings' => $warnings,
            'originalStructure' => $originalStructure,
            'modifiedStructure' => $modifiedStructure,
            'structurePreserved' => empty($violations),
            'formattingPreserved' => empty(array_filter($violations, function($v) {
                return strpos($v['type'], 'formatting_') === 0;
            })),
            'intentPreserved' => empty($warnings)
        ];
    }
    
    /**
     * Check if HTML structure is preserved
     *
     * @param array $originalStructure Original HTML structure
     * @param array $modifiedStructure Modified HTML structure
     * @return array Structure violations
     */
    private function checkStructurePreservation($originalStructure, $modifiedStructure) {
        $violations = [];
        
        // Check if tag counts changed significantly
        foreach ($originalStructure['tags'] as $tag => $count) {
            $modifiedCount = $modifiedStructure['tags'][$tag] ?? 0;
            
            // Allow minor variations (±1) but flag major changes
            if (abs($modifiedCount - $count) > 1) {
                $violations[] = [
                    'type' => 'structure_tag_count_changed',
                    'tag' => $tag,
                    'original' => $count,
                    'modified' => $modifiedCount,
                    'severity' => 'major'
                ];
            }
        }
        
        // Check if heading hierarchy is preserved
        if (count($originalStructure['hierarchy']) !== count($modifiedStructure['hierarchy'])) {
            $violations[] = [
                'type' => 'structure_heading_count_changed',
                'original' => count($originalStructure['hierarchy']),
                'modified' => count($modifiedStructure['hierarchy']),
                'severity' => 'major'
            ];
        }
        
        // Check if image count is preserved
        if (count($originalStructure['attributes']) !== count($modifiedStructure['attributes'])) {
            $violations[] = [
                'type' => 'structure_image_count_changed',
                'original' => count($originalStructure['attributes']),
                'modified' => count($modifiedStructure['attributes']),
                'severity' => 'major'
            ];
        }
        
        return $violations;
    }
    
    /**
     * Check if formatting is preserved
     *
     * @param array $originalStructure Original structure
     * @param array $modifiedStructure Modified structure
     * @return array Formatting violations
     */
    private function checkFormattingPreservation($originalStructure, $modifiedStructure) {
        $violations = [];
        
        // Check paragraph count (allow ±20% variation)
        $originalParagraphs = $originalStructure['content']['paragraphCount'];
        $modifiedParagraphs = $modifiedStructure['content']['paragraphCount'];
        
        if ($originalParagraphs > 0) {
            $variation = abs($modifiedParagraphs - $originalParagraphs) / $originalParagraphs;
            
            if ($variation > 0.2) {
                $violations[] = [
                    'type' => 'formatting_paragraph_count_changed',
                    'original' => $originalParagraphs,
                    'modified' => $modifiedParagraphs,
                    'variation' => round($variation * 100, 2) . '%',
                    'severity' => 'minor'
                ];
            }
        }
        
        // Check list count
        $originalLists = $originalStructure['content']['listCount'];
        $modifiedLists = $modifiedStructure['content']['listCount'];
        
        if ($originalLists !== $modifiedLists) {
            $violations[] = [
                'type' => 'formatting_list_count_changed',
                'original' => $originalLists,
                'modified' => $modifiedLists,
                'severity' => 'minor'
            ];
        }
        
        return $violations;
    }
    
    /**
     * Check if content intent is preserved
     *
     * @param array $originalContent Original content
     * @param array $modifiedContent Modified content
     * @return array Intent warnings
     */
    private function checkIntentPreservation($originalContent, $modifiedContent) {
        $warnings = [];
        
        // Check if content length changed dramatically (>30%)
        $originalLength = strlen($originalContent['content'] ?? '');
        $modifiedLength = strlen($modifiedContent['content'] ?? '');
        
        if ($originalLength > 0) {
            $lengthChange = abs($modifiedLength - $originalLength) / $originalLength;
            
            if ($lengthChange > 0.3) {
                $warnings[] = [
                    'type' => 'intent_content_length_changed',
                    'original' => $originalLength,
                    'modified' => $modifiedLength,
                    'change' => round($lengthChange * 100, 2) . '%',
                    'severity' => 'warning'
                ];
            }
        }
        
        // Check if title changed significantly
        $originalTitle = $originalContent['title'] ?? '';
        $modifiedTitle = $modifiedContent['title'] ?? '';
        
        if ($originalTitle !== $modifiedTitle) {
            // Calculate similarity
            similar_text($originalTitle, $modifiedTitle, $similarity);
            
            if ($similarity < 70) {
                $warnings[] = [
                    'type' => 'intent_title_changed_significantly',
                    'original' => $originalTitle,
                    'modified' => $modifiedTitle,
                    'similarity' => round($similarity, 2) . '%',
                    'severity' => 'warning'
                ];
            }
        }
        
        return $warnings;
    }
    
    /**
     * Rollback to previous snapshot
     *
     * @param string $snapshotId Snapshot ID to rollback to
     * @return array|null Restored content or null if not found
     */
    public function rollback($snapshotId) {
        if (!$this->config['enableRollback']) {
            $this->logMessage("Rollback disabled in configuration", 'warning');
            return null;
        }
        
        if (!isset($this->contentSnapshots[$snapshotId])) {
            $this->logMessage("Snapshot not found: {$snapshotId}", 'error');
            return null;
        }
        
        $snapshot = $this->contentSnapshots[$snapshotId];
        
        $this->logMessage("Rolling back to snapshot: {$snapshotId} ({$snapshot['label']})", 'info');
        
        return [
            'content' => $snapshot['content'],
            'structure' => $snapshot['structure'],
            'checksum' => $snapshot['checksum'],
            'timestamp' => $snapshot['timestamp'],
            'label' => $snapshot['label']
        ];
    }
    
    /**
     * Get all snapshots
     *
     * @return array All content snapshots
     */
    public function getSnapshots() {
        return $this->contentSnapshots;
    }
    
    /**
     * Get latest snapshot
     *
     * @return array|null Latest snapshot or null if none exist
     */
    public function getLatestSnapshot() {
        if (empty($this->contentSnapshots)) {
            return null;
        }
        
        return end($this->contentSnapshots);
    }
    
    /**
     * Clear all snapshots
     */
    public function clearSnapshots() {
        $this->contentSnapshots = [];
        $this->structureCache = [];
        $this->checksums = [];
        $this->logMessage("All snapshots cleared", 'info');
    }
    
    /**
     * Detect content corruption
     *
     * @param array $content Content to check
     * @param string $expectedChecksum Expected checksum
     * @return array Corruption detection result
     */
    public function detectCorruption($content, $expectedChecksum) {
        $actualChecksum = $this->generateChecksum($content);
        
        $isCorrupted = $actualChecksum !== $expectedChecksum;
        
        if ($isCorrupted) {
            $this->logMessage("Content corruption detected", 'error');
        }
        
        return [
            'isCorrupted' => $isCorrupted,
            'expectedChecksum' => $expectedChecksum,
            'actualChecksum' => $actualChecksum,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Preserve content during optimization
     *
     * @param array $originalContent Original content
     * @param array $optimizedContent Optimized content
     * @return array Preservation result
     */
    public function preserveContent($originalContent, $optimizedContent) {
        // Create snapshot of original
        $snapshotId = $this->createSnapshot($originalContent, 'pre_optimization');
        
        // Validate integrity
        $validation = $this->validateIntegrity($originalContent, $optimizedContent);
        
        // If validation fails and rollback is enabled, restore original
        if (!$validation['isValid'] && $this->config['enableRollback']) {
            $this->logMessage("Integrity validation failed, considering rollback", 'warning');
            
            // Check if violations are critical
            $criticalViolations = array_filter($validation['violations'], function($v) {
                return $v['severity'] === 'major';
            });
            
            if (!empty($criticalViolations)) {
                $this->logMessage("Critical violations detected, rolling back", 'error');
                $rollbackResult = $this->rollback($snapshotId);
                
                return [
                    'success' => false,
                    'rolledBack' => true,
                    'content' => $rollbackResult['content'],
                    'validation' => $validation,
                    'snapshotId' => $snapshotId
                ];
            }
        }
        
        return [
            'success' => $validation['isValid'],
            'rolledBack' => false,
            'content' => $optimizedContent,
            'validation' => $validation,
            'snapshotId' => $snapshotId
        ];
    }
    
    /**
     * Get checksums history
     *
     * @return array Checksums history
     */
    public function getChecksums() {
        return $this->checksums;
    }
    
    /**
     * Get error log
     *
     * @return array Error log
     */
    public function getErrorLog() {
        return $this->errorLog;
    }
    
    /**
     * Update configuration
     *
     * @param array $newConfig New configuration settings
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        $this->logMessage("Configuration updated", 'info');
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
     * Log message with appropriate level
     *
     * @param string $message Message to log
     * @param string $level Log level (debug, info, warning, error)
     */
    private function logMessage($message, $level = 'info') {
        $logLevels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        $currentLevel = $logLevels[$this->config['logLevel']] ?? 1;
        $messageLevel = $logLevels[$level] ?? 1;
        
        if ($messageLevel >= $currentLevel) {
            $logEntry = [
                'level' => $level,
                'message' => $message,
                'timestamp' => current_time('mysql')
            ];
            
            $this->errorLog[] = $logEntry;
            
            // Also log to WordPress if error level
            if ($level === 'error') {
                error_log("ContentStructurePreserver [{$level}]: {$message}");
            }
        }
    }
    
    /**
     * Get preservation statistics
     *
     * @return array Statistics summary
     */
    public function getPreservationStats() {
        return [
            'totalSnapshots' => count($this->contentSnapshots),
            'totalChecksums' => count($this->checksums),
            'cacheSize' => count($this->structureCache),
            'config' => $this->config
        ];
    }
}
