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
	protected $log = array();

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
		$this->log['debug'][] = array($msg, $context);
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
		$this->log['err'][] = array($msg, $context);
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
		$this->log['info'][] = array($msg, $context);
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
		$this->log['warn'][] = array($msg, $context);
	}
}