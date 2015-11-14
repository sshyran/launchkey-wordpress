<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SSO_Client_Still_Authenticated_Page_Load_Test extends LaunchKey_WP_SSO_Client_Test_Abstract {

	public function test_user_logged_in_does_nothing() {
		Phake::when( $this->facade )->is_user_logged_in()->thenReturn( false );
		$this->client->launchkey_still_authenticated_page_load();
		Phake::verify( $this->facade )->is_user_logged_in();
		Phake::verifyNoFurtherInteraction( $this->facade );
	}

	public function test_gets_the_current_user_and_checks_their_status() {
		Phake::when( $this->wpdb )->prepare( Phake::anyParameters() )->thenReturn( 'PREPARED QUERY' );
		$this->client->launchkey_still_authenticated_page_load();
		Phake::inOrder(
			Phake::verify( $this->wpdb )->prepare( Phake::capture( $query ), $this->user->ID ),
			Phake::verify( $this->wpdb )->get_var( 'PREPARED QUERY' )
		);
		$this->assertEquals(
			$query,
			"SELECT meta_value FROM {$this->wpdb->usermeta} WHERE user_id = %s AND meta_key = 'launchkey_authorized' LIMIT 1"
		);
	}

	/**
	 * @depends test_gets_the_current_user_and_checks_their_status
	 */
	public function test_does_not_logoout_or_redirect_if_user_did_not_auth_with_launchkey() {
		Phake::when( $this->facade )->is_user_logged_in()->thenReturn( true );
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( null );
		$this->client->launchkey_still_authenticated_page_load();
		Phake::verify( $this->facade, Phake::never() )->wp_redirect( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->update_user_meta( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	/**
	 * @depends test_gets_the_current_user_and_checks_their_status
	 */
	public function test_does_not_logoout_or_redirect_if_user_authed_with_launchkey_and_still_authenticated() {
		Phake::when( $this->facade )->is_user_logged_in()->thenReturn( true );
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'true' );
		$this->client->launchkey_still_authenticated_page_load();
		Phake::verify( $this->facade, Phake::never() )->wp_redirect( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->update_user_meta( Phake::anyParameters() );
		Phake::verify( $this->facade, Phake::never() )->wp_logout( Phake::anyParameters() );
	}

	/**
	 * @depends test_gets_the_current_user_and_checks_their_status
	 */
	public function test_logs_out_user_and_redirects_if_user_authed_with_launchkey_and_no_longer_authenticated() {
		Phake::when( $this->facade )->is_user_logged_in()->thenReturn( true );
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'false' );
		Phake::when( $this->facade)->wp_login_url()->thenReturn('Login URL');
		$this->client->launchkey_still_authenticated_page_load();
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_sso_session', null );
		Phake::verify( $this->facade )->update_user_meta( $this->user->ID, 'launchkey_authorized', null );
		Phake::verify( $this->facade )->wp_logout();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_redirect( 'Login URL' ),
			Phake::verify( $this->facade)->_exit()
		);
	}


	protected function setUp() {
		parent::setUp();
		Phake::when( $this->facade )->is_user_logged_in()->thenReturn( true );
		Phake::when( $this->facade )->wp_get_current_user()->thenReturn( $this->user );
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'true' );
	}

	protected function tearDown() {
		parent::tearDown();
	}
}
