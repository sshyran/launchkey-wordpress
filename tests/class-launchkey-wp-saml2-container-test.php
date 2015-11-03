<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SAML2_Container_Test extends PHPUnit_Framework_TestCase {

	const RANDOM_ITERATIONS = 100;

	/**
	 * @Mock
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 * @var LaunchKey_WP_SAML2_Container
	 */
	private $container;

	public function test_get_logger_returns_logger_injected_via_constructor() {
		$this->assertSame( $this->logger, $this->container->getLogger() );
	}

	public function test_generate_id_does_not_return_integer_as_first_character() {
		for ( $i = 0; $i < static::RANDOM_ITERATIONS; $i ++ ) {
			$id = $this->container->generateId();
			$this->assertFalse( is_numeric( $id[0] ), sprintf( "Failed to assert that %s was not numeric", $id[0] ) );
		}
	}

	public function test_generate_id_returns_pseudo_random_values() {
		$ids = array();
		for ( $i = 0; $i < static::RANDOM_ITERATIONS; $i ++ ) {
			$id = $this->container->generateId();
			$this->assertNotContains( $id, $ids, "ID had been generated previously" );
			$ids[] = $id;
		}
	}

	public function test_debug_message_logs_via_logger_debug() {
		$this->container->debugMessage( "Expected Message", "Expected Type" );
		Phake::verify( $this->logger )->debug( "Incoming message", array(
			"type" => "Expected Type",
			"XML message" => "Expected Message"
		) );
	}

	public function test_redirect_sets_proeper_value_and_is_returned_by_get_redirect_url() {
		$this->container->redirect( "https://saml.com", array( 'key' => "expected value" ) );
		$this->assertEquals( "https://saml.com?key=expected+value", $this->container->getRedirectUrl() );
	}

	public function test_post_redirect_sets_proeper_value_and_is_returned_by_get_redirect_url() {
		$this->container->postRedirect( "https://saml.com", array( 'key' => "expected value" ) );
		$this->assertEquals( "https://saml.com?key=expected+value", $this->container->getRedirectUrl() );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		$this->container = new LaunchKey_WP_SAML2_Container( $this->logger );
	}

	protected function tearDown() {
		$this->logger = null;
		$this->container = null;
	}

}
