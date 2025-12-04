<?php
/**
 * Meta Description Validator Class
 *
 * Validates meta descriptions for SEO compliance including character count
 * and keyword inclusion verification.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class MetaDescriptionValidator
 *
 * Handles validation of meta descriptions against SEO requirements
 */
class MetaDescriptionValidator {
    
    /**
     * @var int Minimum meta description length
     */
    private $minLength;
    
    /**
     * @var int Maximum meta description length
     */
    private $maxLength;
    
    /**
     * @var bool Whether keyword inclusion is required
     */
    private $requireKeyword;
    
    /**
     * Constructor
     *
     * @param int $minLength Minimum character length (default: 120)
     * @param int $maxLength Maximum character length (default: 156)
     * @param bool $requireKeyword Whether to require keyword inclusion
     */
    public function __construct($minLength = 120, $maxLength = 156, $requireKeyword = true) {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->requireKeyword = $requireKeyword;
    }
    
    /**
     * Validate meta description against all requirements
     *
     * @param string $metaDescription The meta description to validate
     * @param string $focusKeyword The focus keyword to check for
     * @param array $synonyms Optional array of keyword synonyms
     * @return SEOValidationResult Validation result with errors and suggestions
     */
    public function validate($metaDescription, $focusKeyword, $synonyms = []) {
        $result = new SEOValidationResult();
        $result->isValid = true;
        
        // Validate character count
        $lengthValidation = $this->validateLength($metaDescription);
        if (!$lengthValidation['isValid']) {
            $result->addError($lengthValidation['message'], 'meta_description_length');
            $result->isValid = false;
        }
        
        // Validate keyword inclusion if required
        if ($this->requireKeyword) {
            $keywordValidation = $this->validateKeywordInclusion($metaDescription, $focusKeyword, $synonyms);
            if (!$keywordValidation['isValid']) {
                $result->addError($keywordValidation['message'], 'meta_description_keyword');
                $result->isValid = false;
            }
        }
        
        // Add suggestions for improvement
        $suggestions = $this->generateSuggestions($metaDescription, $focusKeyword, $synonyms);
        foreach ($suggestions as $suggestion) {
            $result->addSuggestion($suggestion, 'meta_description');
        }
        
        $result->calculateScore();
        return $result;
    }
    
    /**
     * Validate meta description character count
     *
     * @param string $metaDescription The meta description to validate
     * @return array Validation result with status and message
     */
    public function validateLength($metaDescription) {
        $length = strlen($metaDescription);
        
        if ($length < $this->minLength) {
            return [
                'isValid' => false,
                'message' => sprintf(
                    'Meta description is too short (%d characters). Must be at least %d characters.',
                    $length,
                    $this->minLength
                ),
                'currentLength' => $length,
                'requiredLength' => $this->minLength,
                'type' => 'too_short'
            ];
        }
        
        if ($length > $this->maxLength) {
            return [
                'isValid' => false,
                'message' => sprintf(
                    'Meta description is too long (%d characters). Must be no more than %d characters.',
                    $length,
                    $this->maxLength
                ),
                'currentLength' => $length,
                'requiredLength' => $this->maxLength,
                'type' => 'too_long'
            ];
        }
        
        return [
            'isValid' => true,
            'message' => 'Meta description length is within acceptable range.',
            'currentLength' => $length,
            'type' => 'valid'
        ];
    }
    
    /**
     * Validate keyword inclusion in meta description
     *
     * @param string $metaDescription The meta description to validate
     * @param string $focusKeyword The focus keyword to check for
     * @param array $synonyms Optional array of keyword synonyms
     * @return array Validation result with status and message
     */
    public function validateKeywordInclusion($metaDescription, $focusKeyword, $synonyms = []) {
        $lowerDescription = strtolower($metaDescription);
        $lowerKeyword = strtolower($focusKeyword);
        
        // Check for exact keyword match
        if (strpos($lowerDescription, $lowerKeyword) !== false) {
            return [
                'isValid' => true,
                'message' => 'Focus keyword found in meta description.',
                'matchedTerm' => $focusKeyword,
                'matchType' => 'exact'
            ];
        }
        
        // Check for synonym matches
        foreach ($synonyms as $synonym) {
            $lowerSynonym = strtolower($synonym);
            if (strpos($lowerDescription, $lowerSynonym) !== false) {
                return [
                    'isValid' => true,
                    'message' => 'Keyword synonym found in meta description.',
                    'matchedTerm' => $synonym,
                    'matchType' => 'synonym'
                ];
            }
        }
        
        // No keyword or synonym found
        $searchTerms = array_merge([$focusKeyword], $synonyms);
        return [
            'isValid' => false,
            'message' => sprintf(
                'Meta description must include the focus keyword "%s" or one of its synonyms: %s',
                $focusKeyword,
                implode(', ', $synonyms)
            ),
            'searchedTerms' => $searchTerms,
            'matchType' => 'none'
        ];
    }
    
    /**
     * Check if meta description meets all requirements
     *
     * @param string $metaDescription The meta description to check
     * @param string $focusKeyword The focus keyword
     * @param array $synonyms Optional array of keyword synonyms
     * @return bool True if all requirements are met
     */
    public function isCompliant($metaDescription, $focusKeyword, $synonyms = []) {
        $validation = $this->validate($metaDescription, $focusKeyword, $synonyms);
        return $validation->isValid;
    }
    
    /**
     * Get character count information
     *
     * @param string $metaDescription The meta description to analyze
     * @return array Character count details
     */
    public function getCharacterInfo($metaDescription) {
        $length = strlen($metaDescription);
        
        return [
            'currentLength' => $length,
            'minLength' => $this->minLength,
            'maxLength' => $this->maxLength,
            'isWithinRange' => $length >= $this->minLength && $length <= $this->maxLength,
            'charactersNeeded' => max(0, $this->minLength - $length),
            'charactersOver' => max(0, $length - $this->maxLength),
            'remainingCharacters' => max(0, $this->maxLength - $length)
        ];
    }
    
    /**
     * Generate improvement suggestions for meta description
     *
     * @param string $metaDescription The meta description to analyze
     * @param string $focusKeyword The focus keyword
     * @param array $synonyms Optional array of keyword synonyms
     * @return array Array of improvement suggestions
     */
    private function generateSuggestions($metaDescription, $focusKeyword, $synonyms = []) {
        $suggestions = [];
        $length = strlen($metaDescription);
        $charInfo = $this->getCharacterInfo($metaDescription);
        
        // Length-based suggestions
        if ($length < $this->minLength) {
            $suggestions[] = sprintf(
                'Add %d more characters to reach the minimum length. Consider expanding on key benefits or features.',
                $charInfo['charactersNeeded']
            );
        } elseif ($length > $this->maxLength) {
            $suggestions[] = sprintf(
                'Remove %d characters to stay within the optimal length. Focus on the most compelling points.',
                $charInfo['charactersOver']
            );
        } else {
            $suggestions[] = sprintf(
                'Good length! You have %d characters remaining if you need to add more detail.',
                $charInfo['remainingCharacters']
            );
        }
        
        // Keyword-based suggestions
        $keywordValidation = $this->validateKeywordInclusion($metaDescription, $focusKeyword, $synonyms);
        if (!$keywordValidation['isValid']) {
            $suggestions[] = sprintf(
                'Include the focus keyword "%s" naturally in the meta description to improve SEO relevance.',
                $focusKeyword
            );
            
            if (!empty($synonyms)) {
                $suggestions[] = sprintf(
                    'Alternatively, you could use one of these synonyms: %s',
                    implode(', ', $synonyms)
                );
            }
        }
        
        // General quality suggestions
        if (!$this->containsCallToAction($metaDescription)) {
            $suggestions[] = 'Consider adding a call-to-action to encourage clicks (e.g., "Learn more", "Discover", "Find out").';
        }
        
        if (!$this->containsValueProposition($metaDescription)) {
            $suggestions[] = 'Highlight the unique value or benefit users will get from your content.';
        }
        
        return $suggestions;
    }
    
    /**
     * Check if meta description contains a call-to-action
     *
     * @param string $metaDescription The meta description to check
     * @return bool True if call-to-action is present
     */
    private function containsCallToAction($metaDescription) {
        $ctaPatterns = [
            'learn more', 'discover', 'find out', 'explore', 'get started',
            'read more', 'see how', 'try now', 'start today', 'join us',
            'download', 'subscribe', 'sign up', 'contact us', 'book now'
        ];
        
        $lowerDescription = strtolower($metaDescription);
        
        foreach ($ctaPatterns as $pattern) {
            if (strpos($lowerDescription, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if meta description contains a value proposition
     *
     * @param string $metaDescription The meta description to check
     * @return bool True if value proposition indicators are present
     */
    private function containsValueProposition($metaDescription) {
        $valuePatterns = [
            'best', 'top', 'ultimate', 'complete', 'comprehensive', 'expert',
            'proven', 'effective', 'easy', 'quick', 'fast', 'simple',
            'free', 'save', 'improve', 'boost', 'increase', 'reduce'
        ];
        
        $lowerDescription = strtolower($metaDescription);
        
        foreach ($valuePatterns as $pattern) {
            if (strpos($lowerDescription, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Update validation constraints
     *
     * @param int $minLength New minimum length
     * @param int $maxLength New maximum length
     * @param bool $requireKeyword Whether to require keyword inclusion
     */
    public function updateConstraints($minLength, $maxLength, $requireKeyword = true) {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->requireKeyword = $requireKeyword;
    }
    
    /**
     * Get current validation constraints
     *
     * @return array Current constraints
     */
    public function getConstraints() {
        return [
            'minLength' => $this->minLength,
            'maxLength' => $this->maxLength,
            'requireKeyword' => $this->requireKeyword
        ];
    }
}