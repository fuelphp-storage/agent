<?php
/**
 * @package    Fuel\Agent
 * @version    2.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Agent;

use Fuel\Dependency\ServiceProvider;

/**
 * ServicesProvider class
 *
 * Defines the services published by this namespace to the DiC
 *
 * @package  Fuel\Agent
 *
 * @since  1.0.0
 */
class ServicesProvider extends ServiceProvider
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
			return new Agent($config, $method);
		});
	}
}
