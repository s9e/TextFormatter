<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

class RulesGeneratorList extends NormalizedList
{
	/**
	* Normalize the value to an object
	*
	* @param  mixed  $generator  Either a string, or an instance of a rules generator
	* @return object
	*/
	public function normalizeValue($generator)
	{
		if (is_string($generator))
		{
			$className = 's9e\\TextFormatter\\Configurator\\RulesGenerators\\' . $generator;
			$generator = new $className;
		}

		return $generator;
	}
}