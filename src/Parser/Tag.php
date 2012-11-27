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
	* @var bool Whether this tag is invalid and should be skipped
	*/
	protected $isInvalid;

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
		$this->isInvalid = true;

		foreach ($this->cascade as $tag)
		{
			$tag->invalidate();
		}
	}

	/**
	* Return whether this tag is valid
	*
	* @return bool
	*/
	public function isValid()
	{
		return empty($this->isInvalid);
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
}