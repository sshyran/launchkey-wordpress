<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_OAuth_Client_LaunchKey_ShortCode_Test extends PHPUnit_Framework_TestCase {
	/**
	 * @var LaunchKey_WP_OAuth_Client
	 */
	private $client;

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

	public function test_hide_defaults_showing_form() {
		$this->client->launchkey_shortcode( array() );
		Phake::verify( $this->client )->launchkey_form( Phake::anyParameters() );
	}

	public function test_hide_when_true_does_not_show_form() {
		$this->client->launchkey_shortcode( array( 'hide' => 'true' ) );
		Phake::verify( $this->client, Phake::never() )->launchkey_form( Phake::anyParameters() );
	}

	public function test_class_defaults_to_empty() {
		$this->client->launchkey_shortcode( array() );
		Phake::verify( $this->client )->launchkey_form( '', $this->anything(), $this->anything() );
	}

	public function test_class_passes_to_launchkey_form() {
		$this->client->launchkey_shortcode( array( 'class' => 'classy' ) );
		Phake::verify( $this->client )->launchkey_form( 'classy', $this->anything(), $this->anything() );
	}

	public function test_id_defaults_to_empty() {
		$this->client->launchkey_shortcode( array() );
		Phake::verify( $this->client )->launchkey_form( $this->anything(), '', $this->anything() );
	}

	public function test_id_passes_to_launchkey_form() {
		$this->client->launchkey_shortcode( array( 'id' => 'eye dee' ) );
		Phake::verify( $this->client )->launchkey_form( $this->anything(), 'eye dee', $this->anything() );
	}

	public function test_style_defaults_to_empty() {
		$this->client->launchkey_shortcode( array() );
		Phake::verify( $this->client )->launchkey_form( $this->anything(), $this->anything(), '' );
	}

	public function test_style_passes_to_launchkey_form() {
		$this->client->launchkey_shortcode( array( 'style' => 'stylie' ) );
		Phake::verify( $this->client )->launchkey_form( $this->anything(), $this->anything(), 'stylie' );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		$this->client = Phake::partialMock( 'LaunchKey_WP_OAuth_Client', $this->facade, $this->template, false );
	}

	protected function tearDown() {
		$this->client    = null;
		$this->template = null;
		$this->facade    = null;
	}
}
