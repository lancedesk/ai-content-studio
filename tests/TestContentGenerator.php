<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class TestContentGenerator extends TestCase {

    public function test_parse_generated_content_basic() {
        $generator = new ACS_Content_Generator();

        $sample = "TITLE: Sample Title\nCONTENT: <h2>Intro</h2>\n<p>Paragraph</p>\nMETA_DESCRIPTION: Sample meta\nFOCUS_KEYWORD: sample";

        $parsed = $generator->parse_generated_content( $sample );

        $this->assertIsArray( $parsed );
        $this->assertArrayHasKey( 'title', $parsed );
        $this->assertEquals( 'Sample Title', $parsed['title'] );
        $this->assertArrayHasKey( 'content', $parsed );
        $this->assertStringContainsString( '<h2>', $parsed['content'] );
        $this->assertEquals( 'Sample meta', $parsed['meta_description'] );
        $this->assertEquals( 'sample', $parsed['focus_keyword'] );
    }
}
