<?php

namespace Guzzle\Rs\Tests\Command;

use Guzzle\Rs\Model\SecurityGroup1_5;

use Guzzle\Rs\Tests\Utils\ClientCommandsBase;

class SecurityGroupRulesCommandsTest extends ClientCommandsBase {
	
	/**
	 * 
	 * @var SecurityGroup1_5
	 */
	protected static $_security_group;
	protected static $testTs;
	
	public static function setUpBeforeClass() {
		self::$testTs = time();
		self::$_security_group = new SecurityGroup1_5();
		self::$_security_group->name = 'Guzzle_Test_' . self::$testTs;
		self::$_security_group->create();
	}
	
	public static function tearDownAfterClass() {
		self::$_security_group->destroy();
	}
	
	/**
	 * @group v1_5
	 * @group integration
	 */
	public function testCanCreateCidrRule() {
		
	}
	
}