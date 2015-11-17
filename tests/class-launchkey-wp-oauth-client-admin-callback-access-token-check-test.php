<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Oauth_Client_Admin_Callback_Access_Token_Check_Test extends PHPUnit_Framework_TestCase {
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

	/**
	 * @return array
	 */
	public function test_options_passed_to_remote_request() {
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->wp_remote_post( 'https://oauth.launchkey.com/resource/ping', Phake::capture( $options ) );
		$this->assertInternalType( 'array', $options );

		return $options;
	}

	public function data_provider_true_false() {
		return array(
			'True'  => array( true ),
			'False' => array( false )
		);
	}

	/**
	 * @depends      test_options_passed_to_remote_request
	 * @dataProvider data_provider_true_false
	 *
	 * @param $tf
	 *
	 * @return mixed
	 */
	public function test_correct_ssl_verify_added_to_remote_request_options( $tf ) {
		$this->options[LaunchKey_WP_Options::OPTION_SSL_VERIFY] = $tf;
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->wp_remote_post( 'https://oauth.launchkey.com/resource/ping', Phake::capture( $options ) );
		$this->assertArrayHasKey( 'sslverify', $options );
		$this->assertEquals( $tf, $options['sslverify'] );

		return $options;
	}

	/**
	 * @depends test_options_passed_to_remote_request
	 *
	 * @param $options
	 */
	public function test_wp_remote_request_sends_correct_authorization_header( $options ) {
		$this->assertArrayHasKey( 'headers', $options );
		$this->assertArrayHasKey( 'Authorization', $options['headers'] );
		$this->assertEquals( 'Bearer Access Token', $options['headers']['Authorization'] );
	}

	public function test_when_token_still_valid_it_is_not_refreshed() {
		Phake::when( $this->facade )->wp_remote_post( Phake::anyParameters() )->thenReturn( array( 'body' => '{"message": "valid"}' ) );
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->wp_remote_post( 'https://oauth.launchkey.com/resource/ping', $this->anything() );
		Phake::verify( $this->facade, Phake::never() )->wp_remote_post( 'https://oauth.launchkey.com/access_token', $this->anything() );
	}

	public function test_when_no_message_in_callback_logout_and_redirect_occur() {
		Phake::when( $this->facade )
		     ->wp_remote_post( 'https://oauth.launchkey.com/resource/ping', $this->anything() )
		     ->thenReturn( array( 'body' => '{"no-message": "ha ha"}' ) );

		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade, Phake::never() )->wp_remote_get( Phake::anyParameters() );
		Phake::verify( $this->facade )->wp_login_url();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_logout(),
			Phake::verify( $this->facade )->wp_redirect( 'LoginURL?launchkey_ssl_error=1' )
		);
	}

	public function test_when_token_no_loger_valid_and_no_refresh_token_it_is_not_refreshed_and_logout_and_redirect_occur() {
		unset( $_COOKIE['launchkey_refresh_token'] );
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade, Phake::never() )->wp_remote_get( Phake::anyParameters() );
		Phake::verify( $this->facade )->wp_login_url();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_logout(),
			Phake::verify( $this->facade )->wp_redirect( 'LoginURL?loggedout=1' )
		);
	}

	public function test_when_token_invalid_and_refresh_token_exists_query_is_made_for_access_token() {
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )
		     ->wp_remote_post( 'https://oauth.launchkey.com/access_token', Phake::capture( $options ) );

		return $options;
	}

	/**
	 * @depends test_when_token_invalid_and_refresh_token_exists_query_is_made_for_access_token
	 *
	 * @param $options
	 */
	public function test_when_token_invalid_and_refresh_token_exists_query_is_made_for_access_token_with_ssl_verify_correct( $options ) {
		$this->assertArrayHasKey( 'sslverify', $options );
		$this->assertSame( false, $options['sslverify'] );
	}

	/**
	 * @depends test_when_token_invalid_and_refresh_token_exists_query_is_made_for_access_token
	 *
	 * @param $options
	 */
	public function test_when_token_invalid_and_refresh_token_exists_options_has_body_array( $options ) {
		$this->assertArrayHasKey( 'body', $options );
		$this->assertInternalType( 'array', $options['body'] );

		return $options['body'];
	}

	/**
	 * @depends test_when_token_invalid_and_refresh_token_exists_options_has_body_array
	 *
	 * @param $parameters
	 */
	public function test_when_token_invalid_and_refresh_token_exists_query_has_secret_key_as_client_secret( $parameters ) {
		$this->assertArrayHasKey( 'client_secret', $parameters );
		$this->assertEquals( 'Secret Key', $parameters['client_secret'] );
	}

	/**
	 * @depends test_when_token_invalid_and_refresh_token_exists_options_has_body_array
	 *
	 * @param $parameters
	 */
	public function test_when_token_invalid_and_refresh_token_exists_query_has_app_key_as_client_id( $parameters ) {
		$this->assertArrayHasKey( 'client_id', $parameters );
		$this->assertEquals( 'Rocket Key', $parameters['client_id'] );
	}

	/**
	 * @depends test_when_token_invalid_and_refresh_token_exists_options_has_body_array
	 *
	 * @param $parameters
	 */
	public function test_when_token_invalid_and_refresh_token_exists_query_has_admin_url_as_redirect_uri( $parameters ) {
		$this->assertArrayHasKey( 'redirect_uri', $parameters );
		$this->assertEquals( 'AdminURL', $parameters['redirect_uri'] );
	}

	/**
	 * @depends test_when_token_invalid_and_refresh_token_exists_options_has_body_array
	 *
	 * @param $parameters
	 */
	public function test_when_token_invalid_and_refresh_token_exists_query_has_proper_refresh_token( $parameters ) {
		$this->assertArrayHasKey( 'refresh_token', $parameters );
		$this->assertEquals( 'Refresh Token', $parameters['refresh_token'] );
	}

	/**
	 * @depends test_when_token_invalid_and_refresh_token_exists_options_has_body_array
	 *
	 * @param $parameters
	 */
	public function test_when_token_invalid_and_refresh_token_exists_query_has_proper_grant_type( $parameters ) {
		$this->assertArrayHasKey( 'grant_type', $parameters );
		$this->assertEquals( 'refresh_token', $parameters['grant_type'] );
	}

	public function test_when_token_invalid_and_refresh_token_exists_and_refresh_is_error_user_is_logged_out_and_redirected() {
		Phake::when( $this->facade )->is_wp_error( Phake::anyParameters() )->thenReturn( true );
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->is_wp_error( $this->wp_remote_get_response );
		Phake::verify( $this->facade )->wp_login_url();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_logout(),
			Phake::verify( $this->facade )->wp_redirect( 'LoginURL?launchkey_ssl_error=1' )
		);
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_when_token_invalid_and_refresh_token_exists_and_refresh_returns_no_refresh_token__user_is_logged_out_and_redirected() {
		Phake::when( $this->facade )->wp_remote_post( 'https://oauth.launchkey.com/access_token', $this->anything() )->thenReturn(
			array( 'body' => '{"access_token": "New Access Token", "expires_in": 9999}' )
		);
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_logout(),
			Phake::verify( $this->facade )->wp_redirect( 'LoginURL?loggedout=1' )
		);
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_when_token_invalid_and_refresh_token_exists_and_refresh_returns_no_access_token__user_is_logged_out_and_redirected() {
		Phake::when( $this->facade )->wp_remote_post( 'https://oauth.launchkey.com/access_token', $this->anything() )->thenReturn(
			array( 'body' => '{"refresh_token": "New Refresh Token", "expires_in": 9999}' )
		);
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->wp_login_url();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_logout(),
			Phake::verify( $this->facade )->wp_redirect( 'LoginURL?loggedout=1' )
		);
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_when_token_invalid_and_refresh_token_exists_sets_access_token_cookie() {
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->setcookie( 'launchkey_access_token', 'New Access Token', 3592000,
			COOKIEPATH, COOKIE_DOMAIN );
	}

	public function test_when_token_invalid_and_refresh_token_exists_sets_refresh_token_cookie() {
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->setcookie( 'launchkey_refresh_token', 'New Refresh Token', 3592000,
			COOKIEPATH, COOKIE_DOMAIN );
	}

	public function test_when_token_invalid_and_refresh_token_exists_sets_launchKey_expires_token_cookie() {
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->setcookie( 'launchkey_expires', 1009999, 3592000,
			COOKIEPATH, COOKIE_DOMAIN );
	}

	public function test_launchkey_admin_callback_admin_pair_does_nothing_when_launchkey_user_cookie_is_not_set() {
		$_GET['launchkey_admin_pair'] = '1';
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade, Phake::never() )->update_user_meta( Phake::anyParameters() );
	}

	public function test_launchkey_admin_callback_admin_pair_does_nothing_when_launchkey_user_cookie_is_set_but_not_valid() {
		$_GET['launchkey_admin_pair'] = '1';
		$_COOKIE['launchkey_user']    = '123456789';
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade, Phake::never() )->update_user_meta( Phake::anyParameters() );
	}

	public function test_launchkey_admin_callback_admin_pair_updates_launchkey_user_from_cookie_when_launchkey_user_cookie_is_set_and_valid() {
		$_COOKIE['launchkey_user']    = '1234567890123456';
		$_GET['launchkey_admin_pair'] = '1';
		$this->client->launchkey_admin_callback();
		Phake::verify( $this->facade )->update_user_meta( 12345, 'launchkey_user', '1234567890123456' );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		$this->options = array(
			LaunchKey_WP_Options::OPTION_SSL_VERIFY => false,
			LaunchKey_WP_Options::OPTION_ROCKET_KEY =>  'Rocket Key',
			LaunchKey_WP_Options::OPTION_SECRET_KEY =>  'Secret Key',
			LaunchKey_WP_Options::OPTION_REQUEST_TIMEOUT =>  'Timeout Value'
		);

		$_COOKIE['launchkey_access_token']  = 'Access Token';
		$_COOKIE['launchkey_refresh_token'] = 'Refresh Token';
		Phake::when( $this->facade )->wp_get_current_user()
		     ->thenReturn( (object) array( 'data' => (object) array( 'ID' => 12345, 'user_pass' => 'password' ) ) );
		Phake::when( $this->facade )->wp_login_url()->thenReturn( 'LoginURL' );
		Phake::when( $this->facade )->admin_url( Phake::anyParameters() )->thenReturn( 'AdminURL' );

		$that = $this;
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );

		Phake::when( $this->facade )
		     ->wp_remote_post( 'https://oauth.launchkey.com/resource/ping', $this->anything() )
		     ->thenReturn( array( 'body' => '{"message": "invalid"}' ) );
		Phake::when( $this->facade )
		     ->wp_remote_post( 'https://oauth.launchkey.com/access_token', $this->anything() )
		     ->thenReturn( $this->wp_remote_get_response = array( 'body' => '{"refresh_token": "New Refresh Token", "access_token": "New Access Token", "expires_in": 9999}' )
		     );

		Phake::when( $this->facade )->current_time( Phake::anyParameters() )->thenReturn( 1000000 );

		$this->client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, false);
	}

	protected function tearDown() {
		foreach ( array_keys( $_GET ) as $key ) {
			unset( $_GET[ $key ] );
		}
		foreach ( array_keys( $_COOKIE ) as $key ) {
			unset( $_COOKIE[ $key ] );
		}

		$this->client   = null;
		$this->facade   = null;
		$this->options  = null;
		$this->template = null;
	}
}
