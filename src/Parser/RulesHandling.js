/**
* Apply closeAncestor rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function closeAncestor(tag)
{
	if (!HINT.closeAncestor)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.closeAncestor)
		{
			var i = openTags.length;

			while (--i >= 0)
			{
				var ancestor     = openTags[i],
					ancestorName = ancestor.getName();

				if (tagConfig.rules.closeAncestor[ancestorName])
				{
					// We have to close this ancestor. First we reinsert this tag...
					tagStack.push(tag);

					// ...then we add a new end tag for it
					addMagicEndTag(ancestor, tag.getPos());

					return true;
				}
			}
		}
	}

	return false;
}

/**
* Apply closeParent rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function closeParent(tag)
{
	if (!HINT.closeParent)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.closeParent)
		{
			var parent     = openTags[openTags.length - 1],
				parentName = parent.getName();

			if (tagConfig.rules.closeParent[parentName])
			{
				// We have to close that parent. First we reinsert the tag...
				tagStack.push(tag);

				// ...then we add a new end tag for it
				addMagicEndTag(parent, tag.getPos());

				return true;
			}
		}
	}

	return false;
}

/**
* Apply fosterParent rules associated with given tag
*
* NOTE: this rule has the potential for creating an unbounded loop, either if a tag tries to
*       foster itself or two or more tags try to foster each other in a loop. We mitigate the
*       risk by preventing a tag from creating a child of itself (the parent still gets closed)
*       and by checking and increasing the currentFixingCost so that a loop of multiple tags
*       do not run indefinitely. The default tagLimit and nestingLimit also serve to prevent the
*       loop from running indefinitely
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function fosterParent(tag)
{
	if (!HINT.fosterParent)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.fosterParent)
		{
			var parent     = openTags[openTags.length - 1],
				parentName = parent.getName();

			if (tagConfig.rules.fosterParent[parentName])
			{
				if (parentName !== tagName && currentFixingCost < maxFixingCost)
				{
					// Add a 0-width copy of the parent tag right after this tag, and make it
					// depend on this tag
					var child = addCopyTag(parent, tag.getPos() + tag.getLen(), 0);
					tag.cascadeInvalidationTo(child);
				}

				++currentFixingCost;

				// Reinsert current tag
				tagStack.push(tag);

				// And finally close its parent
				addMagicEndTag(parent, tag.getPos());

				return true;
			}
		}
	}

	return false;
}

/**
* Apply requireAncestor rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether this tag has an unfulfilled requireAncestor requirement
*/
function requireAncestor(tag)
{
	if (!HINT.requireAncestor)
	{
		return false;
	}

	var tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName];

	if (tagConfig.rules.requireAncestor)
	{
		var i = tagConfig.rules.requireAncestor.length;
		while (--i >= 0)
		{
			var ancestorName = tagConfig.rules.requireAncestor[i];
			if (cntOpen[ancestorName])
			{
				return false;
			}
		}

		logger.err('Tag requires an ancestor', {
			'requireAncestor' : tagConfig.rules.requireAncestor.join(', '),
			'tag'             : tag
		});

		return true;
	}

	return false;
}