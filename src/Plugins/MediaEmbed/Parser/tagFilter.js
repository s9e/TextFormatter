/**
* Filter a MEDIA tag
*
* This will always invalidate the original tag, and possibly replace it with the tag that
* corresponds to the media site
*
* @param  {!Tag}     tag   The original tag
* @param  {!Object}  sites Map of [host => siteId]
* @return {!boolean}       Always false
*/
function (tag, sites)
{
	var tagName;

	if (tag.hasAttribute('media'))
	{
		// [media=youtube]xxxxxxx[/media]
		tagName = tag.getAttribute('media');

		// If this tag doesn't have an id attribute and the url attribute doesn't really look
		// like an URL, copy the value of the url attribute, so that the tag acts like
		// [media=youtube id=xxxx]xxxx[/media]
		if (!tag.hasAttribute('id')
		 && tag.hasAttribute('url')
		 && tag.getAttribute('url').indexOf('://') === -1)
		{
			tag.setAttribute('id', tag.getAttribute('url'));
		}
	}
	else if (tag.hasAttribute('url'))
	{
		// Capture the scheme and host of the URL
		var p = /^(?:([^:]+):)?(?:\/\/([^\/]+))?/.exec(tag.getAttribute('url'));

		if (p[1] && sites[p[1] + ':'])
		{
			tagName = sites[p[1] + ':'];
		}
		else if (p[2])
		{
			var host = p[2];

			// Start with the full host then pop domain labels off the start until we get a
			// match
			do
			{
				if (sites[host])
				{
					tagName = sites[host];
					break;
				}

				var pos = host.indexOf('.');
				if (pos == -1)
				{
					break;
				}

				host = host.substr(1 + pos);
			}
			while (host > '');
		}
	}

	if (tagName)
	{
		var endTag = tag.getEndTag() || tag;

		// Compute the boundaries of our new tag
		var lpos = tag.getPos(),
			rpos = endTag.getPos() + endTag.getLen();

		// Create a new tag and copy this tag's attributes and priority
		var newTag = addSelfClosingTag(tagName.toUpperCase(), lpos, rpos - lpos);
		newTag.setAttributes(tag.getAttributes());
		newTag.setSortPriority(tag.getSortPriority());
	}

	return false;
}