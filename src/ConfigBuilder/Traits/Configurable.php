<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Traits;

use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\ConfigBuilder\Collections\NormalizedCollection;

/**
* Provides magic __get, __set and __isset implementations
*/
trait Configurable
{
	public function __get($propName)
	{
		if (!property_exists($this, $propName))
		{
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		}

		return $this->$propName;
	}

	public function __set($propName, $propValue)
	{
		$methodName = 'set' . ucfirst($propName);

		// Look for a setter, e.g. setDefaultChildRule()
		if (method_exists($this, $methodName))
		{
			$this->$methodName($propValue);
		}
		else
		{
			// If the property already exists, preserve its type
			if (isset($this->$propName))
			{
				// If we're trying to replace a NormalizedCollection, instead we clear it then
				// iteratively set new values
				if ($this->$propName instanceof NormalizedCollection)
				{
					if (!is_array($propValue)
					 && !($propValue instanceof Traversable))
					{
						throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
					}

					$this->$propName->clear();

					foreach ($propValue as $k => $v)
					{
						$this->$propName->set($k, $v);
					}

					return;
				}

				// Otherwise, we'll just try to match the option's type
				/**
				* @todo perhaps only do that if the cast is lossless, e.g. "1"=>1 but not "1a"=>1
				* @todo don't overwrite an object with another object that does not extend it
				*/
				settype($propValue, gettype($this->$propName));
			}

			$this->$propName = $propValue;
		}
	}

	public function __isset($propName)
	{
		return isset($this->$propName);
	}
}