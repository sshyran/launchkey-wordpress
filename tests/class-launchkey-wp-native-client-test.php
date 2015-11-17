<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Native_Client_Test extends LaunchKey_WP_Native_Client_Test_Abstract {

	public function test_register_actions_registers_show_powered_by_when_white_label_implmentation() {
		Phake::when( $this->facade )
		     ->get_option( Phake::anyParameters() )
		     ->thenReturn( array( LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => LaunchKey_WP_Implementation_Type::WHITE_LABEL ) );
		$this->client->register_actions();
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
		Phake::verify( $this->facade )->add_action( 'login_form', array( $this->client, 'show_powered_by' ) );
	}

	public function provider_non_white_label_implementation_types() {
		return array(
			array( LaunchKey_WP_Implementation_Type::OAUTH ),
			array( LaunchKey_WP_Implementation_Type::NATIVE ),
		);
	}

	/**
	 * @dataProvider provider_non_white_label_implementation_types
	 *
	 * @param $type
	 */
	public function test_register_actions_registers_does_not_show_powered_by_when_not_white_label_implmentation( $type ) {
		Phake::when( $this->facade )
		     ->get_option( Phake::anyParameters() )
		     ->thenReturn( array( LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => $type ) );
		$this->client->register_actions();
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
		Phake::verify( $this->facade, Phake::never() )->add_action( 'login_form', array(
			$this->client,
			'show_powered_by'
		) );
	}

	public function test_register_actions_registers_login_form_progress_bar() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'login_form', array( $this->client, 'progress_bar' ) );
	}

	public function test_register_actions_registers_authentication_controller() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter( 'authenticate', array(
			$this->client,
			'authentication_controller'
		), 0, 3 );
	}

	public function test_register_actions_registers_launchkey_authentication_check() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter( 'init', array(
				$this->client,
				'launchkey_still_authenticated_page_load'
		), 999, 3 );
	}

	public function test_register_actions_registers_launchkey_heartbeat_authentication_check() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter( 'heartbeat_received', array(
				$this->client,
				'launchkey_still_authenticated_heartbeat'
		) );
	}

	public function test_register_actions_registers_shake_error_codes() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter( 'shake_error_codes', array(
			$this->client,
			'register_shake_error_codes'
		) );
	}

	public function test_register_actions_registers_logout() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter( 'wp_logout', array( $this->client, 'logout' ) );
	}

	public function provider_admin_actions() {
		return array(
			'LaunchKey Callback w/privs'         => array(
				'wp_ajax_' . LaunchKey_WP_Native_Client::CALLBACK_AJAX_ACTION,
				'launchkey_callback'
			),
			'LaunchKey Callback wo/privs'        => array(
				'wp_ajax_nopriv_' . LaunchKey_WP_Native_Client::CALLBACK_AJAX_ACTION,
				'launchkey_callback'
			),
			'Pair Callback'                      => array( 'personal_options_update', 'pair_callback' ),
			'Pair Errors Callback'               => array( 'user_profile_update_errors', 'pair_errors_callback' ),
			'White Label Pair Callback w/privs'  => array(
				'wp_ajax_' . LaunchKey_WP_Native_Client::WHITE_LABEL_PAIR_ACTION,
				'white_label_pair_callback'
			),
			'White Label Pair Callback wo/privs' => array(
				'wp_ajax_nopriv_' . LaunchKey_WP_Native_Client::WHITE_LABEL_PAIR_ACTION,
				'white_label_pair_callback'
			),
		);
	}

	/**
	 * @dataProvider provider_admin_actions
	 *
	 * @param $action
	 * @param $callback
	 */
	public function test_register_actions_registers_admin_actions_when_admin( $action, $callback ) {
		Phake::when( $this->facade )->is_admin()->thenReturn( true );
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( $action, array( $this->client, $callback ) );
	}

	/**
	 * @dataProvider provider_admin_actions
	 *
	 * @param $action
	 * @param $callback
	 */
	public function test_register_actions_does_not_register_admin_actions_when_not_admin( $action, $callback ) {
		Phake::when( $this->facade )->is_admin()->thenReturn( false );
		$this->client->register_actions();
		Phake::verify( $this->facade, Phake::never() )->add_action( $action, array( $this->client, $callback ) );
	}

	public function test_show_powered_by() {
		$this->client->show_powered_by();
		Phake::verify( $this->template )->render_template( 'powered-by-launchkey' );
		Phake::verify( $this->facade )->_echo( 'Rendered: powered-by-launchkey' );
	}

	public function test_progress_bar() {
		$this->client->progress_bar();
		Phake::verify( $this->template )->render_template( 'progress-bar', array( 'processing' => 'Processing login' ) );
		Phake::verify( $this->facade )->_echo( 'Rendered: progress-bar' );
	}

	public function test_register_shake_error_codes() {
		$expected = array(
			'pre-existing',
			'launchkey_authentication_timeout',
			'launchkey_authentication_denied',
			'launchkey_authentication_error'
		);
		$actual   = $this->client->register_shake_error_codes( array( 'pre-existing' ) );
		$this->assertEquals( $expected, $actual );
	}

	public function test_register_registers_show_native_login_hint() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'login_form', array( $this->client, 'show_native_login_hint' ) );
	}

	public function test_show_native_login_hint_shows_hint_with_correct_app_display_name() {
		Phake::when( $this->facade )
		     ->get_option( Phake::anyParameters() )
		     ->thenReturn( array( LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME => 'Expected Display Name' ) );
		$this->client->show_native_login_hint();
		Phake::verify( $this->template )->render_template(
			'native-login-hint',
			array( 'app_display_name' => 'Expected Display Name' )
		);
	}

	public function test_register_registers_uses_option_when_not_multi_site() {
		$client = new LaunchKey_WP_Native_Client(
				$this->sdk_client, $this->facade, $this->template, $this->language_domain = 'Test Language Domain', false
		);
		$client->register_actions();
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_register_registers_uses_site_option_when_multi_site() {
		$client = new LaunchKey_WP_Native_Client(
				$this->sdk_client, $this->facade, $this->template, $this->language_domain = 'Test Language Domain', true
		);
		$client->register_actions();
		Phake::verify( $this->facade )->get_site_option( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_show_native_login_hint_uses_option_when_not_multi_site() {
		$client = new LaunchKey_WP_Native_Client(
				$this->sdk_client, $this->facade, $this->template, $this->language_domain = 'Test Language Domain', false
		);
		$client->show_native_login_hint();
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_show_native_login_hint_uses_site_option_when_multi_site() {
		$client = new LaunchKey_WP_Native_Client(
				$this->sdk_client, $this->facade, $this->template, $this->language_domain = 'Test Language Domain', true
		);
		$client->show_native_login_hint();
		Phake::verify( $this->facade )->get_site_option( LaunchKey_WP_Admin::OPTION_KEY );
	}
}
