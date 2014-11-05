<?php
/**
 * @package    Fuel\Agent
 * @version    2.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2014 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Agent;

use phpbrowscap\Browscap;
use InvalidArgumentException;

/**
 * Identifies the platform, browser, robot, or mobile device from the user agent string
 *
 * @package Fuel\Agent
 *
 * @since 1.0.0
 */
class Agent
{
	/**
	 * Default information fields
	 *
	 * @var array
	 */
	protected $defaults = [
		'browser_name'         => 'unknown',
		'browser_name_regex'   => '',
		'browser_name_pattern' => '',
		'Parent'               => '',
		'Platform'             => '',
		'Comment'              => null,
		'Browser'              => null,
		'Version'              => null,
		'MajorVer'             => null,
		'MinorVer'             => null,
		'Frames'               => null,
		'IFrames'              => null,
		'Tables'               => null,
		'Cookies'              => null,
		'JavaScript'           => null,
		'JavaApplets'          => null,
		'CssVersion'           => null,
		'Platform_Version'     => null,
		'Alpha'                => null,
		'Beta'                 => null,
		'Win16'                => null,
		'Win32'                => null,
		'Win64'                => null,
		'BackgroundSounds'     => null,
		'VBScript'             => null,
		'ActiveXControls'      => null,
		'isMobileDevice'       => null,
		'isSyndicationReader'  => null,
		'Crawler'              => null,
		'AolVersion'           => null,
	];

	/**
	 * Information about the current user agent
	 *
	 * @var array
	 */
	protected $properties = [];

	/**
	 * @var array
	 */
	protected $config = [];

	/**
	 * Server variables
	 *
	 * @var array
	 */
	protected $server = [
		'http_accept_language' => '',
		'http_accept_charset'  => '',
		'http_user_agent'      => '',
	];

	/**
	 * Last used user agent string
	 *
	 * @var string
	 */
	protected $userAgent = '';

	/**
	 * Method to be used to fetch user agent information
	 *
	 * @var string
	 */
	protected $method = 'browscap';

	/**
	 * Browscap object
	 *
	 * @var Browscap
	 */
	protected $browscap;

	/**
	 * @param array  $config Class configuration array
	 * @param string $method Method to be used to get user agent details
	 */
	public function __construct(array $config = [], $method = 'browscap')
	{
		// fetch server information, from config or global
		foreach (array_keys($this->server) as $key)
		{
			if (isset($config[$key]))
			{
				$this->server[$key] = $config[$key];
			}
			elseif (isset($_SERVER[strtoupper($key)]))
			{
				$this->server[$key] = $_SERVER[strtoupper($key)];
			}
		}

		// store the config passed
		$this->config = $config;

		// store the request method
		$this->method = strtolower($method);

		// object configuration
		if ($this->method == 'browscap' and ! empty($this->config['lowercase']))
		{
			// convert the defaults keys to lowercase
			$this->defaults = array_change_key_case($this->defaults);
		}

		// perform an initial load
		$this->check($this->server['http_user_agent']);
	}

	/**
	 * Accept a user agent to check, and try to find a match
	 *
	 * @param string|null $userAgent User agent string to check
	 *
	 * @return boolean True if the check was succesful, false otherwise
	 *
	 * @throws InvalidArgumentException If the method passed to the constuctor is invalid
	 */
	 public function check($userAgent = null)
	 {
		// was a user agent passed? If not, use the server one
		if (empty($userAgent))
		{
			$userAgent = $this->server['http_user_agent'];
		}

		// store the user agent
		$this->userAgent = $userAgent;

		// fetch the data
		switch ($this->method)
		{
			case "browscap":
				// create a browscap object if needed?
				if ( ! $this->browscap)
				{
					if ($this->browscap = new Browscap($this->config['cache_dir']))
					{
						// give the browscap some config
						if ( ! empty($this->config['lowercase']))
						{
							$this->browscap->lowercase = true;
						}

						if (isset($this->config['browscap']))
						{
							foreach ($this->config['browscap'] as $key => $value)
							{
								if (property_exists($this->browscap, $key))
								{
									$this->browscap->{$key} = $value;
								}
							}
						}

						$browscap = $this->browscap->getBrowser($this->userAgent, true);

						$this->properties = array_merge($this->defaults, $browscap);
					}
				}
				break;

			default:
				throw new InvalidArgumentException('Invalid detection method "'.$this->method.'", can not load browser information.');
		}

		return true;
	}

	/**
	 * Get any browser property
	 *
	 * @return string
	 */
	public function __get($property)
	{
		if (empty($this->config['lowercase']))
		{
			$properties = array_change_key_case($this->properties);
		}
		else
		{
			$properties =& $this->properties;
		}

		$property = strtolower($property);

		return array_key_exists($property, $properties) ? $properties[$property] : null;
	}

	/**
	 * Get the browser identification string
	 *
	 * @return string
	 */
	public function getBrowser()
	{
		return $this->browser;
	}

	/**
	 * Get the browser platform
	 *
	 * @return string
	 */
	public function getPlatform()
	{
		return $this->platform;
	}

	/**
	 * Get the Browser Version
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Get a single browser property
	 *
	 * @param mixed $property
	 *
	 * @return mixed
	 */
	public function getProperty($property = null)
	{
		return $property === null ? $this->getProperties() : $this->{$property};
	}


	/**
	 * Get all browser properties
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * check if the current browser is a robot or crawler
	 *
	 * @return boolean
	 */
	public function isRobot()
	{
		return $this->crawler;
	}

	/**
	 * check if the current browser is mobile device
	 *
	 * @return boolean
	 */
	public function isMobileDevice()
	{
		return $this->ismobiledevice;
	}

	/**
	 * Alias for isMobileDevice
	 *
	 * @return boolean
	 */
	public function isMobile()
	{
		return $this->isMobileDevice();
	}

	/**
	 * Check if the current browser accepts a specific language
	 *
	 * @param string $language optional ISO language code, defaults to 'en'
	 *
	 * @return boolean
	 */
	public function doesAcceptLanguage($language = 'en')
	{
		return (in_array(strtolower($language), $this->getAcceptLanguages(), true)) ? true : false;
	}

	/**
	 * Check if the current browser accepts a specific character set
	 *
	 * @param string $charset optional character set, defaults to 'utf-8'
	 *
	 * @return boolean
	 */
	public function doesAcceptCharset($charset = 'utf-8')
	{
		return (in_array(strtolower($charset), $this->getAcceptCharsets(), true)) ? true : false;
	}

	/**
	 * Get the list of browser accepted languages
	 *
	 * @return array
	 */
	public function getAcceptLanguages()
	{
		return explode(',', preg_replace('/(;q=[0-9\.]+)/i', '', strtolower(trim($this->server['http_accept_language']))));
	}

	// --------------------------------------------------------------------

	/**
	 * Get the list of browser accepted charactersets
	 *
	 * @return array
	 */
	public function getAcceptCharsets()
	{
		return explode(',', preg_replace('/(;q=.+)/i', '', strtolower(trim($this->server['http_accept_charset']))));
	}

}
