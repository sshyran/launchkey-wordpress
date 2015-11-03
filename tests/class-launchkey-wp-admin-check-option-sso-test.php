<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Admin_Check_Option_SSO_Test extends PHPUnit_Framework_TestCase {
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
	 * @var string
	 */
	private $language_domain;

	public function test_no_entity_id_produces_error() {
		unset( $this->options[LaunchKey_WP_Options::OPTION_SSO_ENTITY_ID] );
		list( $options, $errors ) = $this->admin->check_option( $this->options );
		$this->assertContains( "TRANSLATED [SSO Profile Entity ID is required]", $errors );
	}

	public function test_entity_id_is_returned() {
		list( $options ) = $this->admin->check_option( $this->options );
		$this->assertArrayHasKey( LaunchKey_WP_Options::OPTION_SSO_ENTITY_ID, $options );
		$this->assertEquals( 'Original Entity ID', $options[LaunchKey_WP_Options::OPTION_SSO_ENTITY_ID] );
	}

	public function test_no_sso_certificate_and_no_file_upload_produces_error() {
		unset( $_FILES['sso_idp']['tmp_name'] );
		unset( $this->options[LaunchKey_WP_Options::OPTION_SSO_CERTIFICATE] );
		list( $options, $errors ) = $this->admin->check_option( $this->options );
		$this->assertContains( "TRANSLATED [SSO Profile File is required]", $errors );
	}

	public function test_existing_sso_certificate_with_no_file_upload_returns_original_value() {
		unset( $_FILES['sso_idp']['tmp_name'] );
		list( $options ) = $this->admin->check_option( $this->options );
		$this->assertArrayHasKey( LaunchKey_WP_Options::OPTION_SSO_CERTIFICATE, $options );
		$this->assertEquals( 'Original Certificate', $options[LaunchKey_WP_Options::OPTION_SSO_CERTIFICATE] );
	}

	public function test_file_upload_has_no_errors() {
		list( $options, $errors ) = $this->admin->check_option( $this->options );
		$this->assertEmpty( $errors, "Errors were found when none expected" );
		return $options;
	}

	/**
	 * @depends test_file_upload_has_no_errors
	 * @param array $options
	 */
	public function test_file_upload_sets_certificate( array $options ) {
		$expected = "-----BEGIN CERTIFICATE-----\n" .
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
		$this->assertEquals( $expected, $options[LaunchKey_WP_Options::OPTION_SSO_CERTIFICATE] );
	}

	/**
	 * @depends test_file_upload_has_no_errors
	 * @param array $options
	 */
	public function test_file_upload_sets_login_url( array $options ) {
		$this->assertEquals( "https://sso.launchkey.com/test/sso/redirect", $options[LaunchKey_WP_Options::OPTION_SSO_LOGIN_URL] );
	}

	/**
	 * @depends test_file_upload_has_no_errors
	 * @param array $options
	 */
	public function test_file_upload_sets_logout_url( array $options ) {
		$this->assertEquals( "https://sso.launchkey.com/test/slo/redirect", $options[LaunchKey_WP_Options::OPTION_SSO_LOGOUT_URL] );
	}

	/**
	 * @depends test_file_upload_has_no_errors
	 * @param array $options
	 */
	public function test_file_upload_sets_error_url( array $options ) {
		$this->assertEquals( "https://sso.launchkey.com/test/sso/error", $options[LaunchKey_WP_Options::OPTION_SSO_ERROR_URL] );
	}

	public function test_file_read_adds_error() {
		$_FILES['sso_idp']['tmp_name'] = tempnam( sys_get_temp_dir(), "TEST" );
		list( $options, $errors ) = $this->admin->check_option( $this->options );
		$this->assertNotEmpty( $errors, "No errors when error was expected" );
		$this->assertStringStartsWith("TRANSLATED [The SSO Profile file provided had an error being parsed]: Could not load given string as XML into DOMDocument", $errors[0]);
		unlink( $_FILES['sso_idp']['tmp_name'] );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		$that = $this;
		$this->options = array(
			LaunchKey_WP_Options::OPTION_SSO_ENTITY_ID => 'Original Entity ID',
			LaunchKey_WP_Options::OPTION_SSO_CERTIFICATE => 'Original Certificate',
			LaunchKey_WP_Options::OPTION_SSO_LOGIN_URL => 'Original Login URL',
			LaunchKey_WP_Options::OPTION_SSO_LOGOUT_URL => 'Original Logout URL',
			LaunchKey_WP_Options::OPTION_SSO_ERROR_URL => 'Original Error URL',
			LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => LaunchKey_WP_Implementation_Type::SSO,
		);
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );

		Phake::when( $this->facade )->__( Phake::anyParameters() )->thenReturnCallback( function ( $ignore, $args ) {
			return sprintf( 'TRANSLATED [%s]', array_shift( $args ) );
		} );

		foreach ( array_keys( $_FILES ) as $key ) {
			unset( $_FILES[$key] );
		}

		$_FILES['sso_idp']['tmp_name'] = realpath(
			dirname( __FILE__ ) .
			DIRECTORY_SEPARATOR .
			"__fixtures" .
			DIRECTORY_SEPARATOR .
			"idp.xml"
		);

		$this->admin = new LaunchKey_WP_Admin( $this->facade, $this->template, $this->language_domain = 'launchkey' );
	}

	protected function tearDown() {
		$this->admin = null;
		$this->facade = null;
		$this->options = null;
		$this->template = null;
	}
}
