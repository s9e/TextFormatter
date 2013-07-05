<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use RuntimeException;
use s9e\TextFormatter\Parser\Logger;

class Parser
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
	const RULE_AUTO_CLOSE        =   1;
	const RULE_AUTO_REOPEN       =   2;
	const RULE_BREAK_PARAGRAPH   =   4;
	const RULE_CREATE_PARAGRAPHS =   8;
	const RULE_IGNORE_TEXT       =  16;
	const RULE_IS_TRANSPARENT    =  32;
	const RULE_NO_BR_CHILD       =  64;
	const RULE_NO_BR_DESCENDANT  = 128;
	const RULE_TRIM_WHITESPACE   = 256;
	/**#@-*/

	/**
	* All the characters that are considered whitespace
	*/
	const WHITESPACE = " \n\t";

	/**
	* @var Logger This parser's logger
	*/
	protected $logger;

	/**
	* @var array Variables registered for use in filters
	*/
	protected $registeredVars = [];

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
	* @var integer Random number generated everytime the parser is reset
	*/
	protected $uid;

	/**
	* Constructor
	*/
	public function __construct(array $config)
	{
		$this->pluginsConfig  = $config['plugins'];
		$this->registeredVars = $config['registeredVars'];
		$this->rootContext    = $config['rootContext'];
		$this->tagsConfig     = $config['tags'];

		$this->__wakeup();
	}

	/**
	* Serializer
	*
	* Returns the properties that need to persist through serialization.
	*
	* NOTE: using __sleep() is preferable to implementing Serializable because it leaves the choice
	* of the serializer to the user (e.g. igbinary)
	*
	* @return array
	*/
	public function __sleep()
	{
		return ['pluginsConfig', 'registeredVars', 'rootContext', 'tagsConfig'];
	}

	/**
	* Unserializer
	*
	* @return void
	*/
	public function __wakeup()
	{
		$this->logger = new Logger($this);
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
		// Reset the parser and save the uid
		$this->reset($text);
		$uid = $this->uid;

		// Do the heavy lifting
		$this->executePluginParsers();
		$this->processTags();

		// Check the uid in case a plugin or a filter reset the parser mid-execution
		if ($this->uid !== $uid)
		{
			throw new RuntimeException('The parser has been reset during execution');
		}

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

		// Initialize the rest
		$this->currentFixingCost = 0;
		$this->isRich     = false;
		$this->namespaces = [];
		$this->output     = '';
		$this->text       = $text;
		$this->textLen    = strlen($text);
		$this->tagStack   = [];
		$this->uid        = mt_rand();

		// NOTE: we mark the tag start as unsorted to ensure it gets sorted at least once before use
		$this->tagStackIsSorted = false;
	}
}