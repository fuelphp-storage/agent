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
 * Browscap.ini parsing class with caching and update capabilities
 *
 * Based on https://github.com/GaretJax/phpbrowscap/
 *
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 *
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @license    MIT License
 *
 * @package  Fuel\Agent
 *
 * @since  2.0.0
 */
class Browscap
{
	/**
	 * Different ways to access remote and local files:
	 */

	 /**
	 * UPDATE_FOPEN: Uses the fopen url wrapper (use file_get_contents).
	 *
	 * @const  string
	 */
	const UPDATE_FOPEN = 'URL-wrapper';

	 /**
	 * UPDATE_FSOCKOPEN: Uses the socket functions (fsockopen).
	 *
	 * @const  string
	 */
	const UPDATE_FSOCKOPEN = 'socket';

	 /**
	 * UPDATE_CURL: Uses the cURL extension.
	 *
	 * @const  string
	 */
	const UPDATE_CURL = 'cURL';

	 /**
	 * UPDATE_LOCAL: Updates from a local file (file_get_contents).
	 *
	 * @const  string
	 */
	const UPDATE_LOCAL = 'local';

	/**
	 * Options for regex patterns:
	 */

	 /**
	 * REGEX_DELIMITER: Delimiter of all the regex patterns in the whole class.
	 *
	 * @const  string
	 */
	const REGEX_DELIMITER = '@';

	 /**
	 * REGEX_MODIFIERS: Regex modifiers.
	 *
	 * @const  string
	 */
	const REGEX_MODIFIERS = 'i';

	/**
	 * The values to quote in the ini file
	 *
	 * @const  string
	 */
	const VALUES_TO_QUOTE = 'Browser|Parent';

	/**
	 * Definitions of the function used by the uasort() function to order the
	 * userAgents array:
	 */

	/**
	 * The headers to be sent for checking the version and requesting the file.
	 *
	 * @const  string
	 */
	const REQUEST_HEADERS = "GET %s HTTP/1.0\r\nHost: %s\r\nUser-Agent: %s\r\nConnection: Close\r\n\r\n";

	/**
	 * Options for auto update capabilities
	 */

	/**
	 * The location from which download the ini file. The placeholder for the file should be represented by a %s
	 *
	 * @var  string
	 */
	public $remoteIniUrl = 'http://tempdownloads.browserscap.com//stream.asp?BrowsCapINI';

	/**
	 * The location to use to check out if a new version of the browscap.ini file is available
	 *
	 * @var  string
	 */
	public $remoteVerUrl = 'http://tempdownloads.browserscap.com/versions/version-date.php';

	/**
	 * The timeout for browscap download requests
	 *
	 * @var  int
	 */
	public $timeout = 5;

	/**
	 * The update interval in seconds. Defaults to 5 days
	 *
	 * @var  int
	 */
	public $updateInterval = 432000;

	/**
	 * The next update interval in seconds in case of an error. Defaults to two hours
	 *
	 * @var  int
	 */
	public $errorInterval = 7200;

	/**
	 * Flag to enable/disable the automatic interval based update
	 *
	 * @var  bool
	 */
	public $doAutoUpdate = true;

	/**
	 * The method to use to update the file, has to be a value of an UPDATE_* constant, null or false
	 *
	 * @var  mixed
	 */
	public $updateMethod = null;

	/**
	 * The path of the local version of the browscap.ini file from which to
	 * update (to be set only if used).
	 *
	 * @var string
	 */
	public $localFile = null;

	/**
	 * The useragent to include in the requests made by the class during the
	 * update process.
	 *
	 * @var string
	 */
	public $userAgent = 'FuelPHP Agent library - Browscap class';

	/**
	 * Flag to enable only lowercase indexes in the result.
	 * The cache has to be rebuilt in order to apply this option.
	 *
	 * @var bool
	 */
	public $lowercase = false;

	/**
	 * Flag to enable/disable silent error management.
	 *
	 * In case of an error during the update process the class returns an empty
	 * array/object if the update process can't take place and the browscap.ini
	 * file does not exist.
	 *
	 * @var bool
	 */
	public $silent = false;

	/**
	 * Filename used to store the cached PHP arrays.
	 *
	 * @var string
	 */
	public $cacheFilename = 'browscap.cache';

	/**
	 * Filename to store the downloaded ini file.
	 *
	 * @var string
	 */
	public $iniFilename = 'browscap.ini';

	/**
	 * Path to the browscap cache directory
	 *
	 * @var string
	 */
	public $cacheDir = null;

	/**
	 * Flag to be set to true after loading the cache
	 *
	 * @var bool
	 */
	protected $cacheLoaded = false;

	/**
	 * Where to store the value of the included PHP cache file
	 *
	 * @var array
	 */
	protected $userAgents = array();
	protected $browsers   = array();
	protected $patterns   = array();
	protected $properties = array();

	/**
	 * An associative array of associative arrays in the format
	 * `$arr['wrapper']['option'] = $value` passed to stream_context_create()
	 * when building a stream resource.
	 *
	 * Proxy settings are stored in this variable.
	 *
	 * @see http://www.php.net/manual/en/function.stream-context-create.php
	 *
	 * @var array
	 */
	protected $streamContextOptions = array();

	/**
	 * A valid context resource created with stream_context_create().
	 *
	 * @see http://www.php.net/manual/en/function.stream-context-create.php
	 *
	 * @var resource
	 */
	protected $streamContext = null;

	/**
	 * Constructor class, checks for the existence of (and loads) the cache and
	 * if needed updated the definitions
	 *
	 * @param  string  $cache  optional directory location or filename of the browscap cache
	 */
	public function __construct($cache = null)
	{
		// we need one of those
		if (empty($cache) and empty($this->cacheDir) and empty($this->cacheFilename))
		{
			throw new \InvalidArgumentException('You have to provide a path to read/store the browscap cache file');
		}

		// if no custom path was passed, use the defined one
		if (empty($cache))
		{
			$cache = rtrim($this->cacheDir, '\\/').DIRECTORY_SEPARATOR.$this->cacheFilename;
		}

		// does the cache path exist, and do we have permission to access it?
		if ( ! $cachePath = realpath($cache))
		{
			throw new \InvalidArgumentException('Path "'.$cache.'" is invalid or you don\'t have permission to access it');
		}

		// do we have a filename, or only a path?
		if ( ! empty($file = basename($cache)))
		{
			$this->cacheFilename = $file;
			$this->cacheDir = dirname($cache);
		}
		else
		{
			$this->cacheDir = $cache;
		}

		$this->cacheDir .= DIRECTORY_SEPARATOR;
	}

	/**
	 * Gets the information about the browser by User Agent
	 *
	 * @param  string  $userAgent    the user agent string
	 * @param  bool    $asArray  whether return an array or an object
	 * @throws Exception
	 *
	 * @return object|array  an stdClass object containing the browsers details, or an array if $return_array is set to true
	 */
	public function getBrowser($userAgent = null, $asArray = false)
	{
		// load the cache at the first request
		if ( ! $this->cacheLoaded)
		{
			// construct the filenames
			$cacheFile = $this->cacheDir . $this->cacheFilename;
			$iniFile = $this->cacheDir . $this->iniFilename;

			// set the interval only if needed
			if ($this->doAutoUpdate and file_exists($iniFile))
			{
				$interval = time() - filemtime($iniFile);
			}
			else
			{
				$interval = 0;
			}

			// find out if the cache needs to be updated
			if ( ! file_exists($cacheFile) or ! file_exists($iniFile) or ($interval > $this->updateInterval))
			{
				try
				{
					$this->updateCache();
				}
				catch (\Exception $e)
				{
					if (file_exists($iniFile))
					{
						// adjust the filemtime to the $errorInterval
						touch($iniFile, time() - $this->updateInterval + $this->errorInterval);
					}
					elseif ($this->silent)
					{
						// return an empty array if silent mode is active and the ini db doesn't exsist
						return $asArray ? array() : (object) array();
					}

					// rethrow the exception
					throw $e;
				}
			}

			// populate the cache
			$this->loadCache($cacheFile);
		}

		// get the user agent of the current request if none was passed
		if (empty($userAgent))
		{
			$userAgent = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
		}

		// now for the better regex work...
		$browser = array();

		foreach ($this->patterns as $key => $pattern)
		{
			if (preg_match($pattern.'i', $userAgent))
			{
				$browser = array(
					$userAgent,
					trim(strtolower($pattern), self::REGEX_DELIMITER),
					$this->userAgents[$key]
				);

				$browser = $value = $browser + $this->browsers[$key];

				while (array_key_exists(3, $value) and $value[3])
				{
					$value = $this->browsers[$value[3]];
					$browser += $value;
				}

				if ( ! empty($browser[3]))
				{
					$browser[3] = $this->userAgents[$browser[3]];
				}

				break;
			}
		}

		// add the keys for each property
		$array = array();

		foreach ($browser as $key => $value)
		{
			if ($value === 'true')
			{
				$value = true;
			}
			elseif ($value === 'false')
			{
				$value = false;
			}
			$array[$this->properties[$key]] = $value;
		}

		return $asArray ? $array : (object) $array;
	}

	/**
	 * Load (auto-set) proxy settings from environment variables.
	 */
	public function autodetectProxySettings()
	{
		$wrappers = array('http', 'https', 'ftp');

		foreach ($wrappers as $wrapper)
		{
			$url = getenv($wrapper.'_proxy');
			if ( ! empty($url))
			{
				$params = array_merge(array(
					'port'  => null,
					'user'  => null,
					'pass'  => null,
					),
					parse_url($url)
				);
				$this->addProxySettings($params['host'], $params['port'], $wrapper, $params['user'], $params['pass']);
			}
		}
	}

	/**
	 * Add proxy settings to the stream context array.
	 *
	 * @param  string  $server    Proxy server/host
	 * @param  int     $port      Port
	 * @param  string  $wrapper   Wrapper: "http", "https", "ftp", others...
	 * @param  string  $username  Username (when requiring authentication)
	 * @param  string  $password  Password (when requiring authentication)
	 *
	 * @return Browscap
	 */
	public function addProxySettings($server, $port = 3128, $wrapper = 'http', $username = null, $password = null)
	{
		// streamContext settings
		$settings = array(
			$wrapper => array(
				'proxy'             => sprintf('tcp://%s:%d', $server, $port),
				'request_fulluri'   => true,
			)
		);

		// proxy authentication (optional)
		if ($username !== null and $password !== null)
		{
			$settings[$wrapper]['header'] = 'Proxy-Authorization: Basic '.base64_encode($username.':'.$password);
		}

		// add these new settings to the stream context options array
		$this->streamContextOptions = array_merge(
			$this->streamContextOptions,
			$settings
		);

		/* Return $this so we can chain addProxySettings() calls like this:
		 * $browscap->
		 *   addProxySettings('http')->
		 *   addProxySettings('https')->
		 *   addProxySettings('ftp');
		 */
		return $this;
	}

	/**
	 * Clear proxy settings from the stream context options array.
	 *
	 * @param  string  $wrapper Remove settings from this wrapper only
	 *
	 * @return  array  Wrappers cleared
	 */
	public function clearProxySettings($wrapper = null)
	{
		$wrappers = isset($wrapper) ? array($wrappers) : array_keys($this->streamContextOptions);
		$options = array('proxy', 'request_fulluri', 'header');

		$clearedWrappers = array();

		foreach ($wrappers as $wrapper)
		{
			// remove wrapper options related to proxy settings
			if (isset($this->streamContextOptions[$wrapper]['proxy']))
			{
				foreach ($options as $option)
				{
					unset($this->streamContextOptions[$wrapper][$option]);
				}

				// remove wrapper entry if there are no other options left
				if (empty($this->streamContextOptions[$wrapper]))
				{
					unset($this->streamContextOptions[$wrapper]);
				}

				$clearedWrappers[] = $wrapper;
			}
		}

		return $clearedWrappers;
	}

	/**
	 * Returns the array of stream context options.
	 *
	 * @return array
	 */
	public function getStreamContextOptions()
	{
		return $this->streamContextOptions;
	}

	/**
	 * Parses the ini file and updates the cache files
	 *
	 * @return  bool  whether the file was correctly written to the disk
	 */
	public function updateCache()
	{
		$iniFile = $this->cacheDir . $this->iniFilename;
		$cacheFile = $this->cacheDir . $this->cacheFilename;

		// Choose the right url
		if ($this->getUpdateMethod() == self::UPDATE_LOCAL)
		{
			$url = $this->localFile;
		}
		else
		{
			$url = $this->remoteIniUrl;
		}

		$this->getRemoteIniFile($url, $iniFile);

		$browsers = parse_ini_file($iniFile, true, INI_SCANNER_RAW);

		array_shift($browsers);

		$this->properties = array_keys($browsers['DefaultProperties']);

		array_unshift(
			$this->properties,
			'browser_name',
			'browser_name_regex',
			'browser_name_pattern',
			'Parent'
		);

		$this->userAgents = array_keys($browsers);

		usort(
			$this->userAgents,
			function($a, $b) { $a=strlen($a); $b=strlen($b); return $a==$b ? 0 : ($a<$b ? 1 : -1); }
		);

		$user_agents_keys = array_flip($this->userAgents);
		$properties_keys = array_flip($this->properties);

		$search = array('\*', '\?');
		$replace = array('.*', '.');

		foreach ($this->userAgents as $userAgent)
		{
			$pattern = preg_quote($userAgent, self::REGEX_DELIMITER);
			$this->patterns[] = self::REGEX_DELIMITER
			    .'^'
			    .str_replace($search, $replace, $pattern)
			    .'$'
			    .self::REGEX_DELIMITER;

			if ( ! empty($browsers[$userAgent]['Parent']))
			{
				$parent = $browsers[$userAgent]['Parent'];
				$browsers[$userAgent]['Parent'] = $user_agents_keys[$parent];
			}

			$browser = array();
			foreach ($browsers[$userAgent] as $key => $value)
			{
				$key = $properties_keys[$key] . ".0";
				$browser[$key] = $value;
			}

			$this->browsers[] = $browser;
			unset($browser);
		}
		unset($user_agents_keys, $properties_keys, $browsers);

		// save the keys lowercased if needed
		if ($this->lowercase)
		{
			$this->properties = array_map('strtolower', $this->properties);
		}

		// Get the whole PHP code
		$cache = $this->buildCache();

		// Save and return
		return (bool) file_put_contents($cacheFile, $cache, LOCK_EX);
	}

	/**
	 * Loads the cache into object's properties
	 *
	 * @param  string  $cacheFile  FQFN of the browscap cache file
	 *
	 * @return  void
	 */
	protected function loadCache($cacheFile)
	{
		require $cacheFile;

		$this->browsers = $browsers;
		$this->userAgents = $userAgents;
		$this->patterns = $patterns;
		$this->properties = $properties;

		$this->cacheLoaded = true;
	}

	/**
	 * Parses the array to cache and creates the PHP string to write to disk
	 *
	 * @return string the PHP string to save into the cache file
	 */
	protected function buildCache()
	{
		$cacheTpl = "<?php\n\$properties=%s;\n\$browsers=%s;\n\$userAgents=%s;\n\$patterns=%s;\n";

		$propertiesArray = $this->array2string($this->properties);
		$patternsArray = $this->array2string($this->patterns);
		$userAgentsArray = $this->array2string($this->userAgents);
		$browsersArray = $this->array2string($this->browsers);

		return sprintf(
			$cacheTpl,
			$propertiesArray,
			$browsersArray,
			$userAgentsArray,
			$patternsArray
		);
	}

	/**
	 * Lazy getter for the stream context resource.
	 *
	 * @param  bool  $reCreate  recreates the streamContext if true
	 * @return  resource
	 */
	protected function getStreamContext($reCreate = false)
	{
		if ( ! isset($this->streamContext) or $reCreate === true)
		{
			$this->streamContext = stream_context_create($this->streamContextOptions);
		}

		return $this->streamContext;
	}

	/**
	 * Updates the local copy of the ini file (by version checking) and adapts
	 * his syntax to the PHP ini parser
	 *
	 * @param  string  $url   the url of the remote server
	 * @param  string  $path  the path of the ini file to update
	 *
	 * @throws RuntimeException
	 *
	 * @return  bool  if the ini file was updated
	 */
	protected function getRemoteIniFile($url, $path)
	{
		// check version
		if (file_exists($path) and filesize($path))
		{
			$localTimestamp = filemtime($path);

			if ($this->getUpdateMethod() == self::UPDATE_LOCAL)
			{
				$remoteTimestamp = $this->getLocalMTime();
			}
			else
			{
				$remoteTimestamp = $this->getRemoteMTime();
			}

			if ($remoteTimestamp < $localTimestamp)
			{
				// No update needed
				touch($path);
				return false;
			}
		}

		// Get updated .ini file
		$browscap = $this->getRemoteData($url);
		$browscap = explode("\n", $browscap);

		$pattern = self::REGEX_DELIMITER
		    . '('
		    . self::VALUES_TO_QUOTE
		    . ')="?([^"]*)"?$'
		    . self::REGEX_DELIMITER;

		// ok, lets read the file
		$content = '';
		foreach ($browscap as $subject)
		{
			$subject = trim($subject);
			$content .= preg_replace($pattern, '$1="$2"', $subject) . "\n";
		}

		if ($url != $path)
		{
			if ( ! file_put_contents($path, $content))
			{
				throw new \RunttimeException('Could not write the browscap ini content to '.$path);
			}
		}

		return true;
	}

	/**
	 * Gets the remote ini file update timestamp
	 *
	 * @throws  Exception
	 *
	 * @return  int the remote modification timestamp
	 */
	protected function getRemoteMTime()
	{
		$remote_datetime = $this->getRemoteData($this->remoteVerUrl);
		$remoteTimestamp = strtotime($remote_datetime);

		if (!$remoteTimestamp) {
			throw new Exception("Bad datetime format from {$this->remoteVerUrl}");
		}

		return $remoteTimestamp;
	}

	/**
	 * Gets the local ini file update timestamp
	 *
	 * @throws  RuntimeException
	 *
	 * @return  int  the local modification timestamp
	 */
	protected function getLocalMTime()
	{
		if ( ! is_readable($this->localFile) or !is_file($this->localFile))
		{
			throw new \RuntimeException('The Local file "'.$this->localFile.'" is not readable');
		}

		return filemtime($this->localFile);
	}

	/**
	 * Converts the given array to the PHP string which represent it.
	 * This method optimizes the PHP code and the output differs form the
	 * var_export one as the internal PHP function does not strip whitespace or
	 * convert strings to numbers.
	 *
	 * @param  array  $array  the array to parse and convert
	 *
	 * @return string  the array parsed into a PHP string
	 */
	protected function array2string($array)
	{
		$strings = array();

		foreach ($array as $key => $value)
		{
			if (is_int($key))
			{
				$key = '';
			}
			elseif (ctype_digit((string) $key) or strpos($key, '.0'))
			{
				$key = intval($key) . '=>' ;
			}
			else
			{
				$key = "'" . str_replace("'", "\'", $key) . "'=>" ;
			}

			if (is_array($value))
			{
				$value = $this->array2string($value);
			}
			elseif (ctype_digit((string) $value))
			{
				$value = intval($value);
			}
			else
			{
				$value = "'" . str_replace("'", "\'", $value) . "'";
			}

			$strings[] = $key . $value;
		}

		return 'array(' . implode(',', $strings) . ')';
	}

	/**
	 * Checks for the various possibilities offered by the current configuration
	 * of PHP to retrieve external HTTP data
	 *
	 * @return  string  the name of function to use to retrieve the file
	 */
	protected function getUpdateMethod()
	{
		// Caches the result
		if ($this->updateMethod === null)
		{
			if ($this->localFile !== null)
			{
				$this->updateMethod = self::UPDATE_LOCAL;
			}
			elseif (ini_get('allow_url_fopen') and function_exists('file_get_contents'))
			{
				$this->updateMethod = self::UPDATE_FOPEN;
			}
			elseif (function_exists('fsockopen'))
			{
				$this->updateMethod = self::UPDATE_FSOCKOPEN;
			}
			elseif (extension_loaded('curl'))
			{
				$this->updateMethod = self::UPDATE_CURL;
			}
			else
			{
				$this->updateMethod = false;
			}
		}

		return $this->updateMethod;
	}

	/**
	 * Retrieve the data identified by the URL
	 *
	 * @param  string  $url  the url of the data
	 *
	 * @throws RuntimeException
	 *
	 * @return  string  the retrieved data
	 */
	protected function getRemoteData($url)
	{
		switch ($this->getUpdateMethod())
		{
			case self::UPDATE_LOCAL:
				$ua = ini_get('user_agent');
				ini_set('user_agent', $this->userAgent);

				$file = file_get_contents($url);

				ini_set('user_agent', $ua);

				if ($file !== false)
				{
					return $file;
				}

				throw new \RuntimeException('Cannot open the local file '.$url);

			case self::UPDATE_FOPEN:
				// include proxy settings in the file_get_contents() call
				$context = $this->getStreamContext();
				$file = file_get_contents($url, false, $context);

				if ($file !== false)
				{
					return $file;
				}
				// break intentionally omitted, we'll try sockets if streams fail

			case self::UPDATE_FSOCKOPEN:
				if (function_exists('fsockopen'))
				{
					$remote_url = parse_url($url);
					$remote_handler = fsockopen($remote_url['host'], 80, $c, $e, $this->timeout);

					if ($remote_handler)
					{
						stream_set_timeout($remote_handler, $this->timeout);

						if (isset($remote_url['query']))
						{
							$remote_url['path'] .= '?' . $remote_url['query'];
						}

						$out = sprintf(
							self::REQUEST_HEADERS,
							$remote_url['path'],
							$remote_url['host'],
							$this->userAgent
						);

						fwrite($remote_handler, $out);

						$response = fgets($remote_handler);
						if (strpos($response, '200 OK') !== false)
						{
							$file = '';
							while ( ! feof($remote_handler))
							{
								$file .= fgets($remote_handler);
							}

							$file = str_replace("\r\n", "\n", $file);
							$file = explode("\n\n", $file);
							array_shift($file);

							$file = implode("\n\n", $file);

							fclose($remote_handler);

							return $file;
						}
					}
				}
				// break intentionally omitted, we'll try curl if sockets fail

			case self::UPDATE_CURL:
				if (extension_loaded('curl'))
				{
					$ch = curl_init($url);

					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
					curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

					$file = curl_exec($ch);

					curl_close($ch);

					if ($file !== false)
					{
						return $file;
					}
				}
				// break intentionally omitted, we'll try ... oops, out of options...

			case false:
				throw new \RuntimeException('Your server can\'t connect to external resources. Please update the browscap file manually.');

			default:
				throw new \RuntimeException('Invalid request option configured, unable to get remote browscap data.');
		}
	}
}
