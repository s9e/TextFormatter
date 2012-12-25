/**
* @constructor
*
* @param {!number} type Tag's type
* @param {!string} name Name of the tag
* @param {!number} pos  Position of the tag in the text
* @param {!number} len  Length of text consumed by the tag
*/
function Tag(type, name, pos, len)
{
	this.type = type;
	this.name = name;
	this.pos  = pos;
	this.len  = len;
}

/** @const */
Tag.START_TAG = 1;

/** @const */
Tag.END_TAG = 2;

/** @const */
Tag.SELF_CLOSING_TAG = 3;

/**
* @type {!Object} Dictionary of attributes
*/
Tag.prototype.attributes = {};

/**
* @type {!Array.<Tag>} List of tags that are invalidated when this tag is invalidated
*/
Tag.prototype.cascade = [];

/**
* @type {Tag} End tag that unconditionally ends this start tag
*/
Tag.prototype.endTag;

/**
* @type {!boolean} Whether this tag is be invalid
*/
Tag.prototype.invalid = false;

/**
* @type {!number} Length of text consumed by this tag
*/
Tag.prototype.len;

/**
* @type {!string} Name of this tag
*/
Tag.prototype.name;

/**
* @type {!number} Position of this tag in the text
*/
Tag.prototype.pos;

/**
* @type {!number} Tiebreaker used when sorting identical tags
*/
Tag.prototype.sortPriority = 0;

/**
* @type {Tag} Start tag that is unconditionally closed this end tag
*/
Tag.prototype.startTag;

/**
* @type {!number} Tag type
*/
Tag.prototype.type;

/**
* Set given tag to be invalidated if this tag is invalidated
*
* @param {!Tag} tag
*/
Tag.prototype.cascadeInvalidationTo = function(tag)
{
	this.cascade.push(tag);

	// If this tag is already invalid, cascade it now
	if (this.invalid)
	{
		tag.invalidate();
	}
};

/**
* Invalidate this tag, as well as tags bound to this tag
*/
Tag.prototype.invalidate = function()
{
	// If this tag is already invalid, we can return now. This prevent infinite loops
	if (this.invalid)
	{
		return;
	}

	this.invalid = true;

	this.cascade.forEach(
		/**
		* @param {!Tag} tag
		*/
		function(tag)
		{
			tag.invalidate();
		}
	);
}

/**
* Pair this tag with given tag
*
* @param {!Tag} tag
*/
Tag.prototype.pairWith = function(tag)
{
	if (this.name === tag.name)
	{
		if (this.type === Tag.START_TAG
		 && tag.type  === Tag.END_TAG
		 && tag.pos   >=  this.pos)
		{
			this.endTag  = tag;
			tag.startTag = this;
		}
		else if (this.type === Tag.END_TAG
			 && tag.type  === Tag.START_TAG
			 && tag.pos   <=  this.pos)
		{
			this.startTag = tag;
			tag.endTag    = this;
		}
	}
}

/**
* Set this tag's tiebreaker
*
* @param  {!number} sortPriority
*/
Tag.prototype.setSortPriority = function(sortPriority)
{
	this.sortPriority = sortPriority;
}

//==========================================================================
// Getters
//==========================================================================

/**
* Return this tag's attributes
*
* @return {Object}
*/
Tag.prototype.getAttributes = function()
{
	return this.attributes;
}

/**
* Return this tag's end tag
*
* @return {Tag|boolean} This tag's end tag, or FALSE if none is set
*/
Tag.prototype.getEndTag = function()
{
	return this.endTag || false;
}

/**
* Return the length of text consumed by this tag
*
* @return {!number}
*/
Tag.prototype.getLen = function()
{
	return this.len;
}

/**
* Return this tag's name
*
* @return {!string}
*/
Tag.prototype.getName = function()
{
	return this.name;
}

/**
* Return this tag's position
*
* @return {!number}
*/
Tag.prototype.getPos = function()
{
	return this.pos;
}

/**
* Return this tag's tiebreaker
*
* @return {!number}
*/
Tag.prototype.getSortPriority = function()
{
	return this.sortPriority;
}

/**
* Return this tag's start tag
*
* @return {Tag|boolean} This tag's start tag, or FALSE if none is set
*/
Tag.prototype.getStartTag = function()
{
	return this.startTag || false;
}

/**
* Return this tag's type
*
* @return {!number}
*/
Tag.prototype.getType = function()
{
	return this.type;
}

//==========================================================================
// Tag's status
//==========================================================================

/**
* Test whether this tag is a br tag
*
* @return {!boolean}
*/
Tag.prototype.isBrTag = function()
{
	return (this.name === 'br');
}

/**
* Test whether this tag is an end tag (self-closing tags inclusive)
*
* @return {!boolean}
*/
Tag.prototype.isEndTag = function()
{
	return !!(this.type & Tag.END_TAG);
}

/**
* Test whether this tag is an ignore tag
*
* @return {!boolean}
*/
Tag.prototype.isIgnoreTag = function()
{
	return (this.name === 'i');
}

/**
* Test whether this tag is invalid
*
* @return {!boolean}
*/
Tag.prototype.isInvalid = function()
{
	return this.invalid;
}

/**
* Test whether this tag is a self-closing tag
*
* @return {!boolean}
*/
Tag.prototype.isSelfClosingTag = function()
{
	return (this.type === Tag.SELF_CLOSING_TAG);
}

/**
* Test whether this tag is a start tag (self-closing tags inclusive)
*
* @return {!boolean}
*/
Tag.prototype.isStartTag = function()
{
	return !!(this.type & Tag.START_TAG);
}

//==========================================================================
// Attributes handling
//==========================================================================

/**
* Return the value of given attribute
*
* @param  {!string} attrName
* @return {!string}
*/
Tag.prototype.getAttribute = function(attrName)
{
	return this.attributes[attrName];
}

/**
* Return whether given attribute is set
*
* @param  {!string} attrName
* @return {!boolean}
*/
Tag.prototype.hasAttribute = function(attrName)
{
	return (attrName in this.attributes);
}

/**
* Remove given attribute
*
* @param {!string} attrName
*/
Tag.prototype.removeAttribute = function(attrName)
{
	delete this.attributes[attrName];
}

/**
* Set the value of an attribute
*
* @param {!string} attrName  Attribute's name
* @param {*}       attrValue Attribute's value
*/
Tag.prototype.setAttribute = function(attrName, attrValue)
{
	this.attributes[attrName] = attrValue;
}

/**
* Set all of this tag's attributes at once
*
* @param {!Object} attributes
*/
Tag.prototype.setAttributes = function(attributes)
{
	this.attributes = attributes;
}






























/**
* @type {!Array.<Tag>} Tag storage
*/
var tagStack;

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
		tagStack.push(tag);
	}

	return tag;
}

/**
* Sort tags by position and precedence
*/
function sortTags()
{
	tagStack.sort(compareTags);
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