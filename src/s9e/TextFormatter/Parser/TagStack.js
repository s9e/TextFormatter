/**
* @type {!Array.<Tag>} Tag storage
*/
var tagStack;

/**
* @type {!boolean} Whether the tags in the stack are sorted
*/
var tagStackIsSorted;

/**
* Add a start tag
*
* @param  {!string} name Name of the tag
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @return {!Tag}
*/
function addStartTag(name, pos, len)
{
	return addTag(Tag.START_TAG, name, pos, len);
}

/**
* Add an end tag
*
* @param  {!string} name Name of the tag
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @return {!Tag}
*/
function addEndTag(name, pos, len)
{
	return addTag(Tag.END_TAG, name, pos, len);
}

/**
* Add a self-closing tag
*
* @param  {!string} name Name of the tag
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @return {!Tag}
*/
function addSelfClosingTag(name, pos, len)
{
	return addTag(Tag.SELF_CLOSING_TAG, name, pos, len);
}

/**
* Add a 0-width "br" tag to force a line break at given position
*
* @param  {!number} pos  Position of the tag in the text
* @return {!Tag}
*/
function addBrTag(pos)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'br', pos, 0);
}

/**
* Add an "ignore" tag
*
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @return {!Tag}
*/
function addIgnoreTag(pos, len)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'i', pos, len);
}

/**
* Add a tag
*
* @param  {!number} type Tag's type
* @param  {!string} name Name of the tag
* @param  {!number} pos  Position of the tag in the text
* @param  {!number} len  Length of text consumed by the tag
* @return {!Tag}
*/
function addTag(type, name, pos, len)
{
	// Create the tag
	var tag = new Tag(type, name, pos, len);

	// Invalidate this tag if it's an unknown tag, or if its length or its position is negative
	if (!tagsConfig[name] && name !== 'i' && name !== 'br')
	{
		tag.invalidate();
	}
	else if (len < 0 || pos < 0)
	{
		tag.invalidate();
	}
	else
	{
		if (tagStack.length && pos > tagStack[tagStack.length - 1].getPos())
		{
			tagStackIsSorted = false;
		}

		tagStack.push(tag);
	}

	return tag;
}

/**
* Add a pair of tags
*
* @param  {!string} name     Name of the tags
* @param  {!number} startPos Position of the start tag
* @param  {!number} startLen Length of the starttag
* @param  {!number} endPos   Position of the start tag
* @param  {!number} endLen   Length of the starttag
* @return {!Tag}             Start tag
*/
function addTagPair(name, startPos, startLen, endPos, endLen)
{
	var tag = addStartTag(name, startPos, startLen);
	tag.pairWith(addEndTag(name, endPos, endLen));

	return tag;
}

/**
* Sort tags by position and precedence
*/
function sortTags()
{
	tagStack.sort(compareTags);
	tagStackIsSorted = true;
}

/**
* sortTags() callback
*
* Tags are stored as a stack, in LIFO order. We sort tags by position _descending_ so that they
* are processed in the order they appear in the text.
*
* @param  {!Tag}    a First tag to compare
* @param  {!Tag}    b Second tag to compare
* @return {!number}
*/
function compareTags(a, b)
{
	var aPos = a.getPos(),
		bPos = b.getPos();

	// First we order by pos descending
	if (aPos !== bPos)
	{
		return bPos - aPos;
	}

	var aLen = a.getLen(),
		bLen = b.getLen();

	if (!aLen || !bLen)
	{
		// Zero-width end tags are ordered after zero-width start tags so that a pair that ends
		// with a zero-width tag has the opportunity to be closed before another pair starts
		// with a zero-width tag. For example, the pairs that would enclose each of the letters
		// in the string "XY". Self-closing tags are ordered between end tags and start tags in
		// an attempt to keep them out of tag pairs
		if (!aLen && !bLen)
		{
			var order = {}	
			order[Tag.END_TAG]          = 0;
			order[Tag.SELF_CLOSING_TAG] = 1;
			order[Tag.START_TAG]        = 2;

			return order[b.getType()] - order[a.getType()];
		}

		// Here, we know that only one of a or b is a zero-width tags. Zero-width tags are
		// ordered after wider tags so that they have a chance to be processed before the next
		// character is consumed, which would force them to be skipped
		return (aLen) ? -1 : 1;
	}

	// Here we know that both tags start at the same position and have a length greater than 0.
	// We sort tags by length ascending, so that the longest matches are processed first
	if (aLen !== bLen)
	{
		return (aLen - bLen);
	}

	// Finally, if the tags consume exactly the same text we'll use their sortPriority as
	// tiebreaker. Tags with a lower value get sorted last, which means they'll be processed
	// first. IOW, -10 is processed before 10. Most of the time, this value will be the same,
	// and since PHP's sort isn't stable it means the sort order of identical tags is undefined
	return b.getSortPriority() - a.getSortPriority();
}