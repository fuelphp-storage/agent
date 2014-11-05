<?php
/**
 * @package   Fuel\Agent
 * @version   2.0
 * @author    Fuel Development Team
 * @license   MIT License
 * @copyright 2010 - 2014 Fuel Development Team
 * @link      http://fuelphp.com
 */

namespace Fuel\Agent;

use Codeception\TestCase\Test;
use Codeception\Configuration;
use LogicException;

/**
 * Tests for Agent
 *
 * @package Fuel\Agent
 * @author  Fuel Development Team
 *
 * @coversDefaultClass Fuel\Agent\Agent
 */
class AgentTest extends Test
{
	/**
	 * @var Agent
	 */
	protected $agent;

	protected function _before()
	{
		$this->agent = new Agent([
			'http_accept_language' => 'en;q=0.8',
			'cache_dir'            => Configuration::outputDir(),
			'browscap'             => [
				'doAutoUpdate' => false,
			],
		]);
	}

	public function testConstruct()
	{
		$agent = new Agent([
			'http_accept_language' => 'en;q=0.8',
			'cache_dir'            => Configuration::outputDir(),
			'lowercase'            => true,
			'browscap'             => [
				'doAutoUpdate' => false,
			],
		]);

		$this->assertEquals('Chromium', $agent->getBrowser());
	}

	/**
	 * @covers ::check
	 */
	public function testCheck()
	{
		$this->assertTrue($this->agent->check());
	}

	/**
	 * @covers            ::check
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidCheckMethod()
	{
		$agent = new Agent([
			'cache_dir'            => Configuration::outputDir(),
			'lowercase'            => true,
			'browscap'             => [
				'doAutoUpdate' => false,
			],
		], 'Invalid');

		$agent->check();
	}

	public function testGetters()
	{
		$this->assertEquals('Chromium', $this->agent->getBrowser());
		$this->assertEquals('Linux', $this->agent->getPlatform());
		$this->assertEquals('37.0', $this->agent->getVersion());
		$this->assertEquals('Chromium', $this->agent->getProperty('browser'));
		$this->assertEquals($this->agent->getProperties(), $this->agent->getProperty());
		$this->assertInternalType('array', $this->agent->getProperties());
		$this->assertFalse($this->agent->isRobot());
		$this->assertFalse($this->agent->isMobileDevice());
		$this->assertFalse($this->agent->isMobile());
		$this->assertTrue($this->agent->doesAcceptLanguage('en'));
		$this->assertTrue($this->agent->doesAcceptCharset());
	}
}
