<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SAML2_Request_Service_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var string
	 */
	const UNIQUE_ID = "Expected Unique ID";

	const PREPARED_STARTEMENT = "Expected prepared statement";

	/**
	 * @var XMLSecurityKey
	 */
	private static $key;

	/**
	 * @var string
	 */
	private static $request_data;

	/**
	 * @Mock
	 * @var SAML2_Compat_AbstractContainer
	 */
	protected $container;

	/**
	 * @var LaunchKey_WP_SAML2_Request_Service
	 */
	private $service;

	public static function setUpBeforeClass() {
		$cert = "-----BEGIN CERTIFICATE-----\n" .
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

		static::$key = new XMLSecurityKey( XMLSecurityKey::RSA_SHA1, array( 'type' => 'public' ) );
		static::$key->loadKey( $cert, false, true );

		static::$request_data = "PG5zMDpMb2dvdXRSZXF1ZXN0IHhtbG5zOm5zMD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOnByb3R" .
		                        "vY29sIiB4bWxuczpuczE9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIHhtbG5zOm" .
		                        "5zMj0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyIgRGVzdGluYXRpb249Imh0dHA6Ly8xO" .
		                        "TIuMTY4LjIuOTU6ODA4MC9zbG8vcG9zdCIgSUQ9ImlkLThjMjg1MjJiZDRhMDA0ZjBlOGUxODMyYjQwNThk" .
		                        "NjJjIiBJc3N1ZUluc3RhbnQ9IjIwMTUtMTEtMTNUMjI6MzI6MjdaIiBOb3RPbk9yQWZ0ZXI9IjIwMTUtMTE" .
		                        "tMTNUMjI6NDc6MjdaIiBWZXJzaW9uPSIyLjAiPjxuczE6SXNzdWVyIEZvcm1hdD0idXJuOm9hc2lzOm5hbW" .
		                        "VzOnRjOlNBTUw6Mi4wOm5hbWVpZC1mb3JtYXQ6ZW50aXR5Ij5sYXVuY2hrZXkuY29tPC9uczE6SXNzdWVyP" .
		                        "jxuczI6U2lnbmF0dXJlIHhtbG5zOm5zMj0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+" .
		                        "PG5zMjpTaWduZWRJbmZvPjxuczI6Q2Fub25pY2FsaXphdGlvbk1ldGhvZCBBbGdvcml0aG09Imh0dHA6Ly9" .
		                        "3d3cudzMub3JnLzIwMDEvMTAveG1sLWV4Yy1jMTRuIyIvPjxuczI6U2lnbmF0dXJlTWV0aG9kIEFsZ29yaX" .
		                        "RobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnI3JzYS1zaGExIi8+PG5zMjpSZWZlcmVuY" .
		                        "2UgVVJJPSIjaWQtOGMyODUyMmJkNGEwMDRmMGU4ZTE4MzJiNDA1OGQ2MmMiPjxuczI6VHJhbnNmb3Jtcz48" .
		                        "bnMyOlRyYW5zZm9ybSBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyNlbnZ" .
		                        "lbG9wZWQtc2lnbmF0dXJlIi8+PG5zMjpUcmFuc2Zvcm0gQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy" .
		                        "8yMDAxLzEwL3htbC1leGMtYzE0biMiLz48L25zMjpUcmFuc2Zvcm1zPjxuczI6RGlnZXN0TWV0aG9kIEFsZ" .
		                        "29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnI3NoYTEiLz48bnMyOkRpZ2VzdFZh" .
		                        "bHVlPjR0S01BRHZtYTJ6a0dpa3FraHVnZzNnU00ydz08L25zMjpEaWdlc3RWYWx1ZT48L25zMjpSZWZlcmV" .
		                        "uY2U+PC9uczI6U2lnbmVkSW5mbz48bnMyOlNpZ25hdHVyZVZhbHVlPll4cmZtdG9YNHFRNktZeG5NQnVwWV" .
		                        "V1ejNmNyt2VDR0SVlWQmg5MUFhbFN5MkxVeDZsZ2R1RGVlTVpJbmJWeU8KdjV4aHRVWGtLaXB5eVlDTDBvV" .
		                        "E1RcTZUMkxMdHA0cDNhc0ZmbVhST05OUXZrbVlqVUNHZnI3Q2FubWVIZmJTegpnR3M3MVBVaUZWY2RuQWdn" .
		                        "QzU0MzZHeTV2TEZtQWRUNTB4Qkw4KzJ0dzNXbjVzcHlSczlMK2s3eEltSGdsU1NrCkRkSzBFYnl3V09TVWQ" .
		                        "zVVdHMnFvcVlldm5tZjJ3cVk3eEw3bmtxQ00rbVQ4TnRqY2dVTkRnTHpxMDV1TzVtZ00Kc2pNZTdqMzVhNn" .
		                        "lFSksrNE10ck1LYmp1RVRmRTFOMHRhaWplRVVjMEozenpoNEFnQUlwL0xzeXYzUklxTWhhSQowRFNIYk9qb" .
		                        "nRGeGJ0azFodWs4QVV3PT08L25zMjpTaWduYXR1cmVWYWx1ZT48bnMyOktleUluZm8+PG5zMjpYNTA5RGF0" .
		                        "YT48bnMyOlg1MDlDZXJ0aWZpY2F0ZT5NSUlEZmpDQ0FtYWdBd0lCQVFJQ0p4QXdEUVlKS29aSWh2Y05BUUV" .
		                        "GQlFBd2dZRXhDekFKQmdOVkJBWVRBbFZUTVJJd0VBWURWUVFJRXdsTVlYTWdWbVZuWVhNeEVqQVFCZ05WQk" .
		                        "FjVENVeGhjeUJXWldkaGN6RVlNQllHQTFVRUNoTVBUR0YxYm1Ob1MyVjVMQ0JKYm1NdU1SZ3dGZ1lEVlFRT" .
		                        "EV3OU1ZWFZ1WTJoTFpYa3NJRWx1WXk0eEZqQVVCZ05WQkFNVERXeGhkVzVqYUd0bGVTNWpiMjB3SGhjTk1U" .
		                        "VXhNVEF5TWpNeU56UTVXaGNOTVRZeE1UQXhNak15TnpRNVdqQ0JnVEVMTUFrR0ExVUVCaE1DVlZNeEVqQVF" .
		                        "CZ05WQkFnVENVeGhjeUJXWldkaGN6RVNNQkFHQTFVRUJ4TUpUR0Z6SUZabFoyRnpNUmd3RmdZRFZRUUtFdz" .
		                        "lNWVhWdVkyaExaWGtzSUVsdVl5NHhHREFXQmdOVkJBc1REMHhoZFc1amFFdGxlU3dnU1c1akxqRVdNQlFHQ" .
		                        "TFVRUF4TU5iR0YxYm1Ob2EyVjVMbU52YlRDQ0FTSXdEUVlKS29aSWh2Y05BUUVCQlFBRGdnRVBBRENDQVFv" .
		                        "Q2dnRUJBTjFRM09nNml6eWYzNVVhZWl2Uzg4V2x6amR6MnlQbWp1T2dlL2F3WUphOFYyZEVEMG9DamRBeGV" .
		                        "4OUFrOGxFRTluYUQ2WmN1QTBLdGE1bUhLazFobzVaNGFxMTQ5M3dIRmJQYnpWRmxkQkF6RnFpZzdtNS9rMU" .
		                        "IvUVk4dzdDUDFRRzVhTTllYlFlQ0p3ZGh6N1VCbU5RTDJyMkswMnpuMkRGaEV1dXMxWUtNK3BmU08ySSt5V" .
		                        "GQvQXlCdHE0enUrTHVzaWJOb1U5QURLUTNJb0p0enlaK0NVdXVPRzNqelorend1ekgvMGhwdVRzNlRuQlNB" .
		                        "R1lEMVhvdzJYN2xVTEx6WHdaNFIzU29wVGVzbmNJYlhMYTJsdVRMUUlvZHl1QS9nU2lyYlc3ZzAyelE4RzN" .
		                        "KY08rY2U2VW51c2tsenZkQlBvSjJ2dHRwREVzV2xOcWJTVFdjQ0F3RUFBVEFOQmdrcWhraUc5dzBCQVFVRk" .
		                        "FBT0NBUUVBUno5VjdjQkcyZXQvNzQxbWR0YnNwUVRONEhGMGhVcDNORUp6QnJQL1l0ZE1ZSVZBVWgyc2Mzc" .
		                        "2Yvb2lha0xncVlCQTc4clNrOUNiTmx2NEVKL0ZFQy81WDNsMW85aDVkRkxYdDQwTEw0SStpallZM0Jsc2dS" .
		                        "TDlLMkNOWVJDcTFiSlg4eGxjWTBoVnFxc1ppcHpSNHpleXFRVk1MWEgvelNTY1RyRjVqYjVLUWNZRmlSUDd" .
		                        "BRjMwT3RHb1p4aG5zRFVjRXJoZFdZNWxHdmFTZXg2THNPQzJVR3Rtd0szRld1K05NRHpMMCtvdmRCR3BzbU" .
		                        "RwM0lOMUFLd2Q5LzZFUTNYYlFQeVhvWHBXMFRDQnpzL094R3FuaGlKRDlyUk9DdFZsMVNKemVMV2xsV1Ntb" .
		                        "3NRRmhzWHdTTzVabG5lY2hPK1NNYXhON09yVjdQT092OGFSY3BRPT08L25zMjpYNTA5Q2VydGlmaWNhdGU+" .
		                        "PC9uczI6WDUwOURhdGE+PC9uczI6S2V5SW5mbz48L25zMjpTaWduYXR1cmU+PG5zMTpOYW1lSUQ+dGVzdGV" .
		                        "tYWlsQHRlc3RtZS5vcmc8L25zMTpOYW1lSUQ+PG5zMDpTZXNzaW9uSW5kZXg+aWQtMDcyNjAyMjVmZTdkMW" .
		                        "UyZWU4Zjg4Njg0NmNjNDBhZmE8L25zMDpTZXNzaW9uSW5kZXg+PC9uczA6TG9nb3V0UmVxdWVzdD4=";
	}

	public static function tearDownAfterClass() {
		static::$key = null;
	}

	public function test_get_name_returns_correct_value() {
		$actual = $this->service->get_name();
		$this->assertEquals( "testemail@testme.org", $actual );
	}

	public function test_get_session_index_returns_correct_value() {
		$actual = $this->service->get_session_index();
		$this->assertEquals( "id-07260225fe7d1e2ee8f886846cc40afa", $actual );
	}

	public function data_provider_timestamp_within_restrictions() {
		return array(
			"Below top is true"                  => array( strtotime( "2015-11-13T22:47:26Z" ), true ),
			"At top is false"                    => array( strtotime( "2015-11-13T22:47:27Z" ), false ),
			"Above top is false"                 => array( strtotime( "2015-11-13T22:47:28Z" ), false ),
		);
	}

	/**
	 * @dataProvider data_provider_timestamp_within_restrictions
	 *
	 * @param int $timestamp
	 * @param bool $expected
	 */
	public function test_timestamp_within_restrictions( $timestamp, $expected ) {
		$actual = $this->service->is_timestamp_within_restrictions( $timestamp );
		$this->assertSame( $expected, $actual );
	}


	public function data_provider_valid_destination() {
		return array(
				"Valid is true"    => array( "http://192.168.2.95:8080/slo/post", true ),
				"Invalid is false" => array( "http://192.168.2.95:8080/slo/post-not", false ),
				"Null is false"    => array( null, false ),
		);
	}

	/**
	 * @dataProvider data_provider_valid_destination
	 *
	 * @param string $destination
	 * @param bool $expected
	 */
	public function test_is_valid_destination( $destination, $expected ) {
		$actual = $this->service->is_valid_destination( $destination );
		$this->assertSame( $expected, $actual );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		Phake::when( $this->container )->generateId( Phake::anyParameters() )->thenReturn( static::UNIQUE_ID );
		SAML2_Compat_ContainerSingleton::setContainer( $this->container );
		$this->service = new LaunchKey_WP_SAML2_Request_Service( self::$key );
		$this->service->load_saml_request( self::$request_data );
	}

	protected function tearDown() {
		$this->service = null;
	}
}
