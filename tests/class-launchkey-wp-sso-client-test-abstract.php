<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
abstract class LaunchKey_WP_SSO_Client_Test_Abstract extends PHPUnit_Framework_TestCase {

	/**
	 * @var string
	 */
	const ENTITY_ID = "Expected Entity ID";
	/**
	 * @var string
	 */
	const LOGIN_URL = "Expected Login URL";
	/**
	 * @var string
	 */
	const LOGOUT_URL = "Expected Logout URL";
	/**
	 * @var string
	 */
	const ERROR_URL = "Expected Error URL";
	/**
	 * @var string
	 */
	const UNIQUE_ID = "Expected Unique ID";
	/**
	 * @Mock
	 * @var LaunchKey_WP_Global_Facade
	 */
	protected $facade;
	/**
	 * @Mock
	 * @var LaunchKey_WP_Template
	 */
	protected $template;
	/**
	 * @Mock
	 * @var LaunchKey_WP_SAML2_Request_Service
	 */
	protected $saml_request_service;

	/**
	 * @Mock
	 * @var LaunchKey_WP_SAML2_Response_Service
	 */
	protected $saml_response_service;

	/**
	 * @Mock
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * @Mock
	 * @var WP_User
	 */
	protected $user;

	/**
	 * @var LaunchKey_WP_SSO_Client
	 */
	protected $client;

	protected function setUp() {
		Phake::initAnnotations( $this );

		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'true' );
		$this->wpdb->usermeta = 'usermeta_table_name';

		$this->user->ID = 'User ID';

		$this->client = new LaunchKey_WP_SSO_Client(
			$this->facade,
			$this->template,
			static::ENTITY_ID,
			$this->saml_response_service,
			$this->saml_request_service,
			$this->wpdb,
			static::LOGIN_URL,
			static::LOGOUT_URL,
			static::ERROR_URL
		);
	}

	protected function tearDown() {
		$this->user                 = null;
		$this->client               = null;
		$this->facade               = null;
		$this->template             = null;
		$this->wpdb                 = null;
		$this->saml_response_service = null;
		$this->saml_request_service = null;
	}
}
