<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

class Ruleset
{
	/**
	* @var array
	*/
	protected $rules = array();

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
}