<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Implementation_Type_Test extends PHPUnit_Framework_TestCase {

	public function provider_is_valid( ) {
		return array(
			array(null, false),
			array('', false),
			array('invalid', false),
			array('oauth', true),
			array('native', true),
			array('white-label', true),
			array('sso', true),
		);
	}

	/**
	 * @dataProvider provider_is_valid
	 *
	 * @param $type
	 * @param $expected
	 */
	public function test_is_valid( $type, $expected ) {
		$this->assertSame( $expected, LaunchKey_WP_Implementation_Type::is_valid( $type ) );
	}

	public function provider_requires_private_key( ) {
		return array(
			array('oauth', false),
			array('native', true),
			array('white-label', true),
			array('sso', true),
		);
	}

	/**
	 * @dataProvider provider_requires_private_key
	 *
	 * @param $type
	 * @param $expected
	 */
	public function test_requires_private_key( $type, $expected ) {
		$this->assertSame( $expected, LaunchKey_WP_Implementation_Type::requires_private_key( $type ) );
	}
}
