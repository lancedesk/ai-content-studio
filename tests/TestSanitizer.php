<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class TestSanitizer extends TestCase {

    public function test_sanitize_api_key_valid_and_invalid() {
        $valid = ACS_Sanitizer::sanitize_api_key( 'abcDEF_123.-' );
        $this->assertEquals( 'abcDEF_123.-', $valid );

        $invalid = ACS_Sanitizer::sanitize_api_key( 'bad key!!@@' );
        $this->assertEquals( '', $invalid );
    }

    public function test_sanitize_settings_preserves_provider_fields() {
        $input = array(
            'providers' => array(
                'primary' => 'openai',
                'fallback' => 'groq',
                'groq' => array( 'api_key' => ' GROQKEY ', 'model' => ' model-x ', 'enabled' => '1' ),
                'openai' => array( 'api_key' => ' OPENAIKEY ', 'model' => ' o-model ', 'enabled' => '' ),
            ),
            'default_provider' => ' openai ',
            'image_provider' => ' unsplash ',
            'backup_providers' => array( ' groq ', 'anthropic' ),
        );

        $san = ACS_Sanitizer::sanitize_settings( $input );
        $this->assertArrayHasKey( 'providers', $san );
        $this->assertEquals( 'openai', $san['providers']['primary'] );
        $this->assertEquals( 'groq', $san['providers']['fallback'] );
        $this->assertEquals( 'GROQKEY', $san['providers']['groq']['api_key'] );
        $this->assertEquals( 'openai', $san['default_provider'] );
        $this->assertEquals( 'unsplash', $san['image_provider'] );
        $this->assertIsArray( $san['backup_providers'] );
        $this->assertEquals( array( 'groq', 'anthropic' ), $san['backup_providers'] );
    }
}
