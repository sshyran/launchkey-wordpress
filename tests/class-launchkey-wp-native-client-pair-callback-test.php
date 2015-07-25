<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Native_Client_Pair_Callback_Test extends LaunchKey_WP_Native_Client_Test_Abstract {
	/**
	 * @Mock
	 * @var WP_Error
	 */
	private $wp_error;

	public function test_no_launchkey_username_in_post_does_nothing() {
		unset( $_POST['launchkey_username'] );
		$this->client->pair_callback();
		Phake::verifyNoInteraction( $this->facade );
		Phake::verifyNoInteraction( $this->sdk_auth );
	}

	public function test_launchkey_username_in_get_does_nothing() {
		unset( $_POST['launchkey_username'] );
		$_GET['launchkey_username'] = 'username';
		$this->client->pair_callback();
		Phake::verifyNoInteraction( $this->facade );
		Phake::verifyNoInteraction( $this->sdk_auth );
	}

	public function test_checks_nonce_with_correct_data() {
		$this->client->pair_callback();
		Phake::verify( $this->facade )->wp_verify_nonce( 'expected nonce', LaunchKey_WP_User_Profile::NONCE_KEY );;
	}

	public function test_invalid_nonce_sets_correct_error_used_by_pair_error_callback() {
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( false );
		$this->client->pair_callback();
		$this->client->pair_errors_callback( $this->wp_error );
		Phake::verify( $this->wp_error )->add(
			'launchkey_pair_error',
			sprintf( 'Translated [Invalid nonce.  Please try again.] with [%s]', $this->language_domain ),
			null
		);

	}

	public function test_no_current_user_sets_correct_error_used_by_pair_error_callback() {
		Phake::when( $this->facade )->wp_get_current_user()->thenReturn( null );
		$this->client->pair_callback();
		$this->client->pair_errors_callback( $this->wp_error );
		Phake::verify( $this->wp_error )->add(
			'launchkey_pair_error',
			sprintf( 'Translated [You must me logged in to pair] with [%s]', $this->language_domain ),
			null
		);

	}

	public function test_empty_launchkey_username_sets_correct_error_used_by_pair_error_callback() {
		$_POST['launchkey_username'] = '';
		$this->client->pair_callback();
		$this->client->pair_errors_callback( $this->wp_error );
		Phake::verify( $this->wp_error )->add(
			'launchkey_pair_error',
			sprintf( 'Translated [Username is required to pair] with [%s]', $this->language_domain ),
			null
		);

	}


	public function test_cals_authenticate_with_launchkey_user() {
		$this->client->pair_callback();
		Phake::verify( $this->sdk_auth )->authenticate( $_POST['launchkey_username'] );
	}

	public function test_auth_reject_sets_correct_error_used_by_pair_error_callback() {
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'false' );
		Phake::when( $this->facade )->is_wp_error( Phake::anyParameters() )->thenReturn( true );
		$this->client->pair_callback();
		$this->client->pair_errors_callback( $this->wp_error );
		Phake::verify( $this->wp_error )->add(
			'launchkey_authentication_denied',
			sprintf( 'Translated [Authentication denied!] with [%s]', $this->language_domain ),
			null
		);

	}

	public function test_auth_error_sets_correct_error_used_by_pair_error_callback() {
		Phake::when( $this->sdk_auth )->authenticate( Phake::anyParameters() )
			->thenThrow( Phake::mock( '\LaunchKey\SDK\Service\Exception\NoPairedDevicesError' ) );
		Phake::when( $this->facade )->is_wp_error( Phake::anyParameters() )->thenReturn( true );
		$this->client->pair_callback();
		$this->client->pair_errors_callback( $this->wp_error );
		Phake::verify( $this->wp_error )->add(
			'launchkey_authentication_denied',
			sprintf( 'Translated [No Paired Devices!] with [%s]', $this->language_domain ),
			null
		);

	}

	protected function setUp() {
		parent::setUp();
		$_POST['launchkey_username'] = 'expected username';
		$_POST['launchkey_nonce']    = 'expected nonce';
		Phake::when( $this->facade )->wp_get_current_user()->thenReturn( $this->user );
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );
		Phake::when( $this->wpdb )->get_var( Phake::anyParameters() )->thenReturn( 'true' );
	}

	protected function tearDown() {
		$this->wp_error = null;
		parent::tearDown();
	}


}
