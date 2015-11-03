<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Logger_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @Mock
	 * @var LaunchKey_WP_Global_Facade
	 */
	private $facade;

	/**
	 * @var LaunchKey_WP_Logger
	 */
	private $logger;

	public function non_debug_methods_data_provider() {
		return array(
			array( 'emergency' ),
			array( 'alert' ),
			array( 'critical' ),
			array( 'error' ),
		);
	}

	/**
	 * @dataProvider non_debug_methods_data_provider
	 * @param $method
	 */
	public function test_non_debug_methods_log_when_not_debug( $method ) {
		Phake::when( $this->facade )->is_debug_log( Phake::anyParameters() )->thenReturn( false );
		call_user_func( array( $this->logger, $method ), null );
		Phake::verify( $this->facade )->error_log( Phake::anyParameters() );
	}

	public function debug_methods_data_provider() {
		return array(
				array( 'warning' ),
				array( 'notice' ),
				array( 'info' ),
				array( 'debug' ),
		);
	}

	/**
	 * @dataProvider debug_methods_data_provider
	 * @param $method
	 */
	public function test_debug_methods_do_not_log_when_not_debug( $method ) {
		Phake::when( $this->facade )->is_debug_log( Phake::anyParameters() )->thenReturn( false );
		call_user_func( array( $this->logger, $method ), null);
		Phake::verify( $this->facade, Phake::never() )->error_log( Phake::anyParameters() );
	}

	public function all_methods_data_provider() {
		return array_merge( $this->non_debug_methods_data_provider(), $this->debug_methods_data_provider() );
	}

	/**
	 * @dataProvider all_methods_data_provider
	 * @param $method
	 */
	public function test_shortcut_methods_log_method( $method ) {
		Phake::when( $this->facade )->is_debug_log( Phake::anyParameters() )->thenReturn( true );
		call_user_func( array( $this->logger, $method ), null);
		Phake::verify( $this->facade )->error_log( new PHPUnit_Framework_Constraint_StringContains( $method, true) );
	}

	/**
	 * @dataProvider all_methods_data_provider
	 * @param $method
	 */
	public function test_shortcut_methods_log_message( $method ) {
		Phake::when( $this->facade )->is_debug_log( Phake::anyParameters() )->thenReturn( true );
		call_user_func( array( $this->logger, $method ), "Expected Message");
		Phake::verify( $this->facade )->error_log( new PHPUnit_Framework_Constraint_StringContains( "Expected Message" ) );
	}

	/**
	 * @dataProvider all_methods_data_provider
	 * @param $method
	 */
	public function test_shortcut_methods_log_serialized_context( $method ) {
		Phake::when( $this->facade )->is_debug_log( Phake::anyParameters() )->thenReturn( true );
		call_user_func( array( $this->logger, $method ), null, array( 'key' => 'value' ));
		Phake::verify( $this->facade )->error_log( new PHPUnit_Framework_Constraint_StringContains( "key: value" ) );
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		$this->logger = new LaunchKey_WP_Logger( $this->facade );
		Phake::when( $this->facade )->is_debug_log( Phake::anyParameters() )->thenReturn( true );
	}

	protected function tearDown() {
		$this->logger = null;
		$this->facade = null;
	}
}
