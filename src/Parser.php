<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use s9e\TextFormatter\Parser\AttributeProcessing;
use s9e\TextFormatter\Parser\RulesHandling;
use s9e\TextFormatter\Parser\TagHandling;
use s9e\TextFormatter\Parser\TagProcessing;
use s9e\TextFormatter\Parser\TagStackHandling;

class Parser
{
	use AttributeProcessing;
	use RulesHandling;
	use TagHandling;
	use TagProcessing;
	use TagStackHandling;

	/**
	* Start tag, e.g. [b]
	* -- becomes <B><st>[b]</st>
	*/
	const START_TAG = 1;

	/**
	* End tag, e.g. [/b]
	* -- becomes <et>[/b]</et></B>
	*/
	const END_TAG = 2;

	/**
	* Self-closing tag, e.g. [img="http://..." /]
	* -- becomes <IMG>[img="http://..." /]</IMG>
	*
	* NOTE: SELF_CLOSING_TAG = START_TAG | END_TAG
	*/
	const SELF_CLOSING_TAG = 3;

	/**
	* Characters that are removed by the trim_* config directives
	* @link http://docs.php.net/manual/en/function.trim.php
	*/
	const TRIM_CHARLIST = " \n\r\t\0\x0B";

	//==============================================================================================
	// Application stuff
	//==============================================================================================

	/**
	* @var array Plugins config
	*/
	protected $pluginsConfig;

	/**
	* @var array Custom filters
	*/
	protected $filters = array();

	/**
	* @var array Array of PluginParser instances
	*/
	protected $pluginParsers = array();

	/**
	* @var array Vars available to filters
	*/
	protected $registeredVars = array();

	/**
	* @var array Context to be used for tags at the root of the document
	*/
	protected $rootContext;

	/**
	* @var array Tags config
	*/
	protected $tagsConfig;

	/**
	* @var array URL-specific config (disallowed hosts, allowed schemes, etc...)
	*/
	protected $urlConfig;

	//==============================================================================================
	// Per-formatting vars
	//==============================================================================================

	/**
	* @var string  Formatted text
	*/
	protected $output;

	/**
	* @var array Logged messages, reinitialized whenever a text is parsed
	*/
	protected $logs = array();

	/**
	* @var string  Text being parsed
	*/
	protected $text;

	/**
	* @var integer Length of the text being parsed
	*/
	protected $textLen;

	/**
	* @var array   Unprocessed tags, in reverse order
	*/
	protected $unprocessedTags;

	/**
	* @var array   Tags currently open, in document order
	*/
	protected $openTags;

	/**
	* @var array   Number of tags currently open, using the tag's tagMate value
	*/
	protected $openStartTags;

	/**
	* @var array   Number of open tags for each tag name
	*/
	protected $cntOpen;

	/**
	* @var array   Number of times each tag has been used
	*/
	protected $cntTotal;

	/**
	* @var Tag     Tag currently being processed
	*/
	protected $currentTag;

	/**
	* @var array   Current context
	*/
	protected $context;

	/**
	* @var integer Current position in the text
	*/
	protected $textPos;

	//==============================================================================================
	// Public stuff
	//==============================================================================================

	/**
	* Constructor
	*
	* @param  array $config The config array returned by Configurator->getParserConfig()
	* @return void
	*/
	public function __construct(array $config)
	{
		$this->pluginsConfig = $config['plugins'];
		$this->rootContext   = $config['rootContext'];
		$this->tagsConfig    = $config['tags'];
		$this->urlConfig     = $config['urlConfig'];

		if (isset($config['filters']))
		{
			$this->filters = $config['filters'];
		}
	}

	/**
	* 
	*
	* @return void
	*/
	public function serialize()
	{
		return serialize(array(
			'filters'     => $this->filters,
			'plugins'     => $this->pluginsConfig,
			'rootContext' => $this->rootContext,
			'tags'        => $this->tagsConfig,
			'urlConfig'   => $this->urlConfig
		));
	}

	/**
	* 
	*
	* @return void
	*/
	public function unserialize($data)
	{
		$this->__construct(unserialize($data));
	}

	/**
	* 
	*
	* @return void
	*/
	public function registerVar($name, $value)
	{
		$this->registeredVars[$name] = $value;
	}

	/**
	* Return the message log
	*
	* @return array 2D array, first dimension is the message type: "debug", "warning" or "error"
	*/
	public function getLog()
	{
		return $this->log;
	}

	/**
	* Reset this instance's properties
	*
	* @param  string New text to be parsed
	* @return void
	*/
	public function reset($text)
	{
		$this->output  = '';
		$this->text    = $text;
		$this->textLen = strlen($text);

		$this->log = array();
		$this->unprocessedTags = array();
		$this->openTags        = array();
		$this->openStartTags   = array();
		$this->cntOpen         = array();
		$this->cntTotal        = array();

		unset($this->currentTag, $this->currentAttribute);
	}

	/**
	* Parse given text, return the default (XML) representation
	*
	* @param  string $text Text to parse
	* @return string       XML representation
	*/
	public function parse($text)
	{
		$this->reset($text);

		// Capture all tags
		$this->executePluginParsers();

		// Sort them by position and precedence
		$this->sortTags();

		// Process each tag in order, building the output string
		$this->processTags();

		return $this->output;
	}

	/**
	* Add a message to the error log
	*
	* @param  string $type  Message type: debug, warning or error
	* @param  array  $entry Log info
	* @return void
	*/
	public function log($type, array $entry)
	{
		if (isset($this->currentTag))
		{
			$entry['tagName'] = $this->currentTag['name'];
			$entry['pluginName'] = $this->currentTag['pluginName'];

			if (isset($this->currentAttribute))
			{
				$entry['attrName'] = $this->currentAttribute;
			}

			if (!isset($entry['pos']))
			{
				$entry['pos'] = $this->currentTag['pos'];
				$entry['len'] = $this->currentTag['len'];
			}
		}

		$this->logs[$type][] = $entry;
	}

	/**
	* Execute a plugin's regexps and return the result
	*
	* Takes care of regexpLimit/regexpAction
	*
	* @param  string $pluginName
	* @return mixed              An array of matches, a 2D array of matches, FALSE if no regexp
	*                            produced any matches or NULL if there's no regexp for this plugin
	*/
	protected function executePluginRegexp($pluginName)
	{
		$pluginConfig = $this->pluginsConfig[$pluginName];

		// Some plugins have several regexps in an array, others have a single regexp as a string.
		// We convert the latter to an array so that we can iterate over it.
		$isArray = is_array($pluginConfig['regexp']);
		$regexps = ($isArray) ? $pluginConfig['regexp'] : array($pluginConfig['regexp']);

		/**
		* @var bool If true, skip the rest of the regexps
		*/
		$skip = false;

		$matches = array();
		$cnt = 0;

		foreach ($regexps as $k => $regexp)
		{
			$matches[$k] = array();

			if ($skip)
			{
				continue;
			}

			$_cnt = preg_match_all(
				$regexp,
				$this->text,
				$matches[$k],
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			if (!$_cnt)
			{
				continue;
			}

			$cnt += $_cnt;

			if ($cnt > $pluginConfig['regexpLimit'])
			{
				if ($pluginConfig['regexpLimitAction'] === 'abort')
				{
					throw new RuntimeException($pluginName . ' limit exceeded');
				}
				else
				{
					$limit   = $pluginConfig['regexpLimit'] + $_cnt - $cnt;
					$msgType = ($pluginConfig['regexpLimitAction'] === 'ignore')
							 ? 'debug'
							 : 'warning';

					$matches[$k] = array_slice($matches[$k], 0, $limit);

					$this->log($msgType, array(
						'msg' => '%1$s limit exceeded. Only the first %2$s matches will be processed',
						'params' => array($pluginName, $pluginConfig['regexpLimit'])
					));

					$skip = true;
				}
			}
		}

		if (!$cnt)
		{
			return false;
		}

		return ($isArray)
		     ? $matches
		     : $matches[0];
	}

	/**
	* Return a cached instance of a PluginParser
	*
	* @param  string $pluginName
	* @return PluginParser
	*/
	protected function getPluginParser($pluginName)
	{
		// Cache a new instance if there isn't one already
		if (!isset($this->pluginParsers[$pluginName]))
		{
			$pluginConfig = $this->pluginsConfig[$pluginName];

			$className = (isset($pluginConfig['className']))
			           ? $pluginConfig['className']
			           : __NAMESPACE__ . '\\Plugins\\' . $pluginName . '\\Parser';

			$this->pluginParsers[$pluginName] = new $className($this, $pluginConfig);
		}

		return $this->pluginParsers[$pluginName];
	}

	/**
	* Execute all the plugins and store their tags
	*
	* @return void
	*/
	protected function executePluginParsers()
	{
		foreach ($this->pluginsConfig as $pluginName => $pluginConfig)
		{
			$matches = array();

			if (isset($pluginConfig['regexp']))
			{
				$matches = $this->executePluginRegexp($pluginName);

				if ($matches === false)
				{
					continue;
				}
			}

			$tags = $this->getPluginParser($pluginName)->getTags($this->text, $matches);

			foreach ($tags as $tag)
			{
				$this->unprocessedTags[] = $tag;
			}
		}
	}
}