<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use s9e\TextFormatter\RegexpMaster;

class AttributeParser
{
	/**
	* @param string $regexp
	*/
	public function __construct($regexp)
	{
		$this->regexp = $regexp;
	}
}