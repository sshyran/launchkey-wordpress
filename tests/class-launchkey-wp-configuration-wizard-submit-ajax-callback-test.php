<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

/**
 * Class LaunchKey_WP_Configuration_Wizard_Test
 */
class LaunchKey_WP_Configuration_Wizard_Submit_Ajax_Test extends PHPUnit_Framework_TestCase {

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
		$this->wizard->wizard_submit_ajax();
		Phake::verifyNoInteraction( $this->facade );
	}

	public function test_verifies_nonce_when_present() {
		$_POST['nonce'] = 'expected';
		$this->wizard->wizard_submit_ajax();
		Phake::verify( $this->facade )->wp_verify_nonce( 'expected', LaunchKey_WP_Configuration_Wizard::WIZARD_NONCE_KEY );
	}

	public function test_does_nothing_but_create_nonce_when_nonce_is_invalid() {
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( false );
		$this->wizard->wizard_submit_ajax();
		Phake::verify( $this->facade )->wp_verify_nonce( Phake::anyParameters() );
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_does_nothing_when_user_cannot_manage_options() {
		Phake::when( $this->facade )->current_user_can( 'manage_options' )->thenReturn( false );
		$this->wizard->wizard_submit_ajax();
		Phake::verify( $this->facade )->current_user_can( 'manage_options' );
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_checks_option() {
		$this->wizard->wizard_submit_ajax();
		Phake::verify( $this->admin )->check_option( $_POST );
	}

	public function test_updates_option_when_check_option_returns_no_errors_and_not_multi_site() {
		$wizard = new LaunchKey_WP_Configuration_Wizard(
				$this->facade,
				$this->admin,
				$this->crypt,
				false,
				$this->client
		);
		$expected_option = array( 'expected' => 'option' );
		Phake::when( $this->admin )->check_option( Phake::anyParameters() )->thenReturn( array(
				$expected_option,
				array()
		) );
		$wizard->wizard_submit_ajax();
		Phake::verify( $this->facade )->update_option( LaunchKey_WP_Admin::OPTION_KEY, $expected_option );
	}

	public function test_updates_site_option_when_check_option_returns_no_errors_and_multi_site() {
		$wizard = new LaunchKey_WP_Configuration_Wizard(
				$this->facade,
				$this->admin,
				$this->crypt,
				true,
				$this->client
		);
		$expected_option = array( 'expected' => 'option' );
		Phake::when( $this->admin )->check_option( Phake::anyParameters() )->thenReturn( array(
				$expected_option,
				array()
		) );
		$wizard->wizard_submit_ajax();
		Phake::verify( $this->facade )->update_site_option( LaunchKey_WP_Admin::OPTION_KEY, $expected_option );
	}

	public function test_does_not_update_option_when_check_option_returns_errors() {
		Phake::when( $this->admin )->check_option( Phake::anyParameters() )->thenReturn( array(
			array(),
			array( 'error' )
		) );
		$this->wizard->wizard_submit_ajax();
		Phake::verify( $this->facade, Phake::never() )->update_option( Phake::anyParameters() );
	}


	protected function setUp() {
		$that = $this;
		$this->options_data = array(
			LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => LaunchKey_WP_Implementation_Type::NATIVE,
			LaunchKey_WP_Options::OPTION_ROCKET_KEY => 12345,
			LaunchKey_WP_Options::OPTION_SECRET_KEY => 'Secret Key',
			LaunchKey_WP_Options::OPTION_PRIVATE_KEY => 'Private Key',
			LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME => 'LaunchKey',
			LaunchKey_WP_Options::OPTION_SSL_VERIFY => true,
		);


		Phake::initAnnotations( $this );

		Phake::when( $this->admin )->check_option( Phake::anyParameters() )->thenReturn( array( array(), array() ) );

		Phake::when( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY )->thenReturnCallback(
			function () use ( $that ) {
				return $that->options_data;
			}
		);

		Phake::when( $this->facade )->wp_create_nonce( Phake::anyParameters() )->thenReturn( 'Nonce' );

		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );

		Phake::when( $this->facade )->__( Phake::anyParameters() )->thenReturnCallback( function ( $method, $parameters ) {
			return sprintf( 'TRANSLATED [%s]', $parameters[0] );
		} );

		Phake::when( $this->facade )->current_user_can( Phake::anyParameters() )->thenReturn( true );

		$this->user = new WP_User();

		Phake::when($this->facade)->get_current_user()->thenReturn( $this->user );

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_POST['action'] = null;
		$_POST['nonce'] = 'expected';

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
		$this->wizard = null;
		$this->facade = null;
		$this->admin = null;
		$this->client = null;
		$this->is_multi_site = null;
		$this->crypt = null;

		foreach ( array_keys( $_SERVER ) as $key ) {
			unset( $_SERVER[$key] );
		}

		foreach ( array_keys( $_POST ) as $key ) {
			unset( $_POST[$key] );
		}

		foreach ( array_keys( $_GET ) as $key ) {
			unset( $_GET[$key] );
		}
	}
}
