<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

/**
 * Class LaunchKey_WP_Configuration_Wizard_Test
 */
class LaunchKey_WP_Configuration_Wizard_Verify_Configuration_Callback_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var array LaunchKey Options
	 */
	public $options_data;

	/**
	 * @Mock
	 * @var LaunchKey_WP_Global_Facade
	 */
	private $facade;

	/**
	 * @Mock
	 * @var LaunchKey_WP_Admin
	 */
	private $admin;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Client
	 */
	private $client;

	/**
	 * @var LaunchKey_WP_Configuration_Wizard
	 */
	private $wizard;

	/**
	 * @Mock
	 * @var WP_User
	 */
	private $user;

	/**
	 * @Mock
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Service\AuthService
	 */
	private $auth;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Domain\AuthRequest
	 */
	private $auth_request;

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

	public function test_does_nothing_when_no_nonce() {
		$this->wizard->verify_configuration_callback();
		Phake::verifyNoInteraction( $this->facade );
	}

	public function test_verifies_nonce_when_present() {
		$_REQUEST['nonce'] = 'expected';
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->facade )->wp_verify_nonce( 'expected', LaunchKey_WP_Configuration_Wizard::VERIFIER_NONCE_KEY );
	}

	public function test_does_nothing_else_when_nonce_is_invalid() {
		$_REQUEST['nonce'] = 'not empty';
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( false );
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->facade )->wp_verify_nonce( Phake::anyParameters() );
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_authorizes_username_when_in_post() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['username']         = 'expected';
		$_REQUEST['nonce']         = 'not empty';
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->auth )->authorize( 'expected' );
	}

	public function test_uses_current_user_login_name_when_username_not_in_post() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_REQUEST['nonce']         = 'not empty';
		$this->user->user_login    = 'expected';
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->auth )->authorize( 'expected' );
	}

	public function test_updates_user_meta_when_authorizing_succeeds() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['username']         = 'expected';
		$_REQUEST['nonce']         = 'not empty';
		$this->user->ID            = $user_id = 999;
		Phake::when( $this->auth_request )->getAuthRequestId()->thenReturn( $auth_request_id = 'auth request ID' );
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->facade )->update_user_meta( $user_id, 'launchkey_username', 'expected' );
		Phake::verify( $this->facade )->update_user_meta( $user_id, 'launchkey_auth', $auth_request_id );
		Phake::verify( $this->facade )->update_user_meta( $user_id, 'launchkey_authorized', null );
	}

	public function test_does_not_update_user_meta_on_authorize_exception() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['username']         = 'expected';
		$_REQUEST['nonce']         = 'not empty';
		Phake::when( $this->auth )->authorize( Phake::anyParameters() )->thenThrow( new Exception() );
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->facade, Phake::never() )->update_user_meta( Phake::anyParameters() );
	}

	public function test_sends_json_response_on_authorize_exception() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['username']         = 'expected';
		$_REQUEST['nonce']         = 'not empty';
		Phake::when( $this->auth )->authorize( Phake::anyParameters() )->thenThrow( new Exception( 'exception', 5555 ) );
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );

		return $response;
	}

	/**
	 * @depends test_sends_json_response_on_authorize_exception
	 *
	 * @param array $response
	 */
	public function test_authorize_exception_response_has_nonce( array $response ) {
		$this->assertArrayHasKey( 'nonce', $response );
		$this->assertEquals( 'Nonce', $response['nonce'] );
	}

	/**
	 * @depends test_sends_json_response_on_authorize_exception
	 *
	 * @param array $response
	 */
	public function test_authorize_exception_response_has_exception_code_as_error( array $response ) {
		$this->assertArrayHasKey( 'error', $response );
		$this->assertEquals( 5555, $response['error'] );
	}

	public function test_sends_json_response_on_non_auth_with_null_db_response() {
		$_REQUEST['nonce'] = 'not empty';
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );

		return $response;
	}

	/**
	 * @depends test_sends_json_response_on_non_auth_with_null_db_response
	 *
	 * @param array $response
	 */
	public function test_on_auth_with_null_db_response_nonce( array $response ) {
		$this->assertArrayHasKey( 'nonce', $response );
		$this->assertEquals( 'Nonce', $response['nonce'] );
	}

	/**
	 * @depends test_sends_json_response_on_authorize_exception
	 *
	 * @param array $response
	 */
	public function test_on_auth_with_null_db_response_has_completed_false( array $response ) {
		$this->assertArrayHasKey( 'completed', $response );
		$this->assertEquals( false, $response['completed'] );
	}

	public function test_sends_json_response_on_non_auth_with_true_db_response_has_completed_true() {
		$_REQUEST['nonce'] = 'not empty';
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'true' );
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );
		$this->assertArrayHasKey( 'completed', $response );
		$this->assertEquals( true, $response['completed'] );

	}

	public function test_sends_json_response_on_non_auth_with_false_db_response_has_completed_true() {
		$_REQUEST['nonce'] = 'not empty';
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'false' );
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );
		$this->assertArrayHasKey( 'completed', $response );
		$this->assertEquals( true, $response['completed'] );
	}

	public function test_when_verify_action_is_pair_white_label_create_user_is_called_and_json_response_sent() {
		$_REQUEST['nonce']         = 'not empty';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['verify_action']    = 'pair';
		Phake::when( $this->white_label_user )->getCode()->thenReturn( 'Manual Code' );
		Phake::when( $this->white_label_user )->getQrCodeUrl()->thenReturn( 'QR Code URL' );
		$this->user->user_login = $expected_login = 'expected-login';
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->white_label_service )->createUser( $expected_login );
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );

		return $response;
	}

	/**
	 * @depends test_when_verify_action_is_pair_white_label_create_user_is_called_and_json_response_sent
	 *
	 * @param array $response
	 */
	public function test_verify_action_pair_returns_nonce( $response ) {
		$this->assertArrayHasKey( 'nonce', $response );
		$this->assertEquals( 'Nonce', $response['nonce'] );
	}

	/**
	 * @depends test_when_verify_action_is_pair_white_label_create_user_is_called_and_json_response_sent
	 *
	 * @param array $response
	 */
	public function test_verify_action_pair_returns_QR_code_URL( $response ) {
		$this->assertArrayHasKey( 'qrcode_url', $response );
		$this->assertEquals( 'QR Code URL', $response['qrcode_url'] );
	}

	/**
	 * @depends test_when_verify_action_is_pair_white_label_create_user_is_called_and_json_response_sent
	 *
	 * @param array $response
	 */
	public function test_verify_action_pair_returns_manual_code( $response ) {
		$this->assertArrayHasKey( 'manual_code', $response );
		$this->assertEquals( 'Manual Code', $response['manual_code'] );
	}

	public function test_verify_action_create_user_error_sends_json_response() {
		$_REQUEST['nonce']         = 'not empty';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['verify_action']    = 'pair';
		$this->user->user_login    = $expected_login = 'expected-login';
		Phake::when( $this->white_label_service )
		     ->createUser( Phake::anyParameters() )
		     ->thenThrow( new Exception( 'message', 999 ) );
		$this->wizard->verify_configuration_callback();
		Phake::verify( $this->white_label_service )->createUser( $expected_login );
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $response ) );

		return $response;
	}


	/**
	 * @depends test_verify_action_create_user_error_sends_json_response
	 *
	 * @param array $response
	 */
	public function test_verify_action_create_user_error_returns_nonce( $response ) {
		$this->assertArrayHasKey( 'nonce', $response );
		$this->assertEquals( 'Nonce', $response['nonce'] );
	}

	/**
	 * @depends test_verify_action_create_user_error_sends_json_response
	 *
	 * @param array $response
	 */
	public function test_verify_action_create_user_error_returns_exception_code_in_error( $response ) {
		$this->assertArrayHasKey( 'error', $response );
		$this->assertEquals( 999, $response['error'] );
	}

	protected function setUp() {
		$that               = $this;
		$this->options_data = array(
			LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => LaunchKey_WP_Implementation_Type::NATIVE,
			LaunchKey_WP_Options::OPTION_ROCKET_KEY          => 12345,
			LaunchKey_WP_Options::OPTION_SECRET_KEY          => 'Secret Key',
			LaunchKey_WP_Options::OPTION_PRIVATE_KEY         => 'Private Key',
			LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME    => 'LaunchKey',
			LaunchKey_WP_Options::OPTION_SSL_VERIFY          => true,
		);

		Phake::initAnnotations( $this );

		Phake::when( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY )->thenReturnCallback(
			function () use ( $that ) {
				return $that->options;
			}
		);

		Phake::when( $this->facade )->wp_get_current_user()->thenReturn( $this->user );

		Phake::when( $this->facade )->wp_create_nonce( Phake::anyParameters() )->thenReturn( 'Nonce' );

		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );

		Phake::when( $this->facade )->get_wpdb()->thenReturn( $this->wpdb );
		$this->wpdb->usermeta = 'usermeta_table';

		Phake::when( $this->client )->auth()->thenReturn( $this->auth );

		Phake::when( $this->auth )->authorize( Phake::anyParameters() )->thenReturn( $this->auth_request );

		Phake::when( $this->client )->whiteLabel()->thenReturn( $this->white_label_service );

		Phake::when( $this->white_label_service )
		     ->createUser( Phake::anyParameters() )
		     ->thenReturn( $this->white_label_user );

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_POST['action']           = null;

		$this->wizard = new LaunchKey_WP_Configuration_Wizard(
			$this->facade,
			$this->admin,
			$this->client
		);
	}

	protected function tearDown() {
		$this->wizard       = null;
		$this->facade       = null;
		$this->admin        = null;
		$this->client       = null;
		$this->user         = null;
		$this->wpdb         = null;
		$this->auth         = null;
		$this->auth_request = null;

		foreach ( array_keys( $_SERVER ) as $key ) {
			unset( $_SERVER[ $key ] );
		}

		foreach ( array_keys( $_POST ) as $key ) {
			unset( $_POST[ $key ] );
		}

		foreach ( array_keys( $_GET ) as $key ) {
			unset( $_GET[ $key ] );
		}
	}
}
