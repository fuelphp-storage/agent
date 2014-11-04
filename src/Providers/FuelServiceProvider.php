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

use Fuel\Dependency\ServiceProvider;

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
	public $provides = ['agent'];

	/**
	 * Service provider definitions
	 */
	public function provide()
	{
		// \Fuel\Agent\Agent
		$this->register('agent', function ($dic, array $config = [], $method = 'browscap')
		{
			// get the agent config
			$stack = $this->container->resolve('requeststack');

			if ($request = $stack->top())
			{
				$instance = $request->getComponent()->getConfig();
			}
			else
			{
				$instance = $dic->resolve('application::__main')->getRootComponent()->getConfig();
			}

			$config = \Arr::merge($instance->load('agent', true), $config);

			return $dic->resolve('Fuel\Agent\Agent', [$config, $method]);
		});
	}
}
