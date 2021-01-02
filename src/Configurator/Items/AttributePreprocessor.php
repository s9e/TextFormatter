<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\Regexp;

class AttributePreprocessor extends Regexp
{
	/**
	* Return all the attributes created by the preprocessor along with the regexp that matches them
	*
	* @return array Array of [attribute name => regexp]
	*/
	public function getAttributes()
	{
		return $this->getNamedCaptures();
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