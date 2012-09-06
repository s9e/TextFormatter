<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use s9e\TextFormatter\ConfigBuilder\Validators\TagName;

class Ruleset extends Collection
{
	/**
	* Remove a subset of, or all the rules
	*
	* @param string $type Type of rules to clear
	*/
	public function clear($type = null)
	{
		if (isset($type))
		{
			unset($this->items[$type]);
		}
		else
		{
			$this->items = array();
		}
	}

	/**
	* Add an allowChild rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function allowChild($tagName)
	{
		$this->items['allowChild'][] = TagName::normalize($tagName);
	}

	/**
	* Add an allowDescendant rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function allowDescendant($tagName)
	{
		$this->items['allowDescendant'][] = TagName::normalize($tagName);
	}

	/**
	* Add an closeAncestor rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function closeAncestor($tagName)
	{
		$this->items['closeAncestor'][] = TagName::normalize($tagName);
	}

	/**
	* Add an closeParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function closeParent($tagName)
	{
		$this->items['closeParent'][] = TagName::normalize($tagName);
	}

	/**
	* Add an denyChild rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function denyChild($tagName)
	{
		$this->items['denyChild'][] = TagName::normalize($tagName);
	}

	/**
	* Add an denyDescendant rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function denyDescendant($tagName)
	{
		$this->items['denyDescendant'][] = TagName::normalize($tagName);
	}

	/**
	* Add an reopenChild rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function reopenChild($tagName)
	{
		$this->items['reopenChild'][] = TagName::normalize($tagName);
	}

	/**
	* Add an requireParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function requireParent($tagName)
	{
		$this->items['requireParent'][] = TagName::normalize($tagName);
	}

	/**
	* Add an requireAncestor rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function requireAncestor($tagName)
	{
		$this->items['requireAncestor'][] = TagName::normalize($tagName);
	}
}