<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

class JSParserGenerator
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	/**
	* 
	*
	* @return void
	*/
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;
	}

	/**
	* 
	*
	* @return void
	*/
	public function get(array $options = array())
	{
		$options += array(
			'compression' => 'none'
		);
	}
}