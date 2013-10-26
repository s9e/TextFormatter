<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait FilterProcessing
{
	/**
	* Execute all the attribute preprocessors of given tag
	*
	* @private
	*
	* @param  Tag   $tag       Source tag
	* @param  array $tagConfig Tag's config
	* @return bool             Unconditionally TRUE
	*/
	public static function executeAttributePreprocessors(Tag $tag, array $tagConfig)
	{
		if (!empty($tagConfig['attributePreprocessors']))
		{
			foreach ($tagConfig['attributePreprocessors'] as list($attrName, $regexp))
			{
				if (!$tag->hasAttribute($attrName))
				{
					continue;
				}

				$attrValue = $tag->getAttribute($attrName);

				// If the regexp matches, we add the captured attributes
				if (preg_match($regexp, $attrValue, $m))
				{
					// Set the target attributes
					foreach ($m as $targetName => $targetValue)
					{
						// Skip numeric captures
						if (is_numeric($targetName))
						{
							continue;
						}

						// Attribute preprocessors cannot overwrite other attributes but they can
						// overwrite themselves
						if ($targetName === $attrName || !$tag->hasAttribute($targetName))
						{
							$tag->setAttribute($targetName, $targetValue);
						}
					}
				}
			}
		}

		return true;
	}

	/**
	* Execute a filter
	*
	* @see s9e\TextFormatter\Configurator\Items\ProgrammableCallback
	*
	* @param  array $filter Programmed callback
	* @param  array $vars   Variables to be used when executing the callback
	* @return mixed         Whatever the callback returns
	*/
	protected static function executeFilter(array $filter, array $vars)
	{
		$callback = $filter['callback'];
		$params   = (isset($filter['params'])) ? $filter['params'] : [];

		$args = [];
		foreach ($params as $k => $v)
		{
			if (is_numeric($k))
			{
				// By-value param
				$args[] = $v;
			}
			elseif (isset($vars[$k]))
			{
				// By-name param using a supplied var
				$args[] = $vars[$k];
			}
			elseif (isset($vars['registeredVars'][$k]))
			{
				// By-name param using a registered var
				$args[] = $vars['registeredVars'][$k];
			}
			else
			{
				// Unknown param
				$args[] = null;
			}
		}

		return call_user_func_array($callback, $args);
	}

	/**
	* Filter the attributes of given tag
	*
	* @private
	*
	* @param  Tag    $tag            Tag being checked
	* @param  array  $tagConfig      Tag's config
	* @param  array  $registeredVars Array of registered vars for use in attribute filters
	* @param  Logger $logger         This parser's Logger instance
	* @return bool                   Whether the whole attribute set is valid
	*/
	public static function filterAttributes(Tag $tag, array $tagConfig, array $registeredVars, Logger $logger)
	{
		if (empty($tagConfig['attributes']))
		{
			$tag->setAttributes([]);

			return true;
		}

		// Generate values for attributes with a generator set
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
		{
			if (isset($attrConfig['generator']))
			{
				$tag->setAttribute(
					$attrName,
					self::executeFilter(
						$attrConfig['generator'],
						[
							'attrName'       => $attrName,
							'logger'         => $logger,
							'registeredVars' => $registeredVars
						]
					)
				);
			}
		}

		// Filter and remove invalid attributes
		foreach ($tag->getAttributes() as $attrName => $attrValue)
		{
			// Test whether this attribute exists and remove it if it doesn't
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

			// Record the name of the attribute being filtered into the logger
			$logger->setAttribute($attrName);

			foreach ($attrConfig['filterChain'] as $filter)
			{
				$attrValue = self::executeFilter(
					$filter,
					[
						'attrName'       => $attrName,
						'attrValue'      => $attrValue,
						'logger'         => $logger,
						'registeredVars' => $registeredVars
					]
				);

				if ($attrValue === false)
				{
					$tag->removeAttribute($attrName);
					break;
				}
			}

			// Update the attribute value if it's valid
			if ($attrValue !== false)
			{
				$tag->setAttribute($attrName, $attrValue);
			}

			// Remove the attribute's name from the logger
			$logger->unsetAttribute();
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
				elseif (!empty($attrConfig['required']))
				{
					// This attribute is missing, has no default value and is required, which means
					// the attribute set is invalid
					return false;
				}
			}
		}

		return true;
	}

	/**
	* Execute given tag's filterChain
	*
	* @param  Tag  $tag Tag to filter
	* @return bool      Whether the tag is valid
	*/
	protected function filterTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];
		$isValid   = true;

		if (!empty($tagConfig['filterChain']))
		{
			// Record the tag being processed into the logger it can be added to the context of
			// messages logged during the execution
			$this->logger->setTag($tag);

			// Prepare the variables that are accessible to filters
			$vars = [
				'logger'         => $this->logger,
				'openTags'       => $this->openTags,
				'parser'         => $this,
				'registeredVars' => $this->registeredVars,
				'tag'            => $tag,
				'tagConfig'      => $tagConfig
			];

			foreach ($tagConfig['filterChain'] as $filter)
			{
				if (!self::executeFilter($filter, $vars))
				{
					$isValid = false;
					break;
				}
			}

			// Remove the tag from the logger
			$this->logger->unsetTag();
		}

		return $isValid;
	}

	/**
	* Get all registered vars
	*
	* @return array
	*/
	public function getRegisteredVars()
	{
		return $this->registeredVars;
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