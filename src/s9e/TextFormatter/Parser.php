<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use Serializable;
use s9e\TextFormatter\Parser\Logger;

class Parser implements Serializable
{
	use Parser\FilterProcessing;
	use Parser\OutputHandling;
	use Parser\PluginsHandling;
	use Parser\RulesHandling;
	use Parser\TagProcessing;
	use Parser\TagStack;

	/**#@+
	* Boolean rules bitfield
	*/
	const RULE_AUTO_CLOSE       =  1;
	const RULE_AUTO_REOPEN      =  2;
	const RULE_IGNORE_TEXT      =  4;
	const RULE_IS_TRANSPARENT   =  8;
	const RULE_NO_BR_CHILD      = 16;
	const RULE_NO_BR_DESCENDANT = 32;
	const RULE_TRIM_WHITESPACE  = 64;
	/**#@-*/

	/**
	* @var Logger This parser's logger
	*/
	protected $logger;

	/**
	* @var array Variables registered for use in filters
	*/
	protected $registeredVars = array();

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* @var integer Length of the text being parsed
	*/
	protected $textLen;

	/**
	* Constructor
	*/
	public function __construct(array $config)
	{
		$this->logger         = new Logger($this);
		$this->pluginsConfig  = $config['plugins'];
		$this->registeredVars = $config['registeredVars'];
		$this->rootContext    = $config['rootContext'];
		$this->tagsConfig     = $config['tags'];
	}

	/**
	* Serializer
	*
	* Rebuilds the config array and returns it serialized
	*
	* @return string
	*/
	public function serialize()
	{
		return serialize(array(
			'plugins'        => $this->pluginsConfig,
			'registeredVars' => $this->registeredVars,
			'rootContext'    => $this->rootContext,
			'tags'           => $this->tagsConfig
		));
	}

	/**
	* Unserializer
	*
	* @param  string $data Serialized data
	* @return void
	*/
	public function unserialize($data)
	{
		$this->__construct(unserialize($data));
	}

	//==========================================================================
	// Public API
	//==========================================================================

	/**
	* Disable a tag
	*
	* @param  string $tagName Name of the tag
	* @return void
	*/
	public function disableTag($tagName)
	{
		if (isset($this->tagsConfig[$tagName]))
		{
			$this->tagsConfig[$tagName]['isDisabled'] = true;
		}
	}

	/**
	* Enable a tag
	*
	* @param  string $tagName Name of the tag
	* @return void
	*/
	public function enableTag($tagName)
	{
		if (isset($this->tagsConfig[$tagName]))
		{
			unset($this->tagsConfig[$tagName]['isDisabled']);
		}
	}

	/**
	* Get this parser's Logger instance
	*
	* @return Logger
	*/
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	* Parse a text
	*
	* @param  string $text Text to parse
	* @return string       XML representation
	*/
	public function parse($text)
	{
		$this->reset($text);
		$this->executePluginParsers();
		$this->sortTags();
		$this->processTags();

		return $this->output;
	}

	/**
	* Reset the parser for a new parsing
	*
	* @param  string $text Text to be parsed
	* @return void
	*/
	protected function reset($text)
	{
		// Normalize CR/CRLF to LF, remove control characters that aren't allowed in XML
		$text = preg_replace('/\\r\\n?/', "\n", $text);
		$text = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]+/S', '', $text);

		$this->context    = $this->rootContext;
		$this->currentFixingCost = 0;
		$this->isRich     = false;
		$this->namespaces = array();
		$this->output     = '';
		$this->text       = $text;
		$this->textLen    = strlen($text);
		$this->tagStack   = array();
	}
}