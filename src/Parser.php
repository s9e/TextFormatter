<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

class Parser
{
	/**
	* Constructor
	*/
	public function __construct()
	{
	}

	/**
	* Parse a text
	*
	* @param  string $text Text to parse
	* @return string       XML representation
	*/
	public function parse($text)
	{
		$this->unprocessedTags = $tags->get();

		while (!empty($this->unprocessedTags))
		{
			$this->processTag();
		}
	}
}