<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
class AttributePreprocessor
{
	protected $regexp;
	public function __construct($regexp)
	{
		if (@\preg_match($regexp, '') === \false)
			throw new InvalidArgumentException('Invalid regular expression ' . \var_export($regexp, \true));
		$this->regexp = $regexp;
	}
	public function getAttributes()
	{
		$attributes = [];
		$regexpInfo = RegexpParser::parse($this->regexp);
		if (\strpos($regexpInfo['modifiers'], 'D') === \false)
			$regexpInfo['modifiers'] .= 'D';
		foreach ($regexpInfo['tokens'] as $token)
		{
			if ($token['type'] !== 'capturingSubpatternStart'
			 || !isset($token['name']))
				continue;
			$attrName = $token['name'];
			if (!isset($attributes[$attrName]))
			{
				$regexp = $regexpInfo['delimiter']
				        . '^(?:' . $token['content'] . ')$'
				        . $regexpInfo['delimiter']
				        . $regexpInfo['modifiers'];
				$attributes[$attrName] = $regexp;
			}
		}
		return $attributes;
	}
	public function getRegexp()
	{
		return $this->regexp;
	}
}