<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

class TagCollection extends Collection
{
	public function isValidName($name)
	{
		return (bool) preg_match('#^(?:[a-z_][a-z_0-9]*:)?[a-z_][a-z_0-9]*$#Di', $name);
	}

	public function normalizeName($name)
	{
		// Non-namespaced tags are uppercased
		if (strpos($name, ':') === false)
		{
			$name = strtoupper($name);
		}

		return $name;
	}

	protected function getItemClass()
	{
		return __NAMESPACE__ . '\\Tag';
	}
}