<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use InvalidArgumentException;

class Attribute
{
	/**
	* @param array $options This attribute's options
	*/
	public function __construct(array $options = array())
	{
		$this->setOptions($options);
	}

	/**
	* Return whether a string is a valid attribute name
	*
	* @param  string $name
	* @return bool
	*/
	static public function isValidName($name)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9\\-]*$#Di', $name);
	}

	/**
	* Validate and normalize an attribute name
	*
	* @param  string $name Original attribute name
	* @return string       Normalized attribute name, in lowercase
	*/
	static public function normalizeName($name)
	{
		if (!self::isValidName($name))
		{
			throw new InvalidArgumentException ("Invalid attribute name '" . $name . "'");
		}

		return strtolower($name);
	}

	/**
	* Set several options
	*
	* @param array  $options
	*/
	public function setOptions(array $options)
	{
		foreach ($options as $optionName => $optionValue)
		{
			$this->setOption($optionName, $optionValue);
		}
	}

	/**
	* Set a single option
	*
	* @param string $optionName
	* @param mixed  $optionValue
	*/
	public function setOption($optionName, $optionValue)
	{
		$attrConf =& $this->tags[$tagName]['attrs'][$attrName];

		switch ($optionName)
		{
			case 'filterChain':
				$this->setFilterChain($optionValue);
				return;

			default:
				if (isset($this->defaultAttributeOptions[$optionName]))
				{
					/**
					* Preserve the PHP type of that option, if applicable
					*/
					settype($optionValue, gettype($this->defaultAttributeOptions[$optionName]));
				}

				$attrConf[$optionName] = $optionValue;
		}
	}

	/**
	* @param array $filterChain
	*/
	public function setFilterChain(array $filterChain)
	{
		$this->clearFilterChain();

		foreach ($filterChain as $filter)
		{
			$this->appendFilter($filter);
		}
	}























	/**
	* Return all the options of a tag's attribute
	*
	* @param  string $tagName
	* @param  string $attrName
	* @return array
	*/
	public function getAttributeOptions($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);

		return $this->tags[$tagName]['attrs'][$attrName];
	}

	/**
	* Return the value of an option in a tag's attribute config
	*
	* @param  string $tagName
	* @param  string $attrName
	* @param  string $optionName
	* @return mixed
	*/
	public function getAttributeOption($tagName, $attrName, $optionName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);

		return $this->tags[$tagName]['attrs'][$attrName][$optionName];
	}

	/**
	* Remove an attribute from a tag
	*
	* @param string $tagName
	* @param string $attrName
	*/
	public function removeAttribute($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);
		unset($this->tags[$tagName]['attrs'][$attrName]);
	}

	/**
	* Return whether a tag's attribute exists
	*
	* @param  string $tagName
	* @param  string $attrName
	* @return bool
	*/
	public function attributeExists($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		return isset($this->tags[$tagName]['attrs'][$attrName]);
	}

	/**
	* Remove all filters from an attribute's filter chain
	*
	* @param string $tagName
	* @param string $attrName
	*/
	public function clearAttributeFilterChain($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);

		$this->tags[$tagName]['attrs'][$attrName]['filterChain'] = array();
	}

	/**
	* Append a filter to an attribute's filter chain
	*
	* @param string          $tagName
	* @param string          $attrName
	* @param string|Callback $callback
	*/
	public function appendAttributeFilter($tagName, $attrName, $callback)
	{
		$this->addAttributeFilter('array_push', $tagName, $attrName, $callback);
	}

	/**
	* prepend a filter to an attribute's filter chain
	*
	* @param string          $tagName
	* @param string          $attrName
	* @param string|Callback $callback
	*/
	public function prependAttributeFilter($tagName, $attrName, $callback)
	{
		$this->addAttributeFilter('array_unshift', $tagName, $attrName, $callback);
	}

	/**
	* Append a filter to an attribute's filter chain
	*
	* @param string          $func     Either "array_push" or "array_unshift"
	* @param string          $tagName
	* @param string|Callback $callback
	*/
	protected function addAttributeFilter($func, $tagName, $attrName, $callback)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);
		$callback = $this->normalizeCallback($callback);

		$func($this->tags[$tagName]['attrs'][$attrName]['filterChain'], $callback);
	}
}