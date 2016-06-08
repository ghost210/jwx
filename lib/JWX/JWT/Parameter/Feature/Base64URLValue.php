<?php

namespace JWX\JWT\Parameter\Feature;

use JWX\Util\Base64;


/**
 * Trait for parameter having Base64url value.
 */
trait Base64URLValue
{
	/**
	 * Constructor.
	 *
	 * @param string $str Base64url encoded value
	 */
	abstract public function __construct($str);
	
	/**
	 * Initialize from native value.
	 *
	 * Value shall be encoded using Base64url encoding.
	 *
	 * @param string $value
	 * @return self
	 */
	public static function fromString($value) {
		return new static(Base64::urlEncode($value));
	}
	
	/**
	 * Validate that value is validly base64url encoded.
	 *
	 * @param string $value
	 * @throws \UnexpectedValueException
	 * @return self
	 */
	protected function _validateEncoding($value) {
		if (!Base64::isValidURLEncoding($value)) {
			throw new \UnexpectedValueException(
				"Value must be base64url encoded.");
		}
		return $this;
	}
}
