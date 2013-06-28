/**
* Execute all the attribute preprocessors of given tag
*
* @private
*
* @param  {!Tag}     tag       Source tag
* @param  {!Object}  tagConfig Tag's config
* @return {!boolean}           Unconditionally TRUE
*/
function executeAttributePreprocessors(tag, tagConfig)
{
	if (tagConfig.attributePreprocessors)
	{
		tagConfig.attributePreprocessors.forEach(function(attributePreprocessor)
		{
			var attrName = attributePreprocessor[0],
				regexp   = attributePreprocessor[1],
				map      = attributePreprocessor[2];

			if (!tag.hasAttribute(attrName))
			{
				return;
			}

			var m, attrValue = tag.getAttribute(attrName);

			// If the regexp matches, we remove the source attribute then we add the
			// captured attributes
			if (m = regexp.exec(attrValue))
			{
				// Remove the source attribute
				tag.removeAttribute(attrName);

				// Set the target attributes
				map.forEach(function(targetName, k)
				{
					if (!tag.hasAttribute(targetName))
					{
						tag.setAttribute(targetName, m[k]);
					}
				});
			}
		});
	}

	return true;
}

/**
* Filter the attributes of given tag
*
* @private
*
* @param  {!Tag}     tag            Tag being checked
* @param  {!Object}  tagConfig      Tag's config
* @param  {!Object}  registeredVars Vars registered for use in attribute filters
* @param  {!Logger}  logger         This parser's Logger instance
* @return {!boolean}           Whether the whole attribute set is valid
*/
function filterAttributes(tag, tagConfig, registeredVars, logger)
{
	if (!tagConfig.attributes)
	{
		tag.setAttributes({});

		return true;
	}

	// Generate values for attributes with a generator set
	var attrName, attrConfig;
	for (attrName in tagConfig.attributes)
	{
		attrConfig = tagConfig.attributes[attrName];

		if (attrConfig.generator)
		{
			tag.setAttribute(attrName, attrConfig.generator(attrName));
		}
	}

	// Filter and remove invalid attributes
	var attributes = tag.getAttributes();
	for (attrName in attributes)
	{
		var attrValue = attributes[attrName];

		// Test whether this attribute exists and remove it if it doesn't
		if (!tagConfig.attributes[attrName])
		{
			tag.removeAttribute(attrName);
			continue;
		}

		attrConfig = tagConfig.attributes[attrName];

		// Test whether this attribute has a filterChain
		if (!attrConfig.filterChain)
		{
			continue;
		}

		// Record the name of the attribute being filtered into the logger
		logger.setAttribute(attrName);

		for (var i = 0; i < attrConfig.filterChain.length; ++i)
		{
			// NOTE: attrValue is intentionally set as the first argument to facilitate inlining
			attrValue = attrConfig.filterChain[i](attrValue, attrName);

			if (attrValue === false)
			{
				tag.removeAttribute(attrName);
				break;
			}
		}

		// Update the attribute value if it's valid
		if (attrValue !== false)
		{
			tag.setAttribute(attrName, attrValue);
		}

		// Remove the attribute's name from the logger
		logger.unsetAttribute();
	}

	// Iterate over the attribute definitions to handle missing attributes
	for (attrName in tagConfig.attributes)
	{
		attrConfig = tagConfig.attributes[attrName];

		// Test whether this attribute is missing
		if (!tag.hasAttribute(attrName))
		{
			if (attrConfig.defaultValue !== undefined)
			{
				// Use the attribute's default value
				tag.setAttribute(attrName, attrConfig.defaultValue);
			}
			else if (attrConfig.required)
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
* @param  {!Tag}     tag Tag to filter
* @return {!boolean}     Whether the tag is valid
*/
function filterTag(tag)
{
	var tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName],
		isValid   = true;

	if (tagConfig.filterChain)
	{
		// Record the tag being processed into the logger it can be added to the context of
		// messages logged during the execution
		logger.setTag(tag);

		for (var i = 0; i < tagConfig.filterChain.length; ++i)
		{
			if (!tagConfig.filterChain[i](tag, tagConfig))
			{
				isValid = false;
				break;
			}
		}

		// Remove the tag from the logger
		logger.unsetTag();
	}

	return isValid;
}

/**
* Get all registered vars
*
* @return {!Object}
*/
function getRegisteredVars()
{
	return registeredVars;
}

/**
* Set a variable's value for use in filters
*
* @param  {!string} name  Variable's name
* @param  {*}       value Value
*/
function registerVar(name, value)
{
	registeredVars[name] = value;
}