<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Native_Client_Callback_Handler_Test extends LaunchKey_WP_Native_Client_Test_Abstract {

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Domain\AuthResponse
	 */
	private $auth_response;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Domain\DeOrbitCallback
	 */
	private $deorbit_callback;

	public function test_deorbit_query_parameter_has_slashes_stripped() {
		$_GET['deorbit'] = '{\"key\": \"value\"}';
		$expected        = array( 'deorbit' => '{"key": "value"}' );
		$this->client->launchkey_callback();
		Phake::verify( $this->sdk_auth )->handleCallback( Phake::capture( $actual ) );
		$this->assertEquals( $expected, $actual );
	}

	public function provider_exception_correct_response() {
		return array(
			'Invalid requests are 400'      => array(
				'\LaunchKey\SDK\Service\Exception\InvalidRequestError',
				'Invalid Request',
				400
			),
			'Non-callback requests are 400' => array(
				'\LaunchKey\SDK\Service\Exception\UnknownCallbackActionError',
				'Invalid Request',
				400
			),
			'Everything else is 500'        => array( '\Exception', 'Server Error', 500 ),
		);
	}

	/**
	 * @dataProvider provider_exception_correct_response
	 *
	 * @param $exception_class
	 * @param $expected_status_message
	 * @param $expected_status_code
	 */
	public function test_exceptions_return_correct_response( $exception_class, $expected_status_message, $expected_status_code ) {
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenThrow( Phake::mock( $exception_class ) );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_die( $expected_status_message, $expected_status_code );
	}

	public function test_exceptions_do_not_log_when_not_debug() {
		Phake::when( $this->facade )->is_debug_log()->thenReturn( false );
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenThrow( new Exception() );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade, Phake::never() )->error_log( Phake::anyParameters() );
	}

	public function test_exceptions_log_when_debug() {
		Phake::when( $this->facade )->is_debug_log()->thenReturn( true );
		Phake::when( $this->sdk_auth )
		     ->handleCallback( Phake::anyParameters() )
		     ->thenThrow( new Exception( 'Expected Message' ) );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->error_log( $this->stringContains( 'Expected Message' ) );
	}

	public function test_auth_response_callback_searches_for_user_by_auth_request_id() {
		Phake::when( $this->auth_response )->getAuthRequestId()->thenReturn( 'Auth Request ID' );
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->auth_response );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->get_users( Phake::capture( $actual ) );
		$this->assertEquals( array( 'meta_key' => 'launchkey_auth', 'meta_value' => 'Auth Request ID' ), $actual );
	}

	/**
	 * @depends test_auth_response_callback_searches_for_user_by_auth_request_id
	 */
	public function test_auth_response_callback_when_no_user_found_for_auth_invalid_request_is_returned() {
		Phake::when( $this->facade )->get_users( Phake::anyParameters() )->thenReturn( array() );

		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->auth_response );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_die( 'Invalid Request', 400 );
	}

	/**
	 * @depends test_auth_response_callback_searches_for_user_by_auth_request_id
	 */
	public function test_auth_response_callback_when_mulitple_users_found_for_auth_invalid_request_is_returned() {
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->auth_response );
		Phake::when( $this->facade )->get_users( Phake::anyParameters() )->thenReturn( array(
			$this->user,
			$this->user
		) );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_die( 'Invalid Request', 400 );
	}

	public function provider_authorized_to_meta_value() {
		return array(
			array( true, 'true' ),
			array( false, 'false' ),
		);
	}

	/**
	 * @dataProvider provider_authorized_to_meta_value
	 *
	 * @param $authorized_value
	 * @param $expected_meta_value
	 */
	public function test_auth_response_callback_updates_authorized_with_correct_value( $authorized_value, $expected_meta_value ) {
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->auth_response );
		Phake::when( $this->auth_response )->isAuthorized()->thenReturn( $authorized_value );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_authorized', $expected_meta_value );
	}

	public function test_auth_response_callback_sets_user_hash_in_launchkey_user_meta() {
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->auth_response );
		Phake::when( $this->auth_response )->getUserHash()->thenReturn( $expected = 'Expceted User Hash' );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_user', $expected );
	}

	public function test_auth_response_callback_sets_uaser_push_id_as_launchkey_username_for_native_implementations() {
		Phake::when( $this->facade )
		     ->get_option( Phake::anyParameters() )
		     ->thenReturn( array( LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => LaunchKey_WP_Implementation_Type::NATIVE ) );
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->auth_response );
		Phake::when( $this->auth_response )->getUserPushId()->thenReturn( $expected = 'Expceted User Push ID' );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_username', $expected );
	}

	public function provider_non_native_implementations() {
		return array(
			array( LaunchKey_WP_Implementation_Type::OAUTH ),
			array( LaunchKey_WP_Implementation_Type::WHITE_LABEL )
		);
	}

	/**
	 * @dataProvider provider_non_native_implementations
	 *
	 * @param $type
	 */
	public function test_auth_response_callback_does_not_set_launchkey_username_for_non_native_implementations( $type ) {
		Phake::when( $this->facade )
		     ->get_option( Phake::anyParameters() )
		     ->thenReturn( array( LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => $type ) );
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->auth_response );
		Phake::when( $this->auth_response )->getUserPushId()->thenReturn( $expected = 'Expceted User Push ID' );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade, Phake::never() )
		     ->update_user_meta( $this->user->ID, 'launchkey_username', $this->anything() );
	}

	public function test_deorbit_callback_searches_for_user_by_user_hash() {
		Phake::when( $this->deorbit_callback )->getUserHash()->thenReturn( 'User hash' );
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->deorbit_callback );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->get_users( Phake::capture( $actual ) );
		$this->assertEquals( array( 'meta_key' => 'launchkey_user', 'meta_value' => 'User hash' ), $actual );
	}

	/**
	 * @depends test_deorbit_callback_searches_for_user_by_user_hash
	 */
	public function test_deorbit_callback_when_no_user_found_for_auth_invalid_request_is_returned() {
		Phake::when( $this->facade )->get_users( Phake::anyParameters() )->thenReturn( array() );

		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->deorbit_callback );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_die( 'Invalid Request', 400 );
	}

	/**
	 * @depends test_deorbit_callback_searches_for_user_by_user_hash
	 */
	public function test_deorbit_callback_when_mulitple_users_found_for_auth_invalid_request_is_returned() {
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->deorbit_callback );
		Phake::when( $this->facade )->get_users( Phake::anyParameters() )->thenReturn( array(
			$this->user,
			$this->user
		) );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->wp_die( 'Invalid Request', 400 );
	}

	public function test_deorbit_callback_sets_launchkey_authorized_user_metadata_to_false() {
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->deorbit_callback );
		$this->client->launchkey_callback();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_authorized', 'false' );
	}

	public function test_deorbit_callback_calls_launchkey_deorbit_with_last_auth() {
		$this->user->launchkey_auth = 'Expected Auth Request ID';
		Phake::when( $this->sdk_auth )->handleCallback( Phake::anyParameters() )->thenReturn( $this->deorbit_callback );
		$this->client->launchkey_callback();
		Phake::verify( $this->sdk_auth )->deOrbit( 'Expected Auth Request ID' );
	}


	protected function setUp() {
		parent::setUp();
		$this->user->launchkey_auth = null;
		Phake::when( $this->facade )->get_users( Phake::anyParameters() )->thenReturn( array(
			$this->user
		) );
	}

	protected function tearDown() {
		$this->auth_response    = null;
		$this->deorbit_callback = null;
		parent::tearDown();
	}


}
