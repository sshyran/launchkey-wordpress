<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

/**
 * Class LaunchKey_WP_Configuration_Wizard_Test
 */
class LaunchKey_WP_Configuration_Wizard_Test extends PHPUnit_Framework_TestCase {
	/**
	 * @var array
	 */
	public $option_data;
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
	 * @var LaunchKey\SDK\Client
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

	public function test_register_actions_registers_verify_native_ajax_callback_handler() {
		$this->wizard->register_actions();
		Phake::verify( $this->facade )->add_action(
			'wp_ajax_' . LaunchKey_WP_Configuration_Wizard::VERIFY_CONFIG_AJAX_ACTION,
			array( $this->wizard, 'verify_configuration_callback' )
		);
	}

	public function test_register_actions_registers_enqueue_native_script_method() {
		$this->wizard->register_actions();
		Phake::verify( $this->facade )->add_filter( 'init', array(
			$this->wizard,
			'enqueue_verify_configuration_script'
		) );
	}

	public function test_enqueue_verifier_native_script_does_not_enqueue_script_if_user_cannot_manage_options() {
		Phake::when( $this->facade )->current_user_can( 'manage_options' )->thenReturn( false );
		$this->wizard->enqueue_verify_configuration_script();
		Phake::verify( $this->facade )->current_user_can( 'manage_options' );
		Phake::verify( $this->facade, Phake::never() )->wp_enqueue_script( Phake::anyParameters() );
	}

	public function test_enqueue_verifier_native_script_enqueues_script() {
		$this->wizard->enqueue_verify_configuration_script();
		Phake::verify( $this->facade )->wp_enqueue_script(
			'launchkey-config-verifier-native-script',
			'Plugins URL',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}

	public function test_enqueue_verifier_native_script_supplies_correct_data_for_plugins_url() {
		$this->wizard->enqueue_verify_configuration_script();
		$reflection = new ReflectionClass( 'LaunchKey_WP_Configuration_Wizard' );
		Phake::verify( $this->facade )->plugins_url(
			'/public/launchkey-config-verifier.js',
			dirname( $reflection->getFileName() )
		);
	}

	public function test_enqueue_verifier_native_script_localizes_script() {
		$this->option_data[LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE] = LaunchKey_WP_Implementation_Type::OAUTH;
		$this->wizard->enqueue_verify_configuration_script();
		Phake::verify( $this->facade )->wp_localize_script(
				'launchkey-config-verifier-native-script',
				'launchkey_verifier_config',
				array(
						'url' => 'Admin URL',
						'implementation_type' => LaunchKey_WP_Implementation_Type::OAUTH,
						'nonce' => 'Nonce',
						'is_configured' => false,
				)
		);
	}

	public function test_enqueue_verifier_native_script_gets_launchkey_option_when_not_multi_site() {
		$wizard = new LaunchKey_WP_Configuration_Wizard(
				$this->facade,
				$this->admin,
				false,
				$this->client
		);
		$wizard->enqueue_verify_configuration_script();
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_enqueue_verifier_native_script_gets_launchkey_site_option_when_multi_site() {
		$wizard = new LaunchKey_WP_Configuration_Wizard(
				$this->facade,
				$this->admin,
				true,
				$this->client
		);
		$wizard->enqueue_verify_configuration_script();
		Phake::verify( $this->facade )->get_site_option( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_enqueue_verifier_native_script_supplies_admin_url_correct_value() {
		$this->wizard->enqueue_verify_configuration_script();
		Phake::verify( $this->facade )->admin_url(
			'admin-ajax.php?action=' . LaunchKey_WP_Configuration_Wizard::VERIFY_CONFIG_AJAX_ACTION
		);
	}

	public function test_enqueue_verifier_native_script_supplies_create_nonce_correct_key() {
		$this->wizard->enqueue_verify_configuration_script();
		Phake::verify( $this->facade )->wp_create_nonce(
			LaunchKey_WP_Configuration_Wizard::VERIFIER_NONCE_KEY
		);
	}

	public function test_enqueue_verifier_native_script_localizes_script_before_enqueueing() {
		$this->wizard->enqueue_verify_configuration_script();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_enqueue_script( Phake::anyParameters() ),
			Phake::verify( $this->facade )->wp_localize_script( Phake::anyParameters() )
		);
	}

	public function test_enqueue_verifier_native_script_uses_same_slug_for_enqueueing_and_localizing() {
		$this->wizard->enqueue_verify_configuration_script();
		Phake::verify( $this->facade )->wp_enqueue_script(
			'launchkey-config-verifier-native-script',
			$this->anything(),
			$this->anything(),
			$this->anything(),
			$this->anything()
		);
		Phake::verify( $this->facade )->wp_localize_script(
			'launchkey-config-verifier-native-script',
			$this->anything(),
			$this->anything()
		);
	}


	public function test_register_actions_registers_wizard_submit_ajax() {
		$this->wizard->register_actions();
		Phake::verify( $this->facade )->add_action(
			'wp_ajax_' . LaunchKey_WP_Configuration_Wizard::DATA_SUBMIT_AJAX_ACTION,
			array( $this->wizard, 'wizard_submit_ajax' )
		);
	}

	public function test_register_actions_registers_enqueue_wizard_script_method() {
		$this->wizard->register_actions();
		Phake::verify( $this->facade )->add_filter( 'init', array(
			$this->wizard,
			'enqueue_wizard_script'
		) );
	}

	public function test_enqueue_wizard_script_enqueues_script() {
		$this->wizard->enqueue_wizard_script();
		Phake::verify( $this->facade )->wp_enqueue_script(
			'launchkey-wizard-script',
			'Plugins URL',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}

	public function test_enqueue_wizard_script_supplies_correct_data_for_plugins_url() {
		$this->wizard->enqueue_wizard_script();
		$reflection = new ReflectionClass( 'LaunchKey_WP_Configuration_Wizard' );
		Phake::verify( $this->facade )->plugins_url(
			'/public/launchkey-wizard.js',
			dirname( $reflection->getFileName() )
		);
	}

	public function test_enqueue_wizard_script_localizes_script() {
		$this->option_data[LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE] = LaunchKey_WP_Implementation_Type::OAUTH;
		$this->wizard->enqueue_wizard_script();
		Phake::verify( $this->facade )->wp_localize_script(
			'launchkey-wizard-script',
			'launchkey_wizard_config',
			array(
				'nonce' => 'Nonce',
				'is_configured' => false,
				'implementation_type' => LaunchKey_WP_Implementation_Type::OAUTH,
				'url' => 'Admin URL'
			)
		);
	}

	public function test_enqueue_wizard_script_supplies_admin_url_correct_value() {
		$this->wizard->enqueue_wizard_script();
		Phake::verify( $this->facade )->admin_url(
			'admin-ajax.php?action=' . LaunchKey_WP_Configuration_Wizard::DATA_SUBMIT_AJAX_ACTION
		);
	}

	public function test_enqueue_wizard_script_supplies_create_nonce_correct_key() {
		$this->wizard->enqueue_wizard_script();
		Phake::verify( $this->facade )->wp_create_nonce(
			LaunchKey_WP_Configuration_Wizard::WIZARD_NONCE_KEY
		);
	}

	public function test_enqueue_wizard_script_localizes_script_before_enqueueing() {
		$this->wizard->enqueue_wizard_script();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_enqueue_script( Phake::anyParameters() ),
			Phake::verify( $this->facade )->wp_localize_script( Phake::anyParameters() )
		);
	}

	public function test_enqueue_wizard_script_uses_same_slug_for_enqueueing_and_localizing() {
		$this->wizard->enqueue_wizard_script();
		Phake::verify( $this->facade )->wp_enqueue_script(
			'launchkey-wizard-script',
			$this->anything(),
			$this->anything(),
			$this->anything(),
			$this->anything()
		);
		Phake::verify( $this->facade )->wp_localize_script(
			'launchkey-wizard-script',
			$this->anything(),
			$this->anything()
		);
	}

	public function test_enqueue_wizard_script_gets_launchkey_option_when_not_multi_site() {
		$wizard = new LaunchKey_WP_Configuration_Wizard(
				$this->facade,
				$this->admin,
				false,
				$this->client
		);
		$wizard->enqueue_wizard_script();
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_enqueue_wizard_script_gets_launchkey_site_option_when_multi_site() {
		$wizard = new LaunchKey_WP_Configuration_Wizard(
				$this->facade,
				$this->admin,
				true,
				$this->client
		);
		$wizard->enqueue_wizard_script();
		Phake::verify( $this->facade )->get_site_option( LaunchKey_WP_Admin::OPTION_KEY );
	}
	protected function setUp() {
		$that = $this;
		Phake::initAnnotations( $this );

		$this->is_multi_site = false;

		$this->wizard = new LaunchKey_WP_Configuration_Wizard(
			$this->facade,
			$this->admin,
			$this->is_multi_site,
			$this->client
		);

		Phake::when( $this->facade )->plugins_url( Phake::anyParameters() )->thenReturn( 'Plugins URL' );

		Phake::when( $this->facade )->admin_url( Phake::anyParameters() )->thenReturn( 'Admin URL' );

		Phake::when( $this->facade )->wp_create_nonce( Phake::anyParameters() )->thenReturn( 'Nonce' );

		Phake::when( $this->facade )->current_user_can( Phake::anyParameters() )->thenReturn( true );

		$this->option_data = array( LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => LaunchKey_WP_Implementation_Type::NATIVE );

		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->option_data;
		} );
	}

	protected function tearDown() {
		$this->wizard = null;
		$this->facade = null;
		$this->admin = null;
		$this->is_multi_site = false;
		$this->client = null;
	}
}
