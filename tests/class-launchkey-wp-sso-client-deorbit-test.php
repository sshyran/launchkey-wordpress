<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SSO_Client_Deorbit_Test extends LaunchKey_WP_SSO_Client_Test_Abstract {

	const SAML_REQUEST = "Expected SAML Request";

	const SESSION_INDEX = "Expected Session Index";

	const NAME = "Expected Name";

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

	public function test_no_saml_request_does_nothing() {
		unset( $_REQUEST['SAMLRequest'] );
		$this->client->authenticate( null, null, null );
		Phake::verifyNoInteraction( $this->facade );
	}

	public function test_user_login_searches_for_user_by_username() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->facade )->get_user_by( "login", static::NAME );
	}

	public function test_user_login_dies_with_invalid_request_when_user_not_found() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( false );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
			Phake::verify( $this->facade )->get_user_by( Phake::anyParameters() ),
			Phake::verify( $this->facade )->wp_die( 'Invalid Request', 400 )
		);
	}

	public function test_user_login_dies_with_invalid_request_when_user_is_found_but_session_id_is_not_a_match() {
		Phake::when( $this->user )->get( Phake::anyParameters() )->thenReturn( "Some other session ID" );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
			Phake::verify( $this->facade )->get_user_by( Phake::anyParameters() ),
			Phake::verify( $this->user )->get( Phake::anyParameters() ),
			Phake::verify( $this->facade )->wp_die( 'Invalid Request', 400 )
		);
	}

	public function test_user_login_gets_sessionid_to_validate() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->user )->get( "launchkey_sso_session" );
	}

	public function test_user_login_updates_launchkey_authorized_to_false_when_request_is_valid() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->facade )->update_user_meta( "User ID", "launchkey_authorized", "false" );
	}

	public function test_login_validates_destination() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_request_service )->is_valid_destination( static::SSO_POST_URL );
	}


	public function test_user_login_dies_when_destination_is_invalid() {
		Phake::when( $this->saml_request_service )->is_valid_destination( Phake::anyParameters() )->thenReturn( false );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
			Phake::verify( $this->saml_request_service )->is_valid_destination( Phake::anyParameters() ),
			Phake::verify( $this->facade )->wp_die( 'Invalid Request', 400 )
		);
	}

	public function test_login_checks_timestamp_within_restrictions() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->saml_request_service )->is_timestamp_within_restrictions( static::NOW );
	}

	public function test_dies_with_invalid_request_when_timestamp_not_within_restrictions() {
		Phake::when( $this->saml_request_service )->is_timestamp_within_restrictions( Phake::anyParameters() )->thenReturn( false );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
			Phake::verify( $this->saml_request_service )->is_timestamp_within_restrictions( static::NOW ),
			Phake::verify( $this->facade )->wp_die( 'Invalid Request', 400 )
		);
	}


	protected function setUp() {
		parent::setUp();

		$this->user->ID = "User ID";
		Phake::when( $this->user )->get( "launchkey_sso_session" )->thenReturn( self::SESSION_INDEX );
		foreach ( $_REQUEST as $key => $value ) {
			unset( $_REQUEST[ $key ] );
		}

		$_REQUEST["SAMLRequest"] = self::SAML_REQUEST;

		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( $this->user );
		Phake::when( $this->facade )->time( Phake::anyParameters() )->thenReturn( static::NOW );
		Phake::when( $this->facade )->site_url( Phake::anyParameters() )->thenReturn( static::SSO_POST_URL );

		Phake::when( $this->saml_request_service )->get_session_index()->thenReturn( static::SESSION_INDEX );
		Phake::when( $this->saml_request_service )->get_name()->thenReturn( static::NAME );
		Phake::when( $this->saml_request_service )->is_request_for_entity( Phake::anyParameters() )->thenReturn( true );
		Phake::when( $this->saml_request_service )->is_timestamp_within_restrictions( Phake::anyParameters() )->thenReturn( true );
		Phake::when( $this->saml_request_service )->is_valid_destination( Phake::anyParameters() )->thenReturn( true );
	}

	protected function tearDown() {
		$this->user = null;
		parent::tearDown();
	}
}
