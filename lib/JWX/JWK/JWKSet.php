<?php

namespace JWX\JWK;

use JWX\JWK\Parameter\RegisteredJWKParameter;


/**
 * Represents a JWK set structure.
 *
 * @link https://tools.ietf.org/html/rfc7517#section-5
 */
class JWKSet implements \Countable, \IteratorAggregate
{
	/**
	 * JWK objects.
	 *
	 * @var JWK[] $_jwks
	 */
	protected $_jwks;
	
	/**
	 * Additional members.
	 *
	 * @var array $_additional
	 */
	protected $_additional;
	
	/**
	 * JWK mappings.
	 *
	 * @var array
	 */
	private $_mappings = array();
	
	/**
	 * Constructor
	 *
	 * @param JWK ...$jwks
	 */
	public function __construct(JWK ...$jwks) {
		$this->_jwks = $jwks;
		$this->_additional = array();
	}
	
	/**
	 * Initialize from an array representing a JSON object.
	 *
	 * @param array $members
	 * @throws \UnexpectedValueException
	 * @return self
	 */
	public static function fromArray(array $members) {
		if (!isset($members["keys"]) || !is_array($members["keys"])) {
			throw new \UnexpectedValueException(
				"JWK Set must have a 'keys' member.");
		}
		$jwks = array_map(
			function ($jwkdata) {
				return JWK::fromArray($jwkdata);
			}, $members["keys"]);
		unset($members["keys"]);
		$obj = new self(...$jwks);
		$obj->_additional = $members;
		return $obj;
	}
	
	/**
	 * Initialize from a JSON string.
	 *
	 * @param string $json
	 * @throws \UnexpectedValueException
	 * @return self
	 */
	public static function fromJSON($json) {
		$members = json_decode($json, true, 32, JSON_BIGINT_AS_STRING);
		if (!is_array($members)) {
			throw new \UnexpectedValueException("Invalid JSON.");
		}
		return self::fromArray($members);
	}
	
	/**
	 * Get all JWK's in a set.
	 *
	 * @return JWK[]
	 */
	public function keys() {
		return $this->_jwks;
	}
	
	/**
	 * Get JWK by key ID.
	 *
	 * @param string $id
	 * @return JWK|null Null if not found
	 */
	protected function _getKeyByID($id) {
		$map = $this->_getMapping(RegisteredJWKParameter::PARAM_KEY_ID);
		return isset($map[$id]) ? $map[$id] : null;
	}
	
	/**
	 * Check whether set has a JWK with a given key ID.
	 *
	 * @param string $id
	 * @return bool
	 */
	public function hasKeyID($id) {
		return $this->_getKeyByID($id) !== null;
	}
	
	/**
	 * Get a JWK by a key ID.
	 *
	 * @param string $id
	 * @throws \LogicException
	 * @return JWK
	 */
	public function keyByID($id) {
		$jwk = $this->_getKeyByID($id);
		if (!$jwk) {
			throw new \LogicException("No key ID $id.");
		}
		return $jwk;
	}
	
	/**
	 * Get mapping from parameter values of given parameter name to JWK.
	 *
	 * Later duplicate value shall override earlier JWK.
	 *
	 * @param string $name Parameter name
	 * @return array
	 */
	protected function _getMapping($name) {
		if (!isset($this->_mappings[$name])) {
			$mapping = array();
			foreach ($this->_jwks as $jwk) {
				if ($jwk->has($name)) {
					$key = (string) $jwk->get($name)->value();
					$mapping[$key] = $jwk;
				}
			}
			$this->_mappings[$name] = $mapping;
		}
		return $this->_mappings[$name];
	}
	
	/**
	 * Convert to JSON.
	 *
	 * @return string
	 */
	public function toJSON() {
		$data = $this->_additional;
		$data["keys"] = $this->_jwks;
		return json_encode((object) $data, JSON_UNESCAPED_SLASHES);
	}
	
	/**
	 * Get the number of keys.
	 *
	 * @see Countable::count()
	 */
	public function count() {
		return count($this->_jwks);
	}
	
	/**
	 * Get iterator for JWK objects.
	 *
	 * @see IteratorAggregate::getIterator()
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->_jwks);
	}
}
