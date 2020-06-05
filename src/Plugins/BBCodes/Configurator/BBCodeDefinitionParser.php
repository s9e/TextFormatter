<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

// $filterChain.append="strtolower($attrValue)"
// $filterChain.append("strtolower($attrValue)")
// $attributePreprocessors.add("name", /regexp/)

// foo={INT}
// $attributes.add('foo')
// $attributes.get('foo').filterChain.append('#int')
class BBCodeDefinitionParser
{
	/**
	* @var BBCodeDefinitionMatcher
	*/
	public $matcher;

	/**
	* @param  BBCodeDefinitionMatcher
	* @return void
	*/
	public function __construct(BBCodeDefinitionMatcher $matcher = null): void
	{
		if (!isset($matcher))
		{
			$matcher = new BBCodeDefinitionMatcher;
		}
		$this->matcher = $matcher;
	}
}