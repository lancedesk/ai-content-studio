<?php
/**
 * Mock provider for local end-to-end testing
 */
class ACS_Mock extends ACS_AI_Provider_Base {
    public function __construct( $api_key = '' ) {
        parent::__construct( $api_key );
        $this->provider_name = 'mock';
        $this->api_base_url = 'https://mock.local/';
    }

    public function authenticate( $api_key ) {
        $this->api_key = $api_key;
        return true;
    }

    public function generate_content( $prompt, $options = array() ) {
        // Return a compact JSON string but intentionally include minor issues to test repair
        $obj = array(
            'title' => 'AI Benefits for Small Businesses',
            'meta_description' => 'AI content generation helps small businesses automate content and improve SEO, saving time and money for teams of any size.',
            'slug' => 'ai-benefits-small-businesses',
            'content' => '<p>AI helps small businesses scale content creation. It can improve SEO.</p><h2>AI for Small Businesses: Efficiency and SEO</h2><p>AI tools automate repetitive tasks. They save time. Moreover, they can optimize content for keywords. This helps search rankings.</p><h3>How automation helps</h3><p>For example, an AI system can draft outlines. It can suggest images. It can propose internal links. Therefore, teams publish more consistently.</p>',
            'excerpt' => 'AI content generation helps small businesses automate content creation, improve SEO, and save time through automation and better consistency across channels.',
            'focus_keyword' => 'AI',
            'secondary_keywords' => array('content generation','small business','SEO','automation'),
            'tags' => array('AI','small business'),
            'image_prompts' => array( array('prompt' => 'Small business owner using AI to create content, bright office', 'alt' => 'AI for small business - featured image') ),
            'internal_links' => array( array('url' => home_url('/related'), 'anchor' => 'Related topics'), array('url' => home_url('/resources'), 'anchor' => 'Further reading') ),
            'outbound_links' => array( array('url' => 'https://en.wikipedia.org/wiki/Artificial_intelligence', 'anchor' => 'Artificial intelligence overview') ),
        );

        // Intentionally return as surrounding commentary + JSON to simulate provider behavior
        return "Note: returning generated content\n" . json_encode( $obj, JSON_UNESCAPED_SLASHES );
    }

    public function generate_image( $prompt, $options = array() ) {
        return new WP_Error('not_supported','Mock image generation not supported');
    }

    public function get_models() { return array('mock-model' => 'Mock Model'); }
    public function calculate_cost( $tokens ) { return 0; }
    public function check_rate_limit() { return $this->rate_limit; }

    protected function get_request_headers() { return array(); }
}
