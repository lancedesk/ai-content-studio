<?php
require_once __DIR__ . '/simulate_admin_post.php';
// simulate_admin_post sets up ACS_PLUGIN_PATH, WP_CONTENT_DIR and loads admin class
// But we want generator instance
if ( ! class_exists( 'ACS_Generator_Loader' ) ) {
    require_once ACS_PLUGIN_PATH . 'includes/class-acs-generator-loader.php';
}
$gen = ACS_Generator_Loader::get_instance();
if ( is_wp_error( $gen ) ) {
    echo "Failed to load generator\n"; exit;
}
$topic = 'The Benefits of AI Content Generation for Small Businesses';
$keywords = 'AI, content generation, small business, SEO, automation';
$word_count = 'medium';
$test_internal = array(
    array('title'=>'About Our Services','url'=>'https://localhost/localserver/about-our-services'),
    array('title'=>'AI Tools We Use','url'=>'https://localhost/localserver/ai-tools'),
);
$prompt = $gen->build_prompt( $topic, $keywords, $word_count, $test_internal );
echo "\n=== Prompt with test internal candidates ===\n" . $prompt . "\n";
?>