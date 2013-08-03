<?php
/**
 * @package    Fuel\Agent
 * @version    2.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Agent;

/**
 * Identifies the platform, browser, robot, or mobile device from the user agent string
 *
 * @package  Fuel\Agent
 *
 * @since  1.0.0
 */

class Agent
{
	/**
	 * @var  array  default information fields
	 */
	protected $defaults = array(
		'browser_name' => 'unknown',
		'browser_name_regex' => '',
		'browser_name_pattern' => '',
		'Parent' => '',
		'Platform' => '',
		'Comment' => null,
		'Browser' => null,
		'Version' => null,
		'MajorVer' => null,
		'MinorVer' => null,
		'Frames' => null,
		'IFrames' => null,
		'Tables' => null,
		'Cookies' => null,
		'JavaScript' => null,
		'JavaApplets' => null,
		'CssVersion' => null,
		'Platform_Version' => null,
		'Alpha' => null,
		'Beta' => null,
		'Win16' => null,
		'Win32' => null,
		'Win64' => null,
		'BackgroundSounds' => null,
		'VBScript' =>  null,
		'ActiveXControls' =>  null,
		'isMobileDevice' =>  null,
		'isSyndicationReader' =>  null,
		'Crawler' =>  null,
		'AolVersion' =>  null,
	);

	/**
	 * @var  array  information about the current user agent
	 */
	protected $properties = array(
	);


	/**
	 * @var  array  config items
	 */
	protected $config = array(
	);

	/**
	 * @var  array  server variables
	 */
	protected $server = array(
		'http_accept_language' => '',
		'http_accept_charset' => '',
		'http_user_agent' => '',
	);

	/**
	 * @var  string  last used user agent string
	 */
	protected $userAgent = '';

	/**
	 * @var  string  Method to be used to fetch user agent information
	 */
	protected $method = 'browscap';

	/**
	 * @var  Browscap  Browscap object
	 */
	protected $browscap;

	/**
	 * @param  array   $config  Class configuration array
	 * @param  string  $method  Method to be used to get user agent details
	 *
	 * @throws  InvalidArgumentException if an incorrect method was passed
	 */
	public function __construct(Array $config = array(), $method = 'browscap')
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
	 * @param  string|null  $userAgent  user agent string to check
	 *
	 * @throws  InvalidArgumentException  if the method passed to the constuctor is invalid
	 *
	 * @return  bool  true if the check was succesful, false otherwise
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
					if ($this->browscap = new Browscap($this->config['cacheDir']))
					{
						// give the browscap some config
						if (! empty($this->config['lowercase']))
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
						$this->properties = array_merge($this->defaults, $this->browscap->getBrowser($this->userAgent, true));
					}
				}
				break;

			default:
				throw new \InvalidArgumentException('Invalid detection method "'.$this->method.'", can not load browser information.');
		}

		return true;
	}

	/**
	 * Get any browser property
	 *
	 * @return	string
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
	 * @return	string
	 */
	public function getBrowser()
	{
		return $this->browser;
	}

	/**
	 * Get the browser platform
	 *
	 * @return	string
	 */
	public function getPlatform()
	{
		return $this->platform;
	}

	/**
	 * Get the Browser Version
	 *
	 * @return	string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Get all browser properties
	 *
	 * @return	array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * check if the current browser is a robot or crawler
	 *
	 * @return	bool
	 */
	public function isRobot()
	{
		return $this->crawler;
	}

	/**
	 * check if the current browser is mobile device
	 *
	 * @return	bool
	 */
	public function isMobileDevice()
	{
		return $this->ismobiledevice;
	}

	/**
	 * alias for isMobileDevice
	 *
	 * @return	bool
	 */
	public function isMobile()
	{
		return $this->isMobileDevice();
	}

	/**
	 * check if the current browser accepts a specific language
	 *
	 * @param	string $language	optional ISO language code, defaults to 'en'
	 * @return	bool
	 */
	public function doesAcceptLanguage($language = 'en')
	{
		return (in_array(strtolower($language), $this->acceptLanguages(), true)) ? true : false;
	}

	/**
	 * check if the current browser accepts a specific character set
	 *
	 * @param	string $charset	optional character set, defaults to 'utf-8'
	 * @return	bool
	 */
	public function doesAcceptCharset($charset = 'utf-8')
	{
		return (in_array(strtolower($charset), $this->acceptCharsets(), true)) ? true : false;
	}

	/**
	 * get the list of browser accepted languages
	 *
	 * @return	array
	 */
	public function acceptLanguages()
	{
		return explode(',', preg_replace('/(;q=[0-9\.]+)/i', '', strtolower(trim($this->server['http_accept_language']))));
	}

	// --------------------------------------------------------------------

	/**
	 * get the list of browser accepted charactersets
	 *
	 * @return	array
	 */
	public function acceptCharsets()
	{
		return explode(',', preg_replace('/(;q=.+)/i', '', strtolower(trim($this->server['http_accept_charset']))));
	}

}
