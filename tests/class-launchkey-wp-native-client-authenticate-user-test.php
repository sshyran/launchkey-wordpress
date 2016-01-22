<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Native_Client_Authenticate_User_Test extends LaunchKey_WP_Native_Client_Test_Abstract {

	public function test_get_user_by_passes_correct_parameters() {
		$this->client->launchkey_user_authentication( null, 'username' );
		Phake::verify( $this->facade )->get_user_by( 'login', 'username' );
	}

	public function test_when_get_user_returns_null_a_wp_error_is_returned() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( null );
		$actual = $this->client->launchkey_user_authentication( null, 'username' );
		$this->assertInstanceOf( 'WP_Error', $actual );

		return $actual;
	}

	/**
	 * @depends test_when_get_user_returns_null_a_wp_error_is_returned
	 *
	 * @param WP_Error $error
	 */
	public function test_when_get_user_returns_null_wp_error_is_correct_code( WP_Error $error ) {
		$this->assertEquals( 'launchkey_authentication_denied', $error->get_error_code() );
	}

	/**
	 * @depends test_when_get_user_returns_null_a_wp_error_is_returned
	 *
	 * @param WP_Error $error
	 */
	public function test_when_get_user_returns_null_wp_error_is_correct_message( WP_Error $error ) {
		$this->assertEquals( "Translated [Authentication denied!] with [{$this->language_domain}]", $error->get_error_message() );
	}

	public function test_when_get_user_returns_error_null_is_returned() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( Phake::mock( 'WP_Error' ) );
		$actual = $this->client->launchkey_user_authentication( 'user', 'username' );
		$this->assertNull( $actual );

		return $actual;
	}

	public function provider_error_data_for_auth_exception() {
		return array(
			'No paired devices'    => array(
				'\LaunchKey\SDK\Service\Exception\NoPairedDevicesError',
				'launchkey_authentication_denied',
				'No Paired Devices!'
			),
			'No such user'         => array(
				'\LaunchKey\SDK\Service\Exception\NoSuchUserError',
				'launchkey_authentication_denied',
				'Authentication denied!'
			),
			'Rate Limit Exceeded'  => array(
				'\LaunchKey\SDK\Service\Exception\RateLimitExceededError',
				'launchkey_authentication_denied',
				'Authentication denied!'
			),
			'Auth Request Timeout' => array(
				'\LaunchKey\SDK\Service\Exception\ExpiredAuthRequestError',
				'launchkey_authentication_timeout',
				'Authentication denied!'
			),
			'Any Other'            => array(
				'\Exception',
				'launchkey_authentication_error',
				'Authentication error! Please try again later'
			),
		);
	}

	/**
	 * @dataProvider provider_error_data_for_auth_exception
	 *
	 * @param $exception_class
	 * @param $error_code
	 * @param $error_text
	 */
	public function test_when_authenticate_throws_exception_a_proper_error_is_returned( $exception_class, $error_code, $error_text ) {
		$exception = Phake::mock( $exception_class );
		Phake::when( $this->sdk_auth )->authenticate( Phake::anyParameters() )->thenThrow( $exception );
		Phake::when( $this->facade )->is_wp_error( Phake::anyParameters() )->thenReturn( true );
		$actual = $this->client->launchkey_user_authentication( 'user', 'username' );
		Phake::verify( $this->facade )->is_wp_error( $actual );
		$this->assertInstanceOf( 'WP_Error', $actual, 'Unexpected response type' );
		$this->assertEquals( $error_code, $actual->get_error_code(), 'Unexpected code returned' );
		$this->assertEquals( sprintf( 'Translated [%s] with [%s]', $error_text, $this->language_domain ), $actual->get_error_message(), 'Unexpected code returned' );
	}

	public function test_when_authenticate_throws_exception_it_does_not_log_when_not_debug() {
		Phake::when( $this->facade )->is_debug_log()->thenReturn( false );
		Phake::when( $this->sdk_auth )->authenticate( Phake::anyParameters() )->thenThrow( new Exception() );
		$this->client->launchkey_user_authentication( 'user', 'username' );
		Phake::verify( $this->facade, Phake::never() )->error_log( Phake::anyParameters() );
	}

	public function test_when_authenticate_throws_exception_it_does_log_when_debug() {
		Phake::when( $this->facade )->is_debug_log()->thenReturn( true );
		Phake::when( $this->sdk_auth )
		     ->authenticate( Phake::anyParameters() )
		     ->thenThrow( new Exception( 'Expected Message' ) );
		$this->client->launchkey_user_authentication( 'user', 'username' );
		Phake::verify( $this->facade )->error_log( $this->stringContains( 'Expected Message' ) );
	}

	public function test_when_authenticate_is_denied_auth_denied_error_is_returned() {
		Phake::when( $this->facade )->is_wp_error( Phake::anyParameters() )->thenReturn( true );
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'false' );
		$actual = $this->client->launchkey_user_authentication( 'user', 'username' );
		Phake::verify( $this->facade )->is_wp_error( $actual );
		$this->assertInstanceOf( 'WP_Error', $actual, 'Unexpected response type' );
		$this->assertEquals( 'launchkey_authentication_denied', $actual->get_error_code(), 'Unexpected code returned' );
		$this->assertEquals( sprintf( 'Translated [Authentication denied!] with [%s]', $this->language_domain ), $actual->get_error_message(), 'Unexpected code returned' );
	}

	public function test_checks_the_database_until_the_response_is_none_null() {
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )
		     ->thenReturn( null )
		     ->thenReturn( null )
		     ->thenReturn( 'true' )
		     ->thenReturn( null );
		$this->client->launchkey_user_authentication( 'user', 'username' );
		Phake::verify( $this->wpdb, Phake::times( 3 ) )->get_var( $this->anything() );
	}

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		$this->user = null;
		parent::tearDown();
	}


}
