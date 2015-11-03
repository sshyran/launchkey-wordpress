<?php

/**
 * @author Adam Englander <adam@launchkey.com>
 * @copyright 2015 LaunchKey, Inc. See project license for usage.
 */
class LaunchKey_WP_SAML2_Service {

	/**
	 * @var SAML2_Assertion
	 */
	private $assertions = array();

	/**
	 * @var XMLSecurityKey
	 */
	private $security_key;

	/**
	 * LaunchKey_WP_SAML2_Service constructor.
	 * @param XMLSecurityKey $security_key
	 */
	public function __construct( XMLSecurityKey $security_key ) {
		$this->security_key = $security_key;
	}

	/**
	 * @param string $saml_response Base64 Encoded SAML
	 * @throws Exception When no assertions are found or signature in invalid
	 */
	public function load_saml_response( $saml_response ) {
		$response_element = SAML2_DOMDocumentFactory::fromString( base64_decode( $saml_response ) )->documentElement;
		$signature_info = SAML2_Utils::validateElement( $response_element );
		SAML2_Utils::validateSignature( $signature_info, $this->security_key );
		$response = SAML2_StatusResponse::fromXML( $response_element );
		/** @var SAML2_Assertion[] $assertions */
		$assertions = $response->getAssertions();
		$this->assertions = $assertions;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function get_name() {
		foreach ( $this->assertions as $assertion ) {
			$name_id = $assertion->getNameId();
			if ( $name_id ) {
				$name = $name_id['Value'];
				break;
			}
		}
		return $name;
	}

	/**
	 * @return string
	 */
	public function get_session_index() {
		foreach ( $this->assertions as $assertion ) {
			$index = $assertion->getSessionIndex();
			if ( $index ) {
				break;
			}
		}
		return $index;
	}

	/**
	 * @param string $name Attribute name
	 * @return array
	 */
	public function get_attribute( $name ) {
		$value = null;
		foreach ( $this->assertions as $assertion ) {
			$attributes = $assertion->getAttributes();
			if ( array_key_exists( $name, $attributes ) ) {
				$value = $attributes[$name];
				break;
			}
		}
		return $value;
	}
}
