<?php
/**
 * SEO Issue Detection System
 *
 * Enhanced comprehensive SEO issue detection with classification, quantification,
 * location tracking, and weighted scoring for multi-pass optimization support.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load required classes
require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-result.php';
require_once ACS_PLUGIN_PATH . 'seo/class-content-validation-metrics.php';
require_once ACS_PLUGIN_PATH . 'seo/class-keyword-density-calculator.php';
require_once ACS_PLUGIN_PATH . 'seo/class-passive-voice-analyzer.php';
require_once ACS_PLUGIN_PATH . 'seo/class-meta-description-validator.php';
require_once ACS_PLUGIN_PATH . 'seo/class-sentence-length-analyzer.php';
require_once ACS_PLUGIN_PATH . 'seo/class-transition-word-analyzer.php';

/**
 * SEO Issue Data Model
 */
class SEOIssue {
    public $type;              // 'keyword_density', 'meta_description', 'passive_voice', etc.
    public $severity;          // 'critical', 'major', 'minor'
    public $currentValue;      // Current metric value
    public $targetValue;       // Required target value
    public $locations;         // Specific locations of issues
    public $description;       // Human-readable issue description
    public $priority;          // Correction priority (1-10)
    public $weight;            // Weight for scoring calculation
    
    public function __construct($type, $severity, $currentValue, $targetValue, $locations = [], $description = '', $priority = 5, $weight = 1.0) {
        $this->type = $type;
        $this->severity = $severity;
        $this->currentValue = $currentValue;
        $this->targetValue = $targetValue;
        $this->locations = $locations;
        $this->description = $description;
        $this->priority = $priority;
        $this->weight = $weight;
    }
    
    public function toArray() {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'currentValue' => $this->currentValue,
            'targetValue' => $this->targetValue,
            'locations' => $this->locations,
            'description' => $this->description,
            'priority' => $this->priority,
            'weight' => $this->weight
        ];
    }
}

/**
 * Class SEOIssueDetector
 *
 * Comprehensive SEO issue detection system with multi-pass optimization support
 */
class SEOIssueDetector {
    
    /**
     * @var array Configuration settings
     */
    private $config;
    
    /**
     * @var KeywordDensityCalculator
     */
    private $densityCalculator;
    
    /**
     * @var PassiveVoiceAnalyzer
     */
    private $passiveAnalyzer;
    
    /**
     * @var MetaDescriptionValidator
     */
    private $metaValidator;
    
    /**
     * @var SentenceLengthAnalyzer
     */
    private $sentenceAnalyzer;
    
    /**
     * @var TransitionWordAnalyzer
     */
    private $transitionAnalyzer;
    
    /**
     * @var array Issue severity weights for scoring
     */
    private $severityWeights;
    
    /**
     * Constructor
     *
     * @param array $config Configuration settings
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'minKeywordDensity' => 0.5,
            'maxKeywordDensity' => 2.5,
            'minMetaDescLength' => 120,
            'maxMetaDescLength' => 156,
            'maxPassiveVoice' => 10.0,
            'maxLongSentences' => 25.0,
            'minTransitionWords' => 30.0,
            'maxTitleLength' => 66,
            'maxSubheadingKeywordUsage' => 75.0,
            'requireImages' => true,
            'requireKeywordInAltText' => true
        ], $config);
        
        $this->severityWeights = [
            'critical' => 3.0,
            'major' => 2.0,
            'minor' => 1.0
        ];
        
        $this->initializeComponents();
    }
    
    /**
     * Initialize validation components
     */
    private function initializeComponents() {
        $this->densityCalculator = new Keyword_Density_Calculator();
        $this->passiveAnalyzer = new PassiveVoiceAnalyzer($this->config['maxPassiveVoice']);
        $this->metaValidator = new MetaDescriptionValidator(
            $this->config['minMetaDescLength'],
            $this->config['maxMetaDescLength'],
            true
        );
        
        if (class_exists('SentenceLengthAnalyzer')) {
            $this->sentenceAnalyzer = new SentenceLengthAnalyzer();
        }
        
        if (class_exists('TransitionWordAnalyzer')) {
            $this->transitionAnalyzer = new TransitionWordAnalyzer();
        }
    }
    
    /**
     * Detect all SEO issues in content with comprehensive analysis
     *
     * @param array $content Content array with title, content, meta_description
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return array Comprehensive issue detection result
     */
    public function detectAllIssues($content, $focusKeyword, $secondaryKeywords = []) {
        $issues = [];
        $metrics = new ContentValidationMetrics();
        
        // Detect keyword density issues
        $keywordIssues = $this->detectKeywordDensityIssues($content, $focusKeyword, $secondaryKeywords, $metrics);
        $issues = array_merge($issues, $keywordIssues);
        
        // Detect meta description issues
        $metaIssues = $this->detectMetaDescriptionIssues($content, $focusKeyword, $metrics);
        $issues = array_merge($issues, $metaIssues);
        
        // Detect passive voice issues
        $passiveIssues = $this->detectPassiveVoiceIssues($content, $metrics);
        $issues = array_merge($issues, $passiveIssues);
        
        // Detect sentence length issues
        $sentenceIssues = $this->detectSentenceLengthIssues($content, $metrics);
        $issues = array_merge($issues, $sentenceIssues);
        
        // Detect transition word issues
        $transitionIssues = $this->detectTransitionWordIssues($content, $metrics);
        $issues = array_merge($issues, $transitionIssues);
        
        // Detect title issues
        $titleIssues = $this->detectTitleIssues($content, $focusKeyword, $metrics);
        $issues = array_merge($issues, $titleIssues);
        
        // Detect heading keyword usage issues
        $headingIssues = $this->detectHeadingKeywordIssues($content, $focusKeyword, $metrics);
        $issues = array_merge($issues, $headingIssues);
        
        // Detect image and alt text issues
        $imageIssues = $this->detectImageIssues($content, $focusKeyword, $metrics);
        $issues = array_merge($issues, $imageIssues);
        
        // Calculate overall compliance score
        $complianceScore = $this->calculateComplianceScore($issues);
        
        return [
            'issues' => $issues,
            'totalIssues' => count($issues),
            'criticalIssues' => count(array_filter($issues, function($issue) { return $issue->severity === 'critical'; })),
            'majorIssues' => count(array_filter($issues, function($issue) { return $issue->severity === 'major'; })),
            'minorIssues' => count(array_filter($issues, function($issue) { return $issue->severity === 'minor'; })),
            'complianceScore' => $complianceScore,
            'isCompliant' => $complianceScore >= 100.0,
            'metrics' => $metrics->toArray()
        ];
    }
    
    /**
     * Detect keyword density issues with precise quantification
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Array of keyword density issues
     */
    private function detectKeywordDensityIssues($content, $focusKeyword, $secondaryKeywords, $metrics) {
        $issues = [];
        $contentText = strip_tags($content['content'] ?? '');
        
        $densityResult = $this->densityCalculator->calculate_density($contentText, $focusKeyword, $secondaryKeywords);
        $currentDensity = $densityResult['overall_density'];
        
        // Update metrics
        $metrics->keywordDensity = $currentDensity;
        $metrics->wordCount = $densityResult['total_words'];
        
        if ($currentDensity < $this->config['minKeywordDensity']) {
            $keywordLocations = $this->findKeywordLocations($contentText, $focusKeyword);
            
            // For low density, provide content analysis even if keyword is missing
            if (empty($keywordLocations)) {
                $keywordLocations = [
                    [
                        'position' => 0,
                        'length' => 0,
                        'context' => substr($contentText, 0, 100),
                        'note' => 'Keyword not found in content'
                    ]
                ];
            }
            
            $issues[] = new SEOIssue(
                'keyword_density_low',
                'major',
                $currentDensity,
                $this->config['minKeywordDensity'],
                $keywordLocations,
                sprintf('Keyword density too low (%.2f%%, minimum %.2f%%)', $currentDensity, $this->config['minKeywordDensity']),
                8,
                2.0
            );
        } elseif ($currentDensity > $this->config['maxKeywordDensity']) {
            $issues[] = new SEOIssue(
                'keyword_density_high',
                'critical',
                $currentDensity,
                $this->config['maxKeywordDensity'],
                $this->findKeywordLocations($contentText, $focusKeyword),
                sprintf('Keyword density too high (%.2f%%, maximum %.2f%%)', $currentDensity, $this->config['maxKeywordDensity']),
                9,
                3.0
            );
        }
        
        return $issues;
    }
    
    /**
     * Detect meta description issues with character count analysis
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Array of meta description issues
     */
    private function detectMetaDescriptionIssues($content, $focusKeyword, $metrics) {
        $issues = [];
        $metaDescription = $content['meta_description'] ?? '';
        $length = strlen($metaDescription);
        
        // Update metrics
        $metrics->metaDescriptionLength = $length;
        
        if ($length < $this->config['minMetaDescLength']) {
            $issues[] = new SEOIssue(
                'meta_description_short',
                'critical',
                $length,
                $this->config['minMetaDescLength'],
                [['position' => 0, 'length' => $length, 'text' => $metaDescription]],
                sprintf('Meta description too short (%d chars, minimum %d)', $length, $this->config['minMetaDescLength']),
                10,
                3.0
            );
        } elseif ($length > $this->config['maxMetaDescLength']) {
            $issues[] = new SEOIssue(
                'meta_description_long',
                'major',
                $length,
                $this->config['maxMetaDescLength'],
                [['position' => $this->config['maxMetaDescLength'], 'length' => $length - $this->config['maxMetaDescLength'], 'text' => substr($metaDescription, $this->config['maxMetaDescLength'])]],
                sprintf('Meta description too long (%d chars, maximum %d)', $length, $this->config['maxMetaDescLength']),
                7,
                2.0
            );
        }
        
        // Check keyword inclusion
        if (!$this->metaValidator->validateKeywordInclusion($metaDescription, $focusKeyword)['isValid']) {
            $issues[] = new SEOIssue(
                'meta_description_no_keyword',
                'major',
                0,
                1,
                [['position' => 0, 'length' => $length, 'text' => $metaDescription]],
                sprintf('Meta description missing focus keyword: %s', $focusKeyword),
                6,
                2.0
            );
        }
        
        return $issues;
    }
    
    /**
     * Detect passive voice issues with sentence-level analysis
     *
     * @param array $content Content array
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Array of passive voice issues
     */
    private function detectPassiveVoiceIssues($content, $metrics) {
        $issues = [];
        $contentText = strip_tags($content['content'] ?? '');
        
        $passiveAnalysis = $this->passiveAnalyzer->analyze($contentText);
        $passivePercentage = $passiveAnalysis['passivePercentage'];
        
        // Update metrics
        $metrics->passiveVoicePercentage = $passivePercentage;
        $metrics->sentenceCount = $passiveAnalysis['totalSentences'];
        
        if ($passivePercentage > $this->config['maxPassiveVoice']) {
            $passiveLocations = [];
            foreach ($passiveAnalysis['passiveSentenceDetails'] as $detail) {
                $passiveLocations[] = [
                    'sentence_index' => $detail['index'],
                    'sentence' => $detail['sentence'],
                    'patterns' => $detail['patterns'],
                    'confidence' => $detail['confidence']
                ];
            }
            
            $issues[] = new SEOIssue(
                'passive_voice_high',
                'major',
                $passivePercentage,
                $this->config['maxPassiveVoice'],
                $passiveLocations,
                sprintf('Too much passive voice (%.1f%%, maximum %.1f%%)', $passivePercentage, $this->config['maxPassiveVoice']),
                5,
                2.0
            );
        }
        
        return $issues;
    }
    
    /**
     * Detect sentence length issues
     *
     * @param array $content Content array
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Array of sentence length issues
     */
    private function detectSentenceLengthIssues($content, $metrics) {
        $issues = [];
        $contentText = strip_tags($content['content'] ?? '');
        
        if ($this->sentenceAnalyzer) {
            $sentenceAnalysis = $this->sentenceAnalyzer->analyze($contentText);
            $longSentencePercentage = $sentenceAnalysis['longSentencePercentage'] ?? 0;
            
            // Update metrics
            $metrics->longSentencePercentage = $longSentencePercentage;
            
            if ($longSentencePercentage > $this->config['maxLongSentences']) {
                $longSentenceLocations = [];
                if (isset($sentenceAnalysis['longSentenceDetails']) && is_array($sentenceAnalysis['longSentenceDetails'])) {
                    foreach ($sentenceAnalysis['longSentenceDetails'] as $sentence) {
                        $longSentenceLocations[] = [
                            'sentence' => $sentence['sentence'] ?? '',
                            'word_count' => $sentence['wordCount'] ?? 0,
                            'position' => $sentence['index'] ?? 0
                        ];
                    }
                }
                
                $issues[] = new SEOIssue(
                    'sentence_length_high',
                    'minor',
                    $longSentencePercentage,
                    $this->config['maxLongSentences'],
                    $longSentenceLocations,
                    sprintf('Too many long sentences (%.1f%%, maximum %.1f%%)', $longSentencePercentage, $this->config['maxLongSentences']),
                    3,
                    1.0
                );
            }
        } else {
            // Fallback analysis using ContentValidationMetrics
            $longSentencePercentage = $metrics->calculateLongSentences($contentText);
            
            if ($longSentencePercentage > $this->config['maxLongSentences']) {
                // Find long sentences for location data
                $longSentenceLocations = $this->findLongSentences($contentText);
                
                $issues[] = new SEOIssue(
                    'sentence_length_high',
                    'minor',
                    $longSentencePercentage,
                    $this->config['maxLongSentences'],
                    $longSentenceLocations,
                    sprintf('Too many long sentences (%.1f%%, maximum %.1f%%)', $longSentencePercentage, $this->config['maxLongSentences']),
                    3,
                    1.0
                );
            }
        }
        
        return $issues;
    }
    
    /**
     * Detect transition word issues
     *
     * @param array $content Content array
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Array of transition word issues
     */
    private function detectTransitionWordIssues($content, $metrics) {
        $issues = [];
        $contentText = strip_tags($content['content'] ?? '');
        
        if ($this->transitionAnalyzer) {
            $transitionAnalysis = $this->transitionAnalyzer->analyze($contentText);
            $transitionPercentage = $transitionAnalysis['transitionPercentage'] ?? 0;
        } else {
            // Fallback analysis using ContentValidationMetrics
            $transitionPercentage = $metrics->calculateTransitionWords($contentText);
        }
        
        // Update metrics
        $metrics->transitionWordPercentage = $transitionPercentage;
        
        if ($transitionPercentage < $this->config['minTransitionWords']) {
            $issues[] = new SEOIssue(
                'transition_words_low',
                'minor',
                $transitionPercentage,
                $this->config['minTransitionWords'],
                [],
                sprintf('Not enough transition words (%.1f%%, minimum %.1f%%)', $transitionPercentage, $this->config['minTransitionWords']),
                2,
                1.0
            );
        }
        
        return $issues;
    }
    
    /**
     * Detect title issues
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Array of title issues
     */
    private function detectTitleIssues($content, $focusKeyword, $metrics) {
        $issues = [];
        $title = $content['title'] ?? '';
        $titleLength = strlen($title);
        
        // Update metrics
        $titleAnalysis = $metrics->analyzeTitle($title, $focusKeyword);
        
        if ($titleLength > $this->config['maxTitleLength']) {
            $issues[] = new SEOIssue(
                'title_too_long',
                'major',
                $titleLength,
                $this->config['maxTitleLength'],
                [['position' => $this->config['maxTitleLength'], 'length' => $titleLength - $this->config['maxTitleLength'], 'text' => substr($title, $this->config['maxTitleLength'])]],
                sprintf('Title too long (%d chars, maximum %d)', $titleLength, $this->config['maxTitleLength']),
                7,
                2.0
            );
        }
        
        if (!$titleAnalysis['containsKeyword']) {
            $issues[] = new SEOIssue(
                'title_no_keyword',
                'critical',
                0,
                1,
                [['position' => 0, 'length' => $titleLength, 'text' => $title]],
                sprintf('Title missing focus keyword: %s', $focusKeyword),
                9,
                3.0
            );
        }
        
        return $issues;
    }
    
    /**
     * Detect heading keyword usage issues
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Array of heading keyword issues
     */
    private function detectHeadingKeywordIssues($content, $focusKeyword, $metrics) {
        $issues = [];
        $contentText = $content['content'] ?? '';
        
        $subheadingUsage = $metrics->calculateSubheadingKeywordUsage($contentText, $focusKeyword);
        
        if ($subheadingUsage > $this->config['maxSubheadingKeywordUsage']) {
            // Find specific headings with keywords
            $headingLocations = $this->findHeadingsWithKeyword($contentText, $focusKeyword);
            
            $issues[] = new SEOIssue(
                'subheading_keyword_overuse',
                'minor',
                $subheadingUsage,
                $this->config['maxSubheadingKeywordUsage'],
                $headingLocations,
                sprintf('Too many subheadings contain keyword (%.1f%%, maximum %.1f%%)', $subheadingUsage, $this->config['maxSubheadingKeywordUsage']),
                4,
                1.0
            );
        }
        
        return $issues;
    }
    
    /**
     * Detect image and alt text issues
     *
     * @param array $content Content array
     * @param string $focusKeyword Focus keyword
     * @param ContentValidationMetrics $metrics Metrics object
     * @return array Array of image issues
     */
    private function detectImageIssues($content, $focusKeyword, $metrics) {
        $issues = [];
        $contentText = $content['content'] ?? '';
        
        $imageAnalysis = $metrics->analyzeImages($contentText, $focusKeyword);
        
        if ($this->config['requireImages'] && !$imageAnalysis['hasImages']) {
            $issues[] = new SEOIssue(
                'no_images',
                'major',
                0,
                1,
                [],
                'Content should include at least one image',
                6,
                2.0
            );
        }
        
        if ($this->config['requireKeywordInAltText'] && $imageAnalysis['hasImages'] && !$imageAnalysis['hasProperAltText']) {
            $imageLocations = $this->findImagesWithoutProperAltText($contentText, $focusKeyword);
            
            $issues[] = new SEOIssue(
                'alt_text_no_keyword',
                'minor',
                $imageAnalysis['properAltCount'] ?? 0,
                $imageAnalysis['imageCount'] ?? 1,
                $imageLocations,
                'Image alt text should include focus keyword',
                3,
                1.0
            );
        }
        
        return $issues;
    }
    
    /**
     * Calculate overall SEO compliance score based on issues
     *
     * @param array $issues Array of SEOIssue objects
     * @return float Compliance score (0-100)
     */
    private function calculateComplianceScore($issues) {
        if (empty($issues)) {
            return 100.0;
        }
        
        $totalPenalty = 0;
        $maxPossiblePenalty = 0;
        
        foreach ($issues as $issue) {
            $severityWeight = $this->severityWeights[$issue->severity] ?? 1.0;
            $penalty = $issue->weight * $severityWeight * 10; // Base penalty of 10 points
            $totalPenalty += $penalty;
            $maxPossiblePenalty += $penalty;
        }
        
        // Calculate score with diminishing returns for multiple issues
        $score = 100.0 - ($totalPenalty * 0.8); // 0.8 factor to prevent overly harsh penalties
        
        return max(0.0, min(100.0, $score));
    }
    
    /**
     * Find keyword locations in content
     *
     * @param string $content Content text
     * @param string $keyword Keyword to find
     * @return array Array of keyword locations
     */
    private function findKeywordLocations($content, $keyword) {
        $locations = [];
        $lowerContent = strtolower($content);
        $lowerKeyword = strtolower($keyword);
        $offset = 0;
        
        while (($pos = strpos($lowerContent, $lowerKeyword, $offset)) !== false) {
            $locations[] = [
                'position' => $pos,
                'length' => strlen($keyword),
                'context' => substr($content, max(0, $pos - 50), 100)
            ];
            $offset = $pos + 1;
        }
        
        return $locations;
    }
    
    /**
     * Find headings that contain the keyword
     *
     * @param string $content Content HTML
     * @param string $keyword Keyword to find
     * @return array Array of heading locations
     */
    private function findHeadingsWithKeyword($content, $keyword) {
        $locations = [];
        $pattern = '/<h[2-6][^>]*>(.*?)<\/h[2-6]>/i';
        
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $index => $match) {
                $headingText = $match[0];
                $position = $match[1];
                
                if (stripos($headingText, $keyword) !== false) {
                    $locations[] = [
                        'heading' => strip_tags($headingText),
                        'position' => $position,
                        'full_tag' => $matches[0][$index][0]
                    ];
                }
            }
        }
        
        return $locations;
    }
    
    /**
     * Find images without proper alt text
     *
     * @param string $content Content HTML
     * @param string $keyword Keyword to check for
     * @return array Array of image locations
     */
    private function findImagesWithoutProperAltText($content, $keyword) {
        $locations = [];
        $pattern = '/<img[^>]+>/i';
        
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $imgTag = $match[0];
                $position = $match[1];
                
                // Check if alt text exists and contains keyword
                if (preg_match('/alt=["\']([^"\']+)["\']/i', $imgTag, $altMatch)) {
                    $altText = $altMatch[1];
                    if (strlen($altText) <= 10 || stripos($altText, $keyword) === false) {
                        $locations[] = [
                            'img_tag' => $imgTag,
                            'position' => $position,
                            'alt_text' => $altText,
                            'issue' => strlen($altText) <= 10 ? 'too_short' : 'no_keyword'
                        ];
                    }
                } else {
                    $locations[] = [
                        'img_tag' => $imgTag,
                        'position' => $position,
                        'alt_text' => '',
                        'issue' => 'missing'
                    ];
                }
            }
        }
        
        return $locations;
    }
    
    /**
     * Get issues by severity level
     *
     * @param array $issues Array of SEOIssue objects
     * @param string $severity Severity level to filter by
     * @return array Filtered issues
     */
    public function getIssuesBySeverity($issues, $severity) {
        return array_filter($issues, function($issue) use ($severity) {
            return $issue->severity === $severity;
        });
    }
    
    /**
     * Get issues by type
     *
     * @param array $issues Array of SEOIssue objects
     * @param string $type Issue type to filter by
     * @return array Filtered issues
     */
    public function getIssuesByType($issues, $type) {
        return array_filter($issues, function($issue) use ($type) {
            return $issue->type === $type;
        });
    }
    
    /**
     * Sort issues by priority (highest first)
     *
     * @param array $issues Array of SEOIssue objects
     * @return array Sorted issues
     */
    public function sortIssuesByPriority($issues) {
        usort($issues, function($a, $b) {
            return $b->priority - $a->priority;
        });
        
        return $issues;
    }
    
    /**
     * Update configuration settings
     *
     * @param array $newConfig New configuration settings
     */
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        
        // Reinitialize components with new config
        $this->initializeComponents();
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
     * Find long sentences in content (fallback method)
     *
     * @param string $content Content text
     * @return array Array of long sentence locations
     */
    private function findLongSentences($content) {
        $locations = [];
        $sentences = preg_split('/[.!?]+/', strip_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($sentences as $index => $sentence) {
            $trimmed = trim($sentence);
            if (strlen($trimmed) > 5) { // Ignore very short fragments
                $wordCount = str_word_count($trimmed);
                if ($wordCount > 20) { // Default max sentence length
                    $locations[] = [
                        'sentence' => $trimmed,
                        'word_count' => $wordCount,
                        'position' => $index
                    ];
                }
            }
        }
        
        return $locations;
    }
}