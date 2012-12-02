<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

use s9e\TextFormatter\Parser;

class Logger
{
	/**
	* @var array Log entries
	*/
	protected log = array();

	/**
	* @var Parser
	*/
	protected $parser;

	/**
	* Constructor
	*
	* @param  Parser $parser
	* @return void
	*/
	public function __construct(Parser $parser)
	{
		$this->parser = $parser;
	}

	/**
	* Add a log entry
	*
	* @param  string $type
	* @param  string $msg
	* @param  array  $context
	* @return void
	*/
	protected function add($type, $msg, array $context)
	{
		if (!isset($context['tag']))
		{
			$tag = $this->parser->getCurrentTag();

			if ($tag)
			{
				$context['tag'] = $tag;
			}
		}

		$this->log[$type][] = array($msg, $context);
	}

	/**
	* Clear the log
	*
	* @return void
	*/
	public function clear()
	{
		$this->log = array();
	}

	/**
	* Add an "debug" type log entry
	*
	* @param  string $msg     Log message
	* @param  array  $context
	* @return void
	*/
	public function debug($msg, array $context = array())
	{
		$this->add('debug', $context);
	}

	/**
	* Add an "err" type log entry
	*
	* @param  string $msg     Log message
	* @param  array  $context
	* @return void
	*/
	public function err($msg, array $context = array())
	{
		$this->add('err', $context);
	}

	/**
	* Add an "info" type log entry
	*
	* @param  string $msg     Log message
	* @param  array  $context
	* @return void
	*/
	public function info($msg, array $context = array())
	{
		$this->add('info', $context);
	}

	/**
	* Add an "warn" type log entry
	*
	* @param  string $msg     Log message
	* @param  array  $context
	* @return void
	*/
	public function warn($msg, array $context = array())
	{
		$this->add('warn', $context);
	}
}