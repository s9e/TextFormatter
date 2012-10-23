<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait TagHandling
{
	/**
	* Characters that are removed by the trim_* config directives
	* @link http://docs.php.net/manual/en/function.trim.php
	*/
	const TRIM_CHARLIST = " \n\r\t\0\x0B";

	/**
	* Add trimming info to a tag
	*
	* For tags where one of the trim* directive is set, the "pos" and "len" attributes are adjusted
	* to comprise the surrounding whitespace and two attributes, "trimBefore" and "trimAfter" are
	* added.
	*
	* Note that whitespace that is part of what a pass defines as a tag is left untouched.
	*
	* @param  array &$tag
	* @return void
	*/
	protected function addTrimmingInfoToTag(&$tag)
	{
		$tagConfig = $this->tagsConfig[$tag['name']];

		// Original: "  [b]  -text-  [/b]  "
		// Matches:  "XX[b]  -text-XX[/b]  "
		if (($tag['type']  &  self::START_TAG && !empty($tagConfig['trimBefore']))
		 || ($tag['type'] === self::END_TAG   && !empty($tagConfig['rtrimContent'])))
		{
			$spn = strspn(
				strrev(substr($this->text, 0, $tag['pos'])),
				self::TRIM_CHARLIST
			);

			$tag['trimBefore']  = $spn;
			$tag['len']        += $spn;
			$tag['pos']        -= $spn;
		}

		// Original: "  [b]  -text-  [/b]  "
		// Matches:  "  [b]XX-text-  [/b]XX"
		if (($tag['type'] === self::START_TAG && !empty($tagConfig['ltrimContent']))
		 || ($tag['type']  &  self::END_TAG   && !empty($tagConfig['trimAfter'])))
		{
			$spn = strspn(
				$this->text,
				self::TRIM_CHARLIST,
				$tag['pos'] + $tag['len']
			);

			$tag['trimAfter']  = $spn;
			$tag['len']       += $spn;
		}
	}

	/**
	* Create a START_TAG at given position matching given tag
	*
	* @param  array   $tag  Reference tag
	* @param  integer $pos  Created tag's position
	* @return array         Created tag
	*/
	protected function createStartTag(array $tag, $pos)
	{
		return $this->createMatchingTag($tag, $pos, self::START_TAG);
	}

	/**
	* Create an END_TAG at given position, for given START_TAG
	*
	* @param  array   $tag  Reference tag
	* @param  integer $pos  Created tag's position
	* @return array         Created tag
	*/
	protected function createEndTag(array $tag, $pos)
	{
		return $this->createMatchingTag($tag, $pos, self::END_TAG);
	}

	/**
	* Create a tag at given position matching given tag
	*
	* @param  array   $tag  Reference tag
	* @param  integer $pos  Created tag's position
	* @param  integer $type Created tag's type
	* @return array         Created tag
	*/
	protected function createMatchingTag(array $tag, $pos, $type)
	{
		$newTag = array(
			'id'     => -1,
			'name'   => $tag['name'],
			'pos'    => $pos,
			'len'    => 0,
			'type'   => $type,
			'attributes'  => ($type === self::START_TAG) ? $tag['attributes'] : array(),
			'tagMate'    => $tag['tagMate'],
			'pluginName' => $tag['pluginName']
		);

		$this->addTrimmingInfoToTag($newTag);

		return $newTag;
	}
}