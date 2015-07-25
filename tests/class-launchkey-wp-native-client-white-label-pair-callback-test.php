<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Native_Client_White_Label_Pair_Callback_Test extends LaunchKey_WP_Native_Client_Test_Abstract {
	/**
	 * @Mock
	 * @var WP_Error
	 */
	private $wp_error;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Service\WhiteLabelService
	 */
	private $white_label_service;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Domain\WhiteLabelUser
	 */
	private $white_label_user;

	public function test_no_nonce_in_post_does_nothing() {
		unset( $_POST['nonce'] );
		$this->client->white_label_pair_callback();
		Phake::verifyNoInteraction( $this->facade );
		Phake::verifyNoInteraction( $this->sdk_auth );
	}

	public function test_nonce_in_get_does_nothing() {
		unset( $_POST['nonce'] );
		$_GET['nonce'] = 'GET NONCE';
		$this->client->white_label_pair_callback();
		Phake::verifyNoInteraction( $this->facade );
		Phake::verifyNoInteraction( $this->sdk_auth );
	}

	public function test_invalid_nonce_in_post_does_nothing() {
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( false );
		$this->client->white_label_pair_callback();
		Phake::verify( $this->facade )->wp_verify_nonce( $_POST['nonce'], LaunchKey_WP_User_Profile::NONCE_KEY );
		Phake::verifyNoFurtherInteraction( $this->facade );
		Phake::verifyNoInteraction( $this->sdk_auth );
	}

	public function test_no_current_user_does_nothing() {
		Phake::when( $this->facade )->wp_get_current_user( Phake::anyParameters() )->thenReturn( null );
		$this->client->white_label_pair_callback();
		Phake::verify( $this->facade )->wp_get_current_user();
		Phake::verifyNoFurtherInteraction( $this->facade );
		Phake::verifyNoInteraction( $this->sdk_auth );
	}

	public function test_exceptions_returns_error_response() {
		Phake::when( $this->white_label_service )->createUser( Phake::anyParameters() )->thenThrow( new Exception() );
		$this->client->white_label_pair_callback();
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );
		$this->assertArrayHasKey( 'error', $response, 'Response did not have an error' );
	}

	public function provider_exception_error_messages() {
		return array(
			array(
				'\LaunchKey\SDK\Service\Exception\CommunicationError',
				'There was a communication error encountered during the pairing process.  Please try again later'
			),
			array(
				'\LaunchKey\SDK\Service\Exception\InvalidCredentialsError',
				'There was an error encountered during the pairing process caused by a misconfiguration.  Please contact the administrator.'
			),
			array(
				'Exception',
				'There was an error encountered during the pairing process.  Please contact the administrator.'
			),
		);
	}

	/**
	 * @depends      test_exceptions_returns_error_response
	 * @dataProvider provider_exception_error_messages
	 *
	 * @param $exception_class
	 * @param $expected_error
	 */
	public function test_exceptions_return_correct_error_response( $exception_class, $expected_error ) {
		Phake::when( $this->white_label_service )->createUser( Phake::anyParameters() )
		     ->thenThrow( Phake::mock( $exception_class ) );
		$this->client->white_label_pair_callback();
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );
		$this->assertEquals( $expected_error, $response['error'] );
	}

	public function test_sets_user_login_as_launchkey_username() {
		$this->client->white_label_pair_callback();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_username', $this->user->user_login );
	}

	public function test_sends_response_as_ajax() {
		Phake::when( $this->white_label_user )->getQrCodeUrl()->thenReturn( 'Expected QR Code URL' );
		Phake::when( $this->white_label_user )->getCode()->thenReturn( 'Expected Code' );
		Phake::when( $this->facade )->wp_create_nonce( Phake::anyParameters() )->thenReturn( 'Expected Nonce' );
		$this->client->white_label_pair_callback();
		Phake::verify( $this->facade )->wp_create_nonce( LaunchKey_WP_User_Profile::NONCE_KEY );
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );

		return $response;
	}

	/**
	 * @depends test_sends_response_as_ajax
	 *
	 * @param array $response
	 */
	public function test_sucess_response_has_qrcode( array $response ) {
		$this->assertArrayHasKey( 'qrcode', $response );
		$this->assertEquals( 'Expected QR Code URL', $response['qrcode'] );
	}

	/**
	 * @depends test_sends_response_as_ajax
	 *
	 * @param array $response
	 */
	public function test_sucess_response_has_code( array $response ) {
		$this->assertArrayHasKey( 'code', $response );
		$this->assertEquals( 'Expected Code', $response['code'] );
	}

	/**
	 * @depends test_sends_response_as_ajax
	 *
	 * @param array $response
	 */
	public function test_sucess_response_has_nonce( array $response ) {
		$this->assertArrayHasKey( 'nonce', $response );
		$this->assertEquals( 'Expected Nonce', $response['nonce'] );
	}

	/**
	 * @depends test_sends_response_as_ajax
	 *
	 * @param array $response
	 */
	public function test_sucess_response_has_no_error( array $response ) {
		$this->assertArrayNotHasKey( 'error', $response );
	}


	protected function setUp() {
		parent::setUp();
		$_POST['nonce'] = 'expected nonce';
		$this->user->user_login = 'expected user login';
		Phake::when( $this->sdk_client )->whiteLabel()->thenReturn( $this->white_label_service );
		Phake::when( $this->white_label_service )->createUser( Phake::anyParameters() )
		     ->thenReturn( $this->white_label_user );
		Phake::when( $this->facade )->wp_get_current_user()->thenReturn( $this->user );
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'true' );
	}

	protected function tearDown() {
		$this->wp_error = null;
		$this->white_label_service = null;
		$this->white_label_user = null;
		parent::tearDown();
	}
}
