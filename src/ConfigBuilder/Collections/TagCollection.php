<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use s9e\TextFormatter\ConfigBuilder\Validators\TagName;

class TagCollection extends FactoryCollection
{
	public function isValidName($name)
	{
		return TagName::isValid($name);
	}

	public function normalizeName($name)
	{
		return TagName::normalize($name);
	}

	protected function getItemClass()
	{
		return 's9e\\TextFormatter\\ConfigBuilder\\Items\\Tag';
	}
}