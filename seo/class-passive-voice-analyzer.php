<?php
/**
 * Passive Voice Analyzer Class
 *
 * Detects and analyzes passive voice usage in content for readability compliance.
 * Implements sophisticated pattern matching to identify passive voice constructions.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class PassiveVoiceAnalyzer
 *
 * Analyzes content for passive voice usage and provides detailed metrics
 */
class PassiveVoiceAnalyzer {
    
    /**
     * @var float Maximum allowed passive voice percentage
     */
    private $maxPassivePercentage;
    
    /**
     * @var array Common passive voice indicators
     */
    private $passiveIndicators;
    
    /**
     * @var array Past participle patterns
     */
    private $pastParticiplePatterns;
    
    /**
     * Constructor
     *
     * @param float $maxPassivePercentage Maximum allowed passive voice percentage (default: 10.0)
     */
    public function __construct($maxPassivePercentage = 10.0) {
        $this->maxPassivePercentage = $maxPassivePercentage;
        
        $this->passiveIndicators = [
            'am', 'is', 'are', 'was', 'were', 'being', 'been',
            'have been', 'has been', 'had been', 'will be',
            'would be', 'could be', 'should be', 'might be'
        ];
        
        $this->pastParticiplePatterns = [
            '/\b(am|is|are|was|were|being|been)\s+\w+ed\b/i',
            '/\b(have|has|had)\s+been\s+\w+ed\b/i',
            '/\b(will|would|could|should|might)\s+be\s+\w+ed\b/i',
            '/\b(am|is|are|was|were|being|been)\s+\w+en\b/i',
            '/\b(have|has|had)\s+been\s+\w+en\b/i',
            '/\b(will|would|could|should|might)\s+be\s+\w+en\b/i'
        ];
    }
    
    /**
     * Analyze passive voice usage in content
     *
     * @param string $content The content to analyze
     * @return array Detailed passive voice analysis
     */
    public function analyze($content) {
        $sentences = $this->getSentences($content);
        $totalSentences = count($sentences);
        
        if ($totalSentences === 0) {
            return $this->createEmptyResult();
        }
        
        $passiveSentences = [];
        $passiveCount = 0;
        
        foreach ($sentences as $index => $sentence) {
            $passiveInfo = $this->analyzePassiveInSentence($sentence);
            if ($passiveInfo['isPassive']) {
                $passiveSentences[] = [
                    'index' => $index,
                    'sentence' => trim($sentence),
                    'patterns' => $passiveInfo['patterns'],
                    'confidence' => $passiveInfo['confidence']
                ];
                $passiveCount++;
            }
        }
        
        $passivePercentage = ($passiveCount / $totalSentences) * 100;
        
        return [
            'totalSentences' => $totalSentences,
            'passiveSentences' => $passiveCount,
            'passivePercentage' => $passivePercentage,
            'isCompliant' => $passivePercentage <= $this->maxPassivePercentage,
            'maxAllowed' => $this->maxPassivePercentage,
            'passiveSentenceDetails' => $passiveSentences,
            'suggestions' => $this->generateSuggestions($passiveSentences, $passivePercentage)
        ];
    }
    
    /**
     * Check if content meets passive voice requirements
     *
     * @param string $content The content to check
     * @return bool True if passive voice is within acceptable limits
     */
    public function isCompliant($content) {
        $analysis = $this->analyze($content);
        return $analysis['isCompliant'];
    }
    
    /**
     * Get passive voice percentage for content
     *
     * @param string $content The content to analyze
     * @return float Passive voice percentage
     */
    public function getPassivePercentage($content) {
        $analysis = $this->analyze($content);
        return $analysis['passivePercentage'];
    }
    
    /**
     * Analyze a single sentence for passive voice
     *
     * @param string $sentence The sentence to analyze
     * @return array Analysis result with patterns and confidence
     */
    private function analyzePassiveInSentence($sentence) {
        $matchedPatterns = [];
        $confidence = 0;
        
        // Check each passive voice pattern
        foreach ($this->pastParticiplePatterns as $pattern) {
            if (preg_match($pattern, $sentence, $matches)) {
                $matchedPatterns[] = [
                    'pattern' => $pattern,
                    'match' => $matches[0],
                    'position' => strpos($sentence, $matches[0])
                ];
                $confidence += 0.8; // High confidence for pattern matches
            }
        }
        
        // Additional heuristics for edge cases
        if (empty($matchedPatterns)) {
            // Check for irregular past participles
            $irregularPatterns = [
                '/\b(am|is|are|was|were|being|been)\s+(given|taken|written|spoken|broken|chosen|driven|eaten|fallen|forgotten|hidden|known|seen|shown|thrown|worn)\b/i',
                '/\b(have|has|had)\s+been\s+(given|taken|written|spoken|broken|chosen|driven|eaten|fallen|forgotten|hidden|known|seen|shown|thrown|worn)\b/i'
            ];
            
            foreach ($irregularPatterns as $pattern) {
                if (preg_match($pattern, $sentence, $matches)) {
                    $matchedPatterns[] = [
                        'pattern' => $pattern,
                        'match' => $matches[0],
                        'position' => strpos($sentence, $matches[0])
                    ];
                    $confidence += 0.9; // Very high confidence for irregular forms
                }
            }
        }
        
        return [
            'isPassive' => !empty($matchedPatterns),
            'patterns' => $matchedPatterns,
            'confidence' => min($confidence, 1.0)
        ];
    }
    
    /**
     * Generate suggestions for reducing passive voice
     *
     * @param array $passiveSentences Array of passive sentences
     * @param float $passivePercentage Current passive voice percentage
     * @return array Array of improvement suggestions
     */
    private function generateSuggestions($passiveSentences, $passivePercentage) {
        $suggestions = [];
        
        if ($passivePercentage > $this->maxPassivePercentage) {
            $excessPercentage = $passivePercentage - $this->maxPassivePercentage;
            $suggestions[] = sprintf(
                'Passive voice usage is %.1f%% above the recommended maximum of %.1f%%. Consider rewriting some sentences in active voice.',
                $excessPercentage,
                $this->maxPassivePercentage
            );
            
            // Provide specific examples if available
            if (!empty($passiveSentences)) {
                $suggestions[] = 'Examples of passive sentences to rewrite:';
                $exampleCount = min(3, count($passiveSentences));
                for ($i = 0; $i < $exampleCount; $i++) {
                    $sentence = $passiveSentences[$i]['sentence'];
                    $suggestions[] = sprintf('• "%s"', substr($sentence, 0, 100) . (strlen($sentence) > 100 ? '...' : ''));
                }
            }
            
            $suggestions[] = 'Tips for converting to active voice: Identify the actor performing the action and make them the subject of the sentence.';
        } else {
            $suggestions[] = sprintf(
                'Good job! Passive voice usage (%.1f%%) is within the recommended limit of %.1f%%.',
                $passivePercentage,
                $this->maxPassivePercentage
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Get sentences from content
     *
     * @param string $content The content to split into sentences
     * @return array Array of sentences
     */
    private function getSentences($content) {
        // Remove HTML tags for analysis
        $cleanContent = strip_tags($content);
        
        // Split by sentence endings, preserving the sentence structure
        $sentences = preg_split('/[.!?]+/', $cleanContent, -1, PREG_SPLIT_NO_EMPTY);
        
        // Clean up sentences and filter out very short ones
        $cleanSentences = [];
        foreach ($sentences as $sentence) {
            $trimmed = trim($sentence);
            if (strlen($trimmed) > 10) { // Ignore very short fragments
                $cleanSentences[] = $trimmed;
            }
        }
        
        return $cleanSentences;
    }
    
    /**
     * Create empty result structure
     *
     * @return array Empty analysis result
     */
    private function createEmptyResult() {
        return [
            'totalSentences' => 0,
            'passiveSentences' => 0,
            'passivePercentage' => 0.0,
            'isCompliant' => true,
            'maxAllowed' => $this->maxPassivePercentage,
            'passiveSentenceDetails' => [],
            'suggestions' => ['No content to analyze.']
        ];
    }
    
    /**
     * Update maximum allowed passive voice percentage
     *
     * @param float $maxPercentage New maximum percentage
     */
    public function setMaxPassivePercentage($maxPercentage) {
        $this->maxPassivePercentage = $maxPercentage;
    }
    
    /**
     * Get current maximum allowed passive voice percentage
     *
     * @return float Current maximum percentage
     */
    public function getMaxPassivePercentage() {
        return $this->maxPassivePercentage;
    }
}