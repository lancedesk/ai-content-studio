<?php
/**
 * Meta Description Auto-Correction Engine
 *
 * Automatically corrects meta descriptions to meet SEO requirements
 * including length adjustment and keyword insertion.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class MetaDescriptionCorrector
 *
 * Handles automatic correction of meta descriptions for SEO compliance
 */
class MetaDescriptionCorrector {
    
    /**
     * @var MetaDescriptionValidator Validator instance
     */
    private $validator;
    
    /**
     * @var int Minimum meta description length
     */
    private $minLength;
    
    /**
     * @var int Maximum meta description length
     */
    private $maxLength;
    
    /**
     * Constructor
     *
     * @param int $minLength Minimum character length (default: 120)
     * @param int $maxLength Maximum character length (default: 156)
     */
    public function __construct($minLength = 120, $maxLength = 156) {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->validator = new MetaDescriptionValidator($minLength, $maxLength);
    }
    
    /**
     * Auto-correct meta description to meet all requirements
     *
     * @param string $metaDescription The meta description to correct
     * @param string $focusKeyword The focus keyword to include
     * @param array $synonyms Optional array of keyword synonyms
     * @param string $contentContext Optional content context for intelligent expansion
     * @return array Corrected meta description and correction details
     */
    public function autoCorrect($metaDescription, $focusKeyword, $synonyms = [], $contentContext = '') {
        $corrections = [];
        $correctedDescription = $metaDescription;
        
        // First, ensure keyword is present
        $keywordValidation = $this->validator->validateKeywordInclusion($correctedDescription, $focusKeyword, $synonyms);
        if (!$keywordValidation['isValid']) {
            $correctedDescription = $this->insertKeyword($correctedDescription, $focusKeyword);
            $corrections[] = 'Added focus keyword to meta description';
        }
        
        // Then, adjust length if needed
        $lengthValidation = $this->validator->validateLength($correctedDescription);
        if (!$lengthValidation['isValid']) {
            if ($lengthValidation['type'] === 'too_short') {
                $correctedDescription = $this->expandDescription($correctedDescription, $focusKeyword, $contentContext);
                $corrections[] = sprintf('Extended meta description from %d to %d characters', 
                    $lengthValidation['currentLength'], 
                    strlen($correctedDescription));
            } elseif ($lengthValidation['type'] === 'too_long') {
                $correctedDescription = $this->trimDescription($correctedDescription, $focusKeyword);
                $corrections[] = sprintf('Trimmed meta description from %d to %d characters', 
                    $lengthValidation['currentLength'], 
                    strlen($correctedDescription));
                
                // Check if trimming made it too short, and expand if needed
                $postTrimValidation = $this->validator->validateLength($correctedDescription);
                if (!$postTrimValidation['isValid'] && $postTrimValidation['type'] === 'too_short') {
                    $correctedDescription = $this->expandDescription($correctedDescription, $focusKeyword, $contentContext);
                    $corrections[] = sprintf('Re-extended meta description after trimming from %d to %d characters', 
                        $postTrimValidation['currentLength'], 
                        strlen($correctedDescription));
                }
            }
        }
        
        // Final validation
        $finalValidation = $this->validator->validate($correctedDescription, $focusKeyword, $synonyms);
        
        return [
            'original' => $metaDescription,
            'corrected' => $correctedDescription,
            'corrections' => $corrections,
            'isValid' => $finalValidation->isValid,
            'validation' => $finalValidation,
            'characterCount' => strlen($correctedDescription)
        ];
    }
    
    /**
     * Insert keyword into meta description naturally
     *
     * @param string $metaDescription The meta description
     * @param string $focusKeyword The keyword to insert
     * @return string Meta description with keyword inserted
     */
    public function insertKeyword($metaDescription, $focusKeyword) {
        // If description is empty, create a basic one with the keyword
        if (empty(trim($metaDescription))) {
            return ucfirst($focusKeyword) . ' - comprehensive guide and information.';
        }
        
        // Try to insert keyword at the beginning naturally
        $sentences = $this->splitIntoSentences($metaDescription);
        
        if (empty($sentences)) {
            return ucfirst($focusKeyword) . '. ' . $metaDescription;
        }
        
        // Check if we can prepend keyword to first sentence
        $firstSentence = $sentences[0];
        
        // If first sentence starts with an article, insert after it
        if (preg_match('/^(The|A|An)\s+/i', $firstSentence, $matches)) {
            $article = $matches[1];
            $rest = substr($firstSentence, strlen($article) + 1);
            $sentences[0] = $article . ' ' . $focusKeyword . ' ' . $rest;
        } else {
            // Prepend keyword to the description
            $sentences[0] = ucfirst($focusKeyword) . ': ' . $firstSentence;
        }
        
        return implode(' ', $sentences);
    }
    
    /**
     * Expand meta description to meet minimum length
     *
     * @param string $metaDescription The meta description to expand
     * @param string $focusKeyword The focus keyword
     * @param string $contentContext Optional content context for intelligent expansion
     * @return string Expanded meta description
     */
    public function expandDescription($metaDescription, $focusKeyword, $contentContext = '') {
        $currentLength = strlen($metaDescription);
        $targetLength = $this->minLength;
        $neededChars = $targetLength - $currentLength;
        
        // If we need very few characters, just add a period and space
        if ($neededChars <= 5) {
            return $metaDescription . str_repeat('.', $neededChars);
        }
        
        // Add value-adding phrases
        $expansionPhrases = [
            ' Learn more about ' . $focusKeyword . ' and how it can benefit you.',
            ' Discover everything you need to know about ' . $focusKeyword . '.',
            ' Get expert insights and practical tips.',
            ' Find comprehensive information and guidance.',
            ' Explore detailed explanations and examples.',
            ' Access valuable resources and recommendations.'
        ];
        
        // Try each phrase until we reach minimum length
        $expandedDescription = $metaDescription;
        
        foreach ($expansionPhrases as $phrase) {
            if (strlen($expandedDescription) >= $targetLength) {
                break;
            }
            
            // Only add if it doesn't make it too long
            if (strlen($expandedDescription . $phrase) <= $this->maxLength) {
                $expandedDescription .= $phrase;
            }
        }
        
        // If still too short, add generic but useful content
        while (strlen($expandedDescription) < $targetLength) {
            $remaining = $targetLength - strlen($expandedDescription);
            
            if ($remaining <= 5) {
                // Add periods to reach exact length
                $expandedDescription .= str_repeat('.', $remaining);
                break;
            }
            
            $filler = ' Get the information you need to make informed decisions.';
            
            if (strlen($expandedDescription . $filler) <= $this->maxLength) {
                $expandedDescription .= $filler;
            } else {
                // Add a shorter filler if the long one would exceed max length
                $shortFiller = ' Learn more here.';
                if (strlen($expandedDescription . $shortFiller) <= $this->maxLength) {
                    $expandedDescription .= $shortFiller;
                } else {
                    // Add just enough characters to reach minimum
                    $expandedDescription .= str_repeat('.', $remaining);
                    break;
                }
            }
        }
        
        // Ensure we don't exceed maximum length
        if (strlen($expandedDescription) > $this->maxLength) {
            $expandedDescription = $this->trimDescription($expandedDescription, $focusKeyword);
        }
        
        return $expandedDescription;
    }
    
    /**
     * Trim meta description to meet maximum length
     *
     * @param string $metaDescription The meta description to trim
     * @param string $focusKeyword The focus keyword to preserve
     * @return string Trimmed meta description
     */
    public function trimDescription($metaDescription, $focusKeyword) {
        $targetLength = $this->maxLength;
        
        // If already within limits, return as is
        if (strlen($metaDescription) <= $targetLength) {
            return $metaDescription;
        }
        
        // Try to trim at sentence boundaries
        $sentences = $this->splitIntoSentences($metaDescription);
        $trimmedDescription = '';
        
        foreach ($sentences as $sentence) {
            $testDescription = $trimmedDescription . ($trimmedDescription ? ' ' : '') . $sentence;
            
            // Keep adding sentences while we're under the limit
            if (strlen($testDescription) <= $targetLength) {
                $trimmedDescription = $testDescription;
            } else {
                break;
            }
        }
        
        // If we have a valid trimmed description with the keyword, use it
        if (!empty($trimmedDescription) && stripos($trimmedDescription, $focusKeyword) !== false) {
            return $trimmedDescription;
        }
        
        // Otherwise, do a hard trim at word boundaries
        $words = explode(' ', $metaDescription);
        $trimmedDescription = '';
        
        foreach ($words as $word) {
            $testDescription = $trimmedDescription . ($trimmedDescription ? ' ' : '') . $word;
            
            // Keep adding words while we're under the limit (leaving room for ellipsis)
            if (strlen($testDescription) <= ($targetLength - 3)) {
                $trimmedDescription = $testDescription;
            } else {
                break;
            }
        }
        
        // Ensure keyword is still present
        if (stripos($trimmedDescription, $focusKeyword) === false) {
            // If keyword was lost, try to preserve the part with the keyword
            $keywordPos = stripos($metaDescription, $focusKeyword);
            if ($keywordPos !== false) {
                // Start from keyword position and work backwards/forwards
                $start = max(0, $keywordPos - 50);
                $length = min($targetLength, strlen($metaDescription) - $start);
                $trimmedDescription = substr($metaDescription, $start, $length);
                
                // Clean up partial words at start
                if ($start > 0) {
                    $firstSpace = strpos($trimmedDescription, ' ');
                    if ($firstSpace !== false) {
                        $trimmedDescription = substr($trimmedDescription, $firstSpace + 1);
                    }
                }
            }
        }
        
        // Add ellipsis if we trimmed content
        if (strlen($trimmedDescription) < strlen($metaDescription)) {
            $trimmedDescription = rtrim($trimmedDescription, ' .,;:') . '...';
        }
        
        // Final length check
        if (strlen($trimmedDescription) > $targetLength) {
            $trimmedDescription = substr($trimmedDescription, 0, $targetLength - 3) . '...';
        }
        
        return $trimmedDescription;
    }
    
    /**
     * Optimize meta description for better engagement
     *
     * @param string $metaDescription The meta description to optimize
     * @param string $focusKeyword The focus keyword
     * @return array Optimized meta description and optimization details
     */
    public function optimize($metaDescription, $focusKeyword) {
        $optimizations = [];
        $optimizedDescription = $metaDescription;
        
        // Ensure it starts with a capital letter
        if (!empty($optimizedDescription) && !ctype_upper($optimizedDescription[0])) {
            $optimizedDescription = ucfirst($optimizedDescription);
            $optimizations[] = 'Capitalized first letter';
        }
        
        // Ensure it ends with proper punctuation
        $lastChar = substr($optimizedDescription, -1);
        if (!in_array($lastChar, ['.', '!', '?'])) {
            $optimizedDescription .= '.';
            $optimizations[] = 'Added ending punctuation';
        }
        
        // Remove double spaces
        $beforeSpaces = $optimizedDescription;
        $optimizedDescription = preg_replace('/\s+/', ' ', $optimizedDescription);
        if ($beforeSpaces !== $optimizedDescription) {
            $optimizations[] = 'Removed extra spaces';
        }
        
        // Ensure keyword appears early (within first 100 characters)
        $keywordPos = stripos($optimizedDescription, $focusKeyword);
        if ($keywordPos !== false && $keywordPos > 100) {
            // Try to move keyword earlier
            $withKeywordFirst = $this->insertKeyword($optimizedDescription, $focusKeyword);
            if (strlen($withKeywordFirst) <= $this->maxLength) {
                $optimizedDescription = $withKeywordFirst;
                $optimizations[] = 'Moved keyword to earlier position';
            }
        }
        
        return [
            'original' => $metaDescription,
            'optimized' => $optimizedDescription,
            'optimizations' => $optimizations,
            'characterCount' => strlen($optimizedDescription)
        ];
    }
    
    /**
     * Generate fallback meta description when correction fails
     *
     * @param string $focusKeyword The focus keyword
     * @param string $contentContext Optional content context
     * @return string Fallback meta description
     */
    public function generateFallback($focusKeyword, $contentContext = '') {
        // Create a basic but compliant meta description
        $templates = [
            'Discover comprehensive information about %s. Learn key insights, best practices, and expert recommendations.',
            'Everything you need to know about %s. Get detailed explanations, practical tips, and valuable resources.',
            'Explore %s with our complete guide. Find answers, solutions, and expert advice to help you succeed.',
            'Learn about %s through detailed analysis and practical examples. Get the knowledge you need today.',
            'Complete guide to %s. Discover essential information, expert insights, and actionable recommendations.'
        ];
        
        // Select a random template
        $template = $templates[array_rand($templates)];
        $description = sprintf($template, $focusKeyword);
        
        // Ensure it meets length requirements
        if (strlen($description) < $this->minLength) {
            $description = $this->expandDescription($description, $focusKeyword, $contentContext);
        } elseif (strlen($description) > $this->maxLength) {
            $description = $this->trimDescription($description, $focusKeyword);
        }
        
        return $description;
    }
    
    /**
     * Split text into sentences
     *
     * @param string $text The text to split
     * @return array Array of sentences
     */
    private function splitIntoSentences($text) {
        // Split by sentence endings
        $sentences = preg_split('/([.!?]+)\s+/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        // Recombine sentences with their punctuation
        $result = [];
        for ($i = 0; $i < count($sentences); $i += 2) {
            if (isset($sentences[$i])) {
                $sentence = $sentences[$i];
                if (isset($sentences[$i + 1])) {
                    $sentence .= $sentences[$i + 1];
                }
                $result[] = trim($sentence);
            }
        }
        
        return array_filter($result);
    }
    
    /**
     * Validate and correct meta description with retry logic
     *
     * @param string $metaDescription The meta description to process
     * @param string $focusKeyword The focus keyword
     * @param array $synonyms Optional array of keyword synonyms
     * @param int $maxAttempts Maximum correction attempts
     * @return array Final corrected meta description and attempt details
     */
    public function correctWithRetry($metaDescription, $focusKeyword, $synonyms = [], $maxAttempts = 3) {
        $attempts = [];
        $currentDescription = $metaDescription;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $this->autoCorrect($currentDescription, $focusKeyword, $synonyms);
            $attempts[] = $result;
            
            if ($result['isValid']) {
                return [
                    'success' => true,
                    'metaDescription' => $result['corrected'],
                    'attempts' => $attempt,
                    'attemptDetails' => $attempts,
                    'finalValidation' => $result['validation']
                ];
            }
            
            // Use the corrected version for next attempt
            $currentDescription = $result['corrected'];
        }
        
        // If all attempts failed, generate fallback
        $fallback = $this->generateFallback($focusKeyword);
        
        return [
            'success' => false,
            'metaDescription' => $fallback,
            'attempts' => $maxAttempts,
            'attemptDetails' => $attempts,
            'usedFallback' => true,
            'finalValidation' => $this->validator->validate($fallback, $focusKeyword, $synonyms)
        ];
    }
}
