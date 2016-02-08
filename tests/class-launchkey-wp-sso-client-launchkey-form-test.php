<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SSO_Client_LaunchKey_Form_Test extends LaunchKey_WP_SSO_Client_Test_Abstract {

	public function test_launchkey_error_shows_auth_error_template_with_correct_context() {
		$_GET['launchkey_error'] = 'Yup';

		$expected_context = array(
			'error' => 'Error!',
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
			'error' => 'Error!',
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
			'error' => 'Error!',
			'message' => 'There was a security issue detected and you have been logged out for your safety. Log back in to ensure a secure session.'
		);

		$this->client->launchkey_form();

		Phake::verify( $this->template )->render_template( 'error', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [error]' );

		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_shows_form_template() {
		$this->options[LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME] = 'App Display Name';
		$this->client->launchkey_form( 'expected class', 'expected id', 'expected style' );
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
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
			'French' => array( 'fr_FR', 'small' ),
			'Spanish' => array( 'es_ES', 'small' ),
			'Other' => array( 'other', 'medium' )
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
		$this->assertStringMatchesFormat( 'Expected Login URL?SAMLRequest=%S&RelayState=%s', $actual_url );

		return $actual_url;
	}

	/**
	 * @depends test_login_url_uses_correct_format
	 *
	 * @param $actual_url
	 */
	public function test_login_url_uses_admin_url_for_relay_state( $actual_url ) {
		$this->assertStringMatchesFormat( '%SRelayState=admin+url%S', $actual_url );
	}

	/**
	 * @depends test_login_url_uses_correct_format
	 *
	 * @param $actual_url
	 * @return DOMElement
	 */
	public function test_login_url_has_valid_auth_request( $actual_url ) {
		preg_match( "/SAMLRequest\=(.*)\&/", $actual_url, $matches );
		$request = gzinflate( base64_decode( urldecode( $matches[1] ) ) );
		$authn_request = SAML2_DOMDocumentFactory::fromString( $request );
		$this->assertInstanceOf( "DOMDocument", $authn_request );
		return new SAML2_AuthnRequest( $authn_request->documentElement );
	}

	/**
	 * @depends test_login_url_has_valid_auth_request
	 * @param SAML2_AuthnRequest $request
	 */
	public function test_login_url_request_uses_expected_id( SAML2_AuthnRequest $request ) {
		$this->assertEquals( static::UNIQUE_ID, $request->getId() );
	}

	/**
	 * @depends test_login_url_has_valid_auth_request
	 * @param SAML2_AuthnRequest $request
	 */
	public function test_login_url_request_uses_expected_issuer( SAML2_AuthnRequest $request ) {
		$this->assertEquals( static::ENTITY_ID, $request->getIssuer() );
	}

	/**
	 * @depends test_login_url_has_valid_auth_request
	 * @param SAML2_AuthnRequest $request
	 */
	public function test_login_url_request_uses_login_url_as_assertion_consumer_service_url( SAML2_AuthnRequest $request ) {
		$this->assertEquals( "SiteURL wp-login.php/login_post", $request->getAssertionConsumerServiceURL() );
	}

	/**
	 * @depends test_login_url_has_valid_auth_request
	 * @param SAML2_AuthnRequest $request
	 */
	public function test_login_url_request_uses_post_for_protocol_binding( SAML2_AuthnRequest $request ) {
		$this->assertEquals( SAML2_Const::BINDING_HTTP_POST, $request->getProtocolBinding() );
	}

	/**
	 * @depends test_login_url_has_valid_auth_request
	 * @param SAML2_AuthnRequest $request
	 */
	public function test_login_url_request_has_passive_set_to_false( SAML2_AuthnRequest $request ) {
		$this->assertFalse( $request->getIsPassive() );
	}

	/**
	 * @depends test_login_url_has_valid_auth_request
	 * @param SAML2_AuthnRequest $request
	 */
	public function test_login_url_request_name_id_policy_format_as_persistent_and_allow_create_as_true( SAML2_AuthnRequest $request ) {
		$this->assertEquals(
			array( 'Format' => SAML2_Const::NAMEID_PERSISTENT, 'AllowCreate' => true ),
			$request->getNameIdPolicy()
		);
	}

	public function test_uses_get_option_when_not_multi_site() {
		$client = new LaunchKey_WP_SSO_Client(
				$this->facade,
				$this->template,
				static::ENTITY_ID,
				$this->saml_response_service,
				$this->saml_request_service,
				$this->wpdb,
				static::LOGIN_URL,
				static::LOGOUT_URL,
				static::ERROR_URL,
				false
		);
		$client->launchkey_form();
		Phake::verify( $this->facade )->get_option ( LaunchKey_WP_Admin::OPTION_KEY );
	}

	public function test_uses_get_site_option_when_multi_site() {
		$client = new LaunchKey_WP_SSO_Client(
				$this->facade,
				$this->template,
				static::ENTITY_ID,
				$this->saml_response_service,
				$this->saml_request_service,
				$this->wpdb,
				static::LOGIN_URL,
				static::LOGOUT_URL,
				static::ERROR_URL,
				true
		);
		$client->launchkey_form();
		Phake::verify( $this->facade )->get_site_option ( LaunchKey_WP_Admin::OPTION_KEY );
	}

	protected function setUp() {
		parent::setUp();
		$this->options = array(
			LaunchKey_WP_Options::OPTION_ROCKET_KEY => 12345,
			LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME => 'App Display Name'
		);
		$that = $this;
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );

		Phake::when( $this->facade )->get_site_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );

		Phake::when( $this->template )->render_template( Phake::anyParameters() )->thenReturnCallback( function ( $template ) {
			return "Rendered [{$template}]";
		} );


		Phake::when( $this->facade )->site_url( Phake::anyParameters() )->thenReturnCallback( function ($method, $parameters) {
			return sprintf("SiteURL %s/%s", $parameters[0], $parameters[1]);
		});
	}
}
