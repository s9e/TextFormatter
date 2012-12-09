<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait FilterProcessing
{
	/**
	* 
	*
	* @return void
	*/
	protected function executeAttributePreprocessors()
	{
	}

	/**
	* Filter the attributes of current tag
	*
	* @return bool Whether the whole attribute set is valid
	*/
	public static function filterAttributes(Tag $tag, array $tagConfig)
	{
		// First, remove invalid attributes
		foreach ($tag->getAttributes() as $attrName => $attrValue)
		{
			// Test whether this attribute exists
			if (!isset($tagConfig['attributes'][$attrName]))
			{
				$tag->removeAttribute($attrName);
				continue;
			}

			$attrConfig = $tagConfig['attributes'][$attrName];

			// Test whether this attribute has a filterChain
			if (!isset($attrConfig['filterChain']))
			{
				continue;
			}

			foreach ($attrConfig['filterChain'] as $filter)
			{
				$attrValue = $this->executeAttributeFilter(
					$filter,
					array(
						'attrName'  => $attrName,
						'attrValue' => $attrValue
					)
				);

				if ($attrValue === false)
				{
					$tag->removeAttribute($attrName);
					break;
				}
			}
		}

		// Iterate over the attribute definitions to handle missing attributes
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
		{
			// Test whether this attribute is missing
			if (!$tag->hasAttribute($attrName))
			{
				if (isset($attrConfig['defaultValue']))
				{
					// Use the attribute's default value
					$tag->setAttribute($attrName, $attrConfig['defaultValue']);
				}
				elseif (!empty($attrConfig['isRequired']))
				{
					// This attribute is missing, has no default value and is required, which means
					// its tag is invalid
					return false;
				}
			}
		}

		return true;
	}

	/**
	* Filter current tag
	*
	* @return bool Whether current tag is valid
	*/
	protected function filterTag()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag->getName()];

		if (isset($tagConfig['filterChain']))
		{
			foreach ($tagConfig['filterChain'] as $filter)
			{
				if (!$this->executeTagFilter($filter, array('tag' => $this->currentTag)))
				{
					return false;
				}
			}
			return true;
		}
	}

	/**
	* 
	*
	* @return bool
	*/
	protected function executeFilterChain(array $filterChain)
	{
		foreach ($filterChain as $filter)
		{
			// TODO: built-in filters, e.g. #int or #filterAttributes -- perhaps use a FilterLocator
			//       Also, how to reinject the return value into the filter vars while filtering
			//       attributes
			$value = $this->executeFilter($filter);

			if ($value === false)
			{
				break;
			}
		}
	}

	/**
	* 
	*
	* @return mixed
	*/
	protected function executeAttributeFilter(array $filter, array $vars)
	{
		// Replace built-in filters with an actual callback
		if (is_string($filter) && $filter[0] === '#')
		{
			// TODO
			$filter = array();
		}

		return $this->executeFilter($filter, $vars);
	}

	/**
	* 
	*
	* @return mixed
	*/
	protected function executeTagFilter(array $filter, array $vars)
	{
		// Replace built-in filters with an actual callback
		if (is_string($filter) && $filter[0] === '#')
		{
			// Replace "#filterAttributes" with [$this,'filterAttributes']
			$filter = array($this, substr($filter, 1));
		}

		return $this->executeFilter($filter, $vars);
	}

	/**
	* 
	*
	* @return mixed
	*/
	protected function executeFilter(array $filter, array $vars)
	{
		$callback = $filter['callback'];
		$params   = (isset($filter['params'])) ? $filter['params'] : array();

		$args = array();
		foreach ($params as $k => $v)
		{
			if (is_numeric($k))
			{
				// Literal value
				$args[] = $v;
			}
			elseif (isset($vars[$k]))
			{
				//
				$args[] = $vars[$k];
			}
			elseif (isset($this->registeredVars[$k]))
			{
				$args[] = $this->registeredVars[$k];
			}
			else
			{
				$this->logger->err('Unknown callback parameter', array('paramName' => $k));

				return false;
			}
		}

		return call_user_func_array($callback, $args);
	}

	/**
	* Set a variable's value for use in filters
	*
	* @param  string $name  Variable's name
	* @param  mixed  $value Value
	* @return void
	*/
	public function registerVar($name, $value)
	{
		$this->registeredVars[$name] = $value;
	}
}