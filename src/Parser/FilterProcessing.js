/**
* Execute all the attribute preprocessors of given tag
*
* @private
*
* @param {!Tag}    tag       Source tag
* @param {!Object} tagConfig Tag's config
*/
function executeAttributePreprocessors(tag, tagConfig)
{
	if (typeof tagConfig.attributePreprocessors === 'undefined')
	{
		return;
	}

	tagConfig.attributePreprocessors.forEach(function(ap)
	{
		var attrName = ap[0], regexp = ap[1], map = ap[2];
		if (tag.hasAttribute(attrName))
		{
			executeAttributePreprocessor(tag, attrName, regexp, map);
		}
	});
}

/**
* Filter the attributes of given tag
*
* @private
*
* @param {!Tag}    tag            Tag being checked
* @param {!Object} tagConfig      Tag's config
* @param {!Object} registeredVars Unused
* @param {!Logger} logger         This parser's Logger instance
*/
function filterAttributes(tag, tagConfig, registeredVars, logger)
{
	var attributes = {}, attrName;
	for (attrName in tagConfig.attributes)
	{
		var attrConfig = tagConfig.attributes[attrName],
			attrValue  = false;
		if (tag.hasAttribute(attrName))
		{
			attrValue = executeAttributeFilterChain(attrConfig.filterChain, attrName, tag.getAttribute(attrName));
		}

		if (attrValue !== false)
		{
			attributes[attrName] = attrValue;
		}
		else if (HINT.attributeDefaultValue && typeof attrConfig.defaultValue !== 'undefined')
		{
			attributes[attrName] = attrConfig.defaultValue;
		}
		else if (attrConfig.required)
		{
			tag.invalidate();
		}
	}
	tag.setAttributes(attributes);
}

/**
* Execute a tag's filterChain
*
* @private
*
* @param {!Tag} tag Tag to filter
*/
function filterTag(tag)
{
	var tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName];

	// Record the tag being processed into the logger it can be added to the context of
	// messages logged during the execution
	logger.setTag(tag);

	for (var i = 0; i < tagConfig.filterChain.length; ++i)
	{
		if (tag.isInvalid())
		{
			break;
		}
		tagConfig.filterChain[i](tag, tagConfig);
	}

	// Remove the tag from the logger
	logger.unsetTag();
}

/**
* Execute an attribute's filterChain
*
* @param  {!Array} filterChain Attribute's filterChain
* @param  {string} attrName    Attribute's name
* @param  {*}      attrValue   Original value
* @return {*}                  Filtered value
*/
function executeAttributeFilterChain(filterChain, attrName, attrValue)
{
	logger.setAttribute(attrName);
	for (var i = 0; i < filterChain.length; ++i)
	{
		// NOTE: attrValue is intentionally set as the first argument to facilitate inlining
		attrValue = filterChain[i](attrValue, attrName);
		if (attrValue === false)
		{
			break;
		}
	}
	logger.unsetAttribute();

	return attrValue;
}

/**
* Execute an attribute preprocessor
*
* @param  {!Tag}           tag
* @param  {string}         attrName
* @param  {!RegExp}        regexp
* @param  {!Array<string>} map
*/
function executeAttributePreprocessor(tag, attrName, regexp, map)
{
	var attrValue = tag.getAttribute(attrName),
		captures  = getNamedCaptures(attrValue, regexp, map),
		k;
	for (k in captures)
	{
		// Attribute preprocessors cannot overwrite other attributes but they can
		// overwrite themselves
		if (k === attrName || !tag.hasAttribute(k))
		{
			tag.setAttribute(k, captures[k]);
		}
	}
}

/**
* Execute a regexp and return the values of the mapped captures
*
* @param  {string}                 attrValue
* @param  {!RegExp}                regexp
* @param  {!Array<string>}         map
* @return {!Object<string,string>}
*/
function getNamedCaptures(attrValue, regexp, map)
{
	var m = regexp.exec(attrValue);
	if (!m)
	{
		return [];
	}

	var values = {};
	map.forEach(function(k, i)
	{
		if (typeof m[i] === 'string' && m[i] !== '')
		{
			values[k] = m[i];
		}
	});

	return values;
}