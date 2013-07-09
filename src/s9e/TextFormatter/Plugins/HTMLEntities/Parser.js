var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	var entity = m[0][0],
		chr    = html_entity_decode(entity);

	if (chr === entity)
	{
		// The entity was not decoded, so we assume it's not valid and we ignore it
		return;
	}

	addSelfClosingTag(tagName, m[0][1], entity.length).setAttribute(attrName, chr);
});