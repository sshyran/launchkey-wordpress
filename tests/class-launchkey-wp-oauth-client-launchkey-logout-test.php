<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_OAuth_Client_LaunchKey_Logout_Test extends PHPUnit_Framework_TestCase {
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
	 * @var LaunchKey_WP_Template
	 */
	private $template;

	public function test_launchkey_logout_without_access_token_cookie_does_not_get_token() {
		$this->client->launchkey_logout();
		Phake::verify( $this->facade, Phake::never() )->wp_remote_get( Phake::anyParameters() );
	}

	public function test_launchkey_logout_without_access_token_cookie_does_not_set_cookie() {
		$this->client->launchkey_logout();
		Phake::verify( $this->facade, Phake::never() )->setcookie( Phake::anyParameters() );
	}

	public function test_launchkey_logout_with_ssl_verify_off_sets_sslverify_false() {
		$_COOKIE['launchkey_access_token'] = 'access token';
		$this->options[LaunchKey_WP_Options::OPTION_SSL_VERIFY] = false;
		$this->client->launchkey_logout();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'sslverify', $options );
		$this->assertFalse( $options['sslverify'] );
	}

	public function test_launchkey_logout_with_ssl_verify_on_sets_sslverify_true() {
		$_COOKIE['launchkey_access_token'] = 'access token';
		$this->options[LaunchKey_WP_Options::OPTION_SSL_VERIFY] = true;
		$this->client->launchkey_logout();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'sslverify', $options );
		$this->assertTrue( $options['sslverify'] );
	}

	public function test_launchkey_logout_sets_timeout() {
		$_COOKIE['launchkey_access_token'] = 'access token';
		$this->client->launchkey_logout();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'timeout', $options );
		$this->assertEquals( $this->options[LaunchKey_WP_Options::OPTION_REQUEST_TIMEOUT], $options['timeout'] );
	}

	public function test_launchkey_logout_sets_connection_close_header() {
		$_COOKIE['launchkey_access_token'] = 'access token';
		$this->client->launchkey_logout();
		Phake::verify( $this->facade )->wp_remote_get( $this->anything(), Phake::capture( $options ) );
		$this->assertArrayHasKey( 'headers', $options );
		$this->assertInternalType('array', $options['headers']);
		$this->assertEquals( $this->options[LaunchKey_WP_Options::OPTION_REQUEST_TIMEOUT], $options['timeout'] );
	}

	public function cookie_name_provider() {
		return array(
			array( 'launchkey_user' ),
			array( 'launchkey_access_token' ),
			array( 'launchkey_refresh_token' ),
			array( 'launchkey_expires' ),
		);
	}

	/**
	 * @param $cookie_name
	 *
	 * @dataProvider cookie_name_provider
	 */
	public function test_launchkey_logout_expires_cookies( $cookie_name ) {
		$_COOKIE['launchkey_access_token'] = 'access token';
		$this->client->launchkey_logout();
		Phake::verify( $this->facade )->current_time( 'timestamp', true );
		Phake::verify( $this->facade )
		     ->setcookie( $cookie_name, '1', $this->current_time - 60, COOKIEPATH, COOKIE_DOMAIN );
	}

	public function test_uses_get_option_when_not_multi_site() {
		$client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, false);
		$client->launchkey_logout();
		Phake::verify( $this->facade )->get_option ( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_uses_get_site_option_when_multi_site() {
		$client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, true);
		$client->launchkey_logout();
		Phake::verify( $this->facade )->get_site_option ( LaunchKey_WP_Admin::OPTION_KEY );
	}

	protected function setUp() {
		$that = $this;
		Phake::initAnnotations( $this );
		Phake::when( $this->facade )->current_time( Phake::anyParameters() )->thenReturn(
			$this->current_time = rand( 10000, 99999 ) );
		$this->options = array( LaunchKey_WP_Options::OPTION_SSL_VERIFY => null, LaunchKey_WP_Options::OPTION_REQUEST_TIMEOUT => 111 );
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );

		$this->client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, false);
	}

	protected function tearDown() {
		$this->client   = null;
		$this->facade   = null;
		$this->template = null;

		foreach ( array_keys( $_COOKIE ) as $key ) {
			unset( $_COOKIE[ $key ] );
		}
	}
}
