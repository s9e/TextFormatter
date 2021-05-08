/**
* Set a URL or IMG tag's attributes
*
* @param {!Tag}    tag      URL or IMG tag
* @param {string} linkInfo Link's info: an URL optionally followed by spaces and a title
* @param {string} attrName Name of the URL attribute
*/
function setLinkAttributes(tag, linkInfo, attrName)
{
	var url   = linkInfo.replace(/^\s*/, '').replace(/\s*$/, ''),
		title = '',
		pos   = url.indexOf(' ');
	if (pos !== -1)
	{
		title = url.substring(pos).replace(/^\s*\S/, '').replace(/\S\s*$/, '');
		url   = url.substring(0, pos);
	}
	if (/^<.+>$/.test(url))
	{
		url = url.replace(/^<(.+)>$/, '$1').replace(/\\>/g, '>');
	}

	tag.setAttribute(attrName, decode(url));
	if (title > '')
	{
		tag.setAttribute('title', decode(title));
	}
}