<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SSO_Client_Authenticate_Test extends LaunchKey_WP_SSO_Client_Test_Abstract {

	const SAML_RESPONSE = "Expected SAML Response";

	const SESSION_INDEX = "Expected Session Index";

	const NAME = "Expected Name";

	const ATTRIBUTE_VALUE = "Expected Attribute Value";

	const NOW = 1234567890;

	const SSO_POST_URL = "Expected SSO POST URL";

	public function test_username_in_request_does_nothing() {
		$this->client->authenticate( null, "not null", null );
		Phake::verifyNoInteraction( $this->facade );
	}

	public function test_password_in_response_does_nothing() {
		$this->client->authenticate( null, null, "not null" );
		Phake::verifyNoInteraction( $this->facade );
	}

	public function test_no_saml_response_does_nothing() {
		unset( $_REQUEST['SAMLResponse'] );
		$this->client->authenticate( null, null, null );
		Phake::verifyNoInteraction( $this->facade );
	}

	public function test_user_login_searches_for_user_by_username() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->facade )->update_user_meta( "User ID", "launchkey_sso_session", static::SESSION_INDEX );
	}

	public function test_user_login_updates_launchkey_sso_session() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->facade )->get_user_by( "login", static::NAME );
	}

	public function test_user_login_creates_user_when_none_found() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( null );
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->facade )->wp_insert_user( Phake::capture( $user_data ) );
		return $user_data;
	}

	/**
	 * @depends test_user_login_creates_user_when_none_found
	 * @param array $user_data
	 */
	public function test_when_user_login_creates_user_it_uses_name_as_username( array $user_data ) {
		$this->assertArrayHasKey( "user_login", $user_data, "No user_login value in user data" );
		$this->assertEquals( static::NAME, $user_data["user_login"] );
	}

	/**
	 * @depends test_user_login_creates_user_when_none_found
	 * @param array $user_data
	 */
	public function test_when_user_login_creates_user_it_sets_empty_password( array $user_data ) {
		$this->assertArrayHasKey( "user_pass", $user_data, "No user_pass value in user data" );
		$this->assertEquals( "", $user_data["user_pass"] );
	}

	/**
	 * @depends test_user_login_creates_user_when_none_found
	 */
	public function test_when_user_login_creates_user_sets_role_to_first_role_value_when_provided() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( null );
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service )->get_attribute( "role" );
		Phake::verify( $this->facade )->wp_insert_user( Phake::capture( $user_data ) );
		$this->assertArrayHasKey( "role", $user_data, "No role value in user data" );
		$this->assertEquals( static::ATTRIBUTE_VALUE, $user_data["role"] );
	}

	/**
	 * @depends test_user_login_creates_user_when_none_found
	 */
	public function test_when_user_login_creates_user_sets_role_to_false_when_non_provided() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( null );
		Phake::when( $this->saml_response_service )->get_attribute( Phake::anyParameters() )->thenReturn( null );
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service )->get_attribute( "role" );
		Phake::verify( $this->facade )->wp_insert_user( Phake::capture( $user_data ) );
		$this->assertArrayHasKey( "role", $user_data, "No role value in user data" );
		$this->assertFalse( $user_data["role"] );
	}

	/**
	 * @depends test_user_login_creates_user_when_none_found
	 */
	public function test_when_user_login_creates_user_sets_role_to_administrator_when_role_is_admin() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( null );
		Phake::when( $this->saml_response_service )->get_attribute( Phake::anyParameters() )->thenReturn( array( "admin" ) );
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service )->get_attribute( "role" );
		Phake::verify( $this->facade )->wp_insert_user( Phake::capture( $user_data ) );
		$this->assertArrayHasKey( "role", $user_data, "No role value in user data" );
		$this->assertEquals( "administrator", $user_data["role"] );
	}

	public function test_error_redirects_to_error_url_and_exits() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenThrow( new Exception() );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_redirect( static::ERROR_URL ),
			Phake::verify( $this->facade )->_exit( Phake::anyParameters() )
		);
	}

	public function test_login_checks_entity_is_in_audience() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service )->is_entity_in_audience( static::ENTITY_ID );
	}

	public function test_entity_not_in_audience_redirects_to_error_url_and_exits() {
		Phake::when( $this->saml_response_service )->is_entity_in_audience( Phake::anyParameters() )->thenReturn( false );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
			Phake::verify( $this->saml_response_service )->is_entity_in_audience( static::ENTITY_ID ),
			Phake::verify( $this->facade )->wp_redirect( static::ERROR_URL ),
			Phake::verify( $this->facade )->_exit( Phake::anyParameters() )
		);
	}

	public function test_login_checks_timestamp_within_restrictions() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service )->is_timestamp_within_restrictions( static::NOW );
	}

	public function test_timestamp_not_within_restrictions_redirects_to_error_url_and_exits() {
		Phake::when( $this->saml_response_service )->is_timestamp_within_restrictions( Phake::anyParameters() )->thenReturn( false );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
			Phake::verify( $this->saml_response_service )->is_timestamp_within_restrictions( static::NOW ),
			Phake::verify( $this->facade )->wp_redirect( static::ERROR_URL ),
			Phake::verify( $this->facade )->_exit( Phake::anyParameters() )
		);
	}

	public function test_login_validates_destination() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service )->is_valid_destination( static::SSO_POST_URL );
	}

	public function test_invalid_destination_redirects_to_error_url_and_exits() {
		Phake::when( $this->saml_response_service )->is_valid_destination( Phake::anyParameters() )->thenReturn( false );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
			Phake::verify( $this->saml_response_service )->is_valid_destination( static::SSO_POST_URL ),
			Phake::verify( $this->facade )->wp_redirect( static::ERROR_URL ),
			Phake::verify( $this->facade )->_exit( Phake::anyParameters() )
		);
	}

	public function test_registers_session_index_on_successful_auth() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service )->register_session_index();
	}

	public function test_does_not_register_session_index_on_unsuccessful_auth() {
		Phake::when( $this->saml_response_service )->is_session_index_registered()->thenReturn( true );
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service, Phake::never() )->register_session_index();
	}

	public function test_checks_if_session_index_registered() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_response_service )->is_session_index_registered();
	}

	public function test_session_index_already_registered_redirects_to_error_url_and_exits() {
		Phake::when( $this->saml_response_service )->is_session_index_registered()->thenReturn( true );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
				Phake::verify( $this->saml_response_service )->is_session_index_registered(),
				Phake::verify( $this->facade )->wp_redirect( static::ERROR_URL ),
				Phake::verify( $this->facade )->_exit( Phake::anyParameters() )
		);
	}

	protected function setUp() {
		parent::setUp();

		$this->user->ID = "User ID";
		foreach ( $_REQUEST as $key => $value ) {
			unset( $_REQUEST[$key] );
		}

		$_REQUEST["SAMLResponse"] = self::SAML_RESPONSE;

		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( $this->user );
		Phake::when( $this->facade )->time( Phake::anyParameters() )->thenReturn( static::NOW );
		Phake::when( $this->facade )->wp_login_url()->thenReturn( static::SSO_POST_URL );

		Phake::when( $this->saml_response_service )->get_session_index()->thenReturn( static::SESSION_INDEX );
		Phake::when( $this->saml_response_service )->get_name()->thenReturn( static::NAME );
		Phake::when( $this->saml_response_service )->get_attribute( Phake::anyParameters() )->thenReturn( array( static::ATTRIBUTE_VALUE ) );
		Phake::when( $this->saml_response_service )->is_entity_in_audience( Phake::anyParameters() )->thenReturn( true );
		Phake::when( $this->saml_response_service )->is_timestamp_within_restrictions( Phake::anyParameters() )->thenReturn( true );
		Phake::when( $this->saml_response_service )->is_valid_destination( Phake::anyParameters() )->thenReturn( true );
		Phake::when( $this->saml_response_service )->is_session_index_registered()->thenReturn( false );
	}

	protected function tearDown() {
		$this->user = null;
		parent::tearDown();
	}
}
