<?php
/**
 * Alt Text Accessibility Optimizer Class
 *
 * Optimizes alt text for accessibility while maintaining SEO keyword integration.
 * Ensures meaningful descriptions for screen readers and assistive technologies.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class AltTextAccessibilityOptimizer
 *
 * Handles optimization of alt text for accessibility compliance and SEO
 */
class AltTextAccessibilityOptimizer {
    
    /**
     * @var array Accessibility-focused descriptive words
     */
    private $accessibilityDescriptors = [
        'visual' => ['showing', 'displaying', 'featuring', 'depicting', 'illustrating'],
        'action' => ['working', 'collaborating', 'analyzing', 'discussing', 'presenting'],
        'quality' => ['clear', 'detailed', 'professional', 'well-lit', 'focused'],
        'context' => ['in a', 'within a', 'surrounded by', 'positioned in', 'located at']
    ];
    
    /**
     * @var array Words that should be avoided in accessible alt text
     */
    private $avoidWords = [
        'image of', 'picture of', 'photo of', 'graphic of', 'illustration of',
        'click here', 'see image', 'view picture', 'look at',
        'this image', 'this picture', 'this photo'
    ];
    
    /**
     * @var array Screen reader friendly transition words
     */
    private $screenReaderTransitions = [
        'featuring', 'showing', 'with', 'including', 'containing',
        'displaying', 'presenting', 'highlighting', 'demonstrating'
    ];
    
    /**
     * @var int Maximum recommended alt text length for screen readers
     */
    private $maxAccessibleLength = 100;
    
    /**
     * @var int Minimum meaningful alt text length
     */
    private $minMeaningfulLength = 15;
    
    /**
     * Optimize alt text for accessibility while maintaining keyword relevance
     *
     * @param string $altText Original alt text
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param array $options Optimization options
     * @return array Optimized alt text with accessibility score
     */
    public function optimizeAltText($altText, $focusKeyword, $secondaryKeywords = [], $options = []) {
        if (empty($altText)) {
            throw new InvalidArgumentException('Alt text cannot be empty');
        }
        
        $originalText = $altText;
        
        // Clean and prepare text
        $altText = $this->cleanAltText($altText);
        
        // Remove accessibility-unfriendly phrases
        $altText = $this->removeUnfriendlyPhrases($altText);
        
        // Ensure meaningful description
        $altText = $this->ensureMeaningfulDescription($altText, $focusKeyword, $options);
        
        // Integrate keywords accessibly
        $altText = $this->integrateKeywordsAccessibly($altText, $focusKeyword, $secondaryKeywords, $options);
        
        // Optimize for screen readers
        $altText = $this->optimizeForScreenReaders($altText, $options);
        
        // Ensure proper length
        $altText = $this->adjustLengthForAccessibility($altText, $options);
        
        // Final cleanup
        $altText = $this->finalizeAltText($altText);
        
        // Calculate accessibility score
        $accessibilityScore = $this->calculateAccessibilityScore($altText, $focusKeyword);
        
        return [
            'optimized_text' => $altText,
            'original_text' => $originalText,
            'accessibility_score' => $accessibilityScore,
            'length' => strlen($altText),
            'has_keyword' => $this->containsKeyword($altText, $focusKeyword, $secondaryKeywords),
            'screen_reader_friendly' => $accessibilityScore >= 80,
            'improvements_made' => $this->getImprovementsList($originalText, $altText)
        ];
    }
    
    /**
     * Generate multiple accessible alt text variations
     *
     * @param string $baseDescription Base description of the image
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param int $count Number of variations to generate
     * @param array $options Generation options
     * @return array Array of optimized alt text variations
     */
    public function generateAccessibleVariations($baseDescription, $focusKeyword, $secondaryKeywords = [], $count = 3, $options = []) {
        $variations = [];
        $usedStructures = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Create varied structure
            $structure = $this->selectVariationStructure($usedStructures);
            $usedStructures[] = $structure;
            
            // Build variation based on structure
            $variation = $this->buildVariationFromStructure($baseDescription, $focusKeyword, $secondaryKeywords, $structure, $options);
            
            // Optimize the variation
            $optimized = $this->optimizeAltText($variation, $focusKeyword, $secondaryKeywords, $options);
            
            $variations[] = array_merge($optimized, [
                'variation_index' => $i,
                'structure_type' => $structure,
                'uniqueness_score' => $this->calculateUniquenessScore($variation, array_column($variations, 'optimized_text'))
            ]);
        }
        
        // Sort by accessibility score (highest first)
        usort($variations, function($a, $b) {
            return $b['accessibility_score'] <=> $a['accessibility_score'];
        });
        
        return $variations;
    }
    
    /**
     * Validate alt text for accessibility compliance
     *
     * @param string $altText Alt text to validate
     * @param array $options Validation options
     * @return array Validation results
     */
    public function validateAccessibility($altText, $options = []) {
        $issues = [];
        $warnings = [];
        $suggestions = [];
        
        // Check length
        $length = strlen($altText);
        if ($length < $this->minMeaningfulLength) {
            $issues[] = "Alt text is too short ({$length} characters). Minimum recommended: {$this->minMeaningfulLength}";
        }
        if ($length > $this->maxAccessibleLength) {
            $warnings[] = "Alt text is long ({$length} characters). Consider shortening for better screen reader experience";
        }
        
        // Check for unfriendly phrases
        foreach ($this->avoidWords as $phrase) {
            if (stripos($altText, $phrase) !== false) {
                $issues[] = "Contains screen reader unfriendly phrase: '{$phrase}'";
                $suggestions[] = "Remove redundant phrase '{$phrase}' - screen readers already announce it's an image";
            }
        }
        
        // Check for meaningful content
        if ($this->isGenericDescription($altText)) {
            $warnings[] = "Alt text appears generic. Consider adding more specific descriptive details";
        }
        
        // Check for proper sentence structure
        if (!$this->hasProperSentenceStructure($altText)) {
            $suggestions[] = "Consider using complete sentences or clear descriptive phrases";
        }
        
        // Check for keyword stuffing
        if ($this->hasKeywordStuffing($altText)) {
            $issues[] = "Potential keyword stuffing detected. Prioritize natural description over SEO";
        }
        
        $accessibilityScore = $this->calculateAccessibilityScore($altText);
        
        return [
            'is_accessible' => empty($issues),
            'accessibility_score' => $accessibilityScore,
            'issues' => $issues,
            'warnings' => $warnings,
            'suggestions' => $suggestions,
            'length' => $length,
            'readability_level' => $this->assessReadabilityLevel($altText)
        ];
    }
    
    /**
     * Remove phrases that are unfriendly to screen readers
     *
     * @param string $altText Alt text to clean
     * @return string Cleaned alt text
     */
    private function removeUnfriendlyPhrases($altText) {
        foreach ($this->avoidWords as $phrase) {
            $altText = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/i', '', $altText);
        }
        
        // Clean up extra spaces
        $altText = preg_replace('/\s+/', ' ', $altText);
        return trim($altText);
    }
    
    /**
     * Ensure the description is meaningful and descriptive
     *
     * @param string $altText Current alt text
     * @param string $focusKeyword Primary keyword for context
     * @param array $options Enhancement options
     * @return string Enhanced alt text
     */
    private function ensureMeaningfulDescription($altText, $focusKeyword, $options = []) {
        // If too generic, enhance with context
        if ($this->isGenericDescription($altText)) {
            $altText = $this->enhanceGenericDescription($altText, $focusKeyword);
        }
        
        // Add descriptive elements if too short
        if (strlen($altText) < $this->minMeaningfulLength) {
            $altText = $this->expandDescription($altText, $focusKeyword);
        }
        
        return $altText;
    }
    
    /**
     * Integrate keywords in an accessibility-friendly way
     *
     * @param string $altText Current alt text
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param array $options Integration options
     * @return string Alt text with keywords integrated
     */
    private function integrateKeywordsAccessibly($altText, $focusKeyword, $secondaryKeywords = [], $options = []) {
        $requireKeyword = $options['require_keyword'] ?? true;
        
        if (!$requireKeyword) {
            return $altText;
        }
        
        // Check if keyword is already naturally present
        if ($this->containsKeyword($altText, $focusKeyword, $secondaryKeywords)) {
            return $altText;
        }
        
        // Choose the most natural keyword to integrate
        $keywordToUse = $this->selectMostNaturalKeyword($altText, $focusKeyword, $secondaryKeywords);
        
        // Integrate keyword naturally
        $altText = $this->integrateKeywordNaturally($altText, $keywordToUse);
        
        return $altText;
    }
    
    /**
     * Optimize text specifically for screen reader consumption
     *
     * @param string $altText Alt text to optimize
     * @param array $options Optimization options
     * @return string Screen reader optimized text
     */
    private function optimizeForScreenReaders($altText, $options = []) {
        // Ensure proper punctuation for natural pauses
        $altText = $this->addNaturalPauses($altText);
        
        // Replace technical jargon with accessible language
        $altText = $this->replaceJargonWithAccessibleLanguage($altText);
        
        // Ensure logical flow
        $altText = $this->improveLogicalFlow($altText);
        
        return $altText;
    }
    
    /**
     * Adjust length for optimal accessibility
     *
     * @param string $altText Alt text to adjust
     * @param array $options Length adjustment options
     * @return string Length-adjusted alt text
     */
    private function adjustLengthForAccessibility($altText, $options = []) {
        $maxLength = $options['max_length'] ?? $this->maxAccessibleLength;
        $minLength = $options['min_length'] ?? $this->minMeaningfulLength;
        
        $currentLength = strlen($altText);
        
        // If too short, expand meaningfully
        if ($currentLength < $minLength) {
            $altText = $this->expandMeaningfully($altText, $minLength);
        }
        
        // If too long, trim while preserving meaning
        if ($currentLength > $maxLength) {
            $altText = $this->trimPreservingMeaning($altText, $maxLength);
        }
        
        return $altText;
    }
    
    /**
     * Calculate accessibility score for alt text
     *
     * @param string $altText Alt text to score
     * @param string $focusKeyword Optional focus keyword
     * @return int Accessibility score (0-100)
     */
    private function calculateAccessibilityScore($altText, $focusKeyword = '') {
        $score = 100;
        $length = strlen($altText);
        
        // Length scoring
        if ($length < $this->minMeaningfulLength) {
            $score -= 30;
        } elseif ($length > $this->maxAccessibleLength) {
            $score -= 15;
        }
        
        // Check for unfriendly phrases
        foreach ($this->avoidWords as $phrase) {
            if (stripos($altText, $phrase) !== false) {
                $score -= 20;
            }
        }
        
        // Generic description penalty
        if ($this->isGenericDescription($altText)) {
            $score -= 25;
        }
        
        // Sentence structure bonus
        if ($this->hasProperSentenceStructure($altText)) {
            $score += 10;
        }
        
        // Keyword stuffing penalty
        if ($this->hasKeywordStuffing($altText)) {
            $score -= 30;
        }
        
        // Natural language bonus
        if ($this->usesNaturalLanguage($altText)) {
            $score += 15;
        }
        
        // Descriptive detail bonus
        if ($this->hasDescriptiveDetails($altText)) {
            $score += 10;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Select variation structure for generating different alt text versions
     *
     * @param array $usedStructures Previously used structures
     * @return string Structure type
     */
    private function selectVariationStructure($usedStructures = []) {
        $structures = [
            'descriptive_action', // "Person working on laptop"
            'contextual_scene',   // "Professional office environment with person at desk"
            'focused_detail',     // "Close-up of hands typing on keyboard"
            'environmental'       // "Modern workspace featuring technology setup"
        ];
        
        $available = array_diff($structures, $usedStructures);
        if (empty($available)) {
            $available = $structures;
        }
        
        return $available[array_rand($available)];
    }
    
    /**
     * Build variation from structure template
     *
     * @param string $baseDescription Base description
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param string $structure Structure type
     * @param array $options Generation options
     * @return string Generated variation
     */
    private function buildVariationFromStructure($baseDescription, $focusKeyword, $secondaryKeywords, $structure, $options = []) {
        switch ($structure) {
            case 'descriptive_action':
                return $this->buildDescriptiveActionVariation($baseDescription, $focusKeyword);
                
            case 'contextual_scene':
                return $this->buildContextualSceneVariation($baseDescription, $focusKeyword);
                
            case 'focused_detail':
                return $this->buildFocusedDetailVariation($baseDescription, $focusKeyword);
                
            case 'environmental':
                return $this->buildEnvironmentalVariation($baseDescription, $focusKeyword);
                
            default:
                return $baseDescription;
        }
    }
    
    /**
     * Build descriptive action variation
     *
     * @param string $baseDescription Base description
     * @param string $focusKeyword Primary keyword
     * @return string Action-focused variation
     */
    private function buildDescriptiveActionVariation($baseDescription, $focusKeyword) {
        $actions = $this->accessibilityDescriptors['action'];
        $action = $actions[array_rand($actions)];
        
        return "Person {$action} with {$focusKeyword} in professional setting";
    }
    
    /**
     * Build contextual scene variation
     *
     * @param string $baseDescription Base description
     * @param string $focusKeyword Primary keyword
     * @return string Scene-focused variation
     */
    private function buildContextualSceneVariation($baseDescription, $focusKeyword) {
        $contexts = $this->accessibilityDescriptors['context'];
        $context = $contexts[array_rand($contexts)];
        
        return "Professional workspace {$context} modern environment, featuring {$focusKeyword}";
    }
    
    /**
     * Build focused detail variation
     *
     * @param string $baseDescription Base description
     * @param string $focusKeyword Primary keyword
     * @return string Detail-focused variation
     */
    private function buildFocusedDetailVariation($baseDescription, $focusKeyword) {
        $qualities = $this->accessibilityDescriptors['quality'];
        $quality = $qualities[array_rand($qualities)];
        
        return "Close-up view of {$quality} {$focusKeyword} setup with professional details";
    }
    
    /**
     * Build environmental variation
     *
     * @param string $baseDescription Base description
     * @param string $focusKeyword Primary keyword
     * @return string Environment-focused variation
     */
    private function buildEnvironmentalVariation($baseDescription, $focusKeyword) {
        $visuals = $this->accessibilityDescriptors['visual'];
        $visual = $visuals[array_rand($visuals)];
        
        return "Modern office environment {$visual} {$focusKeyword} with clean, professional aesthetic";
    }
    
    /**
     * Check if description is too generic
     *
     * @param string $altText Alt text to check
     * @return bool True if generic
     */
    private function isGenericDescription($altText) {
        $genericPhrases = [
            'image', 'picture', 'photo', 'graphic', 'illustration',
            'content', 'item', 'thing', 'stuff', 'element'
        ];
        
        $wordCount = str_word_count($altText);
        if ($wordCount < 4) {
            return true;
        }
        
        $genericCount = 0;
        foreach ($genericPhrases as $phrase) {
            if (stripos($altText, $phrase) !== false) {
                $genericCount++;
            }
        }
        
        return $genericCount > 0 && $wordCount < 6;
    }
    
    /**
     * Check if alt text has proper sentence structure
     *
     * @param string $altText Alt text to check
     * @return bool True if has proper structure
     */
    private function hasProperSentenceStructure($altText) {
        // Check for complete thoughts
        $hasSubject = preg_match('/\b(person|people|man|woman|individual|professional|team|group)\b/i', $altText);
        $hasAction = preg_match('/\b(working|showing|displaying|featuring|using|holding|presenting)\b/i', $altText);
        
        return $hasSubject || $hasAction || str_word_count($altText) >= 5;
    }
    
    /**
     * Check for keyword stuffing
     *
     * @param string $altText Alt text to check
     * @return bool True if keyword stuffing detected
     */
    private function hasKeywordStuffing($altText) {
        $words = str_word_count($altText, 1);
        $totalWords = count($words);
        
        if ($totalWords < 5) {
            return false;
        }
        
        // Count repeated words
        $wordCounts = array_count_values(array_map('strtolower', $words));
        
        foreach ($wordCounts as $count) {
            if ($count > 2 && $totalWords < 15) {
                return true;
            }
            if ($count > 3) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if text uses natural language
     *
     * @param string $altText Alt text to check
     * @return bool True if uses natural language
     */
    private function usesNaturalLanguage($altText) {
        // Check for natural connectors
        $naturalConnectors = ['with', 'in', 'on', 'at', 'showing', 'featuring', 'displaying'];
        
        foreach ($naturalConnectors as $connector) {
            if (stripos($altText, $connector) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if text has descriptive details
     *
     * @param string $altText Alt text to check
     * @return bool True if has descriptive details
     */
    private function hasDescriptiveDetails($altText) {
        $descriptiveWords = [
            'professional', 'modern', 'clean', 'detailed', 'clear',
            'bright', 'focused', 'organized', 'structured', 'quality'
        ];
        
        foreach ($descriptiveWords as $word) {
            if (stripos($altText, $word) !== false) {
                return true;
            }
        }
        
        return str_word_count($altText) >= 8;
    }
    
    /**
     * Check if alt text contains keyword
     *
     * @param string $altText Alt text to check
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return bool True if contains keyword
     */
    private function containsKeyword($altText, $focusKeyword, $secondaryKeywords = []) {
        $allKeywords = array_merge([$focusKeyword], $secondaryKeywords);
        
        foreach ($allKeywords as $keyword) {
            if (stripos($altText, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Select the most natural keyword for integration
     *
     * @param string $altText Current alt text
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return string Most natural keyword
     */
    private function selectMostNaturalKeyword($altText, $focusKeyword, $secondaryKeywords) {
        // Prefer shorter, more natural keywords
        $allKeywords = array_merge([$focusKeyword], $secondaryKeywords);
        
        usort($allKeywords, function($a, $b) {
            return strlen($a) <=> strlen($b);
        });
        
        return $allKeywords[0];
    }
    
    /**
     * Integrate keyword naturally into alt text
     *
     * @param string $altText Current alt text
     * @param string $keyword Keyword to integrate
     * @return string Alt text with keyword integrated
     */
    private function integrateKeywordNaturally($altText, $keyword) {
        // Try to integrate at natural points
        $transitions = $this->screenReaderTransitions;
        $transition = $transitions[array_rand($transitions)];
        
        if (strpos($altText, 'with') === false && strpos($altText, 'featuring') === false) {
            $altText .= " {$transition} {$keyword}";
        }
        
        return $altText;
    }
    
    /**
     * Add natural pauses for screen reader flow
     *
     * @param string $altText Alt text to enhance
     * @return string Enhanced alt text
     */
    private function addNaturalPauses($altText) {
        // Ensure proper comma usage for natural pauses
        $altText = preg_replace('/\s+(with|featuring|showing|displaying)\s+/', ', $1 ', $altText);
        
        return $altText;
    }
    
    /**
     * Replace technical jargon with accessible language
     *
     * @param string $altText Alt text to process
     * @return string Processed alt text
     */
    private function replaceJargonWithAccessibleLanguage($altText) {
        $replacements = [
            'UI' => 'user interface',
            'UX' => 'user experience',
            'API' => 'programming interface',
            'SEO' => 'search optimization',
            'CRM' => 'customer management system'
        ];
        
        foreach ($replacements as $jargon => $accessible) {
            $altText = str_ireplace($jargon, $accessible, $altText);
        }
        
        return $altText;
    }
    
    /**
     * Improve logical flow of alt text
     *
     * @param string $altText Alt text to improve
     * @return string Improved alt text
     */
    private function improveLogicalFlow($altText) {
        // Ensure subject comes before action
        // This is a simplified implementation
        return $altText;
    }
    
    /**
     * Expand description meaningfully
     *
     * @param string $altText Current alt text
     * @param int $targetLength Target minimum length
     * @return string Expanded alt text
     */
    private function expandMeaningfully($altText, $targetLength) {
        if (strlen($altText) >= $targetLength) {
            return $altText;
        }
        
        $expansions = [
            ' in professional setting',
            ' with clear details',
            ' showing quality presentation',
            ' in modern environment'
        ];
        
        foreach ($expansions as $expansion) {
            if (strlen($altText . $expansion) <= $this->maxAccessibleLength) {
                $altText .= $expansion;
                if (strlen($altText) >= $targetLength) {
                    break;
                }
            }
        }
        
        return $altText;
    }
    
    /**
     * Trim text while preserving meaning
     *
     * @param string $altText Alt text to trim
     * @param int $maxLength Maximum allowed length
     * @return string Trimmed alt text
     */
    private function trimPreservingMeaning($altText, $maxLength) {
        if (strlen($altText) <= $maxLength) {
            return $altText;
        }
        
        // Remove less essential modifiers first
        $removablePatterns = [
            '/,?\s+with\s+[^,]+quality[^,]*/',
            '/,?\s+in\s+[^,]+environment[^,]*/',
            '/,?\s+showing\s+[^,]+presentation[^,]*/'
        ];
        
        foreach ($removablePatterns as $pattern) {
            $trimmed = preg_replace($pattern, '', $altText);
            if (strlen($trimmed) <= $maxLength && strlen($trimmed) >= $this->minMeaningfulLength) {
                return trim($trimmed);
            }
        }
        
        // If still too long, truncate at word boundary
        $altText = substr($altText, 0, $maxLength);
        $lastSpace = strrpos($altText, ' ');
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.8) {
            $altText = substr($altText, 0, $lastSpace);
        }
        
        return trim($altText);
    }
    
    /**
     * Clean alt text for final output
     *
     * @param string $altText Alt text to clean
     * @return string Cleaned alt text
     */
    private function cleanAltText($altText) {
        // Remove extra spaces
        $altText = preg_replace('/\s+/', ' ', $altText);
        
        // Remove leading/trailing spaces
        $altText = trim($altText);
        
        // Remove any HTML tags
        $altText = strip_tags($altText);
        
        return $altText;
    }
    
    /**
     * Finalize alt text with proper formatting
     *
     * @param string $altText Alt text to finalize
     * @return string Finalized alt text
     */
    private function finalizeAltText($altText) {
        // Ensure proper capitalization
        $altText = ucfirst($altText);
        
        // Remove any double punctuation
        $altText = preg_replace('/[.,]{2,}/', '.', $altText);
        
        // Ensure no trailing punctuation unless it's a complete sentence
        if (str_word_count($altText) > 6 && !preg_match('/[.!?]$/', $altText)) {
            // Don't add period - screen readers handle this naturally
        }
        
        return trim($altText);
    }
    
    /**
     * Get list of improvements made
     *
     * @param string $original Original alt text
     * @param string $optimized Optimized alt text
     * @return array List of improvements
     */
    private function getImprovementsList($original, $optimized) {
        $improvements = [];
        
        if (strlen($optimized) > strlen($original)) {
            $improvements[] = 'Expanded description for better accessibility';
        }
        
        if (strlen($optimized) < strlen($original)) {
            $improvements[] = 'Shortened for optimal screen reader experience';
        }
        
        foreach ($this->avoidWords as $phrase) {
            if (stripos($original, $phrase) !== false && stripos($optimized, $phrase) === false) {
                $improvements[] = "Removed screen reader unfriendly phrase: '{$phrase}'";
            }
        }
        
        if ($this->calculateAccessibilityScore($optimized) > $this->calculateAccessibilityScore($original)) {
            $improvements[] = 'Improved overall accessibility score';
        }
        
        return $improvements;
    }
    
    /**
     * Calculate uniqueness score compared to other variations
     *
     * @param string $text Text to score
     * @param array $otherTexts Other texts to compare against
     * @return int Uniqueness score (0-100)
     */
    private function calculateUniquenessScore($text, $otherTexts) {
        if (empty($otherTexts)) {
            return 100;
        }
        
        $words = str_word_count(strtolower($text), 1);
        $totalSimilarity = 0;
        
        foreach ($otherTexts as $otherText) {
            $otherWords = str_word_count(strtolower($otherText), 1);
            $commonWords = array_intersect($words, $otherWords);
            $similarity = count($commonWords) / max(count($words), count($otherWords));
            $totalSimilarity += $similarity;
        }
        
        $averageSimilarity = $totalSimilarity / count($otherTexts);
        return max(0, 100 - ($averageSimilarity * 100));
    }
    
    /**
     * Assess readability level of alt text
     *
     * @param string $altText Alt text to assess
     * @return string Readability level
     */
    private function assessReadabilityLevel($altText) {
        $wordCount = str_word_count($altText);
        $avgWordLength = strlen(str_replace(' ', '', $altText)) / max(1, $wordCount);
        
        if ($avgWordLength < 4 && $wordCount < 10) {
            return 'Very Easy';
        } elseif ($avgWordLength < 5 && $wordCount < 15) {
            return 'Easy';
        } elseif ($avgWordLength < 6) {
            return 'Moderate';
        } else {
            return 'Complex';
        }
    }
}