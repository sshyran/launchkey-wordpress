<?php
/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */

if ( ! function_exists( 'launchkey_wp_global_facade_test_global_function' ) ) {
	function launchkey_wp_global_facade_test_global_function() {
		return func_get_args();
	}
}

if ( ! function_exists( 'settings_fields' ) ) {
	function settings_fields( $option_group ) {
		echo 'This is settings_fields: ' . $option_group;
	}
}

if ( ! function_exists( 'do_settings_sections' ) ) {
	function do_settings_sections( $page ) {
		echo 'This is do_settings_sections: ' . $page;
	}
}

if ( ! function_exists( 'settings_errors' ) ) {
	function settings_errors( $setting, $sanitize, $hide_on_update ) {
		echo 'This is settings_errors: ' . $setting . '|' . $sanitize . '|' . $hide_on_update;
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text, $type, $name, $wrap, $other_attributes ) {
		echo 'This is submit_button: ';
		echo implode( ' : ', func_get_args() );
	}
}

class LaunchKey_WP_Global_Facade_Test extends \PHPUnit_Framework_TestCase {
	/**
	 * @var LaunchKey_WP_Global_Facade
	 */
	private $facade;

	public function test_magic_call_passes_parameters_to_global_defined_function() {
		$expected = array( "A", 1, "b", 2 );
		$actual   = $this->facade->launchkey_wp_global_facade_test_global_function( "A", 1, "b", 2 );
		$this->assertEquals( $expected, $actual );
	}

	public function test_echo_writes_to_the_buffer() {
		$actual = null;
		ob_start( function ( $buffer ) use ( &$actual ) {
			$actual = $buffer;

			return $actual;
		} );
		$this->facade->_echo( 'expected' );
		ob_end_clean();
		$this->assertEquals( 'expected', $actual );
	}

	public function test_settings_fields_does_not_write_to_the_buffer() {
		$actual = null;
		ob_start( function ( $buffer ) use ( &$actual ) {
			$actual = $buffer;

			return $actual;
		} );
		$this->facade->settings_fields( 'expected' );
		ob_end_clean();
		$this->assertEmpty( $actual );
	}

	public function test_settings_fields_executes_global_and_returns_values() {
		$actual = $this->facade->settings_fields( 'expected' );
		$this->assertEquals( 'This is settings_fields: expected', $actual );
	}

	public function test_do_settings_sections_does_not_write_to_the_buffer() {
		$actual = null;
		ob_start( function ( $buffer ) use ( &$actual ) {
			$actual = $buffer;

			return $actual;
		} );
		$this->facade->do_settings_sections( 'expected' );
		ob_end_clean();
		$this->assertEmpty( $actual );
	}

	public function test_do_settings_sections_executes_global_and_returns_values() {
		$actual = $this->facade->do_settings_sections( 'expected' );
		$this->assertEquals( 'This is do_settings_sections: expected', $actual );
	}

	public function test_settings_errors_does_not_write_to_the_buffer() {
		$actual = null;
		ob_start( function ( $buffer ) use ( &$actual ) {
			$actual = $buffer;

			return $actual;
		} );
		$this->facade->settings_errors( 'setting', 'sanitize', 'hide_on_update' );
		ob_end_clean();
		$this->assertEmpty( $actual );
	}

	public function test_settings_errors_executes_global_and_returns_values() {
		$actual = $this->facade->settings_errors( 'setting', 'sanitize', 'hide_on_update' );
		$this->assertEquals( 'This is settings_errors: setting|sanitize|hide_on_update', $actual );
	}

	public function test_submit_button_does_not_write_to_the_buffer() {
		$actual = null;
		ob_start( function ( $buffer ) use ( &$actual ) {
			$actual = $buffer;

			return $actual;
		} );
		$this->facade->submit_button( 'text', 'type', 'name', 'wrap', 'other_attributes' );
		ob_end_clean();
		$this->assertEmpty( $actual );
	}

	public function test_submit_button_executes_global_and_returns_values() {
		$actual = $this->facade->submit_button( 'text', 'type', 'name', 'wrap', 'other_attributes' );
		$this->assertEquals( 'This is submit_button: text : type : name : wrap : other_attributes', $actual );
	}

	public function test_get_wpdb_returns_global_wpdb() {
		global $wpdb;
		$wpdb = $this;
		$this->assertSame( $this, $this->facade->get_wpdb() );
	}

	public function test_clear_settings_errors_resets_global_settings_errors_value() {
		global $wp_settings_errors;
		$wp_settings_errors = array( 'a', 'b' );

		$this->facade->clear_settings_errors();

		$this->assertEquals( array(), $wp_settings_errors );
	}

	public function test_get_hook_suffix_returns_global_value() {
		global $hook_suffix;
		$hook_suffix = 'expected hook suffix';

		$actual = $this->facade->get_hook_suffix();

		$this->assertEquals( $hook_suffix, $actual );
	}

	public function test_is_debug_log() {
		if ( ! defined( 'WP_DEBUG_LOG' ) ) {
			define( 'WP_DEBUG_LOG', true );
		}
		$this->assertEquals( WP_DEBUG_LOG, $this->facade->is_debug_log() );
	}


	protected function setUp() {
		$this->facade = new LaunchKey_WP_Global_Facade();
	}

	protected function tearDown() {
		$this->facade = null;
	}
}


