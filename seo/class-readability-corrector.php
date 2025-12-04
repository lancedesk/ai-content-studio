<?php
/**
 * Readability Corrector Class
 *
 * Automatically corrects readability issues in content including passive voice,
 * long sentences, and missing transition words.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class ReadabilityCorrector
 *
 * Provides automatic correction for common readability issues
 */
class ReadabilityCorrector {
    
    /**
     * @var PassiveVoiceAnalyzer Passive voice analyzer instance
     */
    private $passiveAnalyzer;
    
    /**
     * @var SentenceLengthAnalyzer Sentence length analyzer instance
     */
    private $lengthAnalyzer;
    
    /**
     * @var TransitionWordAnalyzer Transition word analyzer instance
     */
    private $transitionAnalyzer;
    
    /**
     * @var array Common active voice replacements for passive constructions
     */
    private $activeVoicePatterns;
    
    /**
     * @var array Sentence splitting patterns and conjunctions
     */
    private $splittingPatterns;
    
    /**
     * @var array Transition words for insertion
     */
    private $transitionInsertions;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->passiveAnalyzer = new PassiveVoiceAnalyzer();
        $this->lengthAnalyzer = new SentenceLengthAnalyzer();
        $this->transitionAnalyzer = new TransitionWordAnalyzer();
        
        $this->initializePatterns();
    }
    
    /**
     * Initialize correction patterns and rules
     */
    private function initializePatterns() {
        // Active voice conversion patterns
        $this->activeVoicePatterns = [
            // Simple passive to active patterns
            '/\b(was|were)\s+(\w+ed)\s+by\s+([^.!?]+)/i' => '$3 $2',
            '/\b(is|are)\s+(\w+ed)\s+by\s+([^.!?]+)/i' => '$3 $2',
            '/\b(has been|have been)\s+(\w+ed)\s+by\s+([^.!?]+)/i' => '$3 $2',
            
            // Common passive constructions
            '/\bit\s+(was|is)\s+(\w+ed)\s+that/i' => 'researchers $2 that',
            '/\bthe\s+(\w+)\s+(was|were)\s+(\w+ed)/i' => 'someone $3 the $1',
            '/\bmistakes\s+were\s+made/i' => 'we made mistakes',
            '/\bdecisions\s+were\s+made/i' => 'we made decisions',
        ];
        
        // Sentence splitting patterns (conjunctions that can be split)
        $this->splittingPatterns = [
            'and' => [
                'pattern' => '/,\s+and\s+/i',
                'replacement' => '. ',
                'capitalize_next' => true
            ],
            'but' => [
                'pattern' => '/,\s+but\s+/i',
                'replacement' => '. However, ',
                'capitalize_next' => false
            ],
            'however' => [
                'pattern' => '/,\s+however,?\s+/i',
                'replacement' => '. However, ',
                'capitalize_next' => false
            ],
            'because' => [
                'pattern' => '/\s+because\s+/i',
                'replacement' => '. This is because ',
                'capitalize_next' => false
            ]
        ];
        
        // Transition words for insertion
        $this->transitionInsertions = [
            'start' => ['Furthermore', 'Additionally', 'Moreover', 'Also'],
            'contrast' => ['However', 'Nevertheless', 'On the other hand'],
            'result' => ['Therefore', 'Consequently', 'As a result'],
            'example' => ['For example', 'For instance', 'Specifically'],
            'sequence' => ['Next', 'Then', 'Subsequently', 'Finally']
        ];
    }
    
    /**
     * Correct all readability issues in content
     *
     * @param string $content The content to correct
     * @param array $options Correction options
     * @return array Correction result with new content and changes made
     */
    public function correctReadability($content, $options = []) {
        $defaultOptions = [
            'fix_passive_voice' => true,
            'split_long_sentences' => true,
            'add_transitions' => true,
            'max_iterations' => 3
        ];
        
        $options = array_merge($defaultOptions, $options);
        $correctedContent = $content;
        $changes = [];
        $iterations = 0;
        
        while ($iterations < $options['max_iterations']) {
            $iterationChanges = [];
            $contentChanged = false;
            
            // Fix passive voice
            if ($options['fix_passive_voice']) {
                $passiveResult = $this->correctPassiveVoice($correctedContent);
                if ($passiveResult['changed']) {
                    $correctedContent = $passiveResult['content'];
                    $iterationChanges['passive_voice'] = $passiveResult['changes'];
                    $contentChanged = true;
                }
            }
            
            // Split long sentences
            if ($options['split_long_sentences']) {
                $lengthResult = $this->correctSentenceLength($correctedContent);
                if ($lengthResult['changed']) {
                    $correctedContent = $lengthResult['content'];
                    $iterationChanges['sentence_length'] = $lengthResult['changes'];
                    $contentChanged = true;
                }
            }
            
            // Add transition words
            if ($options['add_transitions']) {
                $transitionResult = $this->addTransitionWords($correctedContent);
                if ($transitionResult['changed']) {
                    $correctedContent = $transitionResult['content'];
                    $iterationChanges['transitions'] = $transitionResult['changes'];
                    $contentChanged = true;
                }
            }
            
            if (!empty($iterationChanges)) {
                $changes[] = [
                    'iteration' => $iterations + 1,
                    'changes' => $iterationChanges
                ];
            }
            
            // Stop if no changes were made
            if (!$contentChanged) {
                break;
            }
            
            $iterations++;
        }
        
        return [
            'original_content' => $content,
            'corrected_content' => $correctedContent,
            'changes_made' => $changes,
            'iterations' => $iterations,
            'final_analysis' => $this->analyzeReadability($correctedContent)
        ];
    }
    
    /**
     * Correct passive voice in content
     *
     * @param string $content The content to correct
     * @return array Correction result
     */
    public function correctPassiveVoice($content) {
        $analysis = $this->passiveAnalyzer->analyze($content);
        
        if ($analysis['isCompliant']) {
            return [
                'content' => $content,
                'changed' => false,
                'changes' => []
            ];
        }
        
        $correctedContent = $content;
        $changes = [];
        
        // Process each passive sentence
        foreach ($analysis['passiveSentenceDetails'] as $passiveInfo) {
            $originalSentence = $passiveInfo['sentence'];
            $correctedSentence = $this->convertPassiveToActive($originalSentence);
            
            if ($correctedSentence !== $originalSentence) {
                $correctedContent = str_replace($originalSentence, $correctedSentence, $correctedContent);
                $changes[] = [
                    'type' => 'passive_to_active',
                    'original' => $originalSentence,
                    'corrected' => $correctedSentence,
                    'confidence' => $passiveInfo['confidence']
                ];
            }
        }
        
        return [
            'content' => $correctedContent,
            'changed' => !empty($changes),
            'changes' => $changes
        ];
    }
    
    /**
     * Correct sentence length issues
     *
     * @param string $content The content to correct
     * @return array Correction result
     */
    public function correctSentenceLength($content) {
        $analysis = $this->lengthAnalyzer->analyze($content);
        
        if ($analysis['isCompliant']) {
            return [
                'content' => $content,
                'changed' => false,
                'changes' => []
            ];
        }
        
        $correctedContent = $content;
        $changes = [];
        
        // Process long sentences
        foreach ($analysis['longSentenceDetails'] as $longSentence) {
            if ($longSentence['wordCount'] > 25) { // Only split very long sentences
                $originalSentence = $longSentence['sentence'];
                $splitSentences = $this->splitLongSentence($originalSentence);
                
                if (count($splitSentences) > 1) {
                    $newSentence = implode('. ', $splitSentences) . '.';
                    $correctedContent = str_replace($originalSentence, $newSentence, $correctedContent);
                    
                    $changes[] = [
                        'type' => 'sentence_split',
                        'original' => $originalSentence,
                        'corrected' => $newSentence,
                        'original_word_count' => $longSentence['wordCount'],
                        'split_count' => count($splitSentences)
                    ];
                }
            }
        }
        
        return [
            'content' => $correctedContent,
            'changed' => !empty($changes),
            'changes' => $changes
        ];
    }
    
    /**
     * Add transition words to improve flow
     *
     * @param string $content The content to correct
     * @return array Correction result
     */
    public function addTransitionWords($content) {
        $analysis = $this->transitionAnalyzer->analyze($content);
        
        if ($analysis['isCompliant']) {
            return [
                'content' => $content,
                'changed' => false,
                'changes' => []
            ];
        }
        
        $sentences = $this->getSentences($content);
        $correctedSentences = [];
        $changes = [];
        
        foreach ($sentences as $index => $sentence) {
            $trimmedSentence = trim($sentence);
            
            // Check if this sentence needs a transition
            if ($this->needsTransition($trimmedSentence, $index, $sentences)) {
                $transitionType = $this->determineTransitionType($trimmedSentence, $index, $sentences);
                $transition = $this->selectTransition($transitionType);
                
                $newSentence = $transition . ', ' . lcfirst($trimmedSentence);
                $correctedSentences[] = $newSentence;
                
                $changes[] = [
                    'type' => 'transition_added',
                    'original' => $trimmedSentence,
                    'corrected' => $newSentence,
                    'transition' => $transition,
                    'transition_type' => $transitionType,
                    'position' => $index
                ];
            } else {
                $correctedSentences[] = $trimmedSentence;
            }
        }
        
        $correctedContent = implode('. ', $correctedSentences);
        
        return [
            'content' => $correctedContent,
            'changed' => !empty($changes),
            'changes' => $changes
        ];
    }
    
    /**
     * Convert passive voice sentence to active voice
     *
     * @param string $sentence The sentence to convert
     * @return string Converted sentence
     */
    private function convertPassiveToActive($sentence) {
        $corrected = $sentence;
        
        // Apply passive to active patterns
        foreach ($this->activeVoicePatterns as $pattern => $replacement) {
            $corrected = preg_replace($pattern, $replacement, $corrected);
            
            // If a change was made, clean up and return
            if ($corrected !== $sentence) {
                $corrected = $this->cleanupSentence($corrected);
                break;
            }
        }
        
        // If no pattern matched, try generic conversion
        if ($corrected === $sentence) {
            $corrected = $this->genericPassiveToActive($sentence);
        }
        
        return $corrected;
    }
    
    /**
     * Generic passive to active conversion
     *
     * @param string $sentence The sentence to convert
     * @return string Converted sentence
     */
    private function genericPassiveToActive($sentence) {
        // Simple heuristic: if we can't identify the actor, use a generic subject
        $patterns = [
            '/\b(was|were|is|are)\s+(\w+ed)\b/i' => 'researchers $2',
            '/\b(has been|have been)\s+(\w+ed)\b/i' => 'studies have $2',
            '/\bit\s+(was|is)\s+(\w+ed)\b/i' => 'research $2'
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $sentence, 1);
            if ($result !== $sentence) {
                return $this->cleanupSentence($result);
            }
        }
        
        return $sentence;
    }
    
    /**
     * Split a long sentence into shorter ones
     *
     * @param string $sentence The sentence to split
     * @return array Array of shorter sentences
     */
    private function splitLongSentence($sentence) {
        // Try to split at natural break points
        foreach ($this->splittingPatterns as $conjunction => $config) {
            if (preg_match($config['pattern'], $sentence)) {
                $parts = preg_split($config['pattern'], $sentence, 2);
                
                if (count($parts) === 2) {
                    $firstPart = trim($parts[0]);
                    $secondPart = trim($parts[1]);
                    
                    // Capitalize second part if needed
                    if ($config['capitalize_next']) {
                        $secondPart = ucfirst($secondPart);
                    }
                    
                    // Remove the replacement text if it was added
                    if (!$config['capitalize_next'] && strpos($config['replacement'], ',') !== false) {
                        $secondPart = trim(str_replace($config['replacement'], '', $secondPart));
                        $secondPart = ucfirst($secondPart);
                    }
                    
                    return [$firstPart, $secondPart];
                }
            }
        }
        
        // If no natural break point, try to split at commas
        if (substr_count($sentence, ',') >= 2) {
            $parts = explode(',', $sentence, 2);
            if (count($parts) === 2) {
                $firstPart = trim($parts[0]);
                $secondPart = trim($parts[1]);
                $secondPart = ucfirst($secondPart);
                
                return [$firstPart, $secondPart];
            }
        }
        
        // Return original sentence if no good split point found
        return [$sentence];
    }
    
    /**
     * Check if a sentence needs a transition word
     *
     * @param string $sentence The sentence to check
     * @param int $index Position in the text
     * @param array $sentences All sentences
     * @return bool True if transition is needed
     */
    private function needsTransition($sentence, $index, $sentences) {
        // Don't add transitions to first sentence
        if ($index === 0) {
            return false;
        }
        
        // Check if sentence already has a transition
        $transitionInfo = $this->transitionAnalyzer->analyze($sentence);
        if ($transitionInfo['transitionPercentage'] > 0) {
            return false;
        }
        
        // Add transition to every 3rd or 4th sentence without one
        return ($index % 3 === 0 || $index % 4 === 0);
    }
    
    /**
     * Determine what type of transition is needed
     *
     * @param string $sentence Current sentence
     * @param int $index Position in text
     * @param array $sentences All sentences
     * @return string Transition type
     */
    private function determineTransitionType($sentence, $index, $sentences) {
        $lowerSentence = strtolower($sentence);
        
        // Analyze content to determine appropriate transition
        if (strpos($lowerSentence, 'example') !== false || strpos($lowerSentence, 'instance') !== false) {
            return 'example';
        }
        
        if (strpos($lowerSentence, 'result') !== false || strpos($lowerSentence, 'therefore') !== false) {
            return 'result';
        }
        
        if (strpos($lowerSentence, 'however') !== false || strpos($lowerSentence, 'but') !== false) {
            return 'contrast';
        }
        
        // Default to additive transitions
        return 'start';
    }
    
    /**
     * Select an appropriate transition word
     *
     * @param string $type Type of transition needed
     * @return string Selected transition word
     */
    private function selectTransition($type) {
        if (isset($this->transitionInsertions[$type])) {
            $options = $this->transitionInsertions[$type];
            return $options[array_rand($options)];
        }
        
        return 'Additionally';
    }
    
    /**
     * Clean up a sentence after correction
     *
     * @param string $sentence The sentence to clean
     * @return string Cleaned sentence
     */
    private function cleanupSentence($sentence) {
        // Remove double spaces
        $sentence = preg_replace('/\s+/', ' ', $sentence);
        
        // Ensure proper capitalization
        $sentence = ucfirst(trim($sentence));
        
        // Ensure sentence ends with punctuation
        if (!preg_match('/[.!?]$/', $sentence)) {
            $sentence .= '.';
        }
        
        return $sentence;
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
     * Analyze readability of content
     *
     * @param string $content The content to analyze
     * @return array Readability analysis
     */
    public function analyzeReadability($content) {
        return [
            'passive_voice' => $this->passiveAnalyzer->analyze($content),
            'sentence_length' => $this->lengthAnalyzer->analyze($content),
            'transitions' => $this->transitionAnalyzer->analyze($content)
        ];
    }
    
    /**
     * Check if content meets all readability requirements
     *
     * @param string $content The content to check
     * @return bool True if all requirements are met
     */
    public function isReadabilityCompliant($content) {
        $analysis = $this->analyzeReadability($content);
        
        return $analysis['passive_voice']['isCompliant'] &&
               $analysis['sentence_length']['isCompliant'] &&
               $analysis['transitions']['isCompliant'];
    }
    
    /**
     * Get readability score for content
     *
     * @param string $content The content to score
     * @return array Readability score breakdown
     */
    public function getReadabilityScore($content) {
        $analysis = $this->analyzeReadability($content);
        
        $scores = [
            'passive_voice' => $analysis['passive_voice']['isCompliant'] ? 100 : max(0, 100 - $analysis['passive_voice']['passivePercentage'] * 2),
            'sentence_length' => $analysis['sentence_length']['isCompliant'] ? 100 : max(0, 100 - $analysis['sentence_length']['longSentencePercentage']),
            'transitions' => min(100, $analysis['transitions']['transitionPercentage'] * 3.33) // Scale to 100
        ];
        
        $overallScore = array_sum($scores) / count($scores);
        
        return [
            'overall_score' => $overallScore,
            'component_scores' => $scores,
            'is_compliant' => $overallScore >= 80,
            'analysis' => $analysis
        ];
    }
}