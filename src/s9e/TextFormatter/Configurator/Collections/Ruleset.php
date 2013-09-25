<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Parser;

/**
* @see docs/Rules.md
*/
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
		unset($config['denyChild']);
		unset($config['denyDescendant']);
		unset($config['requireParent']);

		// If noBrDescendant is true, noBrChild should be true as well
		if (!empty($config['noBrDescendant']))
		{
			$config['noBrChild'] = true;
		}

		// Pack boolean rules into a bitfield
		$bitValues = [
			'autoClose'        => Parser::RULE_AUTO_CLOSE,
			'autoReopen'       => Parser::RULE_AUTO_REOPEN,
			'breakParagraph'   => Parser::RULE_BREAK_PARAGRAPH,
			'createParagraphs' => Parser::RULE_CREATE_PARAGRAPHS,
			'ignoreSurroundingWhitespace' => Parser::RULE_TRIM_WHITESPACE,
			'ignoreTags'       => Parser::RULE_IGNORE_TAGS,
			'ignoreText'       => Parser::RULE_IGNORE_TEXT,
			'isTransparent'    => Parser::RULE_IS_TRANSPARENT,
			'noBrChild'        => Parser::RULE_NO_BR_CHILD,
			'noBrDescendant'   => Parser::RULE_NO_BR_DESCENDANT
		];

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
		foreach (['closeAncestor', 'closeParent', 'fosterParent'] as $ruleName)
		{
			if (isset($config[$ruleName]))
			{
				$targets = array_fill_keys($config[$ruleName], 1);

				$config[$ruleName] = new Variant($targets);
				$config[$ruleName]->set('JS', new Dictionary($targets));
			}
		}

		// Create the JavaScript config. Ensure that BBCode names are preserved
		$jsConfig = new Dictionary;

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
			$this->items = [];
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
	* Remove a specific rule, or all the rules of a given type
	*
	* @param  string $type    Type of rules to clear
	* @param  string $tagName Name of the target tag, or none to remove all rules of given type
	* @return void
	*/
	public function remove($type, $tagName = null)
	{
		if (isset($tagName))
		{
			$tagName = TagName::normalize($tagName);

			if (isset($this->items[$type]))
			{
				// Compute the difference between current list and our one tag name
				$this->items[$type] = array_diff(
					$this->items[$type],
					[$tagName]
				);

				if (empty($this->items[$type]))
				{
					// If the list is now empty, keep it neat and unset it
					unset($this->items[$type]);
				}
				else
				{
					// If the list still have names, keep it neat and rearrange keys
					$this->items[$type] = array_values($this->items[$type]);
				}
			}
		}
		else
		{
			unset($this->items[$type]);
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
	* Add a breakParagraph rule
	*
	* @param bool $bool Whether or not this tag breaks current paragraph if applicable
	*/
	public function breakParagraph($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('breakParagraph() expects a boolean');
		}

		$this->items['breakParagraph'] = $bool;
	}

	/**
	* Add a closeAncestor rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function closeAncestor($tagName)
	{
		$this->items['closeAncestor'][] = TagName::normalize($tagName);
	}

	/**
	* Add a closeParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function closeParent($tagName)
	{
		$this->items['closeParent'][] = TagName::normalize($tagName);
	}

	/**
	* Add a createParagraphs rule
	*
	* @param bool $bool Whether or not paragraphs should automatically be created to handle content
	*/
	public function createParagraphs($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('createParagraphs() expects a boolean');
		}

		$this->items['createParagraphs'] = $bool;
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
	* Add a denyChild rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function denyChild($tagName)
	{
		$this->items['denyChild'][] = TagName::normalize($tagName);
	}

	/**
	* Add a denyDescendant rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function denyDescendant($tagName)
	{
		$this->items['denyDescendant'][] = TagName::normalize($tagName);
	}

	/**
	* Add a fosterParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function fosterParent($tagName)
	{
		$this->items['fosterParent'][] = TagName::normalize($tagName);
	}

	/**
	* Ignore (some) whitespace around tags
	*
	* When true, some whitespace around this tag will be ignored (not transformed to line breaks.)
	* Up to 2 lines outside of a tag pair and 1 line inside of it:
	*     {2 lines}{START_TAG}{1 line}{CONTENT}{1 line}{END_TAG}{2 lines}
	*
	* @param bool $bool Whether whitespace around this tag should be ignored
	*/
	public function ignoreSurroundingWhitespace($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('ignoreSurroundingWhitespace() expects a boolean');
		}

		$this->items['ignoreSurroundingWhitespace'] = $bool;
	}

	/**
	* Add an ignoreTags rule
	*
	* @param bool $bool Whether to silently ignore all tags until current tag is closed
	*/
	public function ignoreTags($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('ignoreTags() expects a boolean');
		}

		$this->items['ignoreTags'] = $bool;
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
	* Add a requireParent rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function requireParent($tagName)
	{
		$this->items['requireParent'][] = TagName::normalize($tagName);
	}

	/**
	* Add a requireAncestor rule
	*
	* @param string $tagName Name of the target tag
	*/
	public function requireAncestor($tagName)
	{
		$this->items['requireAncestor'][] = TagName::normalize($tagName);
	}
}