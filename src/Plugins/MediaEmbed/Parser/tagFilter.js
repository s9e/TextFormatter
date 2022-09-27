/**
* @param {!Tag}    tag      The original tag
* @param {!Object} hosts    Map of [host => siteId]
* @param {!Object} sites    Map of [siteId => siteConfig]
* @param {string}  cacheDir
*/
function (tag, hosts, sites, cacheDir)
{
	/**
	* Filter a MEDIA tag
	*
	* This will always invalidate the original tag, and possibly replace it with the tag that
	* corresponds to the media site
	*
	* @param {!Tag}    tag   The original tag
	* @param {!Object} hosts Map of [host => siteId]
	* @param {!Object} sites Map of [siteId => siteConfig]
	*/
	function filterTag(tag, hosts, sites)
	{
		// Always invalidate this tag
		tag.invalidate();

		if (tag.hasAttribute('url'))
		{
			let url    = tag.getAttribute('url'),
				siteId = getSiteIdFromUrl(url, hosts);
			if (sites[siteId])
			{
				let attributes = getAttributes(url, sites[siteId]);
				if (!empty(attributes))
				{
					createTag(siteId.toUpperCase(), tag).setAttributes(attributes);
				}
			}
		}
	}

	/**
	* Add named captures from a set of regular expressions to a set of attributes
	*
	* @param  {!Object} attributes Associative array of strings
	* @param  {string}  string     Text to match
	* @param  {!Array}  regexps    List of [regexp, map] pairs
	* @return {boolean}            Whether any regexp matched
	*/
	function addNamedCaptures(attributes, string, regexps)
	{
		let matched = false;
		regexps.forEach((pair) =>
		{
			let regexp = pair[0],
				map    = pair[1],
				m      = regexp.exec(string);
			if (!m)
			{
				return;
			}

			matched = true;
			map.forEach((name, i) =>
			{
				if (m[i] > '' && name > '')
				{
					attributes[name] = m[i];
				}
			});
		});

		return matched;
	}

	/**
	* Create a tag for a media embed
	*
	* @param  {string} tagName  Tag's name
	* @param  {!Tag}   tag      Reference tag
	* @return {!Tag}            New tag
	*/
	function createTag(tagName, tag)
	{
		let startPos = tag.getPos(),
			endTag   = tag.getEndTag(),
			startLen,
			endPos,
			endLen;
		if (endTag)
		{
			startLen = tag.getLen();
			endPos   = endTag.getPos();
			endLen   = endTag.getLen();
		}
		else
		{
			startLen = 0;
			endPos   = tag.getPos() + tag.getLen();
			endLen   = 0;
		}

		return addTagPair(tagName, startPos, startLen, endPos, endLen, tag.getSortPriority());
	}

	/**
	* @param  {!Object} attributes
	* @return {boolean}
	*/
	function empty(attributes)
	{
		for (let attrName in attributes)
		{
			return false;
		}

		return true;
	}

	/**
	* Return a set of attributes for given URL based on a site's config
	*
	* @param  {string}  url    Original URL
	* @param  {!Object} config Site config
	* @return {!Object}        Attributes
	*/
	function getAttributes(url, config)
	{
		let attributes = {};
		addNamedCaptures(attributes, url, config[0]);

		return attributes;
	}

	/**
	* Return the siteId that corresponds to given URL
	*
	* @param  {string}  url   Original URL
	* @param  {!Object} hosts Map of [hostname => siteId]
	* @return {string}        URL's siteId, or an empty string
	*/
	function getSiteIdFromUrl(url, hosts)
	{
		let m    = /^https?:\/\/([^\/]+)/.exec(url.toLowerCase()),
			host = m[1] || '';
		while (host > '')
		{
			if (hosts[host])
			{
				return hosts[host];
			}
			host = host.replace(/^[^.]*./, '');
		}

		return '';
	}

	filterTag(tag, hosts, sites);
}