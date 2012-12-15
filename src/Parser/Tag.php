<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

class Tag
{
	/**
	* Tag type: start tag
	*/
	const START_TAG = 1;

	/**
	* Tag type: end tag
	*/
	const END_TAG = 2;

	/**
	* Tag type: self-closing tag -- meant to equal START_TAG | END_TAG
	*/
	const SELF_CLOSING_TAG = 3;

	/**
	* @var array Dictionary of attributes
	*/
	protected $attributes = array();

	/**
	* @var array List of tags that are invalidated when this tag is invalidated
	*/
	protected $cascade = array();

	/**
	* @var bool Whether this tag is be invalid
	*/
	protected $invalid = false;

	/**
	* @var integer Length of text consumed by this tag
	*/
	protected $len;

	/**
	* @var string Name of this tag
	*/
	protected $name;

	/**
	* @var integer Position of this tag in the text
	*/
	protected $pos;

	/**
	* @var integer Tag type
	*/
	protected $type;

	/**
	* Constructor
	*
	* @param  integer $type Tag's type
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return void
	*/
	public function __construct($type, $name, $pos, $len)
	{
		$this->type = (int) $type;
		$this->name = $name;
		$this->pos  = (int) $pos;
		$this->len  = (int) $len;
	}

	/**
	* Set given tag to be invalidated if this tag is invalidated
	*
	* @param  self $tag
	* @return void
	*/
	public function cascadeInvalidationTo(self $tag)
	{
		$this->cascade[] = $tag;

		// If this tag is already invalid, cascade it now
		if ($this->invalid)
		{
			$tag->invalidate();
		}
	}

	/**
	* Test whether this tag would given tag
	*
	* NOTE: it is assumed that this tag's position is after given tag in the text and that this tag
	*       was not invalidated
	*
	* @param  self $tag
	* @return bool
	*/
	public function closes(self $tag)
	{
		// Ensure that their characteristics match
		if ($tag->type !== self::START_TAG
		 || $this->type !== self::END_TAG
		 || $this->name !== $tag->name)
		{
			return false;
		}

		// If given tag has a tagMate, ensure it's this tag
		if ($tag->tagMate && $tag->tagMate !== $this)
		{
			return false;
		}

		// If this tag has a tagMate, ensure it's given tag
		if ($this->tagMate && $this->tagMate !== $tag)
		{
			return false;
		}

		return true;
	}

	/**
	* Return the length of text consumed by this tag
	*
	* @return integer
	*/
	public function getLen()
	{
		return $this->len;
	}

	/**
	* Return this tag's name
	*
	* @return string
	*/
	public function getName()
	{
		return $this->name;
	}

	/**
	* Return this tag's position
	*
	* @return integer
	*/
	public function getPos()
	{
		return $this->pos;
	}

	/**
	* Invalidate this tag, as well as tags bound to this tag
	*
	* @return void
	*/
	public function invalidate()
	{
		// If this tag is already invalid, we can return now. This prevent infinite loops
		if ($this->invalid)
		{
			return;
		}

		$this->invalid = true;

		foreach ($this->cascade as $tag)
		{
			$tag->invalidate();
		}
	}

	/**
	* Test whether this tag is a br tag
	*
	* @return bool
	*/
	public function isBrTag()
	{
		return ($this->name === 'br');
	}

	/**
	* Test whether this tag is an end tag (self-closing tags inclusive)
	*
	* @return bool
	*/
	public function isEndTag()
	{
		return (bool) ($this->type & self::END_TAG);
	}

	/**
	* Test whether this tag is an ignore tag
	*
	* @return bool
	*/
	public function isIgnoreTag()
	{
		return ($this->name === 'i');
	}

	/**
	* Test whether this tag is invalid
	*
	* @return bool
	*/
	public function isInvalid()
	{
		return $this->invalid;
	}

	/**
	* Test whether this tag is a start tag (self-closing tags inclusive)
	*
	* @return bool
	*/
	public function isStartTag()
	{
		return (bool) ($this->type & self::START_TAG);
	}

	/**
	* Pair this tag with given tag
	*
	* @param  self $tag
	* @return void
	*/
	public function pairWith(self $tag)
	{
		if ($this->type === self::START_TAG
		 && $tag->type  === self::END_TAG)
		{
			$this->endTag  = $tag;
			$tag->startTag = $this;
		}
		elseif ($this->type === self::END_TAG
		     && $tag->type  === self::START_TAG)
		{
			$this->startTag = $tag;
			$tag->endTag    = $this;
		}
	}

	//==========================================================================
	// Attributes handling
	//==========================================================================

	/**
	* Return the value of given attribute
	*
	* @param  string $attrName
	* @return mixed
	*/
	public function getAttribute($attrName)
	{
		return $this->attributes[$attrName];
	}

	/**
	* Return this tag's attributes
	*
	* @return array
	*/
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	* Return whether given attribute is set
	*
	* @param  string $attrName
	* @return bool
	*/
	public function hasAttribute($attrName)
	{
		return isset($this->attributes[$attrName]);
	}

	/**
	* Remove given attribute
	*
	* @param  string $attrName
	* @return void
	*/
	public function removeAttribute($attrName)
	{
		unset($this->attributes[$attrName]);
	}

	/**
	* Set the value of an attribute
	*
	* @param  string $attrName  Attribute's name
	* @param  string $attrValue Attribute's value
	* @return void
	*/
	public function setAttribute($attrName, $attrValue)
	{
		$this->attributes[$attrName] = $attrValue;
	}
}