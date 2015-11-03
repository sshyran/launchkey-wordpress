<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_User_Profile_Test extends PHPUnit_Framework_TestCase {
	/**
	 * @Mock
	 * @var LaunchKey_WP_Options
	 */
	public $options;
	/**
	 * @var LaunchKey_WP_User_Profile
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

	/**
	 * @Mock
	 * @var WP_User
	 */
	private $user;

	/**
	 * @var string
	 */
	private $launguage_domain;


	public function test_register_admin_actions_adds_action_profile_personal_options() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'profile_personal_options', array(
			$this->client,
			'launchkey_personal_options'
		) );
	}

	public function test_register_admin_actions_adds_remove_password_handler() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'admin_init', array(
			$this->client,
			'remove_password_handler'
		) );
	}

	public function test_register_admin_actions_adds_unpair_handler() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_action( 'admin_init', array(
			$this->client,
			'unpair_handler'
		) );
	}

	public function test_register_admin_actions_adds_add_users_columns() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter( 'manage_users_columns', array(
			$this->client,
			'add_users_columns'
		) );
	}

	public function test_register_admin_actions_adds_appy_custom_column_filter() {
		$this->client->register_actions();
		Phake::verify( $this->facade )->add_filter(
			'manage_users_custom_column',
			array( $this->client, 'apply_custom_column_filter' ),
			$this->anything(),
			3
		);
	}

	public function test_get_user_meta_uses_proper_id() {
		$this->client->launchkey_personal_options( $this->user );
		Phake::verify( $this->facade )->get_user_meta( 12345 );
	}

	public function provider_unpaired_type_template_matrix() {
		return array(
			'OAuth'       => array(
				LaunchKey_WP_Implementation_Type::OAUTH,
				'personal-options/unpaired-oauth',
				array(
					'app_display_name' => 'App Display Name',
					'pair_uri'         => 'https://oauth.launchkey.com/authorize?client_id=12345&redirect_uri=AdminURL/admin-ajax.php?action=launchkey-callback&launchkey_admin_pair=1',
				)
			),
			'Native'      => array(
				LaunchKey_WP_Implementation_Type::NATIVE,
				'personal-options/unpaired-native',
				array(
					'app_display_name' => 'App Display Name',
					'nonce'            => LaunchKey_WP_User_Profile::NONCE_KEY . '_value',
				)
			),
				'White Label' => array(
						LaunchKey_WP_Implementation_Type::WHITE_LABEL,
						'personal-options/white-label',
						array(
								'app_display_name'    => 'App Display Name',
								'nonce'               => LaunchKey_WP_User_Profile::NONCE_KEY . '_value',
								'pair_uri'            => 'AdminURL/admin-ajax.php?action=' . LaunchKey_WP_Native_Client::WHITE_LABEL_PAIR_ACTION,
								'paired'              => 'false',
								'has_password'        => 'true',
								'password_remove_uri' => 'AdminURL/profile.php?launchkey_remove_password=1&launchkey_nonce=' . LaunchKey_WP_User_Profile::NONCE_KEY . '_value'
						)
				)
		);
	}

	/**
	 * @dataProvider provider_unpaired_type_template_matrix
	 */
	public function test_non_sso_when_no_launchkey_meta_data_unpaired_template_shown_with_the_correct_context( $implementation_type, $expected_template, $expected_context ) {
		$this->options[ LaunchKey_WP_Options::OPTION_ROCKET_KEY ]          = 12345;
		$this->options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = $implementation_type;
		Phake::when( $this->facade )->get_user_meta( Phake::anyParameters() )->thenReturn( array() );
		$this->client->launchkey_personal_options( $this->user );
		Phake::verify( $this->template )->render_template( $expected_template, Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( "Rendered [{$expected_template}]" );
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );

		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_non_sso_when_launchkey_meta_data_and_password_paired_with_password_template_shown_with_the_correct_context() {
		$expected_nonce = LaunchKey_WP_User_Profile::NONCE_KEY . '_value';
		$this->client->launchkey_personal_options( $this->user );
		Phake::verify( $this->template )->render_template( 'personal-options/paired-with-password', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [personal-options/paired-with-password]' );
		Phake::verify( $this->facade )->wp_create_nonce( LaunchKey_WP_User_Profile::NONCE_KEY );
		Phake::verify( $this->facade )->admin_url( "/profile.php?launchkey_unpair=1&launchkey_nonce={$expected_nonce}" );
		Phake::verify( $this->facade )->admin_url( "/profile.php?launchkey_remove_password=1&launchkey_nonce={$expected_nonce}" );

		$expected_context = array(
				'unpair_uri'          => "AdminURL/profile.php?launchkey_unpair=1&launchkey_nonce={$expected_nonce}",
				'password_remove_uri' => "AdminURL/profile.php?launchkey_remove_password=1&launchkey_nonce={$expected_nonce}",
				'app_display_name'    => 'App Display Name',
		);
		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_sso_when_launchkey_meta_data_and_no_password_paired_without_password_template_shown_with_the_correct_context() {
		$this->user->ID        = 1;
		$this->user->user_pass = null;
		$this->options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = LaunchKey_WP_Implementation_Type::SSO;
		$this->client->launchkey_personal_options( $this->user );
		Phake::verify( $this->template )->render_template( 'personal-options/sso-without-password', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [personal-options/sso-without-password]' );

		$expected_context = array(
				'app_display_name' => 'App Display Name',
		);
		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_sso_when_launchkey_meta_data_and_password_paired_with_password_template_shown_with_the_correct_context() {
		$expected_nonce = LaunchKey_WP_User_Profile::NONCE_KEY . '_value';
		$this->options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = LaunchKey_WP_Implementation_Type::SSO;
		$this->client->launchkey_personal_options( $this->user );
		Phake::verify( $this->template )->render_template( 'personal-options/sso-with-password', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [personal-options/sso-with-password]' );
		Phake::verify( $this->facade )->wp_create_nonce( LaunchKey_WP_User_Profile::NONCE_KEY );
		Phake::verify( $this->facade )->admin_url( "/profile.php?launchkey_remove_password=1&launchkey_nonce={$expected_nonce}" );

		$expected_context = array(
				'password_remove_uri' => "AdminURL/profile.php?launchkey_remove_password=1&launchkey_nonce={$expected_nonce}",
				'app_display_name'    => 'App Display Name',
		);
		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_when_launchkey_meta_data_and_no_password_paired_without_password_template_shown_with_the_correct_context() {
		$this->user->ID        = 1;
		$this->user->user_pass = null;
		$this->client->launchkey_personal_options( $this->user );
		Phake::verify( $this->template )->render_template( 'personal-options/paired-without-password', Phake::capture( $actual_context ) );
		Phake::verify( $this->facade )->_echo( 'Rendered [personal-options/paired-without-password]' );

		$expected_context = array(
				'app_display_name' => 'App Display Name',
		);
		$this->assertEquals( $expected_context, $actual_context );
	}

	public function test_unpair_handler_without_verified_nonce_does_not_unpair() {
		$_GET['launchkey_unpair'] = '1';
		$_GET['launchkey_nonce']  = 'nonce value';
		$this->client->unpair_handler();

		Phake::verify( $this->facade )->wp_verify_nonce( 'nonce value', LaunchKey_WP_User_Profile::NONCE_KEY );
		Phake::verify( $this->facade, Phake::never() )->delete_user_meta( Phake::anyParameters() );
	}

	public function test_unpair_handler_with_verified_nonce_unpairs_current_user_if_user_has_password() {
		$_GET['launchkey_unpair'] = '1';
		$_GET['launchkey_nonce']  = 'nonce value';
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );
		$this->client->unpair_handler();
		Phake::verify( $this->facade )->wp_get_current_user();
		Phake::inOrder(
			Phake::verify( $this->facade )->wp_verify_nonce( 'nonce value', LaunchKey_WP_User_Profile::NONCE_KEY ),
			Phake::verify( $this->facade )->delete_user_meta( 12345, 'launchkey_user' )
		);
	}

	/**
	 * @depends test_unpair_handler_with_verified_nonce_unpairs_current_user_if_user_has_password
	 */
	public function test_unpair_handler_with_verified_nonce_removes_all_user_metadata() {
		$_GET['launchkey_unpair'] = '1';
		$_GET['launchkey_nonce']  = 'nonce value';
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );
		$this->client->unpair_handler();
		Phake::verify( $this->facade )->delete_user_meta( 12345, 'launchkey_user' );
		Phake::verify( $this->facade )->delete_user_meta( 12345, 'launchkey_username' );
		Phake::verify( $this->facade )->delete_user_meta( 12345, 'launchkey_auth' );
		Phake::verify( $this->facade )->delete_user_meta( 12345, 'launchkey_authorized' );
	}

	public function test_unpair_handler_with_verified_nonce_does_not_unpair_current_user_if_user_has_no_password() {
		$_GET['launchkey_unpair'] = '1';
		$_GET['launchkey_nonce']  = 'nonce value';
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );
		$this->user->user_pass = '';
		$this->client->unpair_handler();
		Phake::verify( $this->facade, Phake::never() )->delete_user_meta( 12345, 'launchkey_user' );
	}

	public function test_remove_password_handler_without_verified_nonce_does_not_remove_password() {
		$_GET['launchkey_remove_password'] = '1';
		$_GET['launchkey_nonce']           = 'nonce value';
		$this->client->remove_password_handler();

		Phake::verify( $this->facade )->wp_verify_nonce( 'nonce value', LaunchKey_WP_User_Profile::NONCE_KEY );
		Phake::verify( $this->facade, Phake::never() )->wp_update_user( Phake::anyParameters() );
	}

	public function test_remove_password_handler_with_verified_nonce_removes_password() {
		$_GET['launchkey_remove_password'] = '1';
		$_GET['launchkey_nonce']           = 'nonce value';
		Phake::when( $this->facade )->wp_verify_nonce( Phake::anyParameters() )->thenReturn( true );
		$this->client->remove_password_handler();

		Phake::verify( $this->facade )->wp_verify_nonce( 'nonce value', LaunchKey_WP_User_Profile::NONCE_KEY );
		Phake::verify( $this->facade )->wp_update_user( array( 'ID' => 12345, 'user_pass' => '' ) );
	}

	public function test_add_users_columns_adds_launchkey_paired_column_with_paired_title() {
		$actual = $this->client->add_users_columns( array( 'a' => 'b' ) );
		$this->assertArrayHasKey( 'launchkey_paired', $actual );
		$this->assertEquals( 'Translated [Paired]', $actual['launchkey_paired'] );
	}

	function data_provider_apply_custom_column_filter() {
		return array(
			'launchkey_paired with no meta returns No' => array( 'launchkey_paired', '', null, 'Translated [No]' ),
			'launchkey_paired with meta returns Yes'   => array( 'launchkey_paired', '', 'abc', 'Translated [Yes]' ),
		);
	}

	/**
	 * @dataProvider data_provider_apply_custom_column_filter
	 *
	 * @param $column
	 * @param $original_value
	 * @param $launchkey_user_value
	 * @param $expected
	 */
	public function test_apply_custom_column_filter( $column, $original_value, $launchkey_user_value, $expected ) {
		Phake::when( $this->facade )->get_user_meta( Phake::anyParameters() )->thenReturn( $launchkey_user_value );
		$actual = $this->client->apply_custom_column_filter( $original_value, $column, 12345 );
		Phake::verify( $this->facade )->get_user_meta( 12345, 'launchkey_user' );
		$this->assertEquals( $expected, $actual );
	}


	protected function setUp() {
		$that = $this;
		Phake::initAnnotations( $this );
		Phake::when( $this->facade )->get_user_meta( Phake::anyParameters() )
		     ->thenReturn( array( 'launchkey_user' => 'awesome_launchkey_user' ) );
		Phake::when( $this->facade )->admin_url( Phake::anyParameters() )->thenReturnCallback( function ( $func, $args ) {
			return 'AdminURL' . $args[0];
		} );
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );
		Phake::when( $this->facade )->wp_create_nonce( Phake::anyParameters() )->thenReturnCallback( function ( $func, $args ) {
			return $args[0] . '_value';
		} );
		Phake::when( $this->facade )->__( Phake::anyParameters() )->thenReturnCallback( function ( $func, $args ) {
			return sprintf( 'Translated [%s]', $args[0] );
		} );
		Phake::when( $this->template )->render_template( Phake::anyParameters() )->thenReturnCallback( function ( $template ) {
			return "Rendered [{$template}]";
		} );

		Phake::when( $this->facade )->wp_get_current_user()->thenReturn( $this->user );
		$this->options = array(
			LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME    => 'App Display Name',
			LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => LaunchKey_WP_Implementation_Type::OAUTH
		);

		$this->user->ID        = 12345;
		$this->user->user_pass = 'super awesome password';
		$this->launguage_domain = 'Expected Language Domain';
		$this->client          = new LaunchKey_WP_User_Profile( $this->facade, $this->template, $this->launguage_domain );
	}

	protected function tearDown() {
		$this->client   = null;
		$this->options  = null;
		$this->facade   = null;
		$this->user     = null;
		$this->template = null;
	}
}
