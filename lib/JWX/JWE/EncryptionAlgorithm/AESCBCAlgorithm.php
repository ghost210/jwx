<?php

namespace JWX\JWE\EncryptionAlgorithm;

use JWX\JWE\ContentEncryptionAlgorithm;
use JWX\JWE\Exception\AuthenticationException;
use JWX\JWT\Parameter\EncryptionAlgorithmParameter;


/**
 * Base class for algorithms implementing AES in CBC mode with HMAC-SHA.
 *
 * @link https://tools.ietf.org/html/rfc7518#section-5.2
 */
abstract class AESCBCAlgorithm implements ContentEncryptionAlgorithm
{
	/**
	 * Get cipher method name that is recognized by OpenSSL.
	 *
	 * @return string
	 */
	abstract protected function _cipherMethod();
	
	/**
	 * Get algorithm name that is recognized by the Hash extension.
	 *
	 * @return string
	 */
	abstract protected function _hashAlgo();
	
	/**
	 * Get length of the encryption key.
	 *
	 * @return int
	 */
	abstract protected function _encKeyLen();
	
	/**
	 * Get length of the MAC key.
	 *
	 * @return int
	 */
	abstract protected function _macKeyLen();
	
	/**
	 * Get length of the authentication tag.
	 *
	 * @return int
	 */
	abstract protected function _tagLen();
	
	/**
	 * Get cipher method and verify that it's supported.
	 *
	 * @throws \RuntimeException
	 * @return string
	 */
	final protected function _getCipherMethod() {
		static $supported_ciphers;
		if (!isset($supported_ciphers)) {
			$supported_ciphers = array_flip(openssl_get_cipher_methods());
		}
		$method = $this->_cipherMethod();
		if (!isset($supported_ciphers[$method])) {
			throw new \RuntimeException(
				"Cipher method $method is not" .
					 " supported by this version of OpenSSL.");
		}
		return $method;
	}
	
	/**
	 * Check that key is valid.
	 *
	 * @param string $key
	 * @throws \RuntimeException
	 */
	final protected function _validateKey($key) {
		if (strlen($key) != $this->keySize()) {
			throw new \RuntimeException("Invalid key size.");
		}
	}
	
	/**
	 * Check that IV is valid.
	 *
	 * @param string $iv
	 * @throws \RuntimeException
	 */
	final protected function _validateIV($iv) {
		$len = openssl_cipher_iv_length($this->_getCipherMethod());
		if ($len != strlen($iv)) {
			throw new \RuntimeException("Invalid IV length.");
		}
	}
	
	/**
	 * Get MAC key from CEK.
	 *
	 * @param string $key
	 * @return string
	 */
	final protected function _macKey($key) {
		return substr($key, 0, $this->_macKeyLen());
	}
	
	/**
	 * Get encryption key from CEK.
	 *
	 * @param string $key
	 * @return string
	 */
	final protected function _encKey($key) {
		return substr($key, -$this->_encKeyLen());
	}
	
	/**
	 * Compute AL value.
	 *
	 * @param string $aad
	 * @return string 64 bits
	 */
	final protected function _aadLen($aad) {
		// truncate on 32 bit hosts
		if (PHP_INT_SIZE < 8) {
			return "\0\0\0\0" . pack("N", strlen($aad) * 8);
		}
		return pack("J", strlen($aad) * 8);
	}
	
	/**
	 * Compute authentication tag.
	 *
	 * @param string $data
	 * @param string $key CEK
	 * @return string
	 */
	final protected function _computeAuthTag($data, $key) {
		$tag = hash_hmac($this->_hashAlgo(), $data, $this->_macKey($key), true);
		return substr($tag, 0, $this->_tagLen());
	}
	
	public function encrypt($plaintext, $key, $iv, $aad) {
		$this->_validateKey($key);
		$this->_validateIV($iv);
		$ciphertext = openssl_encrypt($plaintext, $this->_getCipherMethod(), 
			$this->_encKey($key), OPENSSL_RAW_DATA, $iv);
		if (false === $ciphertext) {
			throw new \RuntimeException(
				"openssl_encrypt() failed: " . $this->_getLastOpenSSLError());
		}
		$auth_data = $aad . $iv . $ciphertext . $this->_aadLen($aad);
		$auth_tag = $this->_computeAuthTag($auth_data, $key);
		return [$ciphertext, $auth_tag];
	}
	
	public function decrypt($ciphertext, $key, $iv, $aad, $auth_tag) {
		$this->_validateKey($key);
		$this->_validateIV($iv);
		$auth_data = $aad . $iv . $ciphertext . $this->_aadLen($aad);
		if ($this->_computeAuthTag($auth_data, $key) != $auth_tag) {
			throw new AuthenticationException("Message authentication failed.");
		}
		$plaintext = openssl_decrypt($ciphertext, $this->_getCipherMethod(), 
			$this->_encKey($key), OPENSSL_RAW_DATA, $iv);
		if (false === $plaintext) {
			throw new \RuntimeException(
				"openssl_decrypt() failed: " . $this->_getLastOpenSSLError());
		}
		return $plaintext;
	}
	
	/**
	 * Get last OpenSSL error message.
	 *
	 * @return string|null
	 */
	protected function _getLastOpenSSLError() {
		$msg = null;
		while (false !== ($err = openssl_error_string())) {
			$msg = $err;
		}
		return $msg;
	}
	
	public function ivSize() {
		return 16;
	}
	
	public function headerParameters() {
		return array(EncryptionAlgorithmParameter::fromAlgorithm($this));
	}
}
