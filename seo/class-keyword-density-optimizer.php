<?php
/**
 * Keyword Density Optimizer
 *
 * Automatically optimizes keyword density by adding/removing keywords and rewriting subheadings.
 *
 * @package AI_Content_Studio
 * @subpackage SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-keyword-density-calculator.php';

/**
 * Class Keyword_Density_Optimizer
 *
 * Handles automatic optimization of keyword density in content.
 */
class Keyword_Density_Optimizer {

    /**
     * @var Keyword_Density_Calculator
     */
    private $calculator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->calculator = new Keyword_Density_Calculator();
    }

    /**
     * Optimize keyword density in content
     *
     * @param string $content The content to optimize
     * @param string $focus_keyword The primary keyword
     * @param array $synonyms Array of synonym keywords
     * @param float $target_density Target density percentage (default 1.5%)
     * @param float $min_density Minimum acceptable density (default 0.5%)
     * @param float $max_density Maximum acceptable density (default 2.5%)
     * @return array Optimized content and metrics
     */
    public function optimize_content($content, $focus_keyword, $synonyms = [], $target_density = 1.5, $min_density = 0.5, $max_density = 2.5) {
        if (empty($content) || empty($focus_keyword)) {
            return [
                'content' => $content,
                'optimized' => false,
                'changes_made' => [],
                'final_metrics' => []
            ];
        }

        $changes_made = [];
        $optimized_content = $content;
        
        // Calculate initial density
        $initial_metrics = $this->calculator->calculate_density($optimized_content, $focus_keyword, $synonyms);
        $current_density = $initial_metrics['overall_density'];
        
        // Optimize overall keyword density
        if ($current_density < $min_density) {
            // Use a very conservative target to account for the fact that adding keywords also adds words
            // Since each addition can result in ~3x higher density than calculated, use a much lower target
            $safe_target = min($target_density * 0.4, $max_density * 0.4);
            $result = $this->increase_keyword_density($optimized_content, $focus_keyword, $synonyms, $safe_target);
            $optimized_content = $result['content'];
            $changes_made = array_merge($changes_made, $result['changes']);
        } elseif ($current_density > $max_density) {
            $result = $this->reduce_keyword_density($optimized_content, $focus_keyword, $synonyms, $target_density);
            $optimized_content = $result['content'];
            $changes_made = array_merge($changes_made, $result['changes']);
        }
        
        // Check and optimize subheading density
        $updated_metrics = $this->calculator->calculate_density($optimized_content, $focus_keyword, $synonyms);
        if ($updated_metrics['subheading_density'] > 75.0) {
            $result = $this->optimize_subheadings($optimized_content, $focus_keyword, $synonyms);
            $optimized_content = $result['content'];
            $changes_made = array_merge($changes_made, $result['changes']);
        }
        
        // Calculate final metrics
        $final_metrics = $this->calculator->calculate_density($optimized_content, $focus_keyword, $synonyms);
        
        return [
            'content' => $optimized_content,
            'optimized' => !empty($changes_made),
            'changes_made' => $changes_made,
            'initial_metrics' => $initial_metrics,
            'final_metrics' => $final_metrics
        ];
    }

    /**
     * Increase keyword density by strategically adding keywords
     *
     * @param string $content The content to modify
     * @param string $focus_keyword The primary keyword
     * @param array $synonyms Array of synonym keywords
     * @param float $target_density Target density percentage
     * @return array Modified content and changes made
     */
    private function increase_keyword_density($content, $focus_keyword, $synonyms, $target_density) {
        $changes_made = [];
        $modified_content = $content;
        
        // Calculate how many keywords we need to add, accounting for additional words
        $current_metrics = $this->calculator->calculate_density($modified_content, $focus_keyword, $synonyms);
        $current_density = $current_metrics['overall_density'];
        $total_words = $current_metrics['total_words'];
        
        // Each keyword addition adds approximately 1 word ("SEO.")
        $words_per_addition = 1;
        
        // Calculate target keywords more accurately
        // If we add N keywords, we'll have (total_words + N * words_per_addition) total words
        // We want: (current_keywords + N) / (total_words + N * words_per_addition) = target_density / 100
        // Solving for N: N = (target_density * total_words / 100 - current_keywords) / (1 - target_density * words_per_addition / 100)
        
        $target_ratio = $target_density / 100;
        $current_keywords = $current_metrics['keyword_count'];
        
        $numerator = $target_ratio * $total_words - $current_keywords;
        $denominator = 1 - $target_ratio * $words_per_addition;
        
        $keywords_to_add = max(0, ceil($numerator / $denominator));
        
        if ($keywords_to_add > 0) {
            // Only add keywords if the content is long enough to handle it without exceeding max density
            // For very short content, adding even 1 keyword can result in excessive density
            $min_words_for_safe_addition = 50; // Need at least 50 words to safely add keywords
            
            if ($total_words >= $min_words_for_safe_addition) {
                // Strategy 1: Add keywords to paragraph endings
                $modified_content = $this->add_keywords_to_paragraphs($modified_content, $focus_keyword, $synonyms, $keywords_to_add);
                $changes_made[] = "Added {$keywords_to_add} keyword variations to improve density";
            } else {
                $changes_made[] = "Content too short ({$total_words} words) to safely add keywords without exceeding maximum density";
            }
        }
        
        return [
            'content' => $modified_content,
            'changes' => $changes_made
        ];
    }

    /**
     * Reduce keyword density by removing or replacing keywords
     *
     * @param string $content The content to modify
     * @param string $focus_keyword The primary keyword
     * @param array $synonyms Array of synonym keywords
     * @param float $target_density Target density percentage
     * @return array Modified content and changes made
     */
    private function reduce_keyword_density($content, $focus_keyword, $synonyms, $target_density) {
        $changes_made = [];
        $modified_content = $content;
        
        // Calculate how many keywords we need to remove
        $current_metrics = $this->calculator->calculate_density($modified_content, $focus_keyword, $synonyms);
        $current_density = $current_metrics['overall_density'];
        $total_words = $current_metrics['total_words'];
        
        $target_keyword_count = floor(($target_density / 100) * $total_words);
        $keywords_to_remove = max(0, $current_metrics['keyword_count'] - $target_keyword_count);
        
        if ($keywords_to_remove > 0) {
            // Strategy: Replace some keyword instances with pronouns or generic terms
            $modified_content = $this->replace_excess_keywords($modified_content, $focus_keyword, $synonyms, $keywords_to_remove);
            $changes_made[] = "Reduced {$keywords_to_remove} keyword instances to avoid over-optimization";
        }
        
        return [
            'content' => $modified_content,
            'changes' => $changes_made
        ];
    }

    /**
     * Optimize subheadings to reduce keyword over-optimization
     *
     * @param string $content The content to modify
     * @param string $focus_keyword The primary keyword
     * @param array $synonyms Array of synonym keywords
     * @return array Modified content and changes made
     */
    private function optimize_subheadings($content, $focus_keyword, $synonyms) {
        $changes_made = [];
        $modified_content = $content;
        
        // Find all subheadings with keywords
        $all_keywords = array_merge([$focus_keyword], $synonyms);
        $subheading_pattern = '/<h([1-6])[^>]*>(.*?)<\/h\1>/i';
        
        preg_match_all($subheading_pattern, $modified_content, $matches, PREG_SET_ORDER);
        
        $subheadings_modified = 0;
        $max_modifications = ceil(count($matches) * 0.3); // Modify up to 30% of subheadings
        
        foreach ($matches as $match) {
            if ($subheadings_modified >= $max_modifications) {
                break;
            }
            
            $full_tag = $match[0];
            $heading_level = $match[1];
            $heading_text = $match[2];
            $heading_text_lower = strtolower(strip_tags($heading_text));
            
            // Check if this subheading contains keywords
            $contains_keyword = false;
            foreach ($all_keywords as $keyword) {
                $pattern = '/\b' . preg_quote(strtolower($keyword), '/') . '\b/';
                if (preg_match($pattern, $heading_text_lower)) {
                    $contains_keyword = true;
                    break;
                }
            }
            
            if ($contains_keyword) {
                // Rewrite subheading to be more natural while maintaining meaning
                $new_heading_text = $this->rewrite_subheading($heading_text, $all_keywords);
                $new_full_tag = "<h{$heading_level}>{$new_heading_text}</h{$heading_level}>";
                
                $modified_content = str_replace($full_tag, $new_full_tag, $modified_content);
                $subheadings_modified++;
            }
        }
        
        if ($subheadings_modified > 0) {
            $changes_made[] = "Rewrote {$subheadings_modified} subheadings to reduce keyword over-optimization";
        }
        
        return [
            'content' => $modified_content,
            'changes' => $changes_made
        ];
    }

    /**
     * Add keywords strategically to paragraphs
     *
     * @param string $content The content to modify
     * @param string $focus_keyword The primary keyword
     * @param array $synonyms Array of synonym keywords
     * @param int $keywords_to_add Number of keywords to add
     * @return string Modified content
     */
    private function add_keywords_to_paragraphs($content, $focus_keyword, $synonyms, $keywords_to_add) {
        $all_keywords = array_merge([$focus_keyword], $synonyms);
        $paragraphs = explode('</p>', $content);
        $keywords_added = 0;
        
        foreach ($paragraphs as $index => $paragraph) {
            if ($keywords_added >= $keywords_to_add) {
                break;
            }
            
            if (strpos($paragraph, '<p>') !== false && strlen(strip_tags($paragraph)) > 50) {
                // Add a keyword variation more conservatively
                $keyword_to_add = $all_keywords[$keywords_added % count($all_keywords)];
                // Use very short additions to minimize density impact
                $additions = [
                    " {$keyword_to_add}.",
                    " More {$keyword_to_add}.",
                    " {$keyword_to_add} info.",
                    " About {$keyword_to_add}.",
                    " {$keyword_to_add} tips."
                ];
                $addition = $additions[$keywords_added % count($additions)];
                $paragraphs[$index] = $paragraph . $addition;
                $keywords_added++;
            }
        }
        
        return implode('</p>', $paragraphs);
    }

    /**
     * Replace excess keyword instances with alternatives
     *
     * @param string $content The content to modify
     * @param string $focus_keyword The primary keyword
     * @param array $synonyms Array of synonym keywords
     * @param int $keywords_to_remove Number of keywords to remove
     * @return string Modified content
     */
    private function replace_excess_keywords($content, $focus_keyword, $synonyms, $keywords_to_remove) {
        $all_keywords = array_merge([$focus_keyword], $synonyms);
        $replacements = ['this solution', 'this approach', 'this method', 'it', 'this'];
        $keywords_removed = 0;
        
        foreach ($all_keywords as $keyword) {
            if ($keywords_removed >= $keywords_to_remove) {
                break;
            }
            
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $replacement = $replacements[$keywords_removed % count($replacements)];
            
            // Replace only the first few instances
            $content = preg_replace($pattern, $replacement, $content, 1);
            $keywords_removed++;
        }
        
        return $content;
    }

    /**
     * Rewrite subheading to reduce keyword density while maintaining meaning
     *
     * @param string $heading_text The original heading text
     * @param array $keywords Array of keywords to reduce
     * @return string Rewritten heading text
     */
    private function rewrite_subheading($heading_text, $keywords) {
        $rewritten = $heading_text;
        
        // Simple rewriting strategies
        $replacements = [
            'best' => 'effective',
            'top' => 'key',
            'ultimate' => 'comprehensive',
            'complete' => 'full',
            'guide' => 'overview'
        ];
        
        foreach ($replacements as $from => $to) {
            $rewritten = str_ireplace($from, $to, $rewritten);
        }
        
        // If still contains keywords, make it more generic
        foreach ($keywords as $keyword) {
            if (stripos($rewritten, $keyword) !== false) {
                $rewritten = str_ireplace($keyword, 'this topic', $rewritten);
                break; // Only replace one keyword per heading
            }
        }
        
        return $rewritten;
    }

    /**
     * Get optimization recommendations based on current metrics
     *
     * @param array $metrics Current keyword density metrics
     * @param float $min_density Minimum acceptable density
     * @param float $max_density Maximum acceptable density
     * @return array Recommendations for optimization
     */
    public function get_optimization_recommendations($metrics, $min_density = 0.5, $max_density = 2.5) {
        $recommendations = [];
        
        $overall_density = $metrics['overall_density'];
        $subheading_density = $metrics['subheading_density'];
        
        if ($overall_density < $min_density) {
            $recommendations[] = [
                'type' => 'increase_density',
                'message' => 'Keyword density is too low. Consider adding more keyword variations naturally throughout the content.',
                'priority' => 'medium'
            ];
        } elseif ($overall_density > $max_density) {
            $recommendations[] = [
                'type' => 'reduce_density',
                'message' => 'Keyword density is too high. Reduce keyword usage to avoid over-optimization penalties.',
                'priority' => 'high'
            ];
        }
        
        if ($subheading_density > 75.0) {
            $recommendations[] = [
                'type' => 'optimize_subheadings',
                'message' => 'Too many subheadings contain keywords. Rewrite some subheadings to be more natural.',
                'priority' => 'high'
            ];
        }
        
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'optimal',
                'message' => 'Keyword density is within optimal range.',
                'priority' => 'info'
            ];
        }
        
        return $recommendations;
    }
}