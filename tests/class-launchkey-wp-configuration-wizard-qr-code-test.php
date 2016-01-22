<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2016 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Configuration_Wizard_QR_Code_Test extends PHPUnit_Framework_TestCase {

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
	 * @Mock
	 * @var \LaunchKey\SDK\Service\AuthService
	 */
	private $auth;

	/**
	 * @var LaunchKey_WP_Configuration_Wizard
	 */
	private $wizard;

	/**
	 * @var bool
	 */
	private $is_multi_site;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Service\CryptService
	 */
	private $crypt;

	/**
	 * @var WP_User
	 */
	private $user;

	public function test_does_nothing_when_no_nonce() {
		unset( $_POST['nonce'] );
		$this->wizard->wizard_easy_setup_qr_code();
		Phake::verifyNoInteraction( $this->facade );
	}

	public function test_verifies_nonce_when_present() {
		$_POST['nonce'] = 'expected';
		$this->wizard->wizard_easy_setup_qr_code();
		Phake::verify( $this->facade )
		     ->wp_verify_nonce( 'expected', LaunchKey_WP_Configuration_Wizard::WIZARD_NONCE_KEY );
	}

	public function test_does_nothing_but_create_nonce_when_nonce_is_invalid() {
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( false );
		$this->wizard->wizard_easy_setup_qr_code();
		Phake::verify( $this->facade )->wp_verify_nonce( Phake::anyParameters() );
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_does_nothing_when_user_cannot_manage_options() {
		Phake::when( $this->facade )->current_user_can( 'manage_options' )->thenReturn( false );
		$this->wizard->wizard_easy_setup_qr_code();
		Phake::verify( $this->facade )->current_user_can( 'manage_options' );
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_generates_proper_nonce_for_response() {
		$this->wizard->wizard_easy_setup_qr_code();
		Phake::verify( $this->facade )->wp_create_nonce( LaunchKey_WP_Configuration_Wizard::WIZARD_NONCE_KEY );
	}

	public function test_sends_json_response() {
		$this->wizard->wizard_easy_setup_qr_code();
		Phake::verify( $this->facade )->wp_send_json( Phake::capture( $json ) );

		return $json;
	}

	/**
	 * @param $json
	 *
	 * @return mixed
	 * @depends test_sends_json_response
	 */
	public function test_json_response_includes_nonce( $json ) {
		$this->assertArrayHasKey( 'nonce', $json, "No nonce in JSON response" );
		$this->assertEquals( 'Nonce', $json['nonce'], "Unexpected nonce value" );
	}

	/**
	 * @depends test_sends_json_response
	 *
	 * @param $json
	 *
	 * @return array
	 */
	public function test_json_response_includes_base64_encoded_qr_code_json_string( $json ) {
		$this->assertArrayHasKey( 'qr_code', $json, 'No qr_code in JSON response' );
		$decoded = base64_decode( $json['qr_code'] );
		$this->assertNotFalse( $decoded, "Unable to base64 decode qr_code: " . $json['qr_code'] );
		$data = json_decode( $decoded, true );
		$this->assertNotNull( $data, "Invalid JSON string in QR Code: " . $decoded );

		return $data;
	}

	/**
	 * @param $data
	 *
	 * @depends test_json_response_includes_base64_encoded_qr_code_json_string
	 */
	public function test_json_response_qr_code_includes_api_nonce( $data ) {
		$this->assertArrayHasKey( 'nonce', $data, "No nonce in data" );
		$this->assertEquals( 'API Nonce', $data['nonce'], "Unexpected nonce value" );
	}

	/**
	 * @param $data
	 *
	 * @depends test_json_response_includes_base64_encoded_qr_code_json_string
	 */
	public function test_json_response_qr_code_includes_payload( $data ) {
		$this->assertArrayHasKey( 'payload', $data, "No payload in data" );
		$this->assertInternalType( 'array', $data['payload'], "Unexpected type for payload" );

		return $data['payload'];
	}

	/**
	 * @param $payload
	 *
	 * @depends test_json_response_qr_code_includes_payload
	 */
	public function test_json_response_qr_code_payload_has_callback_url( $payload ) {
		$this->assertArrayHasKey( 'callback_url', $payload, "No callback_url in data" );
		$this->assertEquals( 'Callback URL', $payload['callback_url'], "Unexpected callback_url value" );
	}

	/**
	 * @param $payload
	 *
	 * @depends test_json_response_qr_code_includes_payload
	 */
	public function test_json_response_qr_code_payload_has_site_name( $payload ) {
		$this->assertArrayHasKey( 'rocket_name', $payload, "No rocket_name in data" );
		$this->assertEquals( 'blog info - name', $payload['rocket_name'], "Unexpected rocket_name value" );
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

		Phake::when( $this->admin )->check_option( Phake::anyParameters() )->thenReturn( array( array(), array() ) );

		Phake::when( $this->admin )->get_callback_url()->thenReturn( 'Callback URL' );

		Phake::when( $this->facade )->get_bloginfo( Phake::anyParameters() )
		     ->thenReturnCallback( function ( $function, $parameters ) {
			     return 'blog info - ' . $parameters[0];
		     } );

		Phake::when( $this->facade )->wp_create_nonce( Phake::anyParameters() )->thenReturn( 'Nonce' );

		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );

		Phake::when( $this->facade )->__( Phake::anyParameters() )->thenReturnCallback( function (
			$method,
			$parameters
		) {
			return sprintf( 'TRANSLATED [%s]', $parameters[0] );
		} );

		Phake::when( $this->facade )->current_user_can( Phake::anyParameters() )->thenReturn( true );

		Phake::when( $this->client )->auth()->thenReturn( $this->auth );

		Phake::when( $this->auth )->nonce()->thenReturn( new \LaunchKey\SDK\Domain\NonceResponse(
				'API Nonce',
				new DateTime( '+1 day' ) )
		);

  		$this->user = new WP_User();
		Phake::when($this->facade)->wp_get_current_user()->thenReturn( $this->user );

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_POST['action']           = null;
		$_POST['nonce']            = 'expected';

		$this->is_multi_site = false;

		$this->wizard = new LaunchKey_WP_Configuration_Wizard(
			$this->facade,
			$this->admin,
			$this->crypt,
			$this->is_multi_site,
			$this->client
		);
	}

	protected function tearDown() {
		$this->wizard        = null;
		$this->facade        = null;
		$this->admin         = null;
		$this->client        = null;
		$this->is_multi_site = null;
		$this->crypt         = null;

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
