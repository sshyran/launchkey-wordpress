<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SSO_Config_Helper_Test extends PHPUnit_Framework_TestCase {
	static $idp_xml_location;

	public static function setUpBeforeClass() {
		static::$idp_xml_location = realpath(
			dirname( __FILE__ ) .
			DIRECTORY_SEPARATOR .
			'__fixtures' .
			DIRECTORY_SEPARATOR .
			'idp.xml'
		);
	}

	public function test_from_xml_file_returns_instance() {
		$actual = LaunchKey_WP_SSO_Config_Helper::from_xml_file( static::$idp_xml_location );
		$this->assertInstanceOf( "LaunchKey_WP_SSO_Config_Helper", $actual);
	}

	public function test_from_xml_string_returns_instance() {
		$actual = LaunchKey_WP_SSO_Config_Helper::from_xml_string( file_get_contents( static::$idp_xml_location ) );
		$this->assertInstanceOf( "LaunchKey_WP_SSO_Config_Helper", $actual);
	}

	public function test_from_DOM_document_returns_instance() {
		$dom = new DOMDocument();
		$dom->loadXML( file_get_contents( static::$idp_xml_location ) );
		$actual = LaunchKey_WP_SSO_Config_Helper::from_DOM_document( $dom );
		$this->assertInstanceOf( "LaunchKey_WP_SSO_Config_Helper", $actual);
		return $actual;
	}

	/**
	 * @depends test_from_DOM_document_returns_instance
	 * @param LaunchKey_WP_SSO_Config_Helper $helper
	 */
	public function test_get_sso_redirect(LaunchKey_WP_SSO_Config_Helper $helper) {
		$this->assertEquals( "https://sso.launchkey.com/test/sso/redirect", $helper->get_SSO_redirect() );
	}

	/**
	 * @depends test_from_DOM_document_returns_instance
	 * @param LaunchKey_WP_SSO_Config_Helper $helper
	 */
	public function test_get_slo_redirect(LaunchKey_WP_SSO_Config_Helper $helper) {
		$this->assertEquals( "https://sso.launchkey.com/test/slo/redirect", $helper->get_SLO_redirect() );
	}

	/**
	 * @depends test_from_DOM_document_returns_instance
	 * @param LaunchKey_WP_SSO_Config_Helper $helper
	 */
	public function test_get_error_redirect(LaunchKey_WP_SSO_Config_Helper $helper) {
		$this->assertEquals( "https://sso.launchkey.com/test/sso/error", $helper->get_error_redirect() );
	}

	/**
	 * @depends test_from_DOM_document_returns_instance
	 * @param LaunchKey_WP_SSO_Config_Helper $helper
	 */
	public function test_get_name_ID_format(LaunchKey_WP_SSO_Config_Helper $helper) {
		$this->assertEquals( "urn:oasis:names:tc:SAML:2.0:nameid-format:persistent", $helper->get_name_ID_format() );
	}


	/**
	 * @depends test_from_DOM_document_returns_instance
	 * @param LaunchKey_WP_SSO_Config_Helper $helper
	 */
	public function test_get_X509_certificate(LaunchKey_WP_SSO_Config_Helper $helper) {
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
		;
		$this->assertEquals( $expected, $helper->get_X509_certificate() );
	}
}
