<?php
/**
 * Image Prompt Generator Class
 *
 * Generates relevant image prompts and alt text with keyword optimization
 * for SEO-compliant content generation.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class ImagePromptGenerator
 *
 * Handles generation of image prompts and alt text with SEO optimization
 */
class ImagePromptGenerator {
    
    /**
     * @var array Common image styles and contexts
     */
    private $imageStyles = [
        'professional',
        'modern',
        'clean',
        'minimalist',
        'vibrant',
        'corporate',
        'creative',
        'technical'
    ];
    
    /**
     * @var array Image composition types
     */
    private $compositionTypes = [
        'close-up',
        'wide shot',
        'overhead view',
        'side angle',
        'front view',
        'diagonal composition',
        'centered composition'
    ];
    
    /**
     * @var array Lighting conditions
     */
    private $lightingConditions = [
        'natural lighting',
        'soft lighting',
        'bright lighting',
        'studio lighting',
        'warm lighting',
        'professional lighting'
    ];
    
    /**
     * @var array Background types
     */
    private $backgroundTypes = [
        'white background',
        'neutral background',
        'blurred background',
        'office environment',
        'natural setting',
        'clean workspace'
    ];
    
    /**
     * Generate image prompt based on content topic and focus keyword
     *
     * @param string $topic Content topic or title
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Optional secondary keywords
     * @param array $options Additional options for prompt generation
     * @return array Image prompt data with prompt text and metadata
     */
    public function generateImagePrompt($topic, $focusKeyword, $secondaryKeywords = [], $options = []) {
        if (empty($topic) || empty($focusKeyword)) {
            throw new InvalidArgumentException('Topic and focus keyword are required');
        }
        
        // Clean and prepare inputs
        $topic = $this->cleanText($topic);
        $focusKeyword = $this->cleanText($focusKeyword);
        $secondaryKeywords = array_map([$this, 'cleanText'], $secondaryKeywords);
        
        // Determine image context based on topic and keywords
        $context = $this->determineImageContext($topic, $focusKeyword, $secondaryKeywords);
        
        // Generate base prompt
        $basePrompt = $this->buildBasePrompt($context, $focusKeyword, $options);
        
        // Add style and composition elements
        $styledPrompt = $this->addStyleElements($basePrompt, $context, $options);
        
        // Generate alt text
        $altText = $this->generateAltText($context, $focusKeyword, $secondaryKeywords, $options);
        
        return [
            'prompt' => $styledPrompt,
            'alt_text' => $altText,
            'context' => $context,
            'focus_keyword' => $focusKeyword,
            'secondary_keywords' => $secondaryKeywords,
            'metadata' => [
                'topic' => $topic,
                'style' => $context['style'] ?? 'professional',
                'composition' => $context['composition'] ?? 'centered composition',
                'lighting' => $context['lighting'] ?? 'natural lighting',
                'background' => $context['background'] ?? 'neutral background'
            ]
        ];
    }
    
    /**
     * Generate multiple varied image prompts for the same topic
     *
     * @param string $topic Content topic
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param int $count Number of prompts to generate
     * @param array $options Generation options
     * @return array Array of image prompt data
     */
    public function generateVariedImagePrompts($topic, $focusKeyword, $secondaryKeywords = [], $count = 3, $options = []) {
        $prompts = [];
        $usedContexts = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Ensure variation by avoiding duplicate contexts
            $attempts = 0;
            do {
                $context = $this->determineImageContext($topic, $focusKeyword, $secondaryKeywords, $usedContexts);
                $contextKey = $this->getContextKey($context);
                $attempts++;
            } while (in_array($contextKey, $usedContexts) && $attempts < 10);
            
            $usedContexts[] = $contextKey;
            
            // Generate prompt with this context
            $basePrompt = $this->buildBasePrompt($context, $focusKeyword, $options);
            $styledPrompt = $this->addStyleElements($basePrompt, $context, $options);
            
            // Generate varied alt text
            $altText = $this->generateAltText($context, $focusKeyword, $secondaryKeywords, array_merge($options, ['variation' => $i]));
            
            $prompts[] = [
                'prompt' => $styledPrompt,
                'alt_text' => $altText,
                'context' => $context,
                'focus_keyword' => $focusKeyword,
                'secondary_keywords' => $secondaryKeywords,
                'variation_index' => $i,
                'metadata' => [
                    'topic' => $topic,
                    'style' => $context['style'] ?? 'professional',
                    'composition' => $context['composition'] ?? 'centered composition',
                    'lighting' => $context['lighting'] ?? 'natural lighting',
                    'background' => $context['background'] ?? 'neutral background'
                ]
            ];
        }
        
        return $prompts;
    }
    
    /**
     * Generate alt text with keyword optimization
     *
     * @param array $context Image context information
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param array $options Generation options
     * @return string Optimized alt text
     */
    public function generateAltText($context, $focusKeyword, $secondaryKeywords = [], $options = []) {
        $requireKeyword = $options['require_keyword'] ?? true;
        $maxLength = $options['max_length'] ?? 125; // Standard alt text length limit
        $minLength = $options['min_length'] ?? 10;
        $variation = $options['variation'] ?? 0;
        
        // Build descriptive base
        $description = $this->buildAltTextDescription($context, $variation);
        
        // Integrate keyword naturally
        if ($requireKeyword) {
            $description = $this->integrateKeywordIntoAltText($description, $focusKeyword, $secondaryKeywords);
        }
        
        // Ensure proper length
        $description = $this->adjustAltTextLength($description, $minLength, $maxLength);
        
        // Clean and validate
        $description = $this->cleanAltText($description);
        
        return $description;
    }
    
    /**
     * Determine image context based on topic and keywords
     *
     * @param string $topic Content topic
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @param array $excludeContexts Contexts to avoid for variation
     * @return array Context information
     */
    private function determineImageContext($topic, $focusKeyword, $secondaryKeywords = [], $excludeContexts = []) {
        $topic = strtolower($topic);
        $focusKeyword = strtolower($focusKeyword);
        $allKeywords = array_merge([$focusKeyword], array_map('strtolower', $secondaryKeywords));
        
        // Determine primary subject based on keywords and topic
        $subject = $this->determineImageSubject($topic, $allKeywords);
        
        // Determine appropriate style
        $style = $this->determineImageStyle($topic, $allKeywords, $excludeContexts);
        
        // Determine composition
        $composition = $this->selectRandomElement($this->compositionTypes, $excludeContexts);
        
        // Determine lighting
        $lighting = $this->selectRandomElement($this->lightingConditions, $excludeContexts);
        
        // Determine background
        $background = $this->selectRandomElement($this->backgroundTypes, $excludeContexts);
        
        return [
            'subject' => $subject,
            'style' => $style,
            'composition' => $composition,
            'lighting' => $lighting,
            'background' => $background,
            'topic' => $topic,
            'keywords' => $allKeywords
        ];
    }
    
    /**
     * Determine the main subject for the image
     *
     * @param string $topic Content topic
     * @param array $keywords All keywords
     * @return string Image subject
     */
    private function determineImageSubject($topic, $keywords) {
        // Technology-related subjects
        if ($this->containsAny($topic . ' ' . implode(' ', $keywords), [
            'software', 'app', 'technology', 'digital', 'computer', 'coding', 'programming',
            'website', 'online', 'internet', 'tech', 'ai', 'machine learning', 'data'
        ])) {
            return 'person working on laptop with modern technology setup';
        }
        
        // Business-related subjects
        if ($this->containsAny($topic . ' ' . implode(' ', $keywords), [
            'business', 'marketing', 'strategy', 'management', 'corporate', 'professional',
            'team', 'meeting', 'office', 'work', 'productivity', 'success'
        ])) {
            return 'professional business environment with people collaborating';
        }
        
        // Health and wellness subjects
        if ($this->containsAny($topic . ' ' . implode(' ', $keywords), [
            'health', 'wellness', 'fitness', 'medical', 'healthcare', 'nutrition',
            'exercise', 'mental health', 'wellbeing', 'lifestyle'
        ])) {
            return 'healthy lifestyle scene with wellness elements';
        }
        
        // Education-related subjects
        if ($this->containsAny($topic . ' ' . implode(' ', $keywords), [
            'education', 'learning', 'training', 'course', 'study', 'student',
            'teacher', 'school', 'university', 'knowledge', 'skill'
        ])) {
            return 'educational setting with learning materials and engaged people';
        }
        
        // Finance-related subjects
        if ($this->containsAny($topic . ' ' . implode(' ', $keywords), [
            'finance', 'money', 'investment', 'banking', 'financial', 'budget',
            'savings', 'economy', 'market', 'trading', 'cryptocurrency'
        ])) {
            return 'financial planning scene with charts and professional analysis';
        }
        
        // Default to generic professional scene
        return 'professional scene related to ' . $keywords[0];
    }
    
    /**
     * Determine appropriate image style
     *
     * @param string $topic Content topic
     * @param array $keywords All keywords
     * @param array $excludeStyles Styles to avoid
     * @return string Image style
     */
    private function determineImageStyle($topic, $keywords, $excludeStyles = []) {
        $availableStyles = array_diff($this->imageStyles, $excludeStyles);
        
        // Tech content tends to be modern/clean
        if ($this->containsAny($topic . ' ' . implode(' ', $keywords), [
            'technology', 'software', 'digital', 'ai', 'tech'
        ])) {
            $preferredStyles = array_intersect(['modern', 'clean', 'technical'], $availableStyles);
            if (!empty($preferredStyles)) {
                return $this->selectRandomElement($preferredStyles);
            }
        }
        
        // Business content tends to be professional/corporate
        if ($this->containsAny($topic . ' ' . implode(' ', $keywords), [
            'business', 'corporate', 'professional', 'management'
        ])) {
            $preferredStyles = array_intersect(['professional', 'corporate', 'clean'], $availableStyles);
            if (!empty($preferredStyles)) {
                return $this->selectRandomElement($preferredStyles);
            }
        }
        
        // Creative content can be more vibrant
        if ($this->containsAny($topic . ' ' . implode(' ', $keywords), [
            'creative', 'design', 'art', 'marketing', 'brand'
        ])) {
            $preferredStyles = array_intersect(['creative', 'vibrant', 'modern'], $availableStyles);
            if (!empty($preferredStyles)) {
                return $this->selectRandomElement($preferredStyles);
            }
        }
        
        // Default to professional if available, otherwise random
        return in_array('professional', $availableStyles) ? 'professional' : $this->selectRandomElement($availableStyles);
    }
    
    /**
     * Build base image prompt
     *
     * @param array $context Image context
     * @param string $focusKeyword Primary keyword
     * @param array $options Generation options
     * @return string Base prompt
     */
    private function buildBasePrompt($context, $focusKeyword, $options = []) {
        $subject = $context['subject'];
        $style = $context['style'];
        
        // Build descriptive prompt
        $prompt = "A {$style} image showing {$subject}";
        
        // Add keyword context naturally
        if (!empty($focusKeyword)) {
            $prompt .= " related to {$focusKeyword}";
        }
        
        return $prompt;
    }
    
    /**
     * Add style elements to the prompt
     *
     * @param string $basePrompt Base prompt text
     * @param array $context Image context
     * @param array $options Generation options
     * @return string Enhanced prompt
     */
    private function addStyleElements($basePrompt, $context, $options = []) {
        $prompt = $basePrompt;
        
        // Add composition
        if (!empty($context['composition'])) {
            $prompt .= ", {$context['composition']}";
        }
        
        // Add lighting
        if (!empty($context['lighting'])) {
            $prompt .= ", {$context['lighting']}";
        }
        
        // Add background
        if (!empty($context['background'])) {
            $prompt .= ", {$context['background']}";
        }
        
        // Add quality modifiers
        $prompt .= ", high quality, detailed, professional photography";
        
        return $prompt;
    }
    
    /**
     * Build alt text description
     *
     * @param array $context Image context
     * @param int $variation Variation index for different descriptions
     * @return string Alt text description
     */
    private function buildAltTextDescription($context, $variation = 0) {
        $subject = $context['subject'];
        $style = $context['style'];
        
        // Create varied descriptions based on variation index
        $templates = [
            "A {$style} image of {$subject}",
            "{$style} photograph showing {$subject}",
            "{$style} visual depicting {$subject}",
            "Professional image featuring {$subject}"
        ];
        
        $templateIndex = $variation % count($templates);
        return $templates[$templateIndex];
    }
    
    /**
     * Integrate keyword into alt text naturally
     *
     * @param string $description Base description
     * @param string $focusKeyword Primary keyword
     * @param array $secondaryKeywords Secondary keywords
     * @return string Description with keyword integrated
     */
    private function integrateKeywordIntoAltText($description, $focusKeyword, $secondaryKeywords = []) {
        // Check if keyword is already present
        if (stripos($description, $focusKeyword) !== false) {
            return $description;
        }
        
        // Try to integrate naturally
        $keywordToUse = $focusKeyword;
        
        // Use secondary keyword if it fits better
        foreach ($secondaryKeywords as $secondary) {
            if (strlen($secondary) < strlen($keywordToUse) && !empty($secondary)) {
                $keywordToUse = $secondary;
                break;
            }
        }
        
        // Add keyword naturally to the description
        if (strpos($description, 'related to') === false && strpos($description, 'about') === false) {
            $description .= " related to {$keywordToUse}";
        }
        
        return $description;
    }
    
    /**
     * Adjust alt text length to meet requirements
     *
     * @param string $description Alt text description
     * @param int $minLength Minimum length
     * @param int $maxLength Maximum length
     * @return string Adjusted description
     */
    private function adjustAltTextLength($description, $minLength, $maxLength) {
        $currentLength = strlen($description);
        
        // If too short, add descriptive elements
        if ($currentLength < $minLength) {
            $additions = [
                ' with clear details',
                ' in high quality',
                ' showing professional presentation',
                ' with excellent composition'
            ];
            
            foreach ($additions as $addition) {
                if (strlen($description . $addition) <= $maxLength) {
                    $description .= $addition;
                    if (strlen($description) >= $minLength) {
                        break;
                    }
                }
            }
        }
        
        // If too long, trim intelligently
        if (strlen($description) > $maxLength) {
            $description = $this->trimAltTextIntelligently($description, $maxLength);
        }
        
        return $description;
    }
    
    /**
     * Trim alt text intelligently while preserving meaning
     *
     * @param string $description Alt text to trim
     * @param int $maxLength Maximum allowed length
     * @return string Trimmed description
     */
    private function trimAltTextIntelligently($description, $maxLength) {
        if (strlen($description) <= $maxLength) {
            return $description;
        }
        
        // Remove less important phrases first
        $removablePatterns = [
            '/,?\s+with\s+[^,]+quality[^,]*/',
            '/,?\s+showing\s+[^,]+presentation[^,]*/',
            '/,?\s+in\s+[^,]+composition[^,]*/',
            '/,?\s+featuring\s+[^,]+details[^,]*/'
        ];
        
        foreach ($removablePatterns as $pattern) {
            $trimmed = preg_replace($pattern, '', $description);
            if (strlen($trimmed) <= $maxLength && strlen($trimmed) > 10) {
                return trim($trimmed);
            }
        }
        
        // If still too long, truncate at word boundary
        if (strlen($description) > $maxLength) {
            $description = substr($description, 0, $maxLength);
            $lastSpace = strrpos($description, ' ');
            if ($lastSpace !== false && $lastSpace > $maxLength * 0.8) {
                $description = substr($description, 0, $lastSpace);
            }
        }
        
        return trim($description);
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
        
        // Ensure proper capitalization
        $altText = ucfirst($altText);
        
        // Remove any HTML tags that might have crept in
        $altText = strip_tags($altText);
        
        return $altText;
    }
    
    /**
     * Clean text input
     *
     * @param string $text Text to clean
     * @return string Cleaned text
     */
    private function cleanText($text) {
        return trim(strip_tags($text));
    }
    
    /**
     * Check if text contains any of the specified terms
     *
     * @param string $text Text to search in
     * @param array $terms Terms to search for
     * @return bool True if any term is found
     */
    private function containsAny($text, $terms) {
        $text = strtolower($text);
        foreach ($terms as $term) {
            if (strpos($text, strtolower($term)) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Select random element from array, excluding specified elements
     *
     * @param array $elements Elements to choose from
     * @param array $exclude Elements to exclude
     * @return string Selected element
     */
    private function selectRandomElement($elements, $exclude = []) {
        $available = array_diff($elements, $exclude);
        if (empty($available)) {
            $available = $elements; // Fallback to all elements if none available
        }
        return $available[array_rand($available)];
    }
    
    /**
     * Get context key for variation tracking
     *
     * @param array $context Image context
     * @return string Context key
     */
    private function getContextKey($context) {
        return md5(serialize([
            $context['style'] ?? '',
            $context['composition'] ?? '',
            $context['lighting'] ?? '',
            $context['background'] ?? ''
        ]));
    }
    
    /**
     * Validate image prompt data
     *
     * @param array $promptData Image prompt data to validate
     * @return bool True if valid
     * @throws InvalidArgumentException If data is invalid
     */
    public function validateImagePromptData($promptData) {
        $required = ['prompt', 'alt_text', 'context', 'focus_keyword'];
        
        foreach ($required as $field) {
            if (!isset($promptData[$field]) || empty($promptData[$field])) {
                throw new InvalidArgumentException("Required field '{$field}' is missing or empty");
            }
        }
        
        // Validate alt text length
        $altTextLength = strlen($promptData['alt_text']);
        if ($altTextLength < 10 || $altTextLength > 125) {
            throw new InvalidArgumentException("Alt text length ({$altTextLength}) must be between 10 and 125 characters");
        }
        
        // Validate prompt is not empty
        if (strlen($promptData['prompt']) < 20) {
            throw new InvalidArgumentException("Image prompt is too short");
        }
        
        return true;
    }
}