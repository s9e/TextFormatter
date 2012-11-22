<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

class Parser
{
	use PluginsHandling;

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* Constructor
	*/
	public function __construct(array $config)
	{
		$this->logger        = new Logger;
		$this->pluginsConfig = $config['plugins'];
		$this->tagsConfig    = $config['tags'];
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
	}

	/**
	* Reset the parser for a new parsing
	*
	* @return void
	*/
	public function reset($text)
	{
		$this->tagStack = array();
		$this->logger->clear();
		$this->text = $text;
	}
}