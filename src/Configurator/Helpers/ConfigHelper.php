<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use ReflectionMethod;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Collections\FilterCollection;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;

abstract class ConfigHelper
{
	/**
	* Convert a structure to a (possibly multidimensional) array
	*
	* @param  mixed $value
	* @return array
	*/
	public static function toArray($value)
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

			if ($v === array())
			{
				// We don't record empty structures
				continue;
			}

			$array[$k] = $v;
		}

		return $array;
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
			self::replaceFilters(
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
					self::replaceFilters(
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
	protected static function replaceFilters(array &$config, FilterCollection $customFilters, $callbackGenerator)
	{
		if (!isset($config['filterChain']))
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
	* Replace built-in and custom filters in a tag's config
	*
	* @param  array            &$tagConfig     The tag's config
	* @param  FilterCollection  $customFilters Collection of ProgrammableCallback instances
	* @return void
	*/
	protected static function replaceTagFilters(array &$tagConfig, FilterCollection $customFilters)
	{
		if (!isset($tagConfig['filterChain']))
		{
			return;
		}

		foreach ($tagConfig['filterChain'] as &$filter)
		{
			if (is_string($filter['callback']) && $filter['callback'][0] === '#')
			{
				$filterName = substr($filter['callback'], 1);

				if (isset($customFilters[$filterName]))
				{
					// Clone the custom filter so we don't alter the original
					$customFilter = clone $customFilters[$filterName];

					if (!empty($filter['vars']))
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
					$className  = 's9e\\TextFormatter\\Parser';
					$methodName = $filterName;

					if (!method_exists($className, $methodName))
					{
						throw new RuntimeException("Unknown filter '#" . $filterName . "'");
					}

					// Store the built-in filter as a string
					$filter = $methodName;
				}
			}

			// We don't need those anymore
			if (isset($filter['vars']))
			{
				unset($filter['vars']);
			}
		}
		unset($filter);
	}
}