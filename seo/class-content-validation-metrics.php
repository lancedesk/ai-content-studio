<?php
/**
 * Content Validation Metrics Class
 *
 * Tracks and calculates various SEO and readability metrics for content validation.
 * Used to measure compliance with SEO standards and readability requirements.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class ContentValidationMetrics
 *
 * Data model for content metrics used in SEO validation
 */
class ContentValidationMetrics {
    
    /**
     * @var int Total word count in content
     */
    public $wordCount;
    
    /**
     * @var float Keyword density as percentage (0-100)
     */
    public $keywordDensity;
    
    /**
     * @var float Percentage of sentences using passive voice (0-100)
     */
    public $passiveVoicePercentage;
    
    /**
     * @var float Percentage of sentences exceeding 20 words (0-100)
     */
    public $longSentencePercentage;
    
    /**
     * @var float Percentage of sentences with transition words (0-100)
     */
    public $transitionWordPercentage;
    
    /**
     * @var int Character count of meta description
     */
    public $metaDescriptionLength;
    
    /**
     * @var bool Whether content includes images
     */
    public $hasImages;
    
    /**
     * @var bool Whether images have proper alt text
     */
    public $hasProperAltText;
    
    /**
     * @var float Keyword density in subheadings (0-100)
     */
    public $subheadingKeywordPercentage;
    
    /**
     * @var int Number of sentences in content
     */
    public $sentenceCount;
    
    /**
     * @var int Title character length
     */
    public $titleLength;
    
    /**
     * @var bool Whether focus keyword is in title
     */
    public $titleContainsKeyword;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->wordCount = 0;
        $this->keywordDensity = 0.0;
        $this->passiveVoicePercentage = 0.0;
        $this->longSentencePercentage = 0.0;
        $this->transitionWordPercentage = 0.0;
        $this->metaDescriptionLength = 0;
        $this->hasImages = false;
        $this->hasProperAltText = false;
        $this->subheadingKeywordPercentage = 0.0;
        $this->sentenceCount = 0;
        $this->titleLength = 0;
        $this->titleContainsKeyword = false;
    }
    
    /**
     * Calculate keyword density from content and keyword
     *
     * @param string $content The content to analyze
     * @param string $keyword The focus keyword
     * @param array $synonyms Array of keyword synonyms
     * @return float Keyword density percentage
     */
    public function calculateKeywordDensity($content, $keyword, $synonyms = []) {
        $words = str_word_count(strtolower($content), 1);
        $this->wordCount = count($words);
        
        if ($this->wordCount === 0) {
            return 0.0;
        }
        
        // Return 0 if keyword is empty
        if (empty($keyword) || trim($keyword) === '') {
            $this->keywordDensity = 0.0;
            return 0.0;
        }
        
        $keywordCount = 0;
        $searchTerms = array_merge([$keyword], $synonyms);
        
        foreach ($searchTerms as $term) {
            // Skip empty terms
            if (empty($term) || trim($term) === '') {
                continue;
            }
            $keywordCount += substr_count(strtolower($content), strtolower($term));
        }
        
        $this->keywordDensity = ($keywordCount / $this->wordCount) * 100;
        return $this->keywordDensity;
    }
    
    /**
     * Analyze passive voice usage in content
     *
     * @param string $content The content to analyze
     * @return float Percentage of passive voice sentences
     */
    public function calculatePassiveVoice($content) {
        $sentences = $this->getSentences($content);
        $this->sentenceCount = count($sentences);
        
        if ($this->sentenceCount === 0) {
            return 0.0;
        }
        
        $passiveCount = 0;
        $passiveIndicators = ['was', 'were', 'been', 'being', 'is', 'are', 'am'];
        
        foreach ($sentences as $sentence) {
            $words = str_word_count(strtolower($sentence), 1);
            foreach ($passiveIndicators as $indicator) {
                if (in_array($indicator, $words)) {
                    // Simple heuristic: if sentence contains passive indicator + past participle pattern
                    if (preg_match('/\b(was|were|been|being|is|are|am)\s+\w+ed\b/', strtolower($sentence))) {
                        $passiveCount++;
                        break;
                    }
                }
            }
        }
        
        $this->passiveVoicePercentage = ($passiveCount / $this->sentenceCount) * 100;
        return $this->passiveVoicePercentage;
    }
    
    /**
     * Calculate percentage of long sentences (>20 words)
     *
     * @param string $content The content to analyze
     * @return float Percentage of long sentences
     */
    public function calculateLongSentences($content) {
        $sentences = $this->getSentences($content);
        $this->sentenceCount = count($sentences);
        
        if ($this->sentenceCount === 0) {
            return 0.0;
        }
        
        $longSentenceCount = 0;
        
        foreach ($sentences as $sentence) {
            $wordCount = str_word_count($sentence);
            if ($wordCount > 20) {
                $longSentenceCount++;
            }
        }
        
        $this->longSentencePercentage = ($longSentenceCount / $this->sentenceCount) * 100;
        return $this->longSentencePercentage;
    }
    
    /**
     * Calculate percentage of sentences with transition words
     *
     * @param string $content The content to analyze
     * @return float Percentage of sentences with transitions
     */
    public function calculateTransitionWords($content) {
        $sentences = $this->getSentences($content);
        $this->sentenceCount = count($sentences);
        
        if ($this->sentenceCount === 0) {
            return 0.0;
        }
        
        $transitionWords = [
            'however', 'therefore', 'furthermore', 'moreover', 'additionally',
            'consequently', 'meanwhile', 'nevertheless', 'nonetheless', 'thus',
            'hence', 'accordingly', 'similarly', 'likewise', 'conversely',
            'on the other hand', 'in contrast', 'in addition', 'for example',
            'for instance', 'in conclusion', 'finally', 'first', 'second',
            'third', 'next', 'then', 'also', 'besides', 'indeed'
        ];
        
        $transitionCount = 0;
        
        foreach ($sentences as $sentence) {
            $lowerSentence = strtolower($sentence);
            foreach ($transitionWords as $transition) {
                if (strpos($lowerSentence, $transition) !== false) {
                    $transitionCount++;
                    break;
                }
            }
        }
        
        $this->transitionWordPercentage = ($transitionCount / $this->sentenceCount) * 100;
        return $this->transitionWordPercentage;
    }
    
    /**
     * Analyze subheading keyword usage
     *
     * @param string $content The content to analyze
     * @param string $keyword The focus keyword
     * @return float Percentage of subheadings containing keyword
     */
    public function calculateSubheadingKeywordUsage($content, $keyword) {
        // Extract subheadings (H2, H3, H4, H5, H6)
        preg_match_all('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/i', $content, $matches);
        $subheadings = $matches[1];
        
        if (empty($subheadings)) {
            return 0.0;
        }
        
        $keywordCount = 0;
        foreach ($subheadings as $heading) {
            if (stripos($heading, $keyword) !== false) {
                $keywordCount++;
            }
        }
        
        $this->subheadingKeywordPercentage = ($keywordCount / count($subheadings)) * 100;
        return $this->subheadingKeywordPercentage;
    }
    
    /**
     * Check meta description length
     *
     * @param string $metaDescription The meta description
     * @return int Character count
     */
    public function checkMetaDescriptionLength($metaDescription) {
        $this->metaDescriptionLength = strlen($metaDescription);
        return $this->metaDescriptionLength;
    }
    
    /**
     * Check title metrics
     *
     * @param string $title The title to analyze
     * @param string $keyword The focus keyword
     * @return array Title metrics
     */
    public function analyzeTitle($title, $keyword) {
        $this->titleLength = strlen($title);
        $this->titleContainsKeyword = stripos($title, $keyword) !== false;
        
        return [
            'length' => $this->titleLength,
            'containsKeyword' => $this->titleContainsKeyword,
            'isOptimalLength' => $this->titleLength <= 66
        ];
    }
    
    /**
     * Check image and alt text presence
     *
     * @param string $content The content to analyze
     * @param string $keyword The focus keyword
     * @return array Image analysis results
     */
    public function analyzeImages($content, $keyword) {
        // Check for images
        preg_match_all('/<img[^>]+>/i', $content, $imageMatches);
        $this->hasImages = !empty($imageMatches[0]);
        
        if (!$this->hasImages) {
            $this->hasProperAltText = false;
            return ['hasImages' => false, 'hasProperAltText' => false];
        }
        
        // Check alt text quality
        $properAltCount = 0;
        foreach ($imageMatches[0] as $img) {
            if (preg_match('/alt=["\']([^"\']+)["\']/i', $img, $altMatch)) {
                $altText = $altMatch[1];
                // Check if alt text is meaningful (>10 chars) and contains keyword
                if (strlen($altText) > 10 && stripos($altText, $keyword) !== false) {
                    $properAltCount++;
                }
            }
        }
        
        $this->hasProperAltText = $properAltCount > 0;
        
        return [
            'hasImages' => $this->hasImages,
            'hasProperAltText' => $this->hasProperAltText,
            'imageCount' => count($imageMatches[0]),
            'properAltCount' => $properAltCount
        ];
    }
    
    /**
     * Get sentences from content
     *
     * @param string $content The content to split
     * @return array Array of sentences
     */
    private function getSentences($content) {
        // Remove HTML tags for analysis
        $cleanContent = strip_tags($content);
        
        // Split by sentence endings
        $sentences = preg_split('/[.!?]+/', $cleanContent, -1, PREG_SPLIT_NO_EMPTY);
        
        // Clean up sentences
        return array_map('trim', $sentences);
    }
    
    /**
     * Get all metrics as array
     *
     * @return array All calculated metrics
     */
    public function toArray() {
        return [
            'wordCount' => $this->wordCount,
            'keywordDensity' => $this->keywordDensity,
            'passiveVoicePercentage' => $this->passiveVoicePercentage,
            'longSentencePercentage' => $this->longSentencePercentage,
            'transitionWordPercentage' => $this->transitionWordPercentage,
            'metaDescriptionLength' => $this->metaDescriptionLength,
            'hasImages' => $this->hasImages,
            'hasProperAltText' => $this->hasProperAltText,
            'subheadingKeywordPercentage' => $this->subheadingKeywordPercentage,
            'sentenceCount' => $this->sentenceCount,
            'titleLength' => $this->titleLength,
            'titleContainsKeyword' => $this->titleContainsKeyword
        ];
    }
    
    /**
     * Check if metrics meet SEO standards
     *
     * @return array Compliance status for each metric
     */
    public function checkCompliance() {
        return [
            'keywordDensity' => $this->keywordDensity >= 0.5 && $this->keywordDensity <= 2.5,
            'passiveVoice' => $this->passiveVoicePercentage < 10.0,
            'longSentences' => $this->longSentencePercentage <= 25.0,
            'transitionWords' => $this->transitionWordPercentage >= 30.0,
            'metaDescription' => $this->metaDescriptionLength >= 120 && $this->metaDescriptionLength <= 156,
            'title' => $this->titleLength <= 66 && $this->titleContainsKeyword,
            'images' => $this->hasImages && $this->hasProperAltText,
            'subheadingKeywords' => $this->subheadingKeywordPercentage <= 75.0
        ];
    }
}