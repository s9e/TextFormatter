<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\ConfigBuilder\ConfigProvider;
use s9e\TextFormatter\ConfigBuilder\Validators\TagName;

class Ruleset extends Collection implements ArrayAccess, ConfigProvider
{
	/**
	* Test whether a rule category exists
	*
	* @param  string $k Rule name, e.g. "allowChild" or "inheritRules"
	*/
	public function OffsetExists($k)
	{
		return isset($this->items[$k]);
	}

	/**
	* Return the content of a rule category
	*
	* @param  string $k Rule name, e.g. "allowChild" or "inheritRules"
	* @return mixed
	*/
	public function OffsetGet($k)
	{
		return $this->items[$k];
	}

	/**
	* Not supported
	*/
	public function OffsetSet($k, $v)
	{
		throw new RuntimeException('Not supported');
	}

	/**
	* Clear a subset of the rules
	*
	* @see clear()
	*
	* @param  string $k Rule name, e.g. "allowChild" or "inheritRules"
	*/
	public function OffsetUnset($k)
	{
		return $this->clear($k);
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
			unset($this->items[$type]);
		}
		else
		{
			$this->items = array();
		}
	}

	/**
	* Merge a set of rules into this collection
	*
	* @param array|Ruleset $rules 2D array of rule definitions, or instance of Ruleset
	*/
	public function merge($rules)
	{
		if (!is_array($rules)
		 && !($rules instanceof self))
		{
			throw new InvalidArgumentException('merge() expects an array or an instance of Ruleset');
		}

		foreach ($rules as $action => $value)
		{
			if (is_array($value))
			{
				foreach ($value as $tagName)
				{
					$this->$action($tagName);
				}
			}
			else
			{
				$this->$action($value);
			}
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
	* Set the default child rule
	*
	* @param string $rule Either "allow" or "deny"
	*/
	public function defaultChildRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
		{
			throw new InvalidArgumentException("defaultChildRule() only accepts 'allow' or 'deny'");
		}

		$this->items['defaultChildRule'] = $rule;
	}

	/**
	* Set the default descendant rule
	*
	* @param string $rule Either "allow" or "deny"
	*/
	public function defaultDescendantRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
		{
			throw new InvalidArgumentException("defaultDescendantRule() only accepts 'allow' or 'deny'");
		}

		$this->items['defaultDescendantRule'] = $rule;
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
	* Add a disallowAtRoot rule
	*
	* @param bool $bool Whether to disallow the tag to be used at the root of a message
	*/
	public function disallowAtRoot($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('disallowAtRoot() expects a boolean');
		}

		$this->items['disallowAtRoot'] = $bool;
	}

	/**
	* Add a inheritRules rule
	*
	* @param bool $bool Whether or not the tag should use the "transparent" content model
	*/
	public function inheritRules($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('inheritRules() expects a boolean');
		}

		$this->items['inheritRules'] = $bool;
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