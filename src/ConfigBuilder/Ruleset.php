<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use Iterator;

class Ruleset implements Iterator
{
	/**
	* @var array
	*/
	protected $rules = array();

	/**
	* Remove all the rules
	*/
	public function clear()
	{
		$this->rules = array();
	}

	/**
	* Return all the rules as a 2D array
	*/
	public function get()
	{
		return $this->rules;
	}

	public function allowChild($tagName)
	{
		$this->rules['allowChild'][] = Tag::normalizeName($tagName);
	}

	public function allowDescendant($tagName)
	{
		$this->rules['allowDescendant'][] = Tag::normalizeName($tagName);
	}

	public function closeAncestor($tagName)
	{
		$this->rules['closeAncestor'][] = Tag::normalizeName($tagName);
	}

	public function closeParent($tagName)
	{
		$this->rules['closeParent'][] = Tag::normalizeName($tagName);
	}

	public function denyChild($tagName)
	{
		$this->rules['denyChild'][] = Tag::normalizeName($tagName);
	}

	public function denyDescendant($tagName)
	{
		$this->rules['denyDescendant'][] = Tag::normalizeName($tagName);
	}

	public function reopenChild($tagName)
	{
		$this->rules['reopenChild'][] = Tag::normalizeName($tagName);
	}

	public function requireParent($tagName)
	{
		$this->rules['requireParent'][] = Tag::normalizeName($tagName);
	}

	public function requireAncestor($tagName)
	{
		$this->rules['requireAncestor'][] = Tag::normalizeName($tagName);
	}

	//==========================================================================
	// Iterator stuff
	//==========================================================================

	public function rewind()
	{
		reset($this->rules);
	}

	public function current()
	{
		return current($this->rules);
	}

	function key()
	{
		return key($this->rules);
	}

	function next()
	{
		return next($this->rules);
	}

	function valid()
	{
		return (key($this->rules) !== null);
	}
}