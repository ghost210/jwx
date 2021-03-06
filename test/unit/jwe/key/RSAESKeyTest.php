<?php

use CryptoUtil\ASN1\RSA\RSAPrivateKey;
use CryptoUtil\ASN1\RSA\RSAPublicKey;
use CryptoUtil\PEM\PEM;
use JWX\JWA\JWA;
use JWX\JWE\JWE;
use JWX\JWE\KeyAlgorithm\RSAESKeyAlgorithm;
use JWX\JWE\KeyAlgorithm\RSAESPKCS1Algorithm;
use JWX\JWE\KeyManagementAlgorithm;
use JWX\JWK\RSA\RSAPrivateKeyJWK;
use JWX\JWK\RSA\RSAPublicKeyJWK;
use JWX\JWT\Header\Header;
use JWX\JWT\Parameter\AlgorithmParameter;
use JWX\JWT\Parameter\JWTParameter;


/**
 * @group jwe
 * @group key
 */
class RSAESKeyTest extends PHPUnit_Framework_TestCase
{
	private static $_publicKey;
	
	private static $_privateKey;
	
	public static function setUpBeforeClass() {
		$pem = PEM::fromFile(TEST_ASSETS_DIR . "/rsa/private_key.pem");
		self::$_privateKey = RSAPrivateKeyJWK::fromPEM($pem);
		self::$_publicKey = self::$_privateKey->publicKey();
	}
	
	public static function tearDownAfterClass() {
		self::$_publicKey = null;
		self::$_privateKey = null;
	}
	
	public function testCreate() {
		$algo = RSAESPKCS1Algorithm::fromPrivateKey(self::$_privateKey);
		$this->assertInstanceOf(RSAESKeyAlgorithm::class, $algo);
		return $algo;
	}
	
	/**
	 * @depends testCreate
	 *
	 * @param KeyManagementAlgorithm $algo
	 */
	public function testHeaderParameters(KeyManagementAlgorithm $algo) {
		$params = $algo->headerParameters();
		$this->assertContainsOnlyInstancesOf(JWTParameter::class, $params);
	}
	
	/**
	 * @depends testCreate
	 *
	 * @param RSAESKeyAlgorithm $algo
	 */
	public function testPublicKey(RSAESKeyAlgorithm $algo) {
		$this->assertEquals(self::$_publicKey, $algo->publicKey());
	}
	
	/**
	 * @depends testCreate
	 *
	 * @param RSAESKeyAlgorithm $algo
	 */
	public function testHasPrivateKey(RSAESKeyAlgorithm $algo) {
		$this->assertTrue($algo->hasPrivateKey());
	}
	
	/**
	 * @depends testCreate
	 *
	 * @param RSAESKeyAlgorithm $algo
	 */
	public function testPrivateKey(RSAESKeyAlgorithm $algo) {
		$this->assertEquals(self::$_privateKey, $algo->privateKey());
	}
	
	public function testCreateFromPublicKey() {
		$algo = RSAESPKCS1Algorithm::fromPublicKey(self::$_publicKey);
		$this->assertInstanceOf(RSAESKeyAlgorithm::class, $algo);
		return $algo;
	}
	
	/**
	 * @depends testCreateFromPublicKey
	 * @expectedException LogicException
	 *
	 * @param RSAESKeyAlgorithm $algo
	 */
	public function testPrivateKeyNotSet(RSAESKeyAlgorithm $algo) {
		$algo->privateKey();
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	public function testEncryptFail() {
		$jwk = RSAPublicKeyJWK::fromPEM((new RSAPublicKey(0, 0))->toPEM());
		$algo = RSAESPKCS1Algorithm::fromPublicKey($jwk);
		$algo->encrypt("x");
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	public function testDecryptFail() {
		$jwk = RSAPrivateKeyJWK::fromRSAPrivateKey(
			new RSAPrivateKey(0, 0, 0, 0, 0, 0, 0, 0));
		$algo = RSAESPKCS1Algorithm::fromPrivateKey($jwk);
		$algo->decrypt("x");
	}
	
	/**
	 * @depends testCreate
	 * @expectedException RuntimeException
	 *
	 * @param RSAESKeyAlgorithm $algo
	 */
	public function testPubKeyFail(RSAESKeyAlgorithm $algo) {
		$obj = new ReflectionClass($algo);
		$prop = $obj->getProperty("_publicKey");
		$prop->setAccessible(true);
		$prop->setValue($algo, new RSAESKeyTest_KeyMockup());
		$algo->encrypt("test");
	}
	
	/**
	 * @depends testCreate
	 * @expectedException RuntimeException
	 *
	 * @param RSAESKeyAlgorithm $algo
	 */
	public function testPrivKeyFail(RSAESKeyAlgorithm $algo) {
		$obj = new ReflectionClass($algo);
		$prop = $obj->getProperty("_privateKey");
		$prop->setAccessible(true);
		$prop->setValue($algo, new RSAESKeyTest_KeyMockup());
		$algo->decrypt("test");
	}
	
	public function testFromJWKPriv() {
		$header = new Header(new AlgorithmParameter(JWA::ALGO_RSA1_5));
		$algo = RSAESKeyAlgorithm::fromJWK(self::$_privateKey, $header);
		$this->assertInstanceOf(RSAESPKCS1Algorithm::class, $algo);
	}
	
	public function testFromJWKPub() {
		$header = new Header(new AlgorithmParameter(JWA::ALGO_RSA1_5));
		$algo = RSAESKeyAlgorithm::fromJWK(self::$_publicKey, $header);
		$this->assertInstanceOf(RSAESPKCS1Algorithm::class, $algo);
	}
	
	/**
	 * @expectedException UnexpectedValueException
	 */
	public function testFromJWKUnsupportedAlgo() {
		$header = new Header(new AlgorithmParameter(JWA::ALGO_NONE));
		RSAESKeyAlgorithm::fromJWK(self::$_publicKey, $header);
	}
}


class RSAESKeyTest_KeyMockup
{
	public function toPEM() {
		return new RSAESKeyTest_PEMMockup();
	}
}


class RSAESKeyTest_PEMMockup
{
	public function string() {
		return "";
	}
}
