<?php
/**
 * Encryption and security utilities
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/security
 */

/**
 * Handles encryption and decryption of sensitive data.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/security
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Encryption {

    /**
     * Encryption method to use.
     */
    const ENCRYPTION_METHOD = 'AES-256-CBC';

    /**
     * Encrypt a string.
     *
     * @since    1.0.0
     * @param    string    $plaintext    The string to encrypt.
     * @return   string                  The encrypted string.
     */
    public static function encrypt( $plaintext ) {
        if ( empty( $plaintext ) ) {
            return '';
        }

        $key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes( 16 );
        
        $encrypted = openssl_encrypt(
            $plaintext,
            self::ENCRYPTION_METHOD,
            $key,
            0,
            $iv
        );

        return base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypt a string.
     *
     * @since    1.0.0
     * @param    string    $encrypted    The encrypted string to decrypt.
     * @return   string                  The decrypted string.
     */
    public static function decrypt( $encrypted ) {
        if ( empty( $encrypted ) ) {
            return '';
        }

        $key = self::get_encryption_key();
        $data = base64_decode( $encrypted );
        
        if ( strlen( $data ) < 16 ) {
            return '';
        }
        
        $iv = substr( $data, 0, 16 );
        $encrypted_data = substr( $data, 16 );

        return openssl_decrypt(
            $encrypted_data,
            self::ENCRYPTION_METHOD,
            $key,
            0,
            $iv
        );
    }

    /**
     * Get the encryption key.
     *
     * @since    1.0.0
     * @return   string    The encryption key.
     */
    private static function get_encryption_key() {
        // Use WordPress salts as encryption key
        if ( defined( 'AUTH_KEY' ) && ! empty( AUTH_KEY ) ) {
            return hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY );
        }

        // Fallback to a generated key (not recommended for production)
        return hash( 'sha256', 'acs_fallback_key_' . site_url() );
    }

    /**
     * Generate a secure random string.
     *
     * @since    1.0.0
     * @param    int       $length    The length of the random string.
     * @return   string               The random string.
     */
    public static function generate_random_string( $length = 32 ) {
        return bin2hex( openssl_random_pseudo_bytes( $length / 2 ) );
    }

    /**
     * Hash a password or sensitive string.
     *
     * @since    1.0.0
     * @param    string    $string    The string to hash.
     * @return   string               The hashed string.
     */
    public static function hash_string( $string ) {
        return hash( 'sha256', $string . wp_salt() );
    }

    /**
     * Verify a hashed string.
     *
     * @since    1.0.0
     * @param    string    $string    The original string.
     * @param    string    $hash      The hash to verify against.
     * @return   bool                 True if the hash matches.
     */
    public static function verify_hash( $string, $hash ) {
        return hash_equals( $hash, self::hash_string( $string ) );
    }
}