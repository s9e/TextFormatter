/**
* Filter a MEDIA tag
*
* This will always invalidate the original tag, and possibly replace it with the tag that
* corresponds to the media site
*
* @param  {!Tag}     tag      The original tag
* @param  {*}        tagStack Unused
* @param  {!Object}  sites    Map of [host => siteId]
* @return {!boolean}          Always false
*/
function (tag, tagStack, sites)
{
	var tagName;

	if (tag.hasAttribute('media'))
	{
		// [media=youtube]xxxxxxx[/media]
		tagName = tag.getAttribute('media');

		// If this tag doesn't have an id attribute, copy the value of the url attribute, so
		// that the tag acts like [media=youtube id=xxxx]xxxx[/media]
		if (!tag.hasAttribute('id') && tag.hasAttribute('url'))
		{
			tag.setAttribute('id', tag.getAttribute('url'));
		}
	}
	else if (tag.hasAttribute('url'))
	{
		// Match the start of a URL, keep only the last two parts of the hostname
		var regexp = /\/\/(?:[^\/]*\.)?([^./]+\.[^\/]+)/,
			url    = tag.getAttribute('url'),
			m;

		if (m = regexp.exec(url))
		{
			var host = m[1];
			if (sites[host])
			{
				tagName = sites[host];
			}
		}
	}

	if (tagName)
	{
		var endTag = tag.getEndTag() || tag;

		// Compute the boundaries of our new tag
		var lpos = tag.getPos(),
			rpos = endTag.getPos() + endTag.getLen();

		// Create a new tag and copy this tag's attributes
		addSelfClosingTag(tagName.toUpperCase(), lpos, rpos - lpos).setAttributes(tag.getAttributes());
	}

	return false;
}
