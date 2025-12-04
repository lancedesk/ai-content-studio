<?php
/**
 * Title Optimization Engine
 *
 * Generates unique, SEO-optimized titles with keyword inclusion,
 * character limit enforcement, and content variation algorithms.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Title_Optimization_Engine
 *
 * Handles title generation, optimization, and variation for SEO compliance
 * while ensuring uniqueness and proper keyword inclusion.
 */
class Title_Optimization_Engine {

    /**
     * Maximum character limit for titles (SEO best practice)
     * 
     * @var int
     */
    private $max_title_length = 66;

    /**
     * Title uniqueness validator instance
     * 
     * @var Title_Uniqueness_Validator
     */
    private $uniqueness_validator;

    /**
     * Title variation templates for different approaches
     * 
     * @var array
     */
    private $title_templates = [
        'how_to' => [
            'How to {keyword} in {year}',
            'The Complete Guide to {keyword}',
            '{keyword}: A Step-by-Step Guide',
            'Master {keyword} with These Tips'
        ],
        'listicle' => [
            '{number} {keyword} Tips for {audience}',
            'Top {number} {keyword} Strategies',
            '{number} Ways to Improve Your {keyword}',
            'Best {keyword} Practices: {number} Tips'
        ],
        'comparison' => [
            '{keyword} vs {alternative}: Which is Better?',
            'Comparing {keyword} Options in {year}',
            '{keyword} Comparison: Find the Best Option',
            'The Ultimate {keyword} Comparison Guide'
        ],
        'problem_solution' => [
            'Solve Your {keyword} Problems Today',
            'Fix {keyword} Issues with These Solutions',
            'Common {keyword} Problems and Solutions',
            'Troubleshooting {keyword}: Expert Solutions'
        ],
        'benefits' => [
            'Benefits of {keyword} for {audience}',
            'Why {keyword} Matters in {year}',
            'The Power of {keyword}: Key Benefits',
            'Transform Your {area} with {keyword}'
        ],
        'beginner' => [
            '{keyword} for Beginners: Start Here',
            'Getting Started with {keyword}',
            '{keyword} Basics: What You Need to Know',
            'Introduction to {keyword}: A Beginner\'s Guide'
        ]
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->uniqueness_validator = new Title_Uniqueness_Validator();
    }

    /**
     * Generate optimized title with uniqueness validation
     *
     * @param array $params Title generation parameters
     * @return array Generated title with optimization details
     */
    public function generate_optimized_title($params) {
        $defaults = [
            'focus_keyword' => '',
            'topic' => '',
            'content_type' => 'how_to',
            'target_audience' => '',
            'year' => date('Y'),
            'max_attempts' => 10,
            'exclude_post_id' => null
        ];

        $params = array_merge($defaults, $params);

        if (empty($params['focus_keyword'])) {
            return [
                'success' => false,
                'error' => 'Focus keyword is required for title generation',
                'title' => null
            ];
        }

        $attempts = 0;
        $generated_titles = [];

        while ($attempts < $params['max_attempts']) {
            $title = $this->create_title_variation($params, $attempts);
            
            if (!$title) {
                $attempts++;
                continue;
            }

            // Optimize title length and keyword placement
            $optimized_title = $this->optimize_title_length($title, $params['focus_keyword']);
            
            // Validate uniqueness
            $uniqueness_result = $this->uniqueness_validator->validate_title_uniqueness(
                $optimized_title, 
                $params['exclude_post_id']
            );

            // Validate basic requirements
            $requirements_result = $this->uniqueness_validator->validate_title_requirements(
                $optimized_title, 
                $params['focus_keyword']
            );

            $generated_titles[] = [
                'title' => $optimized_title,
                'attempt' => $attempts + 1,
                'is_unique' => $uniqueness_result['is_unique'],
                'meets_requirements' => $requirements_result['is_valid'],
                'character_count' => strlen($optimized_title),
                'similar_titles' => $uniqueness_result['similar_titles'],
                'errors' => array_merge(
                    $uniqueness_result['is_unique'] ? [] : [$uniqueness_result['error']],
                    $requirements_result['errors']
                )
            ];

            // If title is unique and meets requirements, return success
            if ($uniqueness_result['is_unique'] && $requirements_result['is_valid']) {
                return [
                    'success' => true,
                    'title' => $optimized_title,
                    'attempts' => $attempts + 1,
                    'character_count' => strlen($optimized_title),
                    'all_attempts' => $generated_titles
                ];
            }

            $attempts++;
        }

        // If no unique title was generated, return the best attempt
        $best_title = $this->select_best_title($generated_titles);

        return [
            'success' => false,
            'error' => 'Could not generate unique title after ' . $params['max_attempts'] . ' attempts',
            'title' => $best_title['title'],
            'attempts' => $params['max_attempts'],
            'character_count' => $best_title['character_count'],
            'issues' => $best_title['errors'],
            'all_attempts' => $generated_titles
        ];
    }

    /**
     * Create title variation based on parameters and attempt number
     *
     * @param array $params Title generation parameters
     * @param int $attempt_number Current attempt number for variation
     * @return string|null Generated title or null if failed
     */
    private function create_title_variation($params, $attempt_number) {
        $content_type = $params['content_type'];
        $templates = $this->title_templates[$content_type] ?? $this->title_templates['how_to'];
        
        // Select template based on attempt number to ensure variation
        $template_index = $attempt_number % count($templates);
        $template = $templates[$template_index];

        // Prepare replacement variables
        $replacements = [
            '{keyword}' => $params['focus_keyword'],
            '{topic}' => $params['topic'] ?: $params['focus_keyword'],
            '{audience}' => $params['target_audience'] ?: 'Everyone',
            '{year}' => $params['year'],
            '{number}' => $this->get_variation_number($attempt_number),
            '{alternative}' => $this->get_keyword_alternative($params['focus_keyword']),
            '{area}' => $this->get_topic_area($params['focus_keyword'])
        ];

        // Apply replacements
        $title = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Add variation modifiers for uniqueness
        if ($attempt_number > 0) {
            $title = $this->add_variation_modifier($title, $attempt_number);
        }

        return $title;
    }

    /**
     * Optimize title length while preserving keyword and meaning
     *
     * @param string $title Original title
     * @param string $focus_keyword Focus keyword to preserve
     * @return string Optimized title
     */
    private function optimize_title_length($title, $focus_keyword) {
        if (strlen($title) <= $this->max_title_length) {
            return $title;
        }

        // Strategy 1: Remove unnecessary words
        $optimized = $this->remove_filler_words($title, $focus_keyword);
        if (strlen($optimized) <= $this->max_title_length) {
            return $optimized;
        }

        // Strategy 2: Shorten phrases
        $optimized = $this->shorten_phrases($optimized, $focus_keyword);
        if (strlen($optimized) <= $this->max_title_length) {
            return $optimized;
        }

        // Strategy 3: Truncate while preserving keyword
        return $this->truncate_preserving_keyword($optimized, $focus_keyword);
    }

    /**
     * Remove filler words from title while preserving keyword
     *
     * @param string $title Original title
     * @param string $focus_keyword Focus keyword to preserve
     * @return string Title with filler words removed
     */
    private function remove_filler_words($title, $focus_keyword) {
        $filler_words = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 
            'of', 'with', 'by', 'very', 'really', 'quite', 'rather', 'pretty'
        ];

        $words = explode(' ', $title);
        $keyword_words = explode(' ', strtolower($focus_keyword));
        
        $filtered_words = array_filter($words, function($word) use ($filler_words, $keyword_words) {
            $word_lower = strtolower(trim($word, '.,!?:;'));
            // Keep word if it's part of the keyword or not a filler word
            return in_array($word_lower, $keyword_words) || !in_array($word_lower, $filler_words);
        });

        return implode(' ', $filtered_words);
    }

    /**
     * Shorten common phrases in title
     *
     * @param string $title Original title
     * @param string $focus_keyword Focus keyword to preserve
     * @return string Title with shortened phrases
     */
    private function shorten_phrases($title, $focus_keyword) {
        $phrase_replacements = [
            'Step-by-Step Guide' => 'Guide',
            'Complete Guide' => 'Guide',
            'Ultimate Guide' => 'Guide',
            'Comprehensive Guide' => 'Guide',
            'Everything You Need to Know' => 'Complete Guide',
            'A Beginner\'s Guide' => 'Beginner Guide',
            'for Beginners' => 'Basics',
            'in ' . date('Y') => '',
            'Tips and Tricks' => 'Tips',
            'Best Practices' => 'Best Tips'
        ];

        foreach ($phrase_replacements as $long_phrase => $short_phrase) {
            $title = str_ireplace($long_phrase, $short_phrase, $title);
        }

        return $title;
    }

    /**
     * Truncate title while preserving keyword
     *
     * @param string $title Original title
     * @param string $focus_keyword Focus keyword to preserve
     * @return string Truncated title
     */
    private function truncate_preserving_keyword($title, $focus_keyword) {
        $keyword_pos = stripos($title, $focus_keyword);
        
        if ($keyword_pos === false) {
            // If keyword not found, just truncate
            return substr($title, 0, $this->max_title_length);
        }

        $keyword_length = strlen($focus_keyword);
        $available_space = $this->max_title_length - $keyword_length;
        
        // Try to keep some context around the keyword
        $before_keyword = substr($title, 0, $keyword_pos);
        $after_keyword = substr($title, $keyword_pos + $keyword_length);
        
        $before_length = min(strlen($before_keyword), intval($available_space * 0.4));
        $after_length = min(strlen($after_keyword), $available_space - $before_length);
        
        $truncated_before = $before_length < strlen($before_keyword) 
            ? substr($before_keyword, -$before_length) 
            : $before_keyword;
            
        $truncated_after = $after_length < strlen($after_keyword) 
            ? substr($after_keyword, 0, $after_length) 
            : $after_keyword;

        return trim($truncated_before . $focus_keyword . $truncated_after);
    }

    /**
     * Get variation number for listicle-style titles
     *
     * @param int $attempt_number Current attempt number
     * @return int Variation number
     */
    private function get_variation_number($attempt_number) {
        $numbers = [5, 7, 10, 15, 20, 12, 8, 25, 30, 50];
        return $numbers[$attempt_number % count($numbers)];
    }

    /**
     * Get alternative term for keyword
     *
     * @param string $keyword Original keyword
     * @return string Alternative term
     */
    private function get_keyword_alternative($keyword) {
        // Simple alternatives - in a real implementation, this could use a thesaurus API
        $alternatives = [
            'seo' => 'search optimization',
            'marketing' => 'promotion',
            'content' => 'articles',
            'blog' => 'website',
            'social media' => 'social platforms',
            'email' => 'newsletters',
            'wordpress' => 'website platform'
        ];

        $keyword_lower = strtolower($keyword);
        return $alternatives[$keyword_lower] ?? $keyword;
    }

    /**
     * Get topic area for keyword
     *
     * @param string $keyword Original keyword
     * @return string Topic area
     */
    private function get_topic_area($keyword) {
        // Simple topic mapping
        $areas = [
            'seo' => 'website',
            'marketing' => 'business',
            'content' => 'writing',
            'blog' => 'website',
            'social media' => 'online presence',
            'email' => 'communication',
            'wordpress' => 'website'
        ];

        $keyword_lower = strtolower($keyword);
        return $areas[$keyword_lower] ?? 'strategy';
    }

    /**
     * Add variation modifier to make title more unique
     *
     * @param string $title Original title
     * @param int $attempt_number Current attempt number
     * @return string Modified title
     */
    private function add_variation_modifier($title, $attempt_number) {
        $modifiers = [
            'Expert Tips',
            'Pro Strategies',
            'Advanced Methods',
            'Proven Techniques',
            'Essential Guide',
            'Quick Start',
            'Master Class',
            'Deep Dive',
            'Insider Secrets',
            'Best Practices'
        ];

        $modifier = $modifiers[$attempt_number % count($modifiers)];
        
        // Add modifier at the beginning or end based on attempt number
        if ($attempt_number % 2 === 0) {
            return $modifier . ': ' . $title;
        } else {
            return $title . ' - ' . $modifier;
        }
    }

    /**
     * Select the best title from generated attempts
     *
     * @param array $generated_titles Array of generated title attempts
     * @return array Best title attempt
     */
    private function select_best_title($generated_titles) {
        if (empty($generated_titles)) {
            return null;
        }

        // Scoring criteria (higher is better)
        $scored_titles = array_map(function($title_data) {
            $score = 0;
            
            // Prefer unique titles
            if ($title_data['is_unique']) {
                $score += 100;
            }
            
            // Prefer titles that meet requirements
            if ($title_data['meets_requirements']) {
                $score += 50;
            }
            
            // Prefer shorter titles (within limits)
            if ($title_data['character_count'] <= 60) {
                $score += 20;
            } elseif ($title_data['character_count'] <= 66) {
                $score += 10;
            }
            
            // Penalize titles with many errors
            $score -= count($title_data['errors']) * 10;
            
            $title_data['score'] = $score;
            return $title_data;
        }, $generated_titles);

        // Sort by score (highest first)
        usort($scored_titles, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $scored_titles[0];
    }

    /**
     * Generate content variation for similar topics
     *
     * @param array $params Content variation parameters
     * @return array Variation suggestions
     */
    public function generate_content_variations($params) {
        $defaults = [
            'base_topic' => '',
            'focus_keyword' => '',
            'existing_angles' => [],
            'target_audience' => '',
            'content_types' => ['how_to', 'listicle', 'comparison', 'benefits']
        ];

        $params = array_merge($defaults, $params);

        $variations = [];

        foreach ($params['content_types'] as $content_type) {
            $variation_params = array_merge($params, [
                'content_type' => $content_type,
                'topic' => $this->create_topic_variation($params['base_topic'], $content_type)
            ]);

            $title_result = $this->generate_optimized_title($variation_params);
            
            if ($title_result['success']) {
                $variations[] = [
                    'content_type' => $content_type,
                    'title' => $title_result['title'],
                    'angle' => $this->get_content_angle($content_type),
                    'focus_points' => $this->get_focus_points($content_type, $params['focus_keyword']),
                    'target_audience' => $this->get_audience_variation($params['target_audience'], $content_type)
                ];
            }
        }

        return [
            'success' => !empty($variations),
            'variations' => $variations,
            'total_variations' => count($variations)
        ];
    }

    /**
     * Create topic variation based on content type
     *
     * @param string $base_topic Base topic
     * @param string $content_type Content type
     * @return string Varied topic
     */
    private function create_topic_variation($base_topic, $content_type) {
        $variations = [
            'how_to' => $base_topic . ' implementation',
            'listicle' => $base_topic . ' strategies',
            'comparison' => $base_topic . ' options',
            'benefits' => $base_topic . ' advantages',
            'problem_solution' => $base_topic . ' challenges',
            'beginner' => $base_topic . ' fundamentals'
        ];

        return $variations[$content_type] ?? $base_topic;
    }

    /**
     * Get content angle for content type
     *
     * @param string $content_type Content type
     * @return string Content angle description
     */
    private function get_content_angle($content_type) {
        $angles = [
            'how_to' => 'Step-by-step instructional approach',
            'listicle' => 'List-based informational format',
            'comparison' => 'Comparative analysis approach',
            'benefits' => 'Value-focused persuasive angle',
            'problem_solution' => 'Problem-solving methodology',
            'beginner' => 'Introductory educational approach'
        ];

        return $angles[$content_type] ?? 'General informational approach';
    }

    /**
     * Get focus points for content type and keyword
     *
     * @param string $content_type Content type
     * @param string $keyword Focus keyword
     * @return array Focus points
     */
    private function get_focus_points($content_type, $keyword) {
        $base_points = [
            'how_to' => ['Implementation steps', 'Best practices', 'Common mistakes', 'Tools needed'],
            'listicle' => ['Key strategies', 'Practical tips', 'Expert recommendations', 'Action items'],
            'comparison' => ['Feature analysis', 'Pros and cons', 'Use cases', 'Recommendations'],
            'benefits' => ['Key advantages', 'ROI impact', 'Success stories', 'Value proposition'],
            'problem_solution' => ['Common issues', 'Root causes', 'Solution methods', 'Prevention tips'],
            'beginner' => ['Basic concepts', 'Getting started', 'Essential knowledge', 'First steps']
        ];

        return $base_points[$content_type] ?? ['General information', 'Key concepts', 'Practical advice'];
    }

    /**
     * Get audience variation based on content type
     *
     * @param string $base_audience Base target audience
     * @param string $content_type Content type
     * @return string Varied audience
     */
    private function get_audience_variation($base_audience, $content_type) {
        if (empty($base_audience)) {
            $default_audiences = [
                'how_to' => 'Practitioners',
                'listicle' => 'Professionals',
                'comparison' => 'Decision makers',
                'benefits' => 'Business owners',
                'problem_solution' => 'Problem solvers',
                'beginner' => 'Newcomers'
            ];
            return $default_audiences[$content_type] ?? 'General audience';
        }

        return $base_audience;
    }

    /**
     * Set maximum title length
     *
     * @param int $length Maximum character length
     * @return bool True if set successfully
     */
    public function set_max_title_length($length) {
        if ($length > 0 && $length <= 200) {
            $this->max_title_length = $length;
            return true;
        }
        return false;
    }

    /**
     * Get maximum title length
     *
     * @return int Maximum title length
     */
    public function get_max_title_length() {
        return $this->max_title_length;
    }
}