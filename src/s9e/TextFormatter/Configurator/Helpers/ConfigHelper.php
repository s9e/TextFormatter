<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use Exception;
use ReflectionMethod;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Collections\FilterCollection;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Code;

abstract class ConfigHelper
{
	/**
	* Recursively filter a config array to replace variants with the desired value
	*
	* @param  array|Traversable &$config  Config array
	* @param  string             $variant Preferred variant
	* @return void
	*/
	public static function filterVariants(&$config, $variant = null)
	{
		foreach ($config as $k => &$v)
		{
			// Use while instead of if to handle recursive variants. This is not supposed to happen
			// though
			while ($v instanceof Variant)
			{
				$v = $v->get($variant);

				// A null value indicates that the value is not supposed to exist for given variant.
				// This is different from having no specific value for given variant
				if ($v === null)
				{
					unset($config[$k]);
					continue 2;
				}
			}

			if (is_array($v) || $v instanceof Traversable)
			{
				self::filterVariants($v, $variant);
			}
		}
	}

	/**
	* Generate a quickMatch string from a list of strings
	*
	* This is basically a LCS implementation, tuned for small strings and fast failure
	*
	* @param  array $strings Array of strings
	* @return mixed          quickMatch string, or FALSE if none could be generated
	*/
	public static function generateQuickMatchFromList(array $strings)
	{
		foreach ($strings as $string)
		{
			$stringLen  = strlen($string);
			$substrings = array();

			for ($len = $stringLen; $len; --$len)
			{
				$pos = $stringLen - $len;

				do
				{
					$substrings[substr($string, $pos, $len)] = 1;
				}
				while (--$pos >= 0);
			}

			if (isset($goodStrings))
			{
				$goodStrings = array_intersect_key($goodStrings, $substrings);

				if (empty($goodStrings))
				{
					break;
				}
			}
			else
			{
				$goodStrings = $substrings;
			}
		}

		if (empty($goodStrings))
		{
			return false;
		}

		// The strings are stored by length descending, so we return the first in the list
		return strval(key($goodStrings));
	}

	/**
	* Optimize the size of a deep array by deduplicating identical structures
	*
	* This method is meant to be used on a config array which is only read and never modified
	*
	* @param  array &$config
	* @param  array &$cache
	* @return array
	*/
	public static function optimizeArray(array &$config, array &$cache = array())
	{
		foreach ($config as $k => &$v)
		{
			if (!is_array($v))
			{
				continue;
			}

			// Iterate over the cache to look for a matching structure
			foreach ($cache as &$cachedArray)
			{
				if ($cachedArray == $v)
				{
					// Replace the entry in $config with a reference to the cached value
					$config[$k] =& $cachedArray;

					// Skip to the next element
					continue 2;
				}
			}
			unset($cachedArray);

			// Record this value in the cache
			$cache[] =& $v;

			// Dig deeper into this array
			self::optimizeArray($v, $cache);
		}
		unset($v);
	}

	/**
	* Replace built-in filters and custom filters in an array of tags
	*
	* - Custom filters are replaced by the ProgrammableCallback found in $customFilters
	*
	* - Built-in attribute filters are replaced by an actual callback to the corresponding static
	*   method in s9e\TextFormatter\Parser\BuiltInFilters
	*
	* - Built-in attribute filters are replaced by an actual callback to the corresponding static
	*   method in s9e\TextFormatter\Parser
	*
	* @param  array            &$tagsConfig
	* @param  FilterCollection  $customFilters
	* @return void
	*/
	public static function replaceBuiltInFilters(array &$tagsConfig, FilterCollection $customFilters)
	{
		foreach ($tagsConfig as &$tagConfig)
		{
			self::replaceItemFilters(
				$tagConfig,
				$customFilters,
				function ($filterName)
				{
					return 's9e\\TextFormatter\\Parser::' . $filterName;
				}
			);

			if (isset($tagConfig['attributes']))
			{
				foreach ($tagConfig['attributes'] as &$attrConfig)
				{
					self::replaceItemFilters(
						$attrConfig,
						$customFilters,
						function ($filterName)
						{
							return 's9e\\TextFormatter\\Parser\\BuiltInFilters::filter' . ucfirst($filterName);
						}
					);
				}
				unset($attrConfig);
			}
		}
		unset($tagConfig);
	}

	/**
	* Replace built-in and custom filters in an attribute's or a tag's config
	*
	* @param  array            &$config            The item's config
	* @param  FilterCollection  $customFilters     Collection of ProgrammableCallback instances
	* @param  callback          $callbackGenerator Function that generates the name of the callback
	*                                              for a built-in filter
	* @return void
	*/
	protected static function replaceItemFilters(array &$config, FilterCollection $customFilters, $callbackGenerator)
	{
		if (empty($config['filterChain']))
		{
			return;
		}

		foreach ($config['filterChain'] as &$filter)
		{
			if (!is_string($filter['callback']) || $filter['callback'][0] !== '#')
			{
				continue;
			}

			// Get the name of the filter, e.g. "#foo" becomes "foo"
			$filterName = substr($filter['callback'], 1);

			if (isset($customFilters[$filterName]))
			{
				// Clone the custom filter so we don't alter the original
				$customFilter = clone $customFilters[$filterName];

				if (isset($filter['vars']))
				{
					// Add this filter's vars to the custom filter's
					$customFilter->setVars(
						$customFilter->getVars() + $filter['vars']
					);
				}

				// Replace this filter with the custom filter
				$filter = $customFilter->asConfig();
			}
			else
			{
				// Generate the name of the callback for this built-in filter
				$callback = $callbackGenerator($filterName);

				// Ensure that it really exists
				if (!is_callable($callback))
				{
					throw new RuntimeException("Unknown filter '#" . $filterName . "'");
				}

				// Create a new ProgrammableCallback based on this filter's reflection
				$builtInFilter = new ProgrammableCallback($callback);

				// Grab the class/method names from the callback
				list($className, $methodName) = explode('::', $callback);

				// Iterate over parameters and assign them by name to our programmable callback
				$reflection = new ReflectionMethod($className, $methodName);
				foreach ($reflection->getParameters() as $parameter)
				{
					$builtInFilter->addParameterByName($parameter->getName());
				}

				// Copy the vars that were set for this filter
				if (isset($filter['vars']))
				{
					$builtInFilter->setVars($filter['vars']);
				}

				// Replace this filter with the correctly programmed callback
				$filter = $builtInFilter->asConfig();
			}
		}
		unset($filter);
	}

	/**
	* Convert a structure to a (possibly multidimensional) array
	*
	* @param  mixed $value
	* @param  bool  $keepEmpty Whether to keep empty arrays instead of removing them
	* @return array
	*/
	public static function toArray($value, $keepEmpty = false)
	{
		$array = array();

		foreach ($value as $k => $v)
		{
			if (!isset($v))
			{
				// We don't record NULL values
				continue;
			}

			if ($v instanceof ConfigProvider)
			{
				$v = $v->asConfig();
			}
			elseif ($v instanceof Traversable || is_array($v))
			{
				$v = self::toArray($v);
			}
			elseif (!is_scalar($v))
			{
				$type = (is_object($v))
				      ? 'an instance of ' . get_class($v)
				      : 'a ' . gettype($v);

				throw new RuntimeException('Cannot convert ' . $type . ' to array');
			}

			if (!$keepEmpty && $v === array())
			{
				// We don't record empty structures
				continue;
			}

			$array[$k] = $v;
		}

		return $array;
	}
}