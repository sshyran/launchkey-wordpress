<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SSO_Client_Logout_Test extends LaunchKey_WP_SSO_Client_Test_Abstract {

	/**
	 * @var stdClass
	 */
	private $user;

	public function test_nothing_happens_when_no_current_user() {
		Phake::when( $this->facade )->wp_get_current_user( Phake::anyParameters() )->thenReturn( null );
		$this->client->logout();
		Phake::verify( $this->facade )->wp_get_current_user();
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_no_launchkey_sso_session_in_user_meta_does_nothing() {
		unset( $this->user->launchkey_sso_session );
		$this->client->logout();
		Phake::verify( $this->facade )->wp_get_current_user();
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_launchkey_sso_session_in_user_meta_updates_user_meta_for_sso_session_to_null() {
		$this->user->launchkey_sso_session = "Not Null";
		$this->client->logout();
		Phake::verify( $this->facade )->update_user_meta( "User ID", "launchkey_sso_session", null);
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_launchkey_sso_session_redirects_to_logout_url_and_exits() {
		$this->user->launchkey_sso_session = "Not Null";
		$this->client->logout();
		Phake::inOrder(
				Phake::verify( $this->facade )->wp_redirect( static::LOGOUT_URL ),
				Phake::verify( $this->facade )->_exit( Phake::anyParameters() )
		);

		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	protected function setUp() {
		parent::setUp();
		$this->user = new stdClass();
		$this->user->ID = "User ID";
		Phake::when( $this->facade )->wp_get_current_user( Phake::anyParameters() )->thenReturn( $this->user );
	}

	protected function tearDown() {
		$this->user = null;
		parent::tearDown();
	}
}
