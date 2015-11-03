<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SSO_Client_Authenticate_Test extends LaunchKey_WP_SSO_Client_Test_Abstract {

	private static $response;

	/**
	 * @Mock
	 * @var WP_User
	 */
	private $user;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		static::$response = file_get_contents( realpath(
			dirname( __FILE__ ) .
			DIRECTORY_SEPARATOR .
			'__fixtures' .
			DIRECTORY_SEPARATOR .
			'saml-auth-response.xml'
		) );
	}

	public function test_user_in_request_does_nothing() {
		$this->client->authenticate( $this->user, null, null );
		Phake::verifyNoInteraction( $this->facade );
	}

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
		Phake::verify( $this->facade )->update_user_meta( "User ID", "launchkey_sso_session", "id-094a9b2419f7cbb654bd5bb3a5c2bd88" );
	}

	public function test_user_login_updates_launchkey_sso_session() {
		$this->client->authenticate( null, null, null );
		Phake::verify( $this->facade )->get_user_by( "login", "testemail@testme.org" );
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
		$this->assertEquals( "testemail@testme.org", $user_data["user_login"] );
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
	 * @param array $user_data
	 */
	public function test_when_user_login_creates_user_sets_role_to_false_when_non_provided( array $user_data ) {
		$this->assertArrayHasKey( "user_pass", $user_data, "No user_pass value in user data" );
		$this->assertEquals( false, $user_data["role"] );
	}

	public function test_error_redirects_to_error_url_and_exits() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenThrow( new Exception() );
		$this->client->authenticate( null, null, null );
		Phake::inOrder(
				Phake::verify( $this->facade )->wp_redirect( static::ERROR_URL ),
				Phake::verify( $this->facade )->exit( Phake::anyParameters() )
		);
	}

	protected function setUp() {
		parent::setUp();
		$this->security_key = new XMLSecurityKey( XMLSecurityKey::RSA_SHA1, array( 'type' => 'public' ) );
		$key = "-----BEGIN CERTIFICATE-----\n" .
		       "MIIDfjCCAmagAwIBAQICJxAwDQYJKoZIhvcNAQEFBQAwgYExCzAJBgNVBAYTAlVT\r\n" .
		       "MRIwEAYDVQQIEwlMYXMgVmVnYXMxEjAQBgNVBAcTCUxhcyBWZWdhczEYMBYGA1UE\r\n" .
		       "ChMPTGF1bmNoS2V5LCBJbmMuMRgwFgYDVQQLEw9MYXVuY2hLZXksIEluYy4xFjAU\r\n" .
		       "BgNVBAMTDWxhdW5jaGtleS5jb20wHhcNMTUxMTAyMjMyNzQ5WhcNMTYxMTAxMjMy\r\n" .
		       "NzQ5WjCBgTELMAkGA1UEBhMCVVMxEjAQBgNVBAgTCUxhcyBWZWdhczESMBAGA1UE\r\n" .
		       "BxMJTGFzIFZlZ2FzMRgwFgYDVQQKEw9MYXVuY2hLZXksIEluYy4xGDAWBgNVBAsT\r\n" .
		       "D0xhdW5jaEtleSwgSW5jLjEWMBQGA1UEAxMNbGF1bmNoa2V5LmNvbTCCASIwDQYJ\r\n" .
		       "KoZIhvcNAQEBBQADggEPADCCAQoCggEBAN1Q3Og6izyf35UaeivS88Wlzjdz2yPm\r\n" .
		       "juOge/awYJa8V2dED0oCjdAxex9Ak8lEE9naD6ZcuA0Kta5mHKk1ho5Z4aq1493w\r\n" .
		       "HFbPbzVFldBAzFqig7m5/k1B/QY8w7CP1QG5aM9ebQeCJwdhz7UBmNQL2r2K02zn\r\n" .
		       "2DFhEuus1YKM+pfSO2I+yTd/AyBtq4zu+LusibNoU9ADKQ3IoJtzyZ+CUuuOG3jz\r\n" .
		       "Z+zwuzH/0hpuTs6TnBSAGYD1Xow2X7lULLzXwZ4R3SopTesncIbXLa2luTLQIody\r\n" .
		       "uA/gSirbW7g02zQ8G3JcO+ce6UnusklzvdBPoJ2vttpDEsWlNqbSTWcCAwEAATAN\r\n" .
		       "BgkqhkiG9w0BAQUFAAOCAQEARz9V7cBG2et/741mdtbspQTN4HF0hUp3NEJzBrP/\r\n" .
		       "YtdMYIVAUh2sc3sf/oiakLgqYBA78rSk9CbNlv4EJ/FEC/5X3l1o9h5dFLXt40LL\r\n" .
		       "4I+ijYY3BlsgRL9K2CNYRCq1bJX8xlcY0hVqqsZipzR4zeyqQVMLXH/zSScTrF5j\r\n" .
		       "b5KQcYFiRP7AF30OtGoZxhnsDUcErhdWY5lGvaSex6LsOC2UGtmwK3FWu+NMDzL0\r\n" .
		       "+ovdBGpsmDp3IN1AKwd9/6EQ3XbQPyXoXpW0TCBzs/OxGqnhiJD9rROCtVl1SJze\r\n" .
		       "LWllWSmosQFhsXwSO5ZlnechO+SMaxN7OrV7POOv8aRcpQ==\r\n" .
		       "-----END CERTIFICATE-----\n";
		$this->security_key->loadKey( $key, false, true );
		$this->client = new LaunchKey_WP_SSO_Client(
			$this->facade,
			$this->template,
			static::ENTITY_ID,
			$this->security_key,
			static::LOGIN_URL,
			static::LOGOUT_URL,
			static::ERROR_URL
		);

		$this->user->ID = "User ID";
		foreach ( $_REQUEST as $key => $value ) {
			unset( $_REQUEST[$key] );
		}

		$_REQUEST["SAMLResponse"] = base64_encode( static::$response );

		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn( $this->user );
	}

	protected function tearDown() {
		$this->user = null;
		parent::tearDown();
	}
}
