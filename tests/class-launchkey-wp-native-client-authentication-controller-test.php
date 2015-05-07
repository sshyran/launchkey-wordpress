<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Native_Client_Authentication_Controller_Test extends LaunchKey_WP_Native_Client_Test_Abstract {

	public function test_no_login_or_logged_in_user_does_nothing() {
		$this->client->authentication_controller( null, null, null );
		Phake::verify( $this->facade, Phake::never() )->remove_all_filters( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->add_filter( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	public function test_username_and_password_login_does_nothing() {
		$this->client->authentication_controller( null, 'username', 'password' );
		Phake::verify( $this->facade, Phake::never() )->remove_all_filters( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->add_filter( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	public function test_username_login_does_nothing_if_no_user_with_that_username() {
		$this->client->authentication_controller( null, 'username', null );
		Phake::verify( $this->facade, Phake::never() )->remove_all_filters( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->add_filter( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	public function test_username_login_looks_up_user_by_login() {
		$this->client->authentication_controller( null, 'username', null );
		Phake::verify( $this->facade )->get_user_by( 'login', 'username' );
	}

	/**
	 * @depends test_username_login_looks_up_user_by_login
	 */
	public function test_username_login_does_nothing_if_user_with_that_username_has_not_paired() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn($this->user);
		$this->user->launchkey_username = null;
		$this->client->authentication_controller( null, 'username', null );
		Phake::verify( $this->facade, Phake::never() )->remove_all_filters( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->add_filter( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	public function test_username_login_removes_all_authentication_filters_and_then_adds_the_launchkey_filter() {
		Phake::when( $this->facade )->get_user_by( Phake::anyParameters() )->thenReturn($this->user);
		$this->user->launchkey_username = 'launchkey username';
		$this->client->authentication_controller( null, 'username', null );
		Phake::inOrder(
			Phake::verify( $this->facade )->remove_all_filters( 'authenticate' ),
			Phake::verify( $this->facade)->add_filter( 'authenticate', array( $this->client, 'null_method' ) ),
			Phake::verify( $this->facade)->add_filter( 'authenticate', array( $this->client, 'launchkey_user_authentication' ), 30, 2 )
		);
	}

	public function test_non_login_with_loggedin_user_that_is_not_paired_does_nothing() {
		Phake::when( $this->facade )->wp_get_current_user( Phake::anyParameters() )->thenReturn($this->user);
		$this->user->launchkey_username = null;
		$this->client->authentication_controller( null, null, null );
		Phake::verify( $this->facade, Phake::never() )->remove_all_filters( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->add_filter( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	public function test_non_login_with_loggedin_user_that_is_paired_but_was_not_authorized_by_launchkey_does_nothing() {
		Phake::when( $this->facade )->wp_get_current_user( Phake::anyParameters() )->thenReturn($this->user);
		$this->user->launchkey_username = 'launchkey username';
		$this->user->launchkey_authorized = null;
		$this->client->authentication_controller( null, null, null );
		Phake::verify( $this->facade, Phake::never() )->remove_all_filters( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->add_filter( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	public function test_non_login_with_loggedin_user_that_is_paired_and_was_authorized_by_launchkey_does_nothing_when_authenticated_is_true() {
		Phake::when( $this->facade )->wp_get_current_user( Phake::anyParameters() )->thenReturn($this->user);
		$this->user->launchkey_username = 'launchkey username';
		$this->user->launchkey_authorized = 'true';
		$this->client->authentication_controller( null, null, null );
		Phake::verify( $this->facade, Phake::never() )->remove_all_filters( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->add_filter( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	public function test_non_login_with_loggedin_user_that_is_paired_and_was_authorized_by_launchkey_logs_out_user_when_authenticated_is_false() {
		Phake::when( $this->facade )->wp_get_current_user( Phake::anyParameters() )->thenReturn($this->user);
		$this->user->launchkey_username = 'launchkey username';
		$this->user->launchkey_authorized = 'false';
		$this->client->authentication_controller( null, null, null );
		Phake::verify( $this->facade )->wp_logout();
	}

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}


}
