<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SAML2_Service_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var string
	 */
	const UNIQUE_ID = "Expected Unique ID";

	/**
	 * @var XMLSecurityKey
	 */
	private static $key;

	/**
	 * @var string
	 */
	private static $response_data;
	/**
	 * @Mock
	 * @var SAML2_Compat_AbstractContainer
	 */
	protected $container;
	/**
	 * @var LaunchKey_WP_SAML2_Service
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

		static::$response_data = "PG5zMDpSZXNwb25zZSB4bWxuczpuczA9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDpwcm90b2Nv" .
		                         "bCIgeG1sbnM6bnMxPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXNzZXJ0aW9uIiB4bWxuczp" .
		                         "uczI9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyMiIHhtbG5zOnhzaT0iaHR0cDovL3" .
		                         "d3dy53My5vcmcvMjAwMS9YTUxTY2hlbWEtaW5zdGFuY2UiIERlc3RpbmF0aW9uPSJodHRwOi8vMTI3L" .
		                         "jAuMC4xOjgwODAvYWNzL3Bvc3QiIElEPSJpZC0yZmRhNGZmOTlmZjBkMjZhNDg3MjI1OGY0ODk1ZDU4" .
		                         "NSIgSXNzdWVJbnN0YW50PSIyMDE1LTExLTAzVDIyOjQyOjI0WiIgVmVyc2lvbj0iMi4wIj48bnMxOkl" .
		                         "zc3VlciBGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDpuYW1laWQtZm9ybWF0OmVudG" .
		                         "l0eSI+bGF1bmNoa2V5LmNvbTwvbnMxOklzc3Vlcj48bnMyOlNpZ25hdHVyZSB4bWxuczpuczI9Imh0d" .
		                         "HA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyMiPjxuczI6U2lnbmVkSW5mbz48bnMyOkNhbm9u" .
		                         "aWNhbGl6YXRpb25NZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzEwL3htbC1" .
		                         "leGMtYzE0biMiLz48bnMyOlNpZ25hdHVyZU1ldGhvZCBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3" .
		                         "JnLzIwMDAvMDkveG1sZHNpZyNyc2Etc2hhMSIvPjxuczI6UmVmZXJlbmNlIFVSST0iI2lkLTJmZGE0Z" .
		                         "mY5OWZmMGQyNmE0ODcyMjU4ZjQ4OTVkNTg1Ij48bnMyOlRyYW5zZm9ybXM+PG5zMjpUcmFuc2Zvcm0g" .
		                         "QWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwLzA5L3htbGRzaWcjZW52ZWxvcGVkLXNpZ25" .
		                         "hdHVyZSIvPjxuczI6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8xMC" .
		                         "94bWwtZXhjLWMxNG4jIi8+PC9uczI6VHJhbnNmb3Jtcz48bnMyOkRpZ2VzdE1ldGhvZCBBbGdvcml0a" .
		                         "G09Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyNzaGExIi8+PG5zMjpEaWdlc3RWYWx1" .
		                         "ZT5qb1dqNDRmMUZUN3Jwd1p3enBJbjE2RjMzdk09PC9uczI6RGlnZXN0VmFsdWU+PC9uczI6UmVmZXJ" .
		                         "lbmNlPjwvbnMyOlNpZ25lZEluZm8+PG5zMjpTaWduYXR1cmVWYWx1ZT5SUm5Jc091UFlDenVxcG9tMl" .
		                         "BsZjVGRG1tKzlDc1gxY2FUK0JUN01KVzRnMW1idU1sN0VMRyt4d0hmS21YMUpNCndoRnFwYUU0Snd1a" .
		                         "GhQK0Z5OE5ob3E4cWZjekNBU05STnovMHVsYk9KMlZUcmhubXI0TFExUnNuaHMwL2hGckcKKzVxaVl1" .
		                         "b0NVbmhHRlcwL1l3emF5VXlKS3pkOU0yNkhmR0pzUkNOS0tDM3dxTVhlWGNXRTB0MkxTeEdvQXNocAp" .
		                         "FYzhLMzRHK21IWWRDYUgxQnNpMldma3BpWWo0WE12RUFtSEVtUE1WSmRzc21LUmhWYmVqVnNobW53SX" .
		                         "Izck5LCmJXZW9naHc1cnNkN0NXZjVTL1FiVlUvbmtyMVBjeUozR292NUpQRkpjS2xpMDZBQTViWlVBS" .
		                         "GU1YkxvTTNnc2oKMEZNVDV0SnhQU1hRbFlJcU4yRldiUT09PC9uczI6U2lnbmF0dXJlVmFsdWU+PG5z" .
		                         "MjpLZXlJbmZvPjxuczI6WDUwOURhdGE+PG5zMjpYNTA5Q2VydGlmaWNhdGU+TUlJRGZqQ0NBbWFnQXd" .
		                         "JQkFRSUNKeEF3RFFZSktvWklodmNOQVFFRkJRQXdnWUV4Q3pBSkJnTlZCQVlUQWxWVE1SSXdFQVlEVl" .
		                         "FRSUV3bE1ZWE1nVm1WbllYTXhFakFRQmdOVkJBY1RDVXhoY3lCV1pXZGhjekVZTUJZR0ExVUVDaE1QV" .
		                         "EdGMWJtTm9TMlY1TENCSmJtTXVNUmd3RmdZRFZRUUxFdzlNWVhWdVkyaExaWGtzSUVsdVl5NHhGakFV" .
		                         "QmdOVkJBTVREV3hoZFc1amFHdGxlUzVqYjIwd0hoY05NVFV4TVRBeU1qTXlOelE1V2hjTk1UWXhNVEF" .
		                         "4TWpNeU56UTVXakNCZ1RFTE1Ba0dBMVVFQmhNQ1ZWTXhFakFRQmdOVkJBZ1RDVXhoY3lCV1pXZGhjek" .
		                         "VTTUJBR0ExVUVCeE1KVEdGeklGWmxaMkZ6TVJnd0ZnWURWUVFLRXc5TVlYVnVZMmhMWlhrc0lFbHVZe" .
		                         "TR4R0RBV0JnTlZCQXNURDB4aGRXNWphRXRsZVN3Z1NXNWpMakVXTUJRR0ExVUVBeE1OYkdGMWJtTm9h" .
		                         "MlY1TG1OdmJUQ0NBU0l3RFFZSktvWklodmNOQVFFQkJRQURnZ0VQQURDQ0FRb0NnZ0VCQU4xUTNPZzZ" .
		                         "penlmMzVVYWVpdlM4OFdsempkejJ5UG1qdU9nZS9hd1lKYThWMmRFRDBvQ2pkQXhleDlBazhsRUU5bm" .
		                         "FENlpjdUEwS3RhNW1IS2sxaG81WjRhcTE0OTN3SEZiUGJ6VkZsZEJBekZxaWc3bTUvazFCL1FZOHc3Q" .
		                         "1AxUUc1YU05ZWJRZUNKd2RoejdVQm1OUUwycjJLMDJ6bjJERmhFdXVzMVlLTStwZlNPMkkreVRkL0F5" .
		                         "QnRxNHp1K0x1c2liTm9VOUFES1EzSW9KdHp5WitDVXV1T0czanpaK3p3dXpILzBocHVUczZUbkJTQUd" .
		                         "ZRDFYb3cyWDdsVUxMelh3WjRSM1NvcFRlc25jSWJYTGEybHVUTFFJb2R5dUEvZ1NpcmJXN2cwMnpROE" .
		                         "czSmNPK2NlNlVudXNrbHp2ZEJQb0oydnR0cERFc1dsTnFiU1RXY0NBd0VBQVRBTkJna3Foa2lHOXcwQ" .
		                         "kFRVUZBQU9DQVFFQVJ6OVY3Y0JHMmV0Lzc0MW1kdGJzcFFUTjRIRjBoVXAzTkVKekJyUC9ZdGRNWUlW" .
		                         "QVVoMnNjM3NmL29pYWtMZ3FZQkE3OHJTazlDYk5sdjRFSi9GRUMvNVgzbDFvOWg1ZEZMWHQ0MExMNEk" .
		                         "raWpZWTNCbHNnUkw5SzJDTllSQ3ExYkpYOHhsY1kwaFZxcXNaaXB6UjR6ZXlxUVZNTFhIL3pTU2NUck" .
		                         "Y1amI1S1FjWUZpUlA3QUYzME90R29aeGhuc0RVY0VyaGRXWTVsR3ZhU2V4NkxzT0MyVUd0bXdLM0ZXd" .
		                         "StOTUR6TDArb3ZkQkdwc21EcDNJTjFBS3dkOS82RVEzWGJRUHlYb1hwVzBUQ0J6cy9PeEdxbmhpSkQ5" .
		                         "clJPQ3RWbDFTSnplTFdsbFdTbW9zUUZoc1h3U081WmxuZWNoTytTTWF4TjdPclY3UE9PdjhhUmNwUT0" .
		                         "9PC9uczI6WDUwOUNlcnRpZmljYXRlPjwvbnMyOlg1MDlEYXRhPjwvbnMyOktleUluZm8+PC9uczI6U2" .
		                         "lnbmF0dXJlPjxuczA6U3RhdHVzPjxuczA6U3RhdHVzQ29kZSBWYWx1ZT0idXJuOm9hc2lzOm5hbWVzO" .
		                         "nRjOlNBTUw6Mi4wOnN0YXR1czpTdWNjZXNzIi8+PC9uczA6U3RhdHVzPjxuczE6QXNzZXJ0aW9uIElE" .
		                         "PSJpZC05NmJhMjg4MTYxNmM5ODEyNGY2MmZhMWJjM2ExNGM0ZCIgSXNzdWVJbnN0YW50PSIyMDE1LTE" .
		                         "xLTAzVDIyOjQyOjI0WiIgVmVyc2lvbj0iMi4wIj48bnMxOklzc3VlciBGb3JtYXQ9InVybjpvYXNpcz" .
		                         "puYW1lczp0YzpTQU1MOjIuMDpuYW1laWQtZm9ybWF0OmVudGl0eSI+bGF1bmNoa2V5LmNvbTwvbnMxO" .
		                         "klzc3Vlcj48bnMyOlNpZ25hdHVyZSB4bWxuczpuczI9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkv" .
		                         "eG1sZHNpZyMiPjxuczI6U2lnbmVkSW5mbz48bnMyOkNhbm9uaWNhbGl6YXRpb25NZXRob2QgQWxnb3J" .
		                         "pdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzEwL3htbC1leGMtYzE0biMiLz48bnMyOlNpZ25hdH" .
		                         "VyZU1ldGhvZCBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyNyc2Etc" .
		                         "2hhMSIvPjxuczI6UmVmZXJlbmNlIFVSST0iI2lkLTk2YmEyODgxNjE2Yzk4MTI0ZjYyZmExYmMzYTE0" .
		                         "YzRkIj48bnMyOlRyYW5zZm9ybXM+PG5zMjpUcmFuc2Zvcm0gQWxnb3JpdGhtPSJodHRwOi8vd3d3Lnc" .
		                         "zLm9yZy8yMDAwLzA5L3htbGRzaWcjZW52ZWxvcGVkLXNpZ25hdHVyZSIvPjxuczI6VHJhbnNmb3JtIE" .
		                         "FsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8xMC94bWwtZXhjLWMxNG4jIi8+PC9uczI6V" .
		                         "HJhbnNmb3Jtcz48bnMyOkRpZ2VzdE1ldGhvZCBBbGdvcml0aG09Imh0dHA6Ly93d3cudzMub3JnLzIw" .
		                         "MDAvMDkveG1sZHNpZyNzaGExIi8+PG5zMjpEaWdlc3RWYWx1ZT5MK1NJN0VvMklaanZrMElYQnFvbXh" .
		                         "ieEx5Yk09PC9uczI6RGlnZXN0VmFsdWU+PC9uczI6UmVmZXJlbmNlPjwvbnMyOlNpZ25lZEluZm8+PG" .
		                         "5zMjpTaWduYXR1cmVWYWx1ZT5wa25paTA3NGdBWlZnbG1MMk1ZbEEyZ2lyOGZzdzBIbWtyWXlUVFNmM" .
		                         "0dzRUZqRmhiby9BK0piM2dHQS9vRjY5CmxSWHgzU29MeklHdlo1c0lMaVR3aW1qek1ETmEzMWJpb2NF" .
		                         "ckpqajFzMURKVDJTeHNMdFd2L0JKd1JmeE1Rb3AKeHMyZ1JWcmhNTmlWTEtwcytFTit4Sk54MGVrTDh" .
		                         "Bc1YwYWdrZ0Z0dStQY294N0tRbnBIRmhyM0FuaVN1NExNWApWVVk3S001bkhjUksyK0lFckRVelB2Ri" .
		                         "8yQkQ0ZFFad0MzTUlWUjM3R0laU1l4d1hrWXZ3amhVcWZ3YlRRQ0VBCkxKTEp2WFVNdWtkQnhOOEorN" .
		                         "mRxZDN6L0dHRFpZaHRLS21vVUNHSVpQUzZIUEVrZUZCbkRrVkxGZEVlMEY1a1QKMDRjb2ZqZHZ4NTha" .
		                         "SEhBMzhmbjhTUT09PC9uczI6U2lnbmF0dXJlVmFsdWU+PG5zMjpLZXlJbmZvPjxuczI6WDUwOURhdGE" .
		                         "+PG5zMjpYNTA5Q2VydGlmaWNhdGU+TUlJRGZqQ0NBbWFnQXdJQkFRSUNKeEF3RFFZSktvWklodmNOQV" .
		                         "FFRkJRQXdnWUV4Q3pBSkJnTlZCQVlUQWxWVE1SSXdFQVlEVlFRSUV3bE1ZWE1nVm1WbllYTXhFakFRQ" .
		                         "mdOVkJBY1RDVXhoY3lCV1pXZGhjekVZTUJZR0ExVUVDaE1QVEdGMWJtTm9TMlY1TENCSmJtTXVNUmd3" .
		                         "RmdZRFZRUUxFdzlNWVhWdVkyaExaWGtzSUVsdVl5NHhGakFVQmdOVkJBTVREV3hoZFc1amFHdGxlUzV" .
		                         "qYjIwd0hoY05NVFV4TVRBeU1qTXlOelE1V2hjTk1UWXhNVEF4TWpNeU56UTVXakNCZ1RFTE1Ba0dBMV" .
		                         "VFQmhNQ1ZWTXhFakFRQmdOVkJBZ1RDVXhoY3lCV1pXZGhjekVTTUJBR0ExVUVCeE1KVEdGeklGWmxaM" .
		                         "kZ6TVJnd0ZnWURWUVFLRXc5TVlYVnVZMmhMWlhrc0lFbHVZeTR4R0RBV0JnTlZCQXNURDB4aGRXNWph" .
		                         "RXRsZVN3Z1NXNWpMakVXTUJRR0ExVUVBeE1OYkdGMWJtTm9hMlY1TG1OdmJUQ0NBU0l3RFFZSktvWkl" .
		                         "odmNOQVFFQkJRQURnZ0VQQURDQ0FRb0NnZ0VCQU4xUTNPZzZpenlmMzVVYWVpdlM4OFdsempkejJ5UG" .
		                         "1qdU9nZS9hd1lKYThWMmRFRDBvQ2pkQXhleDlBazhsRUU5bmFENlpjdUEwS3RhNW1IS2sxaG81WjRhc" .
		                         "TE0OTN3SEZiUGJ6VkZsZEJBekZxaWc3bTUvazFCL1FZOHc3Q1AxUUc1YU05ZWJRZUNKd2RoejdVQm1O" .
		                         "UUwycjJLMDJ6bjJERmhFdXVzMVlLTStwZlNPMkkreVRkL0F5QnRxNHp1K0x1c2liTm9VOUFES1EzSW9" .
		                         "KdHp5WitDVXV1T0czanpaK3p3dXpILzBocHVUczZUbkJTQUdZRDFYb3cyWDdsVUxMelh3WjRSM1NvcF" .
		                         "Rlc25jSWJYTGEybHVUTFFJb2R5dUEvZ1NpcmJXN2cwMnpROEczSmNPK2NlNlVudXNrbHp2ZEJQb0oyd" .
		                         "nR0cERFc1dsTnFiU1RXY0NBd0VBQVRBTkJna3Foa2lHOXcwQkFRVUZBQU9DQVFFQVJ6OVY3Y0JHMmV0" .
		                         "Lzc0MW1kdGJzcFFUTjRIRjBoVXAzTkVKekJyUC9ZdGRNWUlWQVVoMnNjM3NmL29pYWtMZ3FZQkE3OHJ" .
		                         "TazlDYk5sdjRFSi9GRUMvNVgzbDFvOWg1ZEZMWHQ0MExMNEkraWpZWTNCbHNnUkw5SzJDTllSQ3ExYk" .
		                         "pYOHhsY1kwaFZxcXNaaXB6UjR6ZXlxUVZNTFhIL3pTU2NUckY1amI1S1FjWUZpUlA3QUYzME90R29ae" .
		                         "Ghuc0RVY0VyaGRXWTVsR3ZhU2V4NkxzT0MyVUd0bXdLM0ZXdStOTUR6TDArb3ZkQkdwc21EcDNJTjFB" .
		                         "S3dkOS82RVEzWGJRUHlYb1hwVzBUQ0J6cy9PeEdxbmhpSkQ5clJPQ3RWbDFTSnplTFdsbFdTbW9zUUZ" .
		                         "oc1h3U081WmxuZWNoTytTTWF4TjdPclY3UE9PdjhhUmNwUT09PC9uczI6WDUwOUNlcnRpZmljYXRlPj" .
		                         "wvbnMyOlg1MDlEYXRhPjwvbnMyOktleUluZm8+PC9uczI6U2lnbmF0dXJlPjxuczE6U3ViamVjdD48b" .
		                         "nMxOk5hbWVJRCBGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjEuMTpuYW1laWQtZm9ybWF0" .
		                         "OmVtYWlsQWRkcmVzcyI+dGVzdGVtYWlsQHRlc3RtZS5vcmc8L25zMTpOYW1lSUQ+PG5zMTpTdWJqZWN" .
		                         "0Q29uZmlybWF0aW9uIE1ldGhvZD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmNtOmJlYXJlci" .
		                         "I+PG5zMTpTdWJqZWN0Q29uZmlybWF0aW9uRGF0YSBOb3RPbk9yQWZ0ZXI9IjIwMTUtMTEtMDNUMjI6N" .
		                         "Tc6MjRaIiBSZWNpcGllbnQ9Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9hY3MvcG9zdCIvPjwvbnMxOlN1" .
		                         "YmplY3RDb25maXJtYXRpb24+PC9uczE6U3ViamVjdD48bnMxOkNvbmRpdGlvbnMgTm90QmVmb3JlPSI" .
		                         "yMDE1LTExLTAzVDIyOjQyOjI0WiIgTm90T25PckFmdGVyPSIyMDE1LTExLTAzVDIyOjU3OjI0WiI+PG" .
		                         "5zMTpBdWRpZW5jZVJlc3RyaWN0aW9uPjxuczE6QXVkaWVuY2U+dGVzdC1zc288L25zMTpBdWRpZW5jZ" .
		                         "T48L25zMTpBdWRpZW5jZVJlc3RyaWN0aW9uPjwvbnMxOkNvbmRpdGlvbnM+PG5zMTpBdXRoblN0YXRl" .
		                         "bWVudCBBdXRobkluc3RhbnQ9IjIwMTUtMTEtMDNUMjI6NDI6MjRaIiBTZXNzaW9uSW5kZXg9ImlkLWI" .
		                         "0MzczYzg3YTZmMThmOTc4NjJjOTMxNzQ0ZmQ3OTlmIj48bnMxOkF1dGhuQ29udGV4dD48bnMxOkF1dG" .
		                         "huQ29udGV4dENsYXNzUmVmPnVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphYzpjbGFzc2VzOnVuc" .
		                         "3BlY2lmaWVkPC9uczE6QXV0aG5Db250ZXh0Q2xhc3NSZWY+PG5zMTpBdXRoZW50aWNhdGluZ0F1dGhv" .
		                         "cml0eT5odHRwczovL3NhbWwubGF1bmNoa2V5LmNvbS9pZHAueG1sPC9uczE6QXV0aGVudGljYXRpbmd" .
		                         "BdXRob3JpdHk+PC9uczE6QXV0aG5Db250ZXh0PjwvbnMxOkF1dGhuU3RhdGVtZW50PjxuczE6QXR0cm" .
		                         "lidXRlU3RhdGVtZW50PjxuczE6QXR0cmlidXRlIE5hbWU9ImFrZXkiIE5hbWVGb3JtYXQ9InVybjpvY" .
		                         "XNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48bnMxOkF0dHJpYnV0ZVZh" .
		                         "bHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmF2YWx1ZTwvbnMxOkF0dHJpYnV0ZVZhbHVlPjwvbnMxOkF" .
		                         "0dHJpYnV0ZT48L25zMTpBdHRyaWJ1dGVTdGF0ZW1lbnQ+PC9uczE6QXNzZXJ0aW9uPjwvbnMwOlJlc3" .
		                         "BvbnNlPg==";
	}

	public static function tearDownAfterClass() {
		static::$response_data = null;
		static::$key = null;
	}

	public function test_get_name_returns_correct_value() {
		$actual = $this->service->get_name();
		$this->assertEquals( "testemail@testme.org", $actual );
	}

	public function test_get_session_index_returns_correct_value() {
		$actual = $this->service->get_session_index();
		$this->assertEquals( "id-b4373c87a6f18f97862c931744fd799f", $actual );
	}

	public function test_get_attribute_returns_null_when_not_exists() {
		$actual = $this->service->get_attribute( "not found attribute" );
		$this->assertnull( $actual );
	}

	public function test_get_attribute_returns_correct_values_when_exists() {
		$actual = $this->service->get_attribute( "akey" );
		$this->assertEquals( array( "avalue" ), $actual );
	}

	public function data_provider_entity_in_audience() {
		return array(
			"ID in audience returns true" => array( "test-sso", true ),
			"ID not in audience returns false" => array( "not-in", false ),
			"Null ID returns false" => array( null, false )
		);
	}

	/**
	 * @dataProvider data_provider_entity_in_audience
	 * @param string $entity
	 * @param bool $expected
	 */
	public function test_is_entity_in_audience( $entity, $expected ) {
		$actual = $this->service->is_entity_in_audience( $entity );
		$this->assertSame( $expected, $actual );
	}

	public function data_provider_timestamp_within_restrictions() {
		return array(
			"Below bottom is false" => array( strtotime( "2015-11-03T22:42:23Z" ), false ),
			"Bottom is true" => array( strtotime( "2015-11-03T22:42:24Z" ), true ),
			"Above bottom and below top is true" => array( strtotime( "2015-11-03T22:42:25Z" ), true ),
			"Below top is true" => array( strtotime( "2015-11-03T22:57:23Z" ), true ),
			"At top is false" => array( strtotime( "2015-11-03T22:57:24Z" ), false ),
			"Above top is false" => array( strtotime( "2015-11-03T22:57:25Z" ), false ),
		);
	}

	/**
	 * @dataProvider data_provider_timestamp_within_restrictions
	 * @param int $timestamp
	 * @param bool $expected
	 */
	public function test_timestamp_within_restrictions( $timestamp, $expected ) {
		$actual = $this->service->is_timestamp_within_restrictions( $timestamp );
		$this->assertSame( $expected, $actual );
	}

	public function data_provider_valid_destination() {
		return array(
				"Valid is true" => array( "http://127.0.0.1:8080/acs/post", true ),
				"Invalid is false" => array( "http://127.0.0.1:8080/acs/post-not", false ),
				"Null is false" => array( null, false ),
		);
	}
	/**
	 * @dataProvider data_provider_valid_destination
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
		$this->service = new LaunchKey_WP_SAML2_Service( self::$key );
		$this->service->load_saml_response( self::$response_data );
	}

	protected function tearDown() {
		$this->service = null;
	}
}
