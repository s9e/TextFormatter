<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait TagStack
{
	/**
	* @var array Tag storage
	*/
	protected $tagStack;

	/**
	* Add a start tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	public function addStartTag($name, $pos, $len)
	{
		return $this->addTag(Tag::START_TAG, $name, $pos, $len);
	}

	/**
	* Add an end tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	public function addEndTag($name, $pos, $len)
	{
		return $this->addTag(Tag::END_TAG, $name, $pos, $len);
	}

	/**
	* Add a self-closing tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	public function addSelfClosingTag($name, $pos, $len)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, $name, $pos, $len);
	}

	/**
	* Add a 0-width "br" tag to force a line break at given position
	*
	* @param  integer $pos  Position of the tag in the text
	* @return Tag
	*/
	public function addBrTag($pos)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'br', $pos, 0);
	}

	/**
	* Add an "ignore" tag
	*
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	public function addIgnoreTag($pos, $len)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'i', $pos, $len);
	}

	/**
	* Add a tag
	*
	* @param  integer $type Tag's type
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @return Tag
	*/
	protected function addTag($type, $name, $pos, $len)
	{
		// Create the tag
		$tag = new Tag($type, $name, $pos, $len);

		// Invalidate this tag if it's an unknown tag, or if its length or its position is negative
		if (!isset($this->tagsConfig[$name]) && $name !== 'i' && $name !== 'br')
		{
			$tag->invalidate();
		}
		elseif ($len < 0 || $pos < 0)
		{
			$tag->invalidate();
		}
		else
		{
			$this->tagStack[] = $tag;
		}

		return $tag;
	}

	/**
	* Sort tags by position and precedence
	*
	* @return void
	*/
	protected function sortTags()
	{
		usort($this->tagStack, array(__CLASS__, 'compareTags'));
	}

	/**
	* sortTags() callback
	*
	* Tags are stored as a stack, in LIFO order. We sort tags by position _descending_ so that they
	* are processed in the order they appear in the text.
	*
	* @param  Tag     First tag to compare
	* @param  Tag     Second tag to compare
	* @return integer
	*/
	static protected function compareTags(Tag $a, Tag $b)
	{
		$aPos = $a->getPos();
		$bPos = $b->getPos();

		// First we order by pos descending
		if ($aPos !== $bPos)
		{
			return $bPos - $aPos;
		}

		$aLen = $a->getLen();
		$bLen = $b->getLen();

		if (!$aLen || !$bLen)
		{
			// Zero-width end tags are ordered after zero-width start tags so that a pair that ends
			// with a zero-width tag has the opportunity to be closed before another pair starts
			// with a zero-width tag. For example, the pairs that would enclose each of the letters
			// in the string "XY". Self-closing tags are ordered between end tags and start tags in
			// an attempt to keep them out of tag pairs
			if (!$aLen && !$bLen)
			{
				$order = array(
					Tag::END_TAG          => 2,
					Tag::SELF_CLOSING_TAG => 1,
					Tag::START_TAG        => 0
				);
				return $order[$a->getType()] - $order[$b->getType()];
			}

			// Here, we know that only one of $a or $b is a zero-width tags. Zero-width tags are
			// ordered after wider tags so that they have a chance to be processed before the next
			// character is consumed, which would force them to be skipped
			return ($aLen) ? -1 : 1;
		}

		// Here we know that both tags start at the same position and have a length greater than 0.
		// We sort tags by length ascending, so that the longest matches are processed first
		if ($aLen !== $bLen)
		{
			return ($aLen - $bLen);
		}

		// Finally, if the tags start at the same position and are the same length, sort them by id
		// descending, which is our version of a stable sort (tags that were added first end up
		// being processed first)
		/**
		* @todo reevaluate
		*/
//		return $b['id'] - $a['id'];

		return 0;
	}
}