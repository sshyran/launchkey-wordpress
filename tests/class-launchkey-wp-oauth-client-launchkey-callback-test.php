<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_OAuth_Client_LaunchKey_Callback_Test extends PHPUnit_Framework_TestCase {
	/**
	 * @var array
	 */
	public $options;
	/**
	 * @var LaunchKey_WP_OAuth_Client
	 */
	private $client;
	/**
	 * @Mock
	 * @var LaunchKey_WP_Global_Facade
	 */
	private $facade;
	/**
	 * @Mock
	 * @var LaunchKey_WP_Template
	 */
	private $template;

	public function test_error_in_query_redirects_to_login() {
		$_GET['error'] = 'So Error';
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_error=1' );
	}

	public function test_no_code_in_query_redirects_to_login() {
		unset( $_GET['code'] );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_error=1' );
	}

	public function test_non_alphanumeric_code_in_query_redirects_to_login() {
		$_GET['code'] = str_repeat( '!', 64 );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_error=1' );
	}

	public function test_code_less_than_64_chars_in_query_redirects_to_login() {
		$_GET['code'] = str_repeat( '1', 63 );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_error=1' );
	}

	public function test_code_more_than_64_chars_in_query_redirects_to_login() {
		$_GET['code'] = str_repeat( '1', 65 );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_error=1' );
	}

	public function test_remote_get_error_redirects_to_login_with_ssl_error() {
		Phake::when( $this->facade )->is_wp_error( Phake::anyParameters() )->thenReturn( true );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade, Phake::atLeast( 1 ) )->is_wp_error( array( 'body' => json_encode( $this->oauth_response ) ) );
		Phake::verify( $this->facade )->wp_login_url();
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_ssl_error=1' );
	}

	public function test_remote_get_response_has_no_user_in_body() {
		unset( $this->oauth_response['user'] );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_error=1' );
	}

	public function test_remote_get_response_has_no_access_token_in_body() {
		unset( $this->oauth_response['access_token'] );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_error=1' );
	}

	public function test_remote_get_adds_sslverify_option_false_when_sslverify_off() {
		$this->options[LaunchKey_WP_Options::OPTION_SSL_VERIFY] = false;
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'sslverify', $options );
		$this->assertFalse( $options['sslverify'] );
	}

	public function test_remote_get_adds_sslverify_option_true_when_sslverify_on() {
		$this->options[LaunchKey_WP_Options::OPTION_SSL_VERIFY] = true;
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'sslverify', $options );
		$this->assertTrue( $options['sslverify'] );
	}

	public function test_remote_get_sets_httpversion_as_1_1() {
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'httpversion', $options );
		$this->assertEquals( '1.1', $options['httpversion'] );
	}

	public function test_remote_get_sets_timeout() {
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'timeout', $options );
		$this->assertEquals( $this->options[LaunchKey_WP_Options::OPTION_REQUEST_TIMEOUT], $options['timeout'] );
	}

	public function test_remote_get_sends_connect_close_header() {
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'headers', $options );
		$this->assertInternalType( 'array', $options['headers'] );
		$this->assertArrayHasKey( 'Connection', $options['headers'] );
		$this->assertEquals( 'close', $options['headers']['Connection'] );
	}

	public function test_remote_get_query_is_made_for_access_token() {
		$this->options[LaunchKey_WP_Options::OPTION_ROCKET_KEY] = 12345;
		$this->options[LaunchKey_WP_Options::OPTION_SECRET_KEY] = 'secret key';
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_remote_get(
			Phake::capture( $query ),
			$this->anything()
		);

		return $query;
	}

	/**
	 * @depends test_remote_get_query_is_made_for_access_token
	 *
	 * @param $query
	 */
	public function test_remote_get_has_correct_location( $query ) {
		$this->assertStringMatchesFormat( 'https://oauth.launchkey.com/access_token?%s', $query );
	}

	/**
	 * @depends test_remote_get_query_is_made_for_access_token
	 *
	 * @param $query
	 */
	public function test_remote_get_has_query_parameters( $query ) {
		parse_str( parse_url( $query, PHP_URL_QUERY ), $parameters );
		$this->assertNotCount( 0, $parameters );

		return $parameters;
	}

	/**
	 * @depends test_remote_get_has_query_parameters
	 *
	 * @param $parameters
	 */
	public function test_remote_get_has_secret_key_as_client_secret( $parameters ) {
		$this->assertArrayHasKey( 'client_secret', $parameters );
		$this->assertEquals( 'secret key', $parameters['client_secret'] );
	}

	/**
	 * @depends test_remote_get_has_query_parameters
	 *
	 * @param $parameters
	 */
	public function test_remote_get_has_app_key_as_client_id( $parameters ) {
		$this->assertArrayHasKey( 'client_id', $parameters );
		$this->assertEquals( 12345, $parameters['client_id'] );
	}

	/**
	 * @depends test_remote_get_has_query_parameters
	 *
	 * @param $parameters
	 */
	public function test_remote_get_has_admin_url_as_redirect_uri( $parameters ) {
		$this->assertArrayHasKey( 'redirect_uri', $parameters );
		$this->assertEquals( 'admin.url', $parameters['redirect_uri'] );
	}

	/**
	 * @depends test_remote_get_has_query_parameters
	 *
	 * @param $parameters
	 */
	public function test_remote_get_has_proper_code( $parameters ) {
		$this->assertArrayHasKey( 'code', $parameters );
		$this->assertEquals( $_GET['code'], $parameters['code'] );
	}

	/**
	 * @depends test_remote_get_has_query_parameters
	 *
	 * @param $parameters
	 */
	public function test_remote_get_has_proper_grant_type( $parameters ) {
		$this->assertArrayHasKey( 'grant_type', $parameters );
		$this->assertEquals( 'authorization_code', $parameters['grant_type'] );
	}

	public function test_remote_get_success_looks_up_user() {
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->get_users( array(
			'meta_key'   => 'launchkey_user',
			'meta_value' => 'OAuth User'
		) );
	}

	/**
	 * @depends test_remote_get_success_looks_up_user
	 */
	public function test_remote_get_success_existing_user_sets_the_proper_cookies_and_redirects_to_admin() {
		Phake::when( $this->facade )->get_users( Phake::anyParameters() )
		     ->thenReturn( array( new WP_User( 1 ) ) );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_set_auth_cookie( 1, false );
		Phake::verify( $this->facade )->setcookie( 'launchkey_access_token', 'OAuth Access Token', $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade )->setcookie( 'launchkey_refresh_token', 'OAuth Refresh_token', $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade )->setcookie( 'launchkey_expires', $this->timestamp + $this->expires_in, $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade, Phake::atLeast( 1 ) )->admin_url();
		Phake::verify( $this->facade )->wp_redirect( 'admin.url' );
	}

	/**
	 * @depends test_remote_get_success_looks_up_user
	 */
	public function test_remote_get_success_new_user_who_can_manage_options_sets_the_proper_cookies_and_redirects_to_profile() {
		Phake::when( $this->facade )->current_user_can( Phake::anyParameters() )->thenReturn( true );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->current_user_can( 'manage_options' );
		Phake::verify( $this->facade )->setcookie( 'launchkey_access_token', 'OAuth Access Token', $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade )->setcookie( 'launchkey_refresh_token', 'OAuth Refresh_token', $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade )->setcookie( 'launchkey_expires', $this->timestamp + $this->expires_in, $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade )->admin_url( 'profile.php?launchkey_admin_pair=1&updated=1' );
		Phake::verify( $this->facade )->wp_redirect( 'admin.url' );
	}

	/**
	 * @depends test_remote_get_success_looks_up_user
	 */
	public function test_remote_get_success_new_user_who_can_not_manage_options_sets_the_proper_cookies_and_redirects_to_login() {
		Phake::when( $this->facade )->current_user_can( Phake::anyParameters() )->thenReturn( false );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->current_user_can( 'manage_options' );
		Phake::verify( $this->facade )->setcookie( 'launchkey_access_token', 'OAuth Access Token', $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade )->setcookie( 'launchkey_refresh_token', 'OAuth Refresh_token', $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade )->setcookie( 'launchkey_expires', $this->timestamp + $this->expires_in, $this->timestamp + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		Phake::verify( $this->facade )->wp_redirect( 'loginURL?launchkey_pair=1' );
	}

	protected function setUp() {
		$that = $this;
		Phake::initAnnotations( $this );
		Phake::when( $this->facade )->wp_login_url()->thenReturn( 'loginURL' );
		Phake::when( $this->facade )->admin_url( Phake::anyParameters() )->thenReturn( 'admin.url' );
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );
		$this->options[LaunchKey_WP_Options::OPTION_ROCKET_KEY]      = 12345;
		$this->options[LaunchKey_WP_Options::OPTION_SECRET_KEY]      = 'secret key';
		$this->options[LaunchKey_WP_Options::OPTION_SSL_VERIFY]      = true;
		$this->options[LaunchKey_WP_Options::OPTION_REQUEST_TIMEOUT] = 'Timeout Value';

		$_GET['code']         = str_repeat( 'a1', 32 );
		$this->oauth_response = array(
			'user'          => 'OAuth User',
			'access_token'  => 'OAuth Access Token',
			'refresh_token' => 'OAuth Refresh_token',
			'code'          => $_GET['code'],
			'expires_in'    => $this->expires_in = rand( 100, 999 )
		);
		Phake::when( $this->facade )->wp_remote_get( Phake::anyParameters() )
		     ->thenReturnCallback( function () use ( $that ) {
			     return array( 'body' => json_encode( $that->oauth_response ) );
		     } );

		Phake::when( $this->facade )->current_time( Phake::anyParameters() )->thenReturn( $this->timestamp = rand( 10000, 99999 ) );

		$this->client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, false);
	}

	protected function tearDown() {
		$this->client   = null;
		$this->options  = null;
		$this->facade   = null;
		$this->template = null;

		// Reset global $_GET after each test run
		foreach ( array_keys( $_GET ) as $key ) {
			unset( $_GET[ $key ] );
		}
	}
}
