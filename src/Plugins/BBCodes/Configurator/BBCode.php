<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\AttributeList;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;

/**
* @property AttributeList $contentAttributes List of attributes whose value is to be made the content between the BBCode's tags if it's not explicitly given
* @property string $defaultAttribute Name of the default attribute
* @property bool $forceLookahead Whether the parser should look ahead in the text for an end tag before adding a start tag
* @property AttributeValueCollection $predefinedAttributes Predefined attribute values, can be overwritten by user input
* @property string $tagName Name of the tag used to represent this BBCode in the intermediate representation
*/
class BBCode implements ConfigProvider
{
	/**
	* Magic getter
	*
	* Will return $this->foo if it exists, then $this->getFoo() or will throw an exception if
	* neither exists
	*
	* @param  string $propName
	* @return mixed
	*/
	public function __get($propName)
	{
		$methodName = 'get' . ucfirst($propName);

		// Look for a getter, e.g. getDefaultTemplate()
		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		if (!property_exists($this, $propName))
		{
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		}

		return $this->$propName;
	}

	/**
	* Magic setter
	*
	* Will call $this->setFoo($propValue) if it exists, otherwise it will set $this->foo.
	* If $this->foo is a NormalizedCollection, we do not replace it, instead we clear() it then
	* fill it back up. It will not overwrite an object with a different incompatible object (of a
	* different, non-extending class) and it will throw an exception if the PHP type cannot match
	* without incurring data loss.
	*
	* @param  string $propName
	* @param  mixed  $propValue
	* @return void
	*/
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . ucfirst($propName);

		// Look for a setter, e.g. setDefaultChildRule()
		if (method_exists($this, $methodName))
		{
			$this->$methodName($propValue);

			return;
		}

		// If the property isn't already set, we just create/set it
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;

			return;
		}

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

		// If this property is an object, test whether they are compatible. Otherwise, test if PHP
		// types are compatible
		if (is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
			{
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . get_class($this->$propName) . "' with instance of '" . get_class($propValue) . "'");
			}
		}
		else
		{
			// Test whether the PHP types are compatible
			$oldType = gettype($this->$propName);
			$newType = gettype($propValue);

			// If the property is a boolean, we'll accept "true" and "false" as strings
			if ($oldType === 'boolean')
			{
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = true;
				}
			}

			if ($oldType !== $newType)
			{
				// Test whether the PHP type roundtrip is lossless
				$tmp = $propValue;
				settype($tmp, $oldType);
				settype($tmp, $newType);

				if ($tmp !== $propValue)
				{
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				}

				// Finally, set the new value to the correct type
				settype($propValue, $oldType);
			}
		}

		$this->$propName = $propValue;
	}

	/**
	* Test whether a property is set
	*
	* @param  string $propName
	* @return bool
	*/
	public function __isset($propName)
	{
		$methodName = 'isset' . ucfirst($propName);

		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		return isset($this->$propName);
	}

	/**
	* Unset a property, if the class supports it
	*
	* @param  string $propName
	* @return void
	*/
	public function __unset($propName)
	{
		$methodName = 'unset' . ucfirst($propName);

		if (method_exists($this, $methodName))
		{
			$this->$methodName();

			return;
		}

		if (!isset($this->$propName))
		{
			return;
		}

		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();

			return;
		}

		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}

	/**
	* @var AttributeList List of attributes whose value is to be made the content between the
	*                    BBCode's tags if it's not explicitly given
	*/
	protected $contentAttributes;

	/**
	* @var string Name of the default attribute
	*/
	protected $defaultAttribute;

	/**
	* @var bool Whether the parser should look ahead in the text for an end tag before adding a
	*           start tag
	*/
	protected $forceLookahead = false;

	/**
	* @var AttributeValueCollection Predefined attribute values, can be overwritten by user input
	*/
	protected $predefinedAttributes;

	/**
	* @var string Name of the tag used to represent this BBCode in the intermediate representation
	*/
	protected $tagName;

	/**
	* @param array $options This BBCode's options
	*/
	public function __construct(array $options = null)
	{
		$this->contentAttributes    = new AttributeList;
		$this->predefinedAttributes = new AttributeValueCollection;

		if (isset($options))
		{
			foreach ($options as $optionName => $optionValue)
			{
				$this->__set($optionName, $optionValue);
			}
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$config = ConfigHelper::toArray(get_object_vars($this));
		if (!$this->forceLookahead)
		{
			unset($config['forceLookahead']);
		}
		if (isset($config['predefinedAttributes']))
		{
			$config['predefinedAttributes'] = new Dictionary($config['predefinedAttributes']);
		}

		return $config;
	}

	/**
	* Normalize the name of a BBCode
	*
	* Follows the same rules as tag names with one exception: "*" is kept for compatibility with
	* other BBCode engines
	*
	* @param  string $bbcodeName Original name
	* @return string             Normalized name
	*/
	public static function normalizeName($bbcodeName)
	{
		if ($bbcodeName === '*')
		{
			return '*';
		}

		if (!TagName::isValid($bbcodeName))
		{
			throw new InvalidArgumentException("Invalid BBCode name '" . $bbcodeName . "'");
		}

		return TagName::normalize($bbcodeName);
	}

	/**
	* Set the default attribute name for this BBCode
	*
	* @param string $attrName
	*/
	public function setDefaultAttribute($attrName)
	{
		$this->defaultAttribute = AttributeName::normalize($attrName);
	}

	/**
	* Set the tag name that represents this BBCode in the intermediate representation
	*
	* @param string $tagName
	*/
	public function setTagName($tagName)
	{
		$this->tagName = TagName::normalize($tagName);
	}
}