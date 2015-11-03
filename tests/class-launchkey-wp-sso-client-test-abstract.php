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
	 * @var XMLSecurityKey
	 */
	protected $security_key;
	/**
	 * @Mock
	 * @var SAML2_Compat_AbstractContainer
	 */
	protected $container;

	/**
	 * @var LaunchKey_WP_SSO_Client
	 */
	protected $client;

	protected function setUp() {
		Phake::initAnnotations( $this );
		Phake::when( $this->container )->generateId( Phake::anyParameters() )->thenReturn( static::UNIQUE_ID );
		SAML2_Compat_ContainerSingleton::setContainer( $this->container );
		$this->client = new LaunchKey_WP_SSO_Client(
			$this->facade,
			$this->template,
			static::ENTITY_ID,
			$this->security_key,
			static::LOGIN_URL,
			static::LOGOUT_URL,
			static::ERROR_URL
		);
	}

	protected function tearDown() {
		$this->client = null;
		$this->facade = null;
		$this->template = null;
		$this->security_key = null;
	}
}
