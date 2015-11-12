<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Options_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var LaunchKey_WP_Options
	 */
	private $options;

	/**
	 * @Mock
	 * @var \phpseclib\Crypt\AES
	 */
	private $crypt_aes;

	/**
	 * @return array
	 */
	public function data_provider_test_pre_update_option_filter_has_expected_output() {
		return array(
			'rocket_key is untouched'       => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				'rocket key',
				'rocket key'
			),
			'secret_key is encrypted'       => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				'secret key',
				base64_encode( 'Encrypted [secret key]' )
			),
			'private key is encrypted'      => array(
				LaunchKey_WP_Options::OPTION_PRIVATE_KEY,
				'private key',
				base64_encode( 'Encrypted [private key]' )
			),
			'app_display_name is untouched' => array(
				LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME,
				'app display name',
				'app display name'
			),
			'ssl_verify is untouched'       => array( LaunchKey_WP_Options::OPTION_SSL_VERIFY, false, false ),
			'white_label is untouched'      => array( 'white_label', true, true ),
		);
	}

	/**
	 * @param $key
	 * @param $value
	 * @param $expected
	 *
	 * @dataProvider data_provider_test_pre_update_option_filter_has_expected_output
	 */
	public function test_pre_update_option_filter_has_expected_output( $key, $value, $expected ) {
		$input  = array( $key => $value );
		$output = $this->options->pre_update_option_filter( $input );
		$actual = $output[ $key ];
		$this->assertSame( $expected, $actual );
	}

	public function test_pre_update_option_filter_uses_app_key_for_iv_when_encrypting_secret_key_and_rocket_key_is_not_null() {
		$this->options->pre_update_option_filter( array(
			LaunchKey_WP_Options::OPTION_ROCKET_KEY => 'rocket key',
			LaunchKey_WP_Options::OPTION_SECRET_KEY => 'secret key'
		) );
		Phake::inOrder(
			Phake::verify( $this->crypt_aes )->setIV( 'rocket key' ),
			Phake::verify( $this->crypt_aes )->encrypt( 'secret key' )
		);
	}

	public function test_pre_update_option_filter_uses_empty_string_for_iv_when_encrypting_secret_key_and_app_key_is_null() {
		$this->options->pre_update_option_filter( array(
			'app_key'                               => null,
			LaunchKey_WP_Options::OPTION_SECRET_KEY => 'secret key'
		) );
		Phake::inOrder(
			Phake::verify( $this->crypt_aes )->setIV( LaunchKey_WP_Options::STATIC_IV ),
			Phake::verify( $this->crypt_aes )->encrypt( 'secret key' )
		);
	}

	public function test_pre_update_option_filter_uses_secret_key_for_iv_when_encrypting_private_key_and_secret_key_is_not_null() {
		$this->options->pre_update_option_filter( array(
			LaunchKey_WP_Options::OPTION_SECRET_KEY  => 'secret key',
			LaunchKey_WP_Options::OPTION_PRIVATE_KEY => 'private key'
		) );
		Phake::inOrder(
			Phake::verify( $this->crypt_aes )->setIV( 'secret key' ),
			Phake::verify( $this->crypt_aes )->encrypt( 'private key' )
		);
	}

	public function test_pre_update_option_filter_uses_empty_string_for_iv_when_encrypting_private_key_and_secret_key_is_null() {
		$this->options->pre_update_option_filter( array(
			LaunchKey_WP_Options::OPTION_SECRET_KEY  => null,
			LaunchKey_WP_Options::OPTION_PRIVATE_KEY => 'private key'
		) );
		Phake::inOrder(
			Phake::verify( $this->crypt_aes )->setIV( LaunchKey_WP_Options::STATIC_IV ),
			Phake::verify( $this->crypt_aes )->encrypt( 'private key' )
		);
	}

	/**
	 * @return array
	 */
	public function data_provider_test_post_get_option_filter_has_expected_output() {
		return array(
			'rocket_key is untouched'       => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				'rocket key',
				'rocket key'
			),
			'secret_key is encrypted'       => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				base64_encode( 'encrypted secret key' ),
				'Decrypted [encrypted secret key]'
			),
			'private key is encrypted'      => array(
				LaunchKey_WP_Options::OPTION_PRIVATE_KEY,
				base64_encode( 'encrypted private key' ),
				'Decrypted [encrypted private key]'
			),
			'app_display_name is untouched' => array(
				LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME,
				'app display name',
				'app display name'
			),
			'ssl_verify is untouched'       => array( LaunchKey_WP_Options::OPTION_SSL_VERIFY, false, false ),
			'white_label is untouched'      => array( 'white_label', true, true ),
		);
	}

	/**
	 * @param $key
	 * @param $value
	 * @param $expected
	 *
	 * @dataProvider data_provider_test_post_get_option_filter_has_expected_output
	 */
	public function test_post_get_option_filter_has_expected_output( $key, $value, $expected ) {
		$input  = array( $key => $value );
		$output = $this->options->post_get_option_filter( $input );
		$actual = $output[ $key ];
		$this->assertSame( $expected, $actual );
	}

	public function test_post_get_option_filter_uses_rocket_key_for_iv_when_decrypting_secret_key_and_rocket_key_is_not_null() {
		$this->options->post_get_option_filter( array(
			LaunchKey_WP_Options::OPTION_ROCKET_KEY => 'rocket key',
			LaunchKey_WP_Options::OPTION_SECRET_KEY => base64_encode( 'encrypted secret key' )
		) );
		Phake::inOrder(
			Phake::verify( $this->crypt_aes )->setIV( 'rocket key' ),
			Phake::verify( $this->crypt_aes )->decrypt( 'encrypted secret key' )
		);
	}

	public function test_post_get_option_filter_uses_empty_string_for_iv_when_decrypting_secret_key_and_rocket_key_is_null() {
		$this->options->post_get_option_filter( array(
			LaunchKey_WP_Options::OPTION_SECRET_KEY => base64_encode( 'encrypted secret key' )
		) );
		Phake::inOrder(
			Phake::verify( $this->crypt_aes )->setIV( LaunchKey_WP_Options::STATIC_IV ),
			Phake::verify( $this->crypt_aes )->decrypt( 'encrypted secret key' )
		);
	}

	public function test_post_get_option_filter_uses_decrypted_secret_key_for_iv_when_decrypting_private_key_and_secret_key_is_not_null() {
		$this->options->post_get_option_filter( array(
			LaunchKey_WP_Options::OPTION_SECRET_KEY  => base64_encode( 'encrypted secret key' ),
			LaunchKey_WP_Options::OPTION_PRIVATE_KEY => base64_encode( 'encrypted private key' )
		) );
		Phake::inOrder(
			Phake::verify( $this->crypt_aes )->setIV( 'Decrypted [encrypted secret key]' ),
			Phake::verify( $this->crypt_aes )->decrypt( 'encrypted private key' )
		);
	}

	public function test_post_get_option_filter_uses_empty_string_for_iv_when_decrypting_private_key_and_secret_key_is_null() {
		$this->options->post_get_option_filter( array(
			LaunchKey_WP_Options::OPTION_PRIVATE_KEY => base64_encode( 'encrypted private key' )
		) );
		Phake::inOrder(
			Phake::verify( $this->crypt_aes )->setIV( LaunchKey_WP_Options::STATIC_IV ),
			Phake::verify( $this->crypt_aes )->decrypt( 'encrypted private key' )
		);
	}

	/**
	 * @return array
	 */
	public function data_provider_test_post_get_option_filter_has_expected_defaults() {
		return array(
			'rocket_key is null'            => array( LaunchKey_WP_Options::OPTION_ROCKET_KEY, null ),
			'secret_key is null'            => array( LaunchKey_WP_Options::OPTION_SECRET_KEY, null ),
			'private key is null'           => array( LaunchKey_WP_Options::OPTION_PRIVATE_KEY, null ),
			'app_display_name is LaunchKey' => array( LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME, 'LaunchKey' ),
			'ssl_verify is true'            => array( LaunchKey_WP_Options::OPTION_SSL_VERIFY, true ),
			'implementation_type is Native' => array(
				LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE,
				LaunchKey_WP_Implementation_Type::NATIVE
			),
			'legacy_oauth is false'         => array( LaunchKey_WP_Options::OPTION_LEGACY_OAUTH, false ),
		);
	}

	/**
	 * @param $key
	 * @param $expected
	 *
	 * @dataProvider data_provider_test_post_get_option_filter_has_expected_defaults
	 */
	public function test_post_get_option_filter_has_expected_defaults( $key, $expected ) {
		$output = $this->options->post_get_option_filter( array() );
		$this->assertArrayHasKey( $key, $output );
		$this->assertSame( $expected, $output[ $key ] );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		Phake::when( $this->crypt_aes )->encrypt( Phake::anyParameters() )->thenReturnCallback( function ( $value ) {
			return "Encrypted [{$value}]";
		} );

		Phake::when( $this->crypt_aes )->decrypt( Phake::anyParameters() )->thenReturnCallback( function ( $value ) {
			return "Decrypted [{$value}]";
		} );
		$this->options = new LaunchKey_WP_Options(
			$this->crypt_aes
		);
	}

	protected function tearDown() {
		$this->options   = null;
		$this->crypt_aes = null;
	}
}
