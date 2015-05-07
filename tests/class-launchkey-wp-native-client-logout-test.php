<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Native_Client_Logout_Test extends LaunchKey_WP_Native_Client_Test_Abstract {

	public function test_no_current_user_does_nothing() {
		Phake::when( $this->facade )->wp_get_current_user()->thenReturn( null );
		$this->client->logout();
		Phake::verifyNoInteraction( $this->sdk_auth );
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_user_has_no_auth_does_not_deorbit() {
		$this->user->launchkey_auth = null;
		$this->client->logout();
		Phake::verifyNoInteraction( $this->sdk_auth );
	}

	public function test_user_has_no_auth_nullifies_launchkey_auth_user_metadata() {
		$this->user->launchkey_auth = null;
		$this->client->logout();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_auth', null );;
	}

	public function test_user_has_no_auth_nullifies_launchkey_authorized_user_metadata() {
		$this->user->launchkey_auth = null;
		$this->client->logout();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_authorized', null );;
	}

	public function test_user_has_auth_does_deorbit() {
		$this->user->launchkey_auth = 'auth_request_id';
		$this->client->logout();
		Phake::verify( $this->sdk_auth )->deOrbit( 'auth_request_id' );
	}

	public function test_user_has_auth_nullifies_launchkey_auth_user_metadata() {
		$this->user->launchkey_auth = 'auth_request_id';
		$this->client->logout();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_auth', null );;
	}

	public function test_user_has_auth_nullifies_launchkey_authorized_user_metadata() {
		$this->user->launchkey_auth = 'auth_request_id';
		$this->client->logout();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_authorized', null );;
	}

	public function test_exceptions_do_not_log_when_not_debug() {
		$this->user->launchkey_auth = 'auth_request_id';
		Phake::when( $this->facade )->is_debug_log()->thenReturn( false );
		Phake::when( $this->sdk_auth )->deOrbit( Phake::anyParameters() )->thenThrow( new Exception() );
		$this->client->logout();
		Phake::verify( $this->facade, Phake::never() )->error_log( Phake::anyParameters() );
	}

	public function test_exceptions_log_when_debug() {
		$this->user->launchkey_auth = 'auth_request_id';
		Phake::when( $this->facade )->is_debug_log()->thenReturn( true );
		Phake::when( $this->sdk_auth )
		     ->deOrbit( Phake::anyParameters() )
		     ->thenThrow( new Exception( 'Expected Message' ) );
		$this->client->logout();
		Phake::verify( $this->facade )->error_log( $this->stringContains( 'Expected Message' ) );
	}

	protected function setUp() {
		parent::setUp();
		Phake::when( $this->facade )->wp_get_current_user()->thenReturn( $this->user );
	}

	protected function tearDown() {
		$this->user = null;
		parent::tearDown();
	}


}
