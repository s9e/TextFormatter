/**
* Execute all the attribute preprocessors of given tag
*
* @private
*
* @param  Tag   $tag       Source tag
* @param  array $tagConfig Tag's config
* @return bool             Unconditionally TRUE
*/
function executeAttributePreprocessors(tag, tagConfig)
{
	if (tagConfig.attributePreprocessors))
	{
		foreach ($tagConfig['attributePreprocessors'] as $attrName => $regexps)
		{
			if (!$tag->hasAttribute($attrName))
			{
				continue;
			}

			$attrValue = $tag->getAttribute($attrName);

			foreach ($regexps as $regexp)
			{
				// If the regexp matches, we remove the source attribute then we add the
				// captured attributes
				if (preg_match($regexp, $attrValue, $m))
				{
					$tag->removeAttribute($attrName);

					foreach ($m as $k => $v)
					{
						if (!is_numeric($k) && !$tag->hasAttribute($k))
						{
							$tag->setAttribute($k, $v);
						}
					}

					// We stop processing this attribute after the first match
					break;
				}
			}
		}
	}

	return true;
}

/**
* Filter the attributes of given tag
*
* @private
*
* @param  Tag    $tag            Tag being checked
* @param  array  $tagConfig      Tag's config
* @param  array  $registeredVars Array of registered vars for use in attribute filters
* @return bool                   Whether the whole attribute set is valid
*/
function filterAttributes(tag, tagConfig, registeredVars)
{
	if (!tagConfig.attributes)
	{
		tag.setAttributes({});

		return true;
	}

	// Generate values for attributes with a generator set
	foreach(tagConfig.attributes, function(attrName, attrConfig)
	{
		if (attrConfig.generator)
		{
			tag.setAttribute(
				attrName,
				attrConfig.generator({
					attrName       : attrName,
					registeredVars : registeredVars
				})
			);
		}
	})

	$logger = (isset($registeredVars['logger'])) ? $registeredVars['logger'] : false;

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
		if ($logger)
		{
			$logger->setAttribute($attrName);
		}

		foreach ($attrConfig['filterChain'] as $filter)
		{
			$attrValue = self::executeFilter(
				$filter,
				array(
					'attrName'       => $attrName,
					'attrValue'      => $attrValue,
					'registeredVars' => $registeredVars
				)
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
		if ($logger)
		{
			$logger->unsetAttribute();
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
		$vars = array(
			'registeredVars' => $this->registeredVars,
			'tag'            => $tag,
			'tagConfig'      => $tagConfig
		);

		// Add our logger if there isn't one there already
		if (!isset($vars['registeredVars']['logger']))
		{
			$vars['registeredVars']['logger'] = $this->logger;
		}

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