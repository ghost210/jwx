<?php

namespace JWX\JWE\KeyAlgorithm;

use AESKW\AESKeyWrapAlgorithm;
use JWX\JWA\JWA;
use JWX\JWE\KeyAlgorithm\Feature\RandomCEK;
use JWX\JWE\KeyManagementAlgorithm;
use JWX\JWK\JWK;
use JWX\JWK\Parameter\RegisteredJWKParameter;
use JWX\JWK\Symmetric\SymmetricKeyJWK;
use JWX\JWT\Header;
use JWX\JWT\Parameter\AlgorithmParameter;


/**
 * Base class for algorithms implementing AES key wrap.
 *
 * @link https://tools.ietf.org/html/rfc7518#section-4.4
 */
abstract class AESKWAlgorithm extends KeyManagementAlgorithm
{
	use RandomCEK;
	
	/**
	 * Key encryption key.
	 *
	 * @var string $_kek
	 */
	protected $_kek;
	
	/**
	 * Key wrapping algorithm.
	 *
	 * Lazily initialized.
	 *
	 * @var AESKeyWrapAlgorithm|null $_kw
	 */
	protected $_kw;
	
	/**
	 * Mapping from algorithm name to class name.
	 *
	 * @internal
	 *
	 * @var array
	 */
	const MAP_ALGO_TO_CLASS = array(
		/* @formatter:off */
		JWA::ALGO_A128KW => A128KWAlgorithm::class, 
		JWA::ALGO_A192KW => A192KWAlgorithm::class, 
		JWA::ALGO_A256KW => A256KWAlgorithm::class
		/* @formatter:on */
	);
	
	/**
	 * Get key wrapping algorithm instance.
	 *
	 * @return AESKeyWrapAlgorithm
	 */
	abstract protected function _AESKWAlgo();
	
	/**
	 * Constructor
	 *
	 * @param string $kek Key encryption key
	 */
	public function __construct($kek) {
		$this->_kek = $kek;
	}
	
	/**
	 * Initialize from JWK.
	 *
	 * If algorithm isn't specified, consult the JWK.
	 *
	 * @param JWK $jwk
	 * @param string|null $alg Optional explicitly specified algorithm
	 * @throws \UnexpectedValueException If parameters are missing
	 * @return self
	 */
	public static function fromJWK(JWK $jwk, $alg = null) {
		$jwk = SymmetricKeyJWK::fromJWK($jwk);
		if (!isset($alg)) {
			if (!$jwk->has(RegisteredJWKParameter::P_ALG)) {
				throw new \UnexpectedValueException("No algorithm parameter.");
			}
			$alg = $jwk->get(RegisteredJWKParameter::P_ALG)->value();
		}
		if (!array_key_exists($alg, self::MAP_ALGO_TO_CLASS)) {
			throw new \UnexpectedValueException("Unsupported algorithm '$alg'.");
		}
		$cls = self::MAP_ALGO_TO_CLASS[$alg];
		return new $cls($jwk->key());
	}
	
	/**
	 * Get key wrapping algorithm.
	 *
	 * @return AESKeyWrapAlgorithm
	 */
	protected function _kw() {
		if (!isset($this->_kw)) {
			$this->_kw = $this->_AESKWAlgo();
		}
		return $this->_kw;
	}
	
	protected function _encryptKey($key, Header &$header) {
		return $this->_kw()->wrap($key, $this->_kek);
	}
	
	protected function _decryptKey($ciphertext, Header $header) {
		return $this->_kw()->unwrap($ciphertext, $this->_kek);
	}
	
	public function headerParameters() {
		return array(AlgorithmParameter::fromAlgorithm($this));
	}
}
