<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SSO_Client_Test extends LaunchKey_WP_SSO_Client_Test_Abstract {

	public function test_register_actions_adds_shortcode_for_launchkey_login_with_its_launchkey_shortcode() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_shortcode( "launchkey_login", array( $this->client, 'launchkey_shortcode' ) );
	}

	public function test_register_actions_adds_action_for_login_form_with_its_launchkey_form() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( "login_form", array( $this->client, 'launchkey_form' ) );
	}

	public function test_register_actions_registers_its_authenticate_method_as_the_first_filter_in_the_chain() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter( 'authenticate', array( $this->client, 'authenticate' ), 0, 3 );
	}

	public function test_register_actions_registers_the_logout_handler_with_its_logout_method() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter( 'wp_logout', array( $this->client, 'logout' ) );
	}
}
