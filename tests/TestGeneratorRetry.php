<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class TestGeneratorRetry extends TestCase {

    public function test_validate_post_by_id_reports_missing_fields() {
        // Create a post missing many required fields
        $post_id = acs_create_test_post( array(
            'post_title' => 'Quick Tips',
            'post_content' => '<p>Some brief content without focus keyword.</p>',
            'meta' => array(),
        ) );

        $gen = new ACS_Content_Generator();
        $result = $gen->validate_post_by_id( $post_id );

        $this->assertIsArray( $result );
        $this->assertNotEmpty( $result );
        // Expect at least a missing required field message
        $found = false;
        foreach ( $result as $err ) {
            if ( strpos( $err, 'Missing or empty required field' ) !== false || strpos( $err, 'First paragraph must include' ) !== false ) {
                $found = true;
                break;
            }
        }
        $this->assertTrue( $found, 'Expected validation errors about missing fields or focus keyword' );
    }

    public function test_retry_generation_for_post_updates_post_and_report() {
        $post_id = acs_create_test_post( array(
            'post_title' => 'Old Title',
            'post_content' => '<p>Old content</p>',
            'meta' => array( '_acs_original_keywords' => 'seed' ),
        ) );

        // Create a subclass to override generate() to return a controlled result
        $testGen = new class extends ACS_Content_Generator {
            public function generate( $prompt_data ) {
                return array(
                    'title' => 'Seed Topic - Updated Title',
                    'content' => '<p>Updated content that includes seed.</p>',
                    'meta_description' => 'Short meta',
                    'focus_keyword' => 'seed',
                    'slug' => 'seed-topic-updated-title',
                    'excerpt' => 'Updated excerpt',
                    'image_prompts' => array( array( 'alt' => 'seed image' ) ),
                    'internal_links' => array( 'https://example.com/post1', 'https://example.com/post2' ),
                    'acs_validation_report' => array( 'provider' => 'test', 'initial_errors' => array(), 'auto_fix_applied' => false, 'retry' => false ),
                );
            }
        };

        $result = $testGen->retry_generation_for_post( $post_id );

        $this->assertIsArray( $result );
        $this->assertEquals( 'Seed Topic - Updated Title', get_post( $post_id )->post_title );
        $this->assertStringContainsString( 'Updated content', get_post( $post_id )->post_content );
        $report = get_post_meta( $post_id, '_acs_generation_report', true );
        $this->assertIsArray( $report );
        $this->assertEquals( 'test', $report['provider'] );
    }
}
