<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Items;

class AttributePreprocessor
{
	/**
	* @var string
	*/
	protected $regexp;

	/**
	* @todo parse the regexp, reject multiple subpatterns that use the same name
	*
	* @param string $regexp
	*/
	public function __construct($regexp)
	{
		$this->regexp = $regexp;
	}

	/**
	* Return the regexp this preprocessor is based on
	*
	* @return string
	*/
	public function getRegexp()
	{
		return $this->regexp;
	}
}