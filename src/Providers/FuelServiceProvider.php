<?php
/**
 * @package    Fuel\Agent
 * @version    2.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2014 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Agent\Providers;

use Fuel\Agent\Agent;
use League\Container\ServiceProvider;

/**
 * Fuel ServiceProvider class for Agent
 *
 * @package Fuel\Agent
 *
 * @since 2.0.0
 */
class FuelServiceProvider extends ServiceProvider
{
	/**
	 * @var array
	 */
	protected $provides = ['agent'];

	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		// \Fuel\Agent\Agent
		$this->container->add('agent', function (array $config = [], $method = 'browscap')
		{
			$configInstance = $this->container->get('configInstance');

			$config = \Arr::merge($configInstance->load('agent', true), $config);

			return new Agent($config, $method);
		});
	}
}
