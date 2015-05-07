<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Admin_Test extends PHPUnit_Framework_TestCase {
	/**
	 * @var array
	 */
	public $options;
	/**
	 * @var LaunchKey_WP_Admin
	 */
	private $admin;
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
	 * @var striong
	 */
	private $language_domain;

	public function test_register_actions_adds_filter_for_plugin_action_links() {
		Phake::when( $this->facade )->plugin_basename( Phake::anyParameters() )->thenReturn( 'BASENAME' );
		Phake::when( $this->facade )->plugin_dir_path( Phake::anyParameters() )->thenReturn( 'DIRPATH' );
		$this->admin->register_actions();
		$reflection_class = new ReflectionClass( 'LaunchKey_WP_Admin' );
		Phake::inOrder(
			Phake::verify( $this->facade )->plugin_dir_path(
				dirname( $reflection_class->getFileName() )
			),
			Phake::verify( $this->facade )->plugin_basename( 'DIRPATH' . 'launchkey.php' ),
			Phake::verify( $this->facade )->add_filter( 'plugin_action_links_BASENAME', array(
				$this->admin,
				'add_action_links'
			) )
		);
	}

	public function test_create_admin_page_echos_form_correctly() {
		$this->options[ LaunchKey_WP_Options::OPTION_ROCKET_KEY ]       = 'Expected Rocket Key';
		$this->options[ LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME ] = 'Expected App Display Name';
		$this->options[ LaunchKey_WP_Options::OPTION_SSL_VERIFY ]       = true;
		$expected_context                                               = array(
			'rocket_key'         => 'Expected Rocket Key',
			'app_display_name'   => 'Expected App Display Name',
			'callback_url'       => sprintf( 'AdminURL [admin-ajax.php?action=%s]', LaunchKey_WP_Native_Client::CALLBACK_AJAX_ACTION ),
			'ssl_verify_checked' => 'checked="checked"'
		);
		$this->admin->create_launchkey_settings_page();
		Phake::verify( $this->template )->render_template( 'admin/settings', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [admin/settings]' );


		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_launchkey_plugin_page_registers_create_admin_page() {
		$this->admin->add_launchkey_admin_menus();
		Phake::verify( $this->facade )->add_options_page( 'LaunchKey', 'LaunchKey', 'manage_options', 'launchkey-settings',
			array( $this->admin, 'create_launchkey_settings_page' ) );
	}

	public function test_register_actions_adds_oauth_notice() {
		$this->admin->register_actions();
		Phake::verify( $this->facade )->add_action( 'admin_notices', array(
			$this->admin,
			'oauth_warning'
		) );
	}


	function provider_non_oauth_implementation_types() {
		return array(
			array( LaunchKey_WP_Implementation_Type::NATIVE ),
			array( LaunchKey_WP_Implementation_Type::WHITE_LABEL )
		);
	}


	public function test_oauth_warning_displays_for_oauth_implementation_type() {
		$this->options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = LaunchKey_WP_Implementation_Type::OAUTH;
		$this->admin->oauth_warning();
		Phake::verify( $this->template )->render_template( 'admin/oauth-deprecation-warning', array() );
		Phake::verify( $this->facade )->_echo( 'Rendered [admin/oauth-deprecation-warning]' );
	}

	/**
	 * @dataProvider provider_non_oauth_implementation_types
	 * @depends      test_oauth_warning_displays_for_oauth_implementation_type
	 *
	 * @param $implementation_type
	 */
	public function test_oauth_warning_does_nothing_if_implementation_type_is_not_oauth( $implementation_type ) {
		$this->options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = $implementation_type;
		$this->admin->oauth_warning();
		Phake::verify( $this->template, Phake::never() )->render( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->_echo( Phake::anyParameters() );
	}

	public function provider_notice_hook_suffixes() {
		return array(
			array( 'plugins.php' ),
			array( 'users.php' ),
			array( 'profile.php' ),
		);
	}

	/**
	 * @dataProvider provider_notice_hook_suffixes
	 *
	 * @param $hook_suffix
	 */
	public function test_activate_notice_shows_correct_template_on_correct_pages_when_no_secret_key( $hook_suffix ) {
		Phake::when( $this->facade )->get_hook_suffix()->thenReturn( $hook_suffix );
		$this->admin->activate_notice();
		Phake::verify( $this->template )->render_template( 'admin/activate-plugin', $this->anything() );
		Phake::verify( $this->facade )->_echo( 'Rendered [admin/activate-plugin]' );
	}

	/**
	 * @dataProvider provider_notice_hook_suffixes
	 *
	 * @param $hook_suffix
	 */
	public function test_activate_notice_shows_correct_template_on_correct_pages_when_secret_key( $hook_suffix ) {
		$this->options[ LaunchKey_WP_Options::OPTION_SECRET_KEY ] = 'Not Empty';
		Phake::when( $this->facade )->get_hook_suffix()->thenReturn( $hook_suffix );
		$this->admin->activate_notice();
		Phake::verify( $this->template, Phake::never() )->render_template( 'admin/activate-plugin', $this->anything() );
	}

	public function test_add_action_links_adds_link_for_settings() {
		$actual = $this->admin->add_action_links( array() );
		$this->assert_array_contains(
			'<a href="AdminURL [options-general.php?page=launchkey-settings]">TRANSLATED [Settings]</a>',
			$actual
		);
	}

	public function test_add_action_links_adds_link_for_wizard() {
		$actual = $this->admin->add_action_links( array() );
		$this->assert_array_contains(
			'<a href="AdminURL [tools.php?page=launchkey-config-wizard]">TRANSLATED [Wizard]</a>',
			$actual
		);
	}

	public function test_add_action_links_removes_link_for_edit() {
		$standard_links = array( 'edit' => '<a href="plugin-editor.php?file=akismet/akismet.php" title="Open this file in the Plugin Editor" class="edit">Edit</a>' );
		$actual         = $this->admin->add_action_links( $standard_links );
		$this->assertArrayNotHasKey( 'edit', $actual );
	}


	protected function setUp() {
		Phake::initAnnotations( $this );
		$this->options = array();
		Phake::when( $this->facade )->settings_fields( Phake::anyParameters() )->thenReturn( 'settings_fields response' );
		Phake::when( $this->facade )->do_settings_sections( Phake::anyParameters() )->thenReturn( 'do_settings_sections response' );
		Phake::when( $this->facade )->submit_button( Phake::anyParameters() )->thenReturn( 'submit_button response' );
		$that = $this;
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );
		Phake::when( $this->template )->render_template( Phake::anyParameters() )->thenReturnCallback( function ( $template ) {
			return sprintf( 'Rendered [%s]', $template );
		} );
		Phake::when( $this->facade )->__( Phake::anyParameters() )->thenReturnCallback( function ( $method, $parameters ) {
			return sprintf( 'TRANSLATED [%s]', $parameters[0] );
		} );
		Phake::when( $this->facade )->admin_url( Phake::anyParameters() )->thenReturnCallback( function ( $method, $parameters ) {
			return sprintf( 'AdminURL [%s]', $parameters[0] );
		} );
		$this->admin = new LaunchKey_WP_Admin( $this->facade, $this->template, $this->language_domain );
	}

	protected function tearDown() {
		$this->admin    = null;
		$this->options  = null;
		$this->facade   = null;
		$this->template = null;
	}

	private function assert_array_contains( $value, $array ) {
		$found = array_filter( $array, function ( $array_value ) use ( $value ) {
			return ( $value == $array_value );
		} );
		$this->assertGreaterThanOrEqual( 1, count( $found ), sprintf( 'Failed to assert that array contains ' . $value ) );

	}
}
