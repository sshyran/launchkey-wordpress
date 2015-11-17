<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
abstract class LaunchKey_WP_Native_Client_Test_Abstract extends PHPUnit_Framework_TestCase {

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Client
	 */
	protected $sdk_client;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Service\AuthService
	 */
	protected $sdk_auth;

	/**
	 * @Mock
	 * @var \LaunchKey\SDK\Domain\AuthRequest
	 */
	protected $auth_request;

	/**
	 * @Mock
	 * @var LaunchKey_WP_Global_Facade
	 */
	protected $facade;

	/**
	 * @Mock
	 * @var LaunchKey_WP_Template
	 */
	protected $template;

	/**&
	 * @var string
	 */
	protected $language_domain;

	/**
	 * @var LaunchKey_WP_Native_Client
	 */
	protected $client;

	/**
	 * @Mock
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * @Mock
	 * @var WP_User
	 */
	protected $user;

	protected function setUp() {
		Phake::initAnnotations( $this );

		$this->client = new LaunchKey_WP_Native_Client(
				$this->sdk_client, $this->facade, $this->template, $this->language_domain = 'Test Language Domain', false
		);

		Phake::when( $this->sdk_client )->auth()->thenReturn( $this->sdk_auth );
		Phake::when( $this->sdk_auth )->authenticate( Phake::anyParameters() )->thenReturn( $this->auth_request );

		Phake::when( $this->template )->render_template( Phake::anyParameters() )->thenReturnCallback( function ( $template ) {
			return 'Rendered: ' . $template;
		} );

		Phake::when( $this->facade )->__( Phake::anyParameters() )->thenReturnCallback( function ( $method, $parameters ) {
			return sprintf( 'Translated [%s] with [%s]', $parameters[0], $parameters[1] );
		} );
		Phake::when( $this->facade )->get_wpdb()->thenReturn( $this->wpdb );
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( $this->user );

		$this->user->launchkey_username = null;
		$this->user->ID = 'User ID';

		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'true' );
		$this->wpdb->usermeta = 'usermeta_table_name';

		Phake::when( $this->facade )->is_debug_log()->thenReturn( false );
	}

	protected function tearDown() {
		$this->client     = null;
		$this->sdk_client = null;
		$this->facade     = null;
		$this->template   = null;
		$this->user       = null;
	}

}
