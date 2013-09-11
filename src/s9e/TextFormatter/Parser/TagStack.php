<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
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
	* @var bool Whether the tags in the stack are sorted
	*/
	protected $tagStackIsSorted;

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
	* Add a paragraph break at given position
	*
	* Uses a zero-width tag that is actually never output in the result
	*
	* @param  integer $pos  Position of the tag in the text
	* @return Tag
	*/
	public function addParagraphBreak($pos)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'pb', $pos, 0);
	}

	/**
	* Add a copy of given tag at given position and length
	*
	* @param  Tag     $tag Original tag
	* @param  integer $pos Copy's position
	* @param  integer $len Copy's length
	* @return Tag          Copy tag
	*/
	public function addCopyTag(Tag $tag, $pos, $len)
	{
		$copy = $this->addTag($tag->getType(), $tag->getName(), $pos, $len);
		$copy->setAttributes($tag->getAttributes());
		$copy->setSortPriority($tag->getSortPriority());

		return $copy;
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

		// Set this tag's rules bitfield
		if (isset($this->tagsConfig[$name]))
		{
			$tag->setFlags($this->tagsConfig[$name]['rules']['flags']);
		}

		// Invalidate this tag if it's an unknown tag, a disabled tag or if its length or its
		// position is negative
		if (!isset($this->tagsConfig[$name]) && $name !== 'i' && $name !== 'br' && $name !== 'pb')
		{
			$tag->invalidate();
		}
		elseif (!empty($this->tagsConfig[$name]['isDisabled']))
		{
			$this->logger->warn(
				'Tag is disabled',
				[
					'tag'     => $tag,
					'tagName' => $name
				]
			);
			$tag->invalidate();
		}
		elseif ($len < 0 || $pos < 0)
		{
			$tag->invalidate();
		}
		else
		{
			if (!empty($this->tagStack) && $pos > end($this->tagStack)->getPos())
			{
				$this->tagStackIsSorted = false;
			}

			$this->tagStack[] = $tag;
		}

		return $tag;
	}

	/**
	* Add a pair of tags
	*
	* @param  string  $name     Name of the tags
	* @param  integer $startPos Position of the start tag
	* @param  integer $startLen Length of the starttag
	* @param  integer $endPos   Position of the start tag
	* @param  integer $endLen   Length of the starttag
	* @return Tag               Start tag
	*/
	public function addTagPair($name, $startPos, $startLen, $endPos, $endLen)
	{
		$tag = $this->addStartTag($name, $startPos, $startLen);
		$tag->pairWith($this->addEndTag($name, $endPos, $endLen));

		return $tag;
	}

	/**
	* Sort tags by position and precedence
	*
	* @return void
	*/
	protected function sortTags()
	{
		usort($this->tagStack, [__CLASS__, 'compareTags']);
		$this->tagStackIsSorted = true;
	}

	/**
	* sortTags() callback
	*
	* Tags are stored as a stack, in LIFO order. We sort tags by position _descending_ so that they
	* are processed in the order they appear in the text.
	*
	* @param  Tag     $a First tag to compare
	* @param  Tag     $b Second tag to compare
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

		// If the tags start at the same position, we'll use their sortPriority if applicable. Tags
		// with a lower value get sorted last, which means they'll be processed first. IOW, -10 is
		// processed before 10
		if ($a->getSortPriority() !== $b->getSortPriority())
		{
			return $b->getSortPriority() - $a->getSortPriority();
		}

		// If the tags start at the same position and have the same priority, we'll sort them
		// according to their length, with special considerations for  zero-width tags
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
				$order = [
					Tag::END_TAG          => 0,
					Tag::SELF_CLOSING_TAG => 1,
					Tag::START_TAG        => 2
				];

				return $order[$b->getType()] - $order[$a->getType()];
			}

			// Here, we know that only one of $a or $b is a zero-width tags. Zero-width tags are
			// ordered after wider tags so that they have a chance to be processed before the next
			// character is consumed, which would force them to be skipped
			return ($aLen) ? -1 : 1;
		}

		// Here we know that both tags start at the same position and have a length greater than 0.
		// We sort tags by length ascending, so that the longest matches are processed first. If
		// their length is identical, the order is undefined as PHP's sort isn't stable
		return $aLen - $bLen;
	}
}