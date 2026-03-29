<?php
/**
 * Encryption helper class
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Encryption
 */
class Forminator_Encryption {

	/**
	 * Encrypt secret keys
	 *
	 * @param array $keys Keys.
	 * @param array $settings Settings.
	 * @param array $symbols_to_save Symbols to save from the beginning and end of the key.
	 * @return array
	 */
	public static function encrypt_secret_keys( array $keys, array $settings, array $symbols_to_save = array( 0, 4 ) ): array {
		foreach ( $keys as $key ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$original_key = $settings[ $key ];
				// save relevant symbols from the beginning and end of the key.
				$partial_secret = substr( $original_key, 0, $symbols_to_save[0] )
					. str_repeat( '*', 10 )
					. substr( $original_key, -$symbols_to_save[1] );

				$settings[ $key ] = $partial_secret;

				$settings['is_salty']            = self::use_wp_salt();
				$settings[ $key . '_encrypted' ] = self::encrypt( $original_key );
			}
		}

		return $settings;
	}

	/**
	 * Encrypt data by sodium
	 *
	 * @param string $value Value to encrypt.
	 *
	 * @return string
	 */
	public static function encrypt( string $value ): string {
		try {
			$encryption_key = self::get_encryption_key();
			// Random nonce, unique for each encryption.
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

			// Encrypt input with secret encryption_key and nonce.
			$ciphertext = sodium_crypto_secretbox( $value, $nonce, $encryption_key );

			// We then convert the encrypted message with the nonce to base64 for safe transport or storage.
			// Again we use a timing-safe variant of base64_encode() to do this.
			$result = sodium_bin2base64( $nonce . $ciphertext, SODIUM_BASE64_VARIANT_ORIGINAL );

			// Overwrite $input, $nonce and $encryption_key with null bytes in order to prevent sensitive data leak.
			sodium_memzero( $nonce );
			sodium_memzero( $encryption_key );
			sodium_memzero( $value );
		} catch ( \Exception $e ) {
			$result = $value;
			unset( $value );
		}

		// Apply filter to allow custom encryption methods.
		$encrypted_value = apply_filters( 'forminator_custom_encryption', $result );
		try {
			// Overwrite $result with null bytes in order to prevent sensitive data leak.
			sodium_memzero( $result );
		} catch ( \Exception $e ) {
			unset( $result );
		}

		return $encrypted_value;
	}

	/**
	 * Decrypt data by sodium
	 *
	 * @param string $value Value to decrypt.
	 * @param bool   $was_salty Was encrypted with WP salt.
	 * @return string
	 */
	public static function decrypt( string $value, bool $was_salty = false ): string {
		// Apply filter to allow custom decryption methods.
		$value = apply_filters( 'forminator_custom_decryption', $value );
		try {
			$encryption_key = self::get_encryption_key( $was_salty );
			// Convert the base64 encoded message to binary using sodium_base642bin().
			$ciphertext = sodium_base642bin( $value, SODIUM_BASE64_VARIANT_ORIGINAL );

			// Extract nonce from the ciphertext by taking the first 24 (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) chars.
			$nonce = mb_substr( $ciphertext, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );

			// Remaining part is the encrypted message.
			$ciphertext = mb_substr( $ciphertext, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

			// Decrypt the message with the secret key and nonce.
			$plaintext = (string) sodium_crypto_secretbox_open( $ciphertext, $nonce, $encryption_key );

			// Overwrite $ciphertext, $nonce and $key with null bytes in order to prevent sensitive data leak.
			sodium_memzero( $nonce );
			sodium_memzero( $encryption_key );
			sodium_memzero( $ciphertext );
		} catch ( \Exception $e ) {
			return $value;
		}

		return $plaintext;
	}

	/**
	 * Get encryption key
	 *
	 * @param bool $with_salt Based on WP salt.
	 * @return string
	 */
	private static function get_encryption_key( $with_salt = false ): string {
		$encryption_key = $with_salt ? wp_salt() : self::get_encryption_key_base();
		// Make the string suit for Sodium crypto secret key.
		return sodium_crypto_generichash( $encryption_key, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}

	/**
	 * Get encryption key base
	 *
	 * @return string
	 */
	private static function get_encryption_key_base(): string {
		if ( defined( 'FORMINATOR_ENCRYPTION_KEY' ) && ! empty( FORMINATOR_ENCRYPTION_KEY ) ) {
			$encryption_key = FORMINATOR_ENCRYPTION_KEY;
		} else {
			// get WP salt.
			$encryption_key = wp_salt();
		}

		return apply_filters( 'forminator_encryption_key', $encryption_key );
	}

	/**
	 * Check if WP salt is used
	 *
	 * @return bool
	 */
	public static function use_wp_salt(): bool {
		return self::get_encryption_key_base() === wp_salt();
	}

	/**
	 * Generate encryption key
	 *
	 * @return string
	 */
	public static function generate_encryption_key(): string {
		return str_replace( "'", '', wp_generate_password( 64, true, true ) );
	}
}
