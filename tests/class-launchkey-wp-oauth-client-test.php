<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_OAuth_Client_Test extends PHPUnit_Framework_TestCase {
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
	 * @var LaunchKey_WP_Options
	 */
	private $options;

	/**
	 * @Mock
	 * @var LaunchKey_WP_Template
	 */
	private $template;

	public function test_register_actions_adds_action_login_form() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'login_form', array( $this->client, 'launchkey_form' ) );
	}

	public function test_register_actions_adds_action_wp_login() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'wp_login', array( $this->client, 'launchkey_pair' ), 1, 2 );
	}

	public function test_register_actions_adds_action_wp_logout() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'wp_logout', array( $this->client, 'launchkey_logout' ), 1, 2 );
	}

	public function test_register_actions_adds_short_code_launchkey_login() {
		$this->client->register_actions();
		Phake::verify( $this->facade )
		     ->add_shortcode( 'launchkey_login', array( $this->client, 'launchkey_shortcode' ) );
	}

	public function test_register_admin_actions_adds_action_wp_ajax_launchkey_callback_when_admin() {
		Phake::when($this->facade)->is_admin()->thenReturn(true);
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'wp_ajax_launchkey-callback', array(
			$this->client,
			'launchkey_callback'
		) );
	}

	public function test_register_admin_actions_adds_action_wp_ajax_nopriv_launchkey_callback_when_admin() {
		Phake::when($this->facade)->is_admin()->thenReturn(true);
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'wp_ajax_nopriv_launchkey-callback', array(
			$this->client,
			'launchkey_callback'
		) );
	}

	public function test_register_admin_actions_adds_action_admin_init_when_admin() {
		Phake::when($this->facade)->is_admin()->thenReturn(true);
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'admin_init', array(
			$this->client,
			'launchkey_admin_callback'
		) );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		Phake::when( $this->facade )->get_launchkey_options()->thenReturn( $this->options );
		$this->client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, false);
	}

	protected function tearDown() {
		$this->client   = null;
		$this->options  = null;
		$this->facade   = null;
		$this->template = null;
	}
}
