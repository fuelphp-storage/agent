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
 * FuelPHP ServiceProvider class for this package
 *
 * @package  Fuel\Agent
 *
 * @since  1.0.0
 */
class FuelServiceProvider extends ServiceProvider
{
	/**
	 * @var  array  list of service names provided by this provider
	 */
	public $provides = array('agent');

	/**
	 * Service provider definitions
	 */
	public function provide()
	{
		// \Fuel\Agent\Agent
		$this->register('agent', function ($dic, Array $config = array(), $method = 'browscap')
		{
			// get the agent config
			$stack = $this->container->resolve('requeststack');
			if ($request = $stack->top())
			{
				$instance = $request->getComponent()->getConfig();
			}
			else
			{
				$instance = $dic->resolve('application::__main')->getComponent()->getConfig();
			}
			$config = \Arr::merge($instance->load('agent', true), $config);

			return $dic->resolve('Fuel\Agent\Agent', array($config, $method));
		});
	}
}
