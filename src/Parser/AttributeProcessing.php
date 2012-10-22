<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait AttributeProcessing
{
	/**
	* Filter attributes from current tag
	*
	* Will execute attribute parsers if applicable, then it will filter the attributes, replacing
	* invalid attributes with their default value or returning FALSE if a required attribute is
	* missing or invalid (and with no default value.)
	*
	* @return bool Whether the set of attributes is valid
	*/
	protected function filterAttributes()
	{
		// Handle parsable attributes
		$this->parseAttributes();

		// Save the current attribute values then reset current tag's attributes
		$attrValues = $this->currentTag['attrs'];
		$this->currentTag['attrs'] = array();

		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (empty($tagConfig['attrs']))
		{
			// No attributes defined
			return true;
		}

		foreach ($tagConfig['attrs'] as $attrName => $attrConfig)
		{
			$this->currentAttribute = $attrName;

			// We initialize with an invalid value. If the attribute is missing, we treat it as if
			// it was invalid
			$attrValue = false;

			// If the attribute exists, filter it
			if (isset($attrValues[$attrName]))
			{
				$attrValue = $this->filterAttribute($attrValues[$attrName], $attrConfig);

				if ($attrValue === false)
				{
					// The attribute is invalid
					$this->log('error', array(
						'msg'    => "Invalid attribute '%s'",
						'params' => array($attrName)
					));
				}
			}

			// If the attribute is missing or invalid...
			if ($attrValue === false)
			{
				if (isset($attrConfig['defaultValue']))
				{
					// Use its default value
					$attrValue = $attrConfig['defaultValue'];
				}
				elseif (!empty($attrConfig['required']))
				{
					// No default value and the attribute is required... log it and bail
					$this->log('error', array(
						'msg'    => "Missing attribute '%s'",
						'params' => array($attrName)
					));

					return false;
				}
				else
				{
					// The attribute is invalid but it's not required so we move on to the next
					continue;
				}
			}

			// We have a value for this attribute, we can add it back to the tag
			$this->currentTag['attrs'][$attrName] = $attrValue;
		}

		return true;
	}

	/**
	* Filter an attribute value according to given config
	*
	* @param  mixed $attrValue  Attribute value
	* @param  array $attrConfig Attribute config
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	protected function filterAttribute($attrValue, array $attrConfig)
	{
		if (!empty($attrConfig['filterChain']))
		{
			// Execute each filter of the chain, in order
			foreach ($attrConfig['filterChain'] as $filter)
			{
				// Coalesce the vars associated with this filter, the vars registered with this
				// parser as well as the default vars such as the attribute's value and config
				$vars = (isset($filter['vars'])) ? $filter['vars'] : array();

				foreach ($this->registeredVars as $k => $v)
				{
					$vars[$k] = $v;
				}

				$vars['attrValue']  = $attrValue;
				$vars['attrConfig'] = $attrConfig;
				$vars['parser']     = $this;
				$vars['urlConfig']  = $this->urlConfig;

				// Call the filter
				$attrValue = $this->callFilter($filter, $vars);

				// If the attribute is invalid, we break the chain and return FALSE
				if ($attrValue === false)
				{
					return false;
				}
			}
		}

		return $attrValue;
	}

	/**
	* Split parsable attributes and append them to the existing attributes
	*/
	protected function parseAttributes()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (empty($tagConfig['attributeParsers']))
		{
			return;
		}

		$attrs = array();

		foreach ($tagConfig['attributeParsers'] as $attrName => $regexps)
		{
			if (!isset($this->currentTag['attrs'][$attrName]))
			{
				continue;
			}

			foreach ($regexps as $regexp)
			{
				if (preg_match($regexp, $this->currentTag['attrs'][$attrName], $m))
				{
					foreach ($m as $k => $v)
					{
						if (!is_numeric($k))
						{
							$attrs[$k] = $v;
						}
					}

					// The attribute is removed from the current list
					unset($this->currentTag['attrs'][$attrName]);

					// We're done with this attribute
					break;
				}
			}
		}

		/**
		* Append the split attributes to the existing attributes. Values from split attributes won't
		* overwrite existing values
		*/
		$this->currentTag['attrs'] += $attrs;
	}

	//==========================================================================
	// Filters and callbacks handling
	//==========================================================================

	/**
	* Call a filter
	*
	* @param  mixed $filter Either a string representing a built-in filter or an array with at least
	*                       a "callback" key containing a valid callback
	* @params array $vars   Variables to be used as parameters for the callback
	* @return mixed         Callback's return value, or FALSE in case of error
	*/
	protected function callFilter($filter, array $vars)
	{
		// Test whether the filter is a built-in (possible custom) filter
		if (is_string($filter) && $filter[0] === '#')
		{
			if (isset($this->filters[$filter]))
			{
				// This is a custom filter, replace the string with the definition
				$filter = $this->filters[$filter];
			}
			else
			{
				// Use the built-in filter
				$className = __NAMESPACE__ . '\\Filters\\' . ucfirst(substr($filter, 1));

				if (!class_exists($className))
				{
					$this->log('debug', array(
						'msg'    => "Unknown filter '%s'",
						'params' => array($filter)
					));

					return false;
				}

				$filter = $className::getFilter();
			}
		}

		// Prepare the actual callback and its signature
		$callback  = $filter['callback'];
		$signature = (isset($filter['params']))
		           ? $filter['params']
		           : array('attrValue' => null);

		// Parameters to be passed to the callback
		$params = array();

		foreach ($signature as $k => $v)
		{
			if (is_numeric($k))
			{
				$params[] = $v;
			}
			elseif (isset($vars[$k]))
			{
				$params[] = $vars[$k];
			}
			else
			{
				$this->log('error', array(
					'msg'    => "Unknown callback parameter '%s'",
					'params' => array($k)
				));

				return false;
			}
		}

		return call_user_func_array($callback, $params);
	}

	//==========================================================================
	// Built-in filters
	//==========================================================================

	protected function validateId($id)
	{
		return filter_var($id, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '#^[A-Za-z0-9\\-_]+$#D')
		));
	}

	protected function validateSimpletext($text)
	{
		return filter_var($text, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '#^[A-Za-z0-9\\-+.,_ ]+$#D')
		));
	}

	protected function validateEmail($email, array $attrConf)
	{
		$email = filter_var($email, FILTER_VALIDATE_EMAIL);

		if (!$email)
		{
			return false;
		}

		if (!empty($attrConf['forceUrlencode']))
		{
			$email = '%' . implode('%', str_split(bin2hex($email), 2));
		}

		return $email;
	}

	protected function validateInt($int)
	{
		return filter_var($int, FILTER_VALIDATE_INT);
	}

	protected function validateFloat($float)
	{
		return filter_var($float, FILTER_VALIDATE_FLOAT);
	}

	protected function validateUint($uint)
	{
		return filter_var($uint, FILTER_VALIDATE_INT, array(
			'options' => array('min_range' => 0)
		));
	}

	protected function validateRange($number, array $attrConf)
	{
		$number = filter_var($number, FILTER_VALIDATE_INT);

		if ($number === false)
		{
			return false;
		}

		if ($number < $attrConf['min'])
		{
			$this->log('warning', array(
				'msg'    => 'Value outside of range, adjusted up to %d',
				'params' => array($attrConf['min'])
			));
			return $attrConf['min'];
		}

		if ($number > $attrConf['max'])
		{
			$this->log('warning', array(
				'msg'    => 'Value outside of range, adjusted down to %d',
				'params' => array($attrConf['max'])
			));
			return $attrConf['max'];
		}

		return $number;
	}

	protected function validateColor($color)
	{
		return filter_var($color, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '/^(?:#[0-9a-f]{3,6}|[a-z]+)$/Di')
		));
	}

	protected function validateRegexp($attrVal, array $attrConf)
	{
		return (preg_match($attrConf['regexp'], $attrVal, $match))
		     ? $attrVal
		     : false;
	}
}