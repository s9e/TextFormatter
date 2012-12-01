<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Parser;

class Ruleset extends Collection implements ArrayAccess, ConfigProvider
{
	//==========================================================================
	// ArrayAccess methods
	//==========================================================================

	/**
	* Test whether a rule category exists
	*
	* @param  string $k Rule name, e.g. "allowChild" or "isTransparent"
	*/
	public function OffsetExists($k)
	{
		return isset($this->items[$k]);
	}

	/**
	* Return the content of a rule category
	*
	* @param  string $k Rule name, e.g. "allowChild" or "isTransparent"
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
	* @param  string $k Rule name, e.g. "allowChild" or "isTransparent"
	*/
	public function OffsetUnset($k)
	{
		return $this->clear($k);
	}

	//==========================================================================
	// Generic methods
	//==========================================================================

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$config = $this->items;

		// Remove rules that are not needed at parsing time. All of those are resolved when building
		// the allowedChildren and allowedDescendants bitfields
		unset($config['allowChild']);
		unset($config['allowDescendant']);
		unset($config['defaultChildRule']);
		unset($config['defaultDescendantRule']);
		unset($config['denyAll']);
		unset($config['denyChild']);
		unset($config['denyDescendant']);
		unset($config['requireParent']);

		// Pack boolean rules into a bitfield
		$bitValues = array(
			'autoClose'      => Parser::RULE_AUTO_CLOSE,
			'autoReopen'     => Parser::RULE_AUTO_REOPEN,
			'ignoreText'     => Parser::RULE_IGNORE_TEXT,
			'isTransparent'  => Parser::RULE_IS_TRANSPARENT,
			'noBrChild'      => Parser::RULE_NO_BR_CHILD,
			'noBrDescendant' => Parser::RULE_NO_BR_DESCENDANT,
			'trimWhitespace' => Parser::RULE_TRIM_WHITESPACE
		);

		$bitfield = 0;
		foreach ($bitValues as $ruleName => $bitValue)
		{
			if (!empty($config[$ruleName]))
			{
				$bitfield |= $bitValue;
			}

			unset($config[$ruleName]);
		}

		// In order to speed up lookups, we use the tag names as keys
		foreach ($config as $ruleName => $targets)
		{
			$config[$ruleName] = array_fill_keys($targets, 1);
		}

		// Add the bitfield to the config
		$config['flags'] = $bitfield;

		return $config;
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
	* Test whether given tag name is used as target for given rule
	*
	* @param  string $ruleName
	* @param  string $tagName
	* @return bool
	*/
	protected function hasTarget($ruleName, $tagName)
	{
		if (!isset($this->items[$ruleName]))
		{
			return false;
		}

		return in_array($tagName, $this->items[$ruleName], true);
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

	//==========================================================================
	// Rules
	//==========================================================================

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
	* Add an autoClose rule
	*
	* NOTE: this rule exists so that plugins don't have to specifically handle tags whose end tag
	*       may/must be omitted such as <hr> or [img]
	*
	* @param bool $bool Whether or not the tag should automatically be closed if its start tag is not followed by an end tag
	*/
	public function autoClose($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('autoClose() expects a boolean');
		}

		$this->items['autoClose'] = $bool;
	}

	/**
	* Add an autoReopen rule
	*
	* @param bool $bool Whether or not the tag should automatically be reopened if closed by an end tag of a different name
	*/
	public function autoReopen($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('autoReopen() expects a boolean');
		}

		$this->items['autoReopen'] = $bool;
	}

	/**
	* Add an closeAncestor rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function closeAncestor($tagName)
	{
		$tagName = TagName::normalize($tagName);

		if ($this->hasTarget('forceParent', $tagName))
		{
			throw new RuntimeException("Cannot set both closeAncestor and forceParent on '" . $tagName . "'");
		}

		$this->items['closeAncestor'][] = $tagName;
	}

	/**
	* Add an closeParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function closeParent($tagName)
	{
		$tagName = TagName::normalize($tagName);

		if ($this->hasTarget('forceParent', $tagName))
		{
			throw new RuntimeException("Cannot set both closeParent and forceParent on '" . $tagName . "'");
		}

		$this->items['closeParent'][] = $tagName;
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
	* Add a denyAll rule
	*
	* @param bool $bool Whether to disallow any children to this tag
	*/
	public function denyAll($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('denyAll() expects a boolean');
		}

		$this->items['denyAll'] = $bool;
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
	* Add a forceParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function forceParent($tagName)
	{
		$tagName = TagName::normalize($tagName);

		if ($this->hasTarget('closeParent', $tagName))
		{
			throw new RuntimeException("Cannot set both closeParent and forceParent on '" . $tagName . "'");
		}

		if ($this->hasTarget('closeAncestor', $tagName))
		{
			throw new RuntimeException("Cannot set both closeAncestor and forceParent on '" . $tagName . "'");
		}

		$this->items['forceParent'][] = $tagName;
	}

	/**
	* Add an ignoreText rule
	*
	* @param bool $bool Whether or not the tag should ignore text nodes
	*/
	public function ignoreText($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('ignoreText() expects a boolean');
		}

		$this->items['ignoreText'] = $bool;
	}

	/**
	* Add a isTransparent rule
	*
	* @param bool $bool Whether or not the tag should use the "transparent" content model
	*/
	public function isTransparent($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('isTransparent() expects a boolean');
		}

		$this->items['isTransparent'] = $bool;
	}

	/**
	* Add a noBrChild rule
	*
	* @param bool $bool Whether *not* to convert newlines to <br/> in child text nodes
	*/
	public function noBrChild($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('noBrChild() expects a boolean');
		}

		$this->items['noBrChild'] = $bool;
	}

	/**
	* Add a noBrDescendant rule
	*
	* @param bool $bool Whether *not* to convert newlines to <br/> in descendant text nodes
	*/
	public function noBrDescendant($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('noBrDescendant() expects a boolean');
		}

		$this->items['noBrDescendant'] = $bool;
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

	/**
	* Trim whitespace around tags
	*
	* @param bool $bool Whether whitespace around this tag should be trimmed
	*/
	public function trimWhitespace($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('trimWhitespace() expects a boolean');
		}

		$this->items['trimWhitespace'] = $bool;
	}
}