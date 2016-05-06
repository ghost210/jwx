<?php

namespace JWX\JWT\Parameter;

use JWX\Util\Base64;


/**
 * Implements 'X.509 Certificate Chain' parameter.
 *
 * @link https://tools.ietf.org/html/rfc7515#section-4.1.6
 */
class X509CertificateChainParameter extends RegisteredJWTParameter
{
	/**
	 * Constructor
	 *
	 * @param string ...$certs Base64 encoded DER certificates
	 */
	public function __construct(...$certs) {
		foreach ($certs as $cert) {
			if (Base64::isValid($cert)) {
				throw new \UnexpectedValueException(
					"Certificate must be base64 encoded.");
			}
		}
		parent::__construct(self::PARAM_X509_CERTIFICATE_CHAIN, $certs);
	}
	
	public static function fromJSONValue($value) {
		if (!is_array($value)) {
			throw new \UnexpectedValueException("x5c must be an array.");
		}
		return new static(...$value);
	}
}
