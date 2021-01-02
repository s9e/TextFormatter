<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use ArrayObject;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;

/**
* This class's sole purpose is to identify arrays that need their keys to be preserved in JavaScript
*/
class Dictionary extends ArrayObject implements FilterableConfigValue
{
	/**
	* {@inheritdoc}
	*/
	public function filterConfig($target)
	{
		$value = $this->getArrayCopy();
		if ($target === 'JS')
		{
			$value = new Dictionary(ConfigHelper::filterConfig($value, $target));
		}

		return $value;
	}
}