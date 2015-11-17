<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_OAuth_Client_LaunchKey_Form_Test extends PHPUnit_Framework_TestCase {
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
	 * @var array
	 */
	public $options;

	/**
	 * @Mock
	 * @var LaunchKey_WP_Template
	 */
	private $template;


	public function test_launchkey_error_shows_auth_error_template_with_correct_context() {
		$_GET['launchkey_error'] = 'Yup';

		$expected_context = array(
			'error'   => 'Error!',
			'message' => 'The LaunchKey request was denied or an issue was detected during authentication. Please try again.'
		);

		$this->client->launchkey_form();

		Phake::verify( $this->template )->render_template( 'error', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [error]' );

		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_launchkey_ssl_error_shows_auth_error_template_with_correct_context() {
		$_GET['launchkey_ssl_error'] = 'Yup';

		$expected_context = array(
			'error'   => 'Error!',
			'message' => 'There was an error trying to request the LaunchKey servers. If this persists you may need to disable SSL verification.'
		);

		$this->client->launchkey_form();

		Phake::verify( $this->template )->render_template( 'error', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [error]' );

		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_launchkey_security_shows_auth_error_template_with_correct_context() {
		$_GET['launchkey_security'] = 'Yup';

		$expected_context = array(
			'error'   => 'Error!',
			'message' => 'There was a security issue detected and you have been logged out for your safety. Log back in to ensure a secure session.'
		);

		$this->client->launchkey_form();

		Phake::verify( $this->template )->render_template( 'error', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [error]' );

		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_launchkey_pair_shows_auth_message_template_with_correct_context() {
		$_GET['launchkey_pair'] = 'Yup';

		$expected_context = array(
			'alert'   => 'Almost finished!',
			'message' => 'Log in with your WordPress username and password for the last time to finish the user pair process. After this you can login exclusively with LaunchKey!'
		);

		$this->client->launchkey_form();

		Phake::verify( $this->template )->render_template( 'auth-message', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [auth-message]' );

		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_launchkey_pair_does_not_show_launch_key_form() {
		$_GET['launchkey_pair'] = 'Yup';

		$this->client->launchkey_form();

		Phake::verify( $this->facade, Phake::never() )->render_template( 'launchkey-form', $this->anything() );
	}

	public function test_shows_form_template() {
		$this->options[LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME] = 'App Display Name';
		$this->client->launchkey_form( 'expected class', 'expected id', 'expected style' );
		Phake::verify( $this->facade )->get_option(LaunchKey_WP_Admin::OPTION_KEY);
		Phake::verify( $this->template )->render_template( 'launchkey-form', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [launchkey-form]' );

		return $actual_context;
	}

	/**
	 * @depends test_shows_form_template
	 *
	 * @param array $actual_context
	 */
	public function test_class_is_used( array $actual_context ) {
		$this->assertArrayHasKey( 'class', $actual_context );
		$this->assertEquals( 'expected class', $actual_context['class'] );
	}

	/**
	 * @depends test_shows_form_template
	 *
	 * @param array $actual_context
	 */
	public function test_id_is_used( array $actual_context ) {
		$this->assertArrayHasKey( 'id', $actual_context );
		$this->assertEquals( 'expected id', $actual_context['id'] );
	}

	/**
	 * @depends test_shows_form_template
	 *
	 * @param array $actual_context
	 */
	public function test_style_is_used( array $actual_context ) {
		$this->assertArrayHasKey( 'style', $actual_context );
		$this->assertEquals( 'expected style', $actual_context['style'] );
	}

	/**
	 * @depends test_shows_form_template
	 *
	 * @param array $actual_context
	 */
	public function test_login_text_is_correct( array $actual_context ) {
		$this->assertArrayHasKey( 'login_text', $actual_context );
		$this->assertEquals( 'Log in with', $actual_context['login_text'] );
	}

	/**
	 * @depends test_shows_form_template
	 *
	 * @param array $actual_context
	 */
	public function test_login_with_app_name_is_correct( array $actual_context ) {
		$this->assertArrayHasKey( 'login_with_app_name', $actual_context );
		$this->assertEquals( 'App Display Name', $actual_context['login_with_app_name'] );
	}

	public function provider_size_per_language() {
		return array(
			'French'  => array( 'fr_FR', 'small' ),
			'Spanish' => array( 'es_ES', 'small' ),
			'Other'   => array( 'other', 'medium' )
		);
	}

	/**
	 * @param $language
	 * @param $expected
	 *
	 * @dataProvider provider_size_per_language
	 */
	public function test_size_per_language( $language, $expected ) {
		Phake::when( $this->facade )->get_locale()->thenReturn( $language );
		$this->client->launchkey_form();
		Phake::verify( $this->template )->render_template( 'launchkey-form', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [launchkey-form]' );
		$this->assertArrayHasKey( 'size', $actual_context );
		$this->assertEquals( $expected, $actual_context['size'] );
	}

	public function test_login_url_in_context() {
		$this->options[LaunchKey_WP_Options::OPTION_ROCKET_KEY] = 12345;
		Phake::when( $this->facade )->admin_url( Phake::anyParameters() )->thenReturn( 'admin url' );
		$this->client->launchkey_form();
		Phake::verify( $this->template )->render_template( 'launchkey-form', Phake::capture( $context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [launchkey-form]' );
		$this->assertArrayHasKey( 'login_url', $context );

		return $context['login_url'];
	}

	/**
	 * @depends test_login_url_in_context
	 *
	 * @param $actual_url
	 */
	public function test_login_url_uses_correct_format( $actual_url ) {
		$this->assertStringMatchesFormat( 'https://oauth.launchkey.com/authorize?client_id=%S&redirect_uri=%S', $actual_url );

		return $actual_url;
	}


	/**
	 * @depends test_login_url_uses_correct_format
	 *
	 * @param $actual_url
	 */
	public function test_login_url_uses_app_key_for_client( $actual_url ) {
		$this->assertStringMatchesFormat( '%Sclient_id=12345%S', $actual_url );
	}

	/**
	 * @depends test_login_url_uses_correct_format
	 *
	 * @param $actual_url
	 */
	public function test_login_url_uses_proper_encoded_uri_for_redirect_uri( $actual_url ) {
		$this->assertStringMatchesFormat( '%Sredirect_uri=admin+url%S', $actual_url );
	}

	public function test_uses_get_option_when_not_multi_site() {
		$client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, false);
		$client->launchkey_form();
		Phake::verify( $this->facade )->get_option ( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_uses_get_site_option_when_multi_site() {
		$client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, true);
		$client->launchkey_form();
		Phake::verify( $this->facade )->get_site_option ( LaunchKey_WP_Admin::OPTION_KEY );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		$this->options = array(LaunchKey_WP_Options::OPTION_ROCKET_KEY => 12345, LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME => 'App Display Name');
		$that = $this;
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ($that) {
			return $that->options;
		} );

		Phake::when($this->template)->render_template(Phake::anyParameters())->thenReturnCallback(function ($template) {
			return "Rendered [{$template}]";
		});

		$this->client = new LaunchKey_WP_OAuth_Client( $this->facade, $this->template, false);
	}

	protected function tearDown() {
		$this->client  = null;
		$this->options = null;
		$this->facade  = null;
		$this->template = null;
		foreach ( array_keys( $_GET ) as $key ) {
			unset( $_GET[ $key ] );
		}
	}
}
