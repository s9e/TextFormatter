<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

use InvalidArgumentException;
use s9e\TextFormatter\Parser;

class Logger
{
	/**
	* @var string Name of the attribute being processed
	*/
	protected $attrName;

	/**
	* @var array Log entries in the form [[<type>,<msg>,<context>]]
	*/
	protected $logs = [];

	/**
	* @var Tag Tag being processed
	*/
	protected $tag;

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
		if (!isset($context['attrName']) && isset($this->attrName))
		{
			$context['attrName'] = $this->attrName;
		}

		if (!isset($context['tag']) && isset($this->tag))
		{
			$context['tag'] = $this->tag;
		}

		$this->logs[] = [$type, $msg, $context];
	}

	/**
	* Clear the log
	*
	* @return void
	*/
	public function clear()
	{
		$this->logs = [];
		$this->unsetAttribute();
		$this->unsetTag();
	}

	/**
	* Return the logs
	*
	* @return array
	*/
	public function getLogs()
	{
		return $this->logs;
	}

	/**
	* Record the name of the attribute being processed
	*
	* @param  string $attrName
	* @return void
	*/
	public function setAttribute($attrName)
	{
		$this->attrName = $attrName;
	}

	/**
	* Record the tag being processed
	*
	* @param  Tag  $tag
	* @return void
	*/
	public function setTag(Tag $tag)
	{
		$this->tag = $tag;
	}

	/**
	* Unset the name of the attribute being processed
	*
	* @return void
	*/
	public function unsetAttribute()
	{
		unset($this->attrName);
	}

	/**
	* Unset the tag being processed
	*
	* @return void
	*/
	public function unsetTag()
	{
		unset($this->tag);
	}

	//==========================================================================
	// Log levels
	//==========================================================================

	/**
	* Add a "debug" type log entry
	*
	* @param  string $msg     Log message
	* @param  array  $context
	* @return void
	*/
	public function debug($msg, array $context = [])
	{
		$this->add('debug', $msg, $context);
	}

	/**
	* Add an "err" type log entry
	*
	* @param  string $msg     Log message
	* @param  array  $context
	* @return void
	*/
	public function err($msg, array $context = [])
	{
		$this->add('err', $msg, $context);
	}

	/**
	* Add an "info" type log entry
	*
	* @param  string $msg     Log message
	* @param  array  $context
	* @return void
	*/
	public function info($msg, array $context = [])
	{
		$this->add('info', $msg, $context);
	}

	/**
	* Add a "warn" type log entry
	*
	* @param  string $msg     Log message
	* @param  array  $context
	* @return void
	*/
	public function warn($msg, array $context = [])
	{
		$this->add('warn', $msg, $context);
	}
}