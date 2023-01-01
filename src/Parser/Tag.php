<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
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
	* Tag type: self-closing tag
	*/
	const SELF_CLOSING_TAG = self::START_TAG | self::END_TAG;

	/**
	* @var array Dictionary of attributes
	*/
	protected $attributes = [];

	/**
	* @var array List of tags that are invalidated when this tag is invalidated
	*/
	protected $cascade = [];

	/**
	* @var Tag End tag that unconditionally ends this start tag
	*/
	protected $endTag = null;

	/**
	* @var integer Bitfield of boolean rules that apply to this tag
	*/
	protected $flags = 0;

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
	* @var integer Tiebreaker used when sorting identical tags
	*/
	protected $sortPriority;

	/**
	* @var Tag Start tag that is unconditionally closed this end tag
	*/
	protected $startTag = null;

	/**
	* @var integer Tag type
	*/
	protected $type;

	/**
	* Constructor
	*
	* @param  integer $type     Tag's type
	* @param  string  $name     Name of the tag
	* @param  integer $pos      Position of the tag in the text
	* @param  integer $len      Length of text consumed by the tag
	* @param  integer $priority This tag's sorting tiebreaker
	*/
	public function __construct($type, $name, $pos, $len, $priority = 0)
	{
		$this->type = (int) $type;
		$this->name = $name;
		$this->pos  = (int) $pos;
		$this->len  = (int) $len;
		$this->sortPriority = (int) $priority;
	}

	//==========================================================================
	// Actions
	//==========================================================================

	/**
	* Add a set of flags to this tag's
	*
	* @param  integer $flags
	* @return void
	*/
	public function addFlags($flags)
	{
		$this->flags |= $flags;
	}

	/**
	* Set given tag to be invalidated if this tag is invalidated
	*
	* @param  Tag  $tag
	* @return void
	*/
	public function cascadeInvalidationTo(Tag $tag)
	{
		$this->cascade[] = $tag;

		// If this tag is already invalid, cascade it now
		if ($this->invalid)
		{
			$tag->invalidate();
		}
	}

	/**
	* Invalidate this tag, as well as tags bound to this tag
	*
	* @return void
	*/
	public function invalidate()
	{
		// Only invalidate if this tag is valid to prevent infinite loops
		if (!$this->invalid)
		{
			$this->invalid = true;
			foreach ($this->cascade as $tag)
			{
				$tag->invalidate();
			}
		}
	}

	/**
	* Pair this tag with given tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	public function pairWith(Tag $tag)
	{
		if ($this->canBePaired($this, $tag))
		{
			$this->endTag  = $tag;
			$tag->startTag = $this;

			$this->cascadeInvalidationTo($tag);
		}
		elseif ($this->canBePaired($tag, $this))
		{
			$this->startTag = $tag;
			$tag->endTag    = $this;
		}
	}

	/**
	* Test whether two tags can be paired
	*/
	protected function canBePaired(Tag $startTag, Tag $endTag): bool
	{
		return $startTag->name === $endTag->name && $startTag->type === self::START_TAG && $endTag->type === self::END_TAG && $startTag->pos <= $endTag->pos;
	}

	/**
	* Remove a set of flags from this tag's
	*
	* @param  integer $flags
	* @return void
	*/
	public function removeFlags($flags)
	{
		$this->flags &= ~$flags;
	}

	/**
	* Set the bitfield of boolean rules that apply to this tag
	*
	* @param  integer $flags Bitfield of boolean rules that apply to this tag
	* @return void
	*/
	public function setFlags($flags)
	{
		$this->flags = $flags;
	}

	//==========================================================================
	// Getters
	//==========================================================================

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
	* Return this tag's end tag
	*
	* @return Tag|null This tag's end tag, or NULL if none is set
	*/
	public function getEndTag()
	{
		return $this->endTag;
	}

	/**
	* Return the bitfield of boolean rules that apply to this tag
	*
	* @return integer
	*/
	public function getFlags()
	{
		return $this->flags;
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
	* Return this tag's tiebreaker
	*
	* @return integer
	*/
	public function getSortPriority()
	{
		return $this->sortPriority;
	}

	/**
	* Return this tag's start tag
	*
	* @return Tag|null This tag's start tag, or NULL if none is set
	*/
	public function getStartTag()
	{
		return $this->startTag;
	}

	/**
	* Return this tag's type
	*
	* @return integer
	*/
	public function getType()
	{
		return $this->type;
	}

	//==========================================================================
	// Tag's status
	//==========================================================================

	/**
	* Test whether this tag can close given start tag
	*
	* @param  Tag  $startTag A start tag
	* @return bool
	*/
	public function canClose(Tag $startTag)
	{
		if ($this->invalid
		 || !$this->canBePaired($startTag, $this)
		 || ($this->startTag && $this->startTag !== $startTag)
		 || ($startTag->endTag && $startTag->endTag !== $this))
		{
			return false;
		}

		return true;
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
	* Test whether this tag represents a paragraph break
	*
	* @return bool
	*/
	public function isParagraphBreak()
	{
		return ($this->name === 'pb');
	}

	/**
	* Test whether this tag is a self-closing tag
	*
	* @return bool
	*/
	public function isSelfClosingTag()
	{
		return ($this->type === self::SELF_CLOSING_TAG);
	}

	/**
	* Test whether this tag is a special tag: "br", "i", "pb" or "v"
	*
	* @return bool
	*/
	public function isSystemTag()
	{
		return (strpos('br i pb v', $this->name) !== false);
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
	* Test whether this tag represents verbatim text
	*
	* @return bool
	*/
	public function isVerbatim()
	{
		return ($this->name === 'v');
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

	/**
	* Set all of this tag's attributes at once
	*
	* @param  array $attributes
	* @return void
	*/
	public function setAttributes(array $attributes)
	{
		$this->attributes = $attributes;
	}
}