<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_Admin_Check_Option_Test extends PHPUnit_Framework_TestCase {
	/**
	 * @var array
	 */
	public $options;
	/**
	 * @var LaunchKey_WP_Admin
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
	 * @var string
	 */
	private $language_domain;

	public function data_provider_no_option_check() {
		return array(
			LaunchKey_WP_Options::OPTION_ROCKET_KEY  => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				'Original Rocket Key'
			),
			LaunchKey_WP_Options::OPTION_SECRET_KEY  => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				'Original Secret Key'
			),
			LaunchKey_WP_Options::OPTION_PRIVATE_KEY => array(
				LaunchKey_WP_Options::OPTION_PRIVATE_KEY,
				'Original Private Key'
			),
		);
	}

	/**
	 * @dataProvider data_provider_no_option_check
	 *
	 * @param $key
	 * @param $expected
	 */
	public function test_check_option_returns_original_value_when_no_option( $key, $expected ) {
		list( $actual, $errors ) = $this->client->check_option( array() );
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
		$this->assertSame( $expected, $actual[ $key ] );
	}

	public function data_provider_option_check_return_non_file_input() {
		return array(
			'Rocket Key - Null value returns original value'                => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				null,
				'Original Rocket Key'
			),
			'Rocket Key - Empty value returns original value'               => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				'',
				'Original Rocket Key'
			),
			'Rocket Key - Non-numeric value returns original value'         => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				'abc',
				'Original Rocket Key'
			),
			'Rocket Key - Less than 10 digits returns original value'       => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				'123456789',
				'Original Rocket Key'
			),
			'Rocket Key - More than 10 digits returns original value'       => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				'12345678901',
				'Original Rocket Key'
			),
			'Rocket Key - 10 digits returns option value'                   => array(
				LaunchKey_WP_Options::OPTION_ROCKET_KEY,
				'1234567890',
				'1234567890'
			),
			'Secret Key - Null value returns original value'                => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				null,
				'Original Secret Key'
			),
			'Secret Key - Empty value returns original value'               => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				'',
				'Original Secret Key'
			),
			'Secret Key - Non-alphanumeric value returns original value'    => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				'#$%^&',
				'Original Secret Key'
			),
			'Secret Key - Less than 32 characters returns original value'   => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				'1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6',
				'Original Secret Key'
			),
			'Secret Key - More then 32 characters returns original value'   => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				'1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f7',
				'Original Secret Key'
			),
			'Secret Key - 32 character alpha numeric returns options value' => array(
				LaunchKey_WP_Options::OPTION_SECRET_KEY,
				'1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f',
				'1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f'
			),
			'App Display Name - Null value returns null'                    => array(
				LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME,
				null,
				null
			),
			'App Display Name - Empty value returns null'                   => array(
				LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME,
				'',
				null
			),
			'App Display Name - Non-empty value returns options value'      => array(
				LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME,
				'New App Display Name',
				'New App Display Name'
			),
			'Verify SSL - Null value returns false'                         => array(
				LaunchKey_WP_Options::OPTION_SSL_VERIFY,
				null,
				false
			),
			'Verify SSL - Empty value returns false'                        => array(
				LaunchKey_WP_Options::OPTION_SSL_VERIFY,
				'',
				false
			),
			'Verify SSL - Non "on" value returns false'                     => array(
				LaunchKey_WP_Options::OPTION_SSL_VERIFY,
				'off',
				false
			),
			'Verify SSL - "on" value returns true'                          => array(
				LaunchKey_WP_Options::OPTION_SSL_VERIFY,
				'on',
				true
			),
			'Implementation Type - Null value returns original value'       => array(
				'implementation_type',
				null,
				LaunchKey_WP_Implementation_Type::WHITE_LABEL
			),
			'Implementation Type - Invalid value returns original value'    => array(
				'implementation_type',
				'INAVLID TYPE',
				LaunchKey_WP_Implementation_Type::WHITE_LABEL
			),
			'Implementation Type - Valid value returns options value'       => array(
				'implementation_type',
				LaunchKey_WP_Implementation_Type::NATIVE,
				LaunchKey_WP_Implementation_Type::NATIVE
			),
		);
	}

	/**
	 * @dataProvider data_provider_option_check_return_non_file_input
	 *
	 * @param $key
	 * @param $value
	 * @param $expected
	 */
	public function test_check_option_return_for_non_file_input( $key, $value, $expected ) {
		$options         = $this->options;
		$options[ $key ] = $value;
		list( $actual, $errors ) = $this->client->check_option( $options );
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
		$this->assertArrayHasKey( $key, $actual );
		$this->assertSame( $expected, $actual[ $key ] );
	}

	public function data_provider_option_check_return_for_private_key() {
		$valid_key_file   = __DIR__ . '/__fixtures/private.key';
		$valid_key        = file_get_contents( $valid_key_file );
		$public_key_file  = __DIR__ . '/__fixtures/public.key';
		$invalid_key_file = __DIR__ . '/__fixtures/invalid.key';

		return array(
			'No key returns original value'                                         => array(
				array(),
				'Original Private Key'
			),
			'No temp_name returns original value'                                   => array(
				array( LaunchKey_WP_Options::OPTION_PRIVATE_KEY => array( 'tmp_name' => null ) ),
				'Original Private Key'
			),
			'Private Key - Invalid RSA key returns original value'                  => array(
				array( LaunchKey_WP_Options::OPTION_PRIVATE_KEY => array( 'tmp_name' => $invalid_key_file ) ),
				'Original Private Key'
			),
			'Private Key - Valid RSA key but Not Private Key returns options value' => array(
				array( LaunchKey_WP_Options::OPTION_PRIVATE_KEY => array( 'tmp_name' => $public_key_file ) ),
				'Original Private Key'
			),
			'Private Key - Valid RSA key returns options value'                     => array(
				array( LaunchKey_WP_Options::OPTION_PRIVATE_KEY => array( 'tmp_name' => $valid_key_file ) ),
				$valid_key
			),
		);
	}

	/**
	 * @dataProvider data_provider_option_check_return_for_private_key
	 *
	 * @param $files_value
	 * @param $expected
	 */
	public function test_check_option_return_for_file_input( $files_value, $expected ) {
		$_FILES = $files_value;
		list( $actual, $errors ) = $this->client->check_option( $this->options );
		Phake::verify( $this->facade )->get_option( LaunchKey_WP_Admin::OPTION_KEY );
		$this->assertSame( $expected, $actual[ LaunchKey_WP_Options::OPTION_PRIVATE_KEY ] );
	}

	public function data_provider_rocket_key_invalid_rocket_key_errors() {
		return array(
			'Null'                => array( null, 'TRANSLATED [Rocket Key is a required field]' ),
			'Empty'               => array( null, 'TRANSLATED [Rocket Key is a required field]' ),
			'Non-numeric'         => array( 'abcdef', 'TRANSLATED [Rocket Key must be numeric]' ),
			'Less than 10 digits' => array( '123456789', 'TRANSLATED [Rocket Key must be 10 digits]' ),
			'More than 10 digits' => array( '12345678901', 'TRANSLATED [Rocket Key must be 10 digits]' ),
		);
	}

	/**
	 * @dataProvider data_provider_rocket_key_invalid_rocket_key_errors
	 *
	 * @param $value
	 * @param $expected
	 */
	public function test_rocket_key_invalid_rocket_key_error( $value, $expected ) {
		$options                                            = $this->options;
		$options[ LaunchKey_WP_Options::OPTION_ROCKET_KEY ] = $value;
		list( $actual, $errors ) = $this->client->check_option( $options );
		$this->assertContains( $expected, $errors );
	}

	public function data_provider_secret_key_invalid_secret_key_errors() {
		return array(
			'No option and no input'       => array( null, null, 'TRANSLATED [Secret Key is a required field]' ),
			'Wmpty option and empty input' => array( '', '', 'TRANSLATED [Secret Key is a required field]' ),
		);
	}

	/**
	 * @dataProvider data_provider_secret_key_invalid_secret_key_errors
	 *
	 * @param $option
	 * @param $input
	 * @param $expected
	 */
	public function test_secret_key_invalid_secret_key_error( $option, $input, $expected ) {
		$options                                                  = $this->options;
		$options[ LaunchKey_WP_Options::OPTION_SECRET_KEY ]       = $input;
		$this->options[ LaunchKey_WP_Options::OPTION_SECRET_KEY ] = $option;
		list( $actual, $errors ) = $this->client->check_option( $options );
		$this->assertContains( $expected, $errors );
	}

	public function provider_valid_app_display_name_and_implementation_type_combinations() {
		return array(
			'OAuth and Launchkey'           => array( 'LaunchKey', LaunchKey_WP_Implementation_Type::OAUTH ),
			'Native and Launchkey'          => array( 'LaunchKey', LaunchKey_WP_Implementation_Type::NATIVE ),
			'White Label and non-Launchkey' => array( 'Not LaunchKey', LaunchKey_WP_Implementation_Type::WHITE_LABEL ),
		);
	}

	/**
	 * @dataProvider provider_valid_app_display_name_and_implementation_type_combinations
	 *
	 * @param $app_display_name
	 * @param $implementation_type
	 */
	public function test_valid_app_display_name_and_implementation_type_combinations( $app_display_name, $implementation_type ) {
		$options                                                           = $this->options;
		$options[ LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME ]          = $app_display_name;
		$this->options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = $implementation_type;
		list( $actual, $errors ) = $this->client->check_option( $options );
		$this->assertArrayHasKey( LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME, $actual );
		$this->assertEquals( $app_display_name, $actual[ LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME ] );
	}


	public function provider_invalid_app_display_name_and_implementation_type_combinations() {
		return array(
			'OAuth and Launchkey'  => array( 'Not LaunchKey', LaunchKey_WP_Implementation_Type::OAUTH ),
			'Native and Launchkey' => array( 'Not LaunchKey', LaunchKey_WP_Implementation_Type::NATIVE ),
		);
	}

	/**
	 * @dataProvider provider_invalid_app_display_name_and_implementation_type_combinations
	 *
	 * @param $app_display_name
	 * @param $implementation_type
	 */
	public function test_app_display_name_will_error_and_launchkey_for_invalid_app_display_name_and_implementation_type_combinations( $app_display_name, $implementation_type ) {
		$options                                                           = $this->options;
		$options[ LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME ]          = $app_display_name;
		$options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = $implementation_type;
		list( $actual, $errors ) = $this->client->check_option( $options );
		$this->assertContains( 'TRANSLATED [App Display Name can only be modified for White Label implementations]', $errors );
		$this->assertArrayHasKey( LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME, $actual );
		$this->assertEquals( 'LaunchKey', $actual[ LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME ] );
	}

	public function test_changing_implementation_type_from_white_label_to_another_resets_app_display_name_to_launchkey_and_tells_user() {
		$this->options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = LaunchKey_WP_Implementation_Type::WHITE_LABEL;
		$this->options[ LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME ]    = 'Not LaunchKey';
		list( $actual, $errors ) = $this->client->check_option(
			array(
				'implementation_type' => LaunchKey_WP_Implementation_Type::NATIVE,
				'app_display_name'    => 'Not LaunchKey'
			)
		);
		$this->assertArrayHasKey( LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME, $actual );
		$this->assertEquals( 'LaunchKey', $actual[ LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME ] );
		$this->assertContains( 'TRANSLATED [App Display Name was reset as the Implementation Type is no longer White Label]', $errors );
	}


	public function provider_implementation_types_that_require_private_key() {
		return array(
			array( LaunchKey_WP_Implementation_Type::NATIVE ),
			array( LaunchKey_WP_Implementation_Type::WHITE_LABEL ),
		);

	}

	/**
	 * @dataProvider provider_implementation_types_that_require_private_key
	 *
	 * @param $implementation_type
	 */
	public function test_no_private_key_errors_for_implementation_types_that_require_private_key( $implementation_type ) {
		$this->options[ LaunchKey_WP_Options::OPTION_PRIVATE_KEY ]         = null;
		$this->options[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ] = LaunchKey_WP_Implementation_Type::OAUTH;
		list( $actual, $errors ) = $this->client->check_option( array( 'implementation_type' => $implementation_type ) );
		$this->assertContains( 'TRANSLATED [Private Key is required]', $errors );
		$this->assertEquals(
			$implementation_type,
			$actual[ LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE ],
			'check_option returned and unexpected implementation type'
		);
	}

	protected function setUp() {
		Phake::initAnnotations( $this );
		$that          = $this;
		$this->options = array(
			LaunchKey_WP_Options::OPTION_ROCKET_KEY          => 'Original Rocket Key',
			LaunchKey_WP_Options::OPTION_SECRET_KEY          => 'Original Secret Key',
			LaunchKey_WP_Options::OPTION_PRIVATE_KEY         => 'Original Private Key',
			LaunchKey_WP_Options::OPTION_APP_DISPLAY_NAME    => 'Original App Display Name',
			LaunchKey_WP_Options::OPTION_SSL_VERIFY          => null,
			LaunchKey_WP_Options::OPTION_IMPLEMENTATION_TYPE => LaunchKey_WP_Implementation_Type::WHITE_LABEL,
		);
		Phake::when( $this->facade )->get_option( Phake::anyParameters() )->thenReturnCallback( function () use ( $that ) {
			return $that->options;
		} );

		Phake::when( $this->facade )->__( Phake::anyParameters() )->thenReturnCallback( function ( $ignore, $args ) {
			return sprintf( 'TRANSLATED [%s]', array_shift( $args ) );
		} );

		foreach ( array_keys( $_FILES ) as $key ) {
			unset( $_FILES[ $key ] );
		}

		$this->client = new LaunchKey_WP_Admin( $this->facade, $this->template, $this->language_domain = 'launchkey', false );
	}

	protected function tearDown() {
		$this->client   = null;
		$this->facade   = null;
		$this->options  = null;
		$this->template = null;
	}
}
