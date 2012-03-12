<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use InvalidArgumentException,
    Iterator;

class Ruleset extends Collection
{
	/**
	* @var tagCollection The tagCollection instance used to validate tag names
	*/
	protected $tagCollection;

	/**
	* @param  TagCollection $tagCollection The tagCollection instance used to validate tag names
	*/
	public function __construct(TagCollection $tagCollection)
	{
		$this->tagCollection = $tagCollection;
	}

	/**
	* Validate and normalize the name of a tag
	*
	* @param  string $name Original name
	* @return string       Normalized name
	*/
	protected function validateName($name)
	{
		if (!$this->tagCollection->isValidName($name))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $name . "'");
		}

		return $this->tagCollection->normalizeName($name);
	}

	/**
	* Remove a subset of, or all the rules
	*
	* @param string $type Type of rules to clear
	*/
	public function clear($type = null)
	{
		if (isset($type))
		{
			$this->items[$type] = array();
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
		$this->items['allowChild'][] = $this->validateName($tagName);
	}

	/**
	* Add an allowDescendant rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function allowDescendant($tagName)
	{
		$this->items['allowDescendant'][] = $this->validateName($tagName);
	}

	/**
	* Add an closeAncestor rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function closeAncestor($tagName)
	{
		$this->items['closeAncestor'][] = $this->validateName($tagName);
	}

	/**
	* Add an closeParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function closeParent($tagName)
	{
		$this->items['closeParent'][] = $this->validateName($tagName);
	}

	/**
	* Add an denyChild rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function denyChild($tagName)
	{
		$this->items['denyChild'][] = $this->validateName($tagName);
	}

	/**
	* Add an denyDescendant rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function denyDescendant($tagName)
	{
		$this->items['denyDescendant'][] = $this->validateName($tagName);
	}

	/**
	* Add an reopenChild rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function reopenChild($tagName)
	{
		$this->items['reopenChild'][] = $this->validateName($tagName);
	}

	/**
	* Add an requireParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function requireParent($tagName)
	{
		$this->items['requireParent'][] = $this->validateName($tagName);
	}

	/**
	* Add an requireAncestor rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function requireAncestor($tagName)
	{
		$this->items['requireAncestor'][] = $this->validateName($tagName);
	}
}