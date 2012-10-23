<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait TagStackHandling
{
	/**
	* Normalize a tag's name and options, and ensure its existence
	*
	* @param  array      $tag Original tag
	* @return array|bool      Normalized tag, or FALSE if invalid/unknown
	*/
	protected function normalizeTag(array $tag)
	{
		/**
		* If the tag's name isn't prefixed, we change it to uppercase.
		*
		* NOTE: we don't bother checking if the tag name would be valid since we check for the
		*       tag's existence in $this->tagsConfig and only valid tags should be found there
		*/
		if (strpos($tag['name'], ':') === false)
		{
			$tag['name'] = strtoupper($tag['name']);
		}

		if (!isset($this->tagsConfig[$tag['name']]))
		{
			return false;
		}

		// Cast 'pos' and 'len' to int
		$tag['pos'] = (int) $tag['pos'];
		$tag['len'] = (int) $tag['len'];

		// Some methods expect those keys to always be set
		$tag += array(
			'attrs'   => array(),
			'tagMate' => ''
		);

		$tag['tagMate'] = $tag['pluginName']
		                . '-' . $tag['name']
		                . '#' . $tag['tagMate'];

		// Add trimming info
		$this->addTrimmingInfoToTag($tag);

		// Return the normalized tag
		return $tag;
	}


	/**
	* Sort tags by position and precedence
	*
	* @return void
	*/
	protected function sortTags()
	{
		// Unprocessed tags are stored as a stack, so their order is LIFO. We sort tags by position
		// *descending* so that they are processed in the order they appear in the text.
		usort($this->unprocessedTags, function (array $a, array $b)
		{
			// First we order by pos descending
			if ($a['pos'] !== $b['pos'])
			{
				return ($b['pos'] - $a['pos']);
			}

			if (!$a['len'] || !$b['len'])
			{
				// Zero-width end tags are ordered after zero-width start tags so that a pair that
				// ends with a zero-width tag has the opportunity to be closed before another pair
				// starts with a zero-width tag. For example, the pairs that would enclose the
				// letters X and Y in the string "XY". Self-closing tags are ordered between end
				// tags and start tags in an attempt to keep them out of tag pairs
				if (!$a['len'] && !$b['len'])
				{
					$order = array(
						self::END_TAG => 2,
						self::SELF_CLOSING_TAG => 1,
						self::START_TAG => 0
					);
					return $order[$a['type']] - $order[$b['type']];
				}

				// Here, we know that only one of $a or $b is a zero-width tags. Zero-width tags are
				// ordered after wider tags so that they have a chance to be processed before the
				// next character is consumed, which would force them to be skipped
				return ($a['len']) ? -1 : 1;
			}

			// Here we know that both tags start at the same position and have a length greater than
			// 0. We sort tags by length ascending, so that the longest matches are processed first
			if ($a['len'] !== $b['len'])
			{
				return ($a['len'] - $b['len']);
			}

			// Finally, if the tags start at the same position and are the same length, sort them by
			// id descending, which is our version of a stable sort (tags that were added first end
			// up being processed first)
			return ($b['id'] - $a['id']);
		});
	}

	/**
	* Pop the top unprocessed tag, put it in $this->currentTag and return it
	*
	* @return array
	*/
	protected function nextTag()
	{
		$this->currentTag = $this->popNextTag();

		return $this->currentTag;
	}

	/**
	* Peek at the top unprocessed tag without touching current tag
	*
	* @return array|bool Next tag to be processed, or FALSE if there's none left
	*/
	protected function peekNextTag()
	{
		return end($this->unprocessedTags);
	}

	/**
	* Pop at the top unprocessed tag without touching current tag
	*
	* @return array|bool Popped tag, or FALSE if there's none left
	*/
	protected function popNextTag()
	{
		return array_pop($this->unprocessedTags);
	}
}