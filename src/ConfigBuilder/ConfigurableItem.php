<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use InvalidArgumentException;

class ConfigurableItem
{
	public function __get($optionName)
	{
		if (!property_exists($this, $optionName))
		{
			throw new InvalidArgumentException("Option '" . $optionName . "' does not exist");
		}

		return $this->$optionName;
	}

	public function __set($optionName, $optionValue)
	{
		$methodName = 'set' . ucfirst($optionName);

		// Look for a setter, e.g. setDefaultChildRule()
		if (method_exists($this, $methodName))
		{
			return $this->$methodName($optionValue);
		}

		// If the property already exists, preserve its type
		if (isset($this->$optionName))
		{
			// If this is a Collection, we clear its content then add every item in order
			if ($this->$optionName instanceof Collection)
			{
				$this->$optionName->clear();

				foreach ($optionValue as $itemName => $item)
				{
					$this->$optionName->add($itemName, $item);
				}

				return;
			}

			settype($optionValue, gettype($this->$optionName));
		}

		$this->$optionName = $optionValue;
	}
}