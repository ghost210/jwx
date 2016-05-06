<?php

use JWX\JWK\Parameter\ECCPrivateKeyParameter;
use JWX\JWK\Parameter\JWKParameter;
use JWX\JWK\Parameter\RegisteredJWKParameter;


/**
 * @group jwk
 * @group parameter
 */
class ECCPrivateKeyParameterTest extends PHPUnit_Framework_TestCase
{
	public function testCreate() {
		$param = ECCPrivateKeyParameter::fromString("0123456789abcdef");
		$this->assertInstanceOf(ECCPrivateKeyParameter::class, $param);
		return $param;
	}
	
	/**
	 * @depends testCreate
	 *
	 * @param JWKParameter $param
	 */
	public function testParamName(JWKParameter $param) {
		$this->assertEquals(RegisteredJWKParameter::PARAM_ECC_PRIVATE_KEY, 
			$param->name());
	}
}