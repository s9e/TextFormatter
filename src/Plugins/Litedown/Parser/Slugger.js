/**
* @param {!Tag}   tag
* @param {string} innerText
*/
function filterTag(tag, innerText)
{
	let slug = innerText.toLowerCase();
	slug = slug.replace(/[^a-z0-9]+/g, '-');
	slug = slug.replace(/^-/, '').replace(/-$/, '');
	if (slug !== '')
	{
		tag.setAttribute('slug', slug);
	}
}
