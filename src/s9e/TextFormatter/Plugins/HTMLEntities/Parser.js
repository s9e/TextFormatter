function html_entity_decode(str)
{
	var b = document.createElement('b');

	// We escape left brackets so that we don't inadvertently evaluate some nasty HTML such as
	// <img src=... onload=evil() />
	b.innerHTML = str.replace(/</g, '&lt;');

	return b.textContent;
}
	
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