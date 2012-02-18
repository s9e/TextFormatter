<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

/**
* Extends AttributeCollection in order to match its naming convention
*/
class AttributeParserCollection extends AttributeCollection
{
	protected function getItemClass()
	{
		return __NAMESPACE__ . '\\AttributeParser';
	}
}