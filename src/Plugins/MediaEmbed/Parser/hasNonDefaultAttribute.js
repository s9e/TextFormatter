/**
* Invalidate given tag if it doesn't have at least one non-default attribute
*
* @param {!Tag} tag The original tag
*/
function (tag)
{
	for (var attrName in tag.getAttributes())
	{
		if (attrName !== 'url')
		{
			return;
		}
	}

	tag.invalidate();
}