<?php
require_once( __DIR__ . '/vendor/autoload.php' );
define( 'COOKIE_DOMAIN', 'cookie.domain' );
define( 'COOKIEPATH', 'cookie/path' );

if ( ! class_exists( 'WP_User' ) ) {
	class WP_User {

		/**
		 * @var string
		 */
		public $ID;

		/**
		 * WP_User constructor.
		 */
		public function __construct( $id ) {
			$this->ID = $id;
		}

		public function get( $key ) {

		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		var $errors = array();
		var $error_data = array();

		function __construct( $code = '', $message = '', $data = '' ) {
			if ( empty( $code ) ) {
				return;
			}

			$this->errors[ $code ][] = $message;

			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}

		function get_error_codes() {
			if ( empty( $this->errors ) ) {
				return array();
			}

			return array_keys( $this->errors );
		}

		function get_error_code() {
			$codes = $this->get_error_codes();

			if ( empty( $codes ) ) {
				return '';
			}

			return $codes[0];
		}

		function get_error_messages( $code = '' ) {
			// Return all messages if no code specified.
			if ( empty( $code ) ) {
				$all_messages = array();
				foreach ( (array) $this->errors as $code => $messages ) {
					$all_messages = array_merge( $all_messages, $messages );
				}

				return $all_messages;
			}

			if ( isset( $this->errors[ $code ] ) ) {
				return $this->errors[ $code ];
			} else {
				return array();
			}
		}

		function get_error_message( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			$messages = $this->get_error_messages( $code );
			if ( empty( $messages ) ) {
				return '';
			}

			return $messages[0];
		}

		function get_error_data( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}

			if ( isset( $this->error_data[ $code ] ) ) {
				return $this->error_data[ $code ];
			}

			return null;
		}

		function add( $code, $message, $data = '' ) {
			$this->errors[ $code ][] = $message;
			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}

		function add_data( $data, $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}

			$this->error_data[ $code ] = $data;
		}
	}
}

if ( ! class_exists( 'wpdb' ) ) {
	interface wpdb {
		function get_var( $query = null, $x = 0, $y = 0 );

		function prepare( $query, $args = null );

		function query( $query );
	}
}
