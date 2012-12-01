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
	* @var array List of tags that are invalidated when this tag is invalidated
	*/
	protected $cascade = array();

	/**
	* @var integer Length of text consumed by this tag
	*/
	protected $len;

	/**
	* @var string Name of this tag
	*/
	protected $name;

	/**
	* @var string Name of the plugin that created this tag
	*/
	protected $pluginName;

	/**
	* @var integer Position of this tag in the text
	*/
	protected $pos;

	/**
	* @var bool Whether this tag should be skipped
	*/
	protected $skip = false;

	/**
	* @var Tag Tag that is uniquely paired with this tag
	*/
	protected $tagMate = null;

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
	public function __construct($type, $pluginName, $name, $pos, $len)
	{
		$this->type       = (int) $type;
		$this->pluginName = $pluginName;
		$this->name       = $name;
		$this->pos        = (int) $pos;
		$this->len        = (int) $len;
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
	* 
	*
	* @param  Tag  $tag
	* @return void
	*/
	public function cascadeInvalidationTo(Tag $tag)
	{
		$this->cascade[] = $tag;
	}

	/**
	* Invalidate this tag, as well as tags bound to this tag
	*
	* @return void
	*/
	public function invalidate()
	{
		$this->skip = true;

		foreach ($this->cascade as $tag)
		{
			$tag->invalidate();
		}
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
	* Test whether this tag is a start tag (self-closing tags inclusive)
	*
	* @return bool
	*/
	public function isStartTag()
	{
		return (bool) ($this->type & self::START_TAG);
	}

	/**
	* 
	*
	* @param  Tag  $tag
	* @return void
	*/
	public function pairWith(Tag $tag)
	{
		$this->tagMate = 'xxx';
		$tag->tagMate = 'xxx';
	}

	/**
	* Test whether this tag should be skipped
	*
	* Will return true if this tag was invalidated or if the parser's cursor is past its position
	*
	* @param  integer $pos Parser's position in text
	* @return bool
	*/
	public function shouldBeSkipped($pos)
	{
		return ($pos > $this->pos || $this->skip);
	}
}