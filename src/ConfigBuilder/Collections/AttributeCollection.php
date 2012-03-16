<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

class AttributeCollection extends FactoryCollection
{
	public function isValidName($name)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9\\-]*$#Di', $name);
	}

	public function normalizeName($name)
	{
		return strtolower($name);
	}

	protected function getItemClass()
	{
		return 's9e\\TextFormatter\\ConfigBuilder\\Items\\Attribute';
	}
}