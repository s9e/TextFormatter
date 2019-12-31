<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use ArrayAccess;
use BadMethodCallException;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Parser;

/**
* @method void allowChild(string $tagName)
* @method void allowDescendant(string $tagName)
* @method void autoClose(bool $bool = true)
* @method void autoReopen(bool $bool = true)
* @method void breakParagraph(bool $bool = true)
* @method void closeAncestor(string $tagName)
* @method void closeParent(string $tagName)
* @method void createChild(string $tagName)
* @method void createParagraphs(bool $bool = true)
* @method void denyChild(string $tagName)
* @method void denyDescendant(string $tagName)
* @method void disableAutoLineBreaks(bool $bool = true)
* @method void enableAutoLineBreaks(bool $bool = true)
* @method void fosterParent(string $tagName)
* @method void ignoreSurroundingWhitespace(bool $bool = true)
* @method void ignoreTags(bool $bool = true)
* @method void ignoreText(bool $bool = true)
* @method void isTransparent(bool $bool = true)
* @method void preventLineBreaks(bool $bool = true)
* @method void requireParent(string $tagName)
* @method void requireAncestor(string $tagName)
* @method void suspendAutoLineBreaks(bool $bool = true)
* @method void trimFirstLine(bool $bool = true)
* @see /docs/Rules.md
*/
class Ruleset extends Collection implements ArrayAccess, ConfigProvider
{
	/**
	* @var array Supported rules and the method used to add them
	*/
	protected $rules = [
		'allowChild'                  => 'addTargetedRule',
		'allowDescendant'             => 'addTargetedRule',
		'autoClose'                   => 'addBooleanRule',
		'autoReopen'                  => 'addBooleanRule',
		'breakParagraph'              => 'addBooleanRule',
		'closeAncestor'               => 'addTargetedRule',
		'closeParent'                 => 'addTargetedRule',
		'createChild'                 => 'addTargetedRule',
		'createParagraphs'            => 'addBooleanRule',
		'denyChild'                   => 'addTargetedRule',
		'denyDescendant'              => 'addTargetedRule',
		'disableAutoLineBreaks'       => 'addBooleanRule',
		'enableAutoLineBreaks'        => 'addBooleanRule',
		'fosterParent'                => 'addTargetedRule',
		'ignoreSurroundingWhitespace' => 'addBooleanRule',
		'ignoreTags'                  => 'addBooleanRule',
		'ignoreText'                  => 'addBooleanRule',
		'isTransparent'               => 'addBooleanRule',
		'preventLineBreaks'           => 'addBooleanRule',
		'requireParent'               => 'addTargetedRule',
		'requireAncestor'             => 'addTargetedRule',
		'suspendAutoLineBreaks'       => 'addBooleanRule',
		'trimFirstLine'               => 'addBooleanRule'
	];

	/**
	* Add a rule to this set
	*
	* @param  string $methodName Rule name
	* @param  array  $args       Arguments used to add given rule
	* @return self
	*/
	public function __call($methodName, array $args)
	{
		if (!isset($this->rules[$methodName]))
		{
			throw new BadMethodCallException("Undefined method '" . $methodName . "'");
		}

		array_unshift($args, $methodName);
		call_user_func_array([$this, $this->rules[$methodName]], $args);

		return $this;
	}

	//==========================================================================
	// ArrayAccess methods
	//==========================================================================

	/**
	* Test whether a rule category exists
	*
	* @param  string $k Rule name, e.g. "allowChild" or "isTransparent"
	*/
	public function offsetExists($k)
	{
		return isset($this->items[$k]);
	}

	/**
	* Return the content of a rule category
	*
	* @param  string $k Rule name, e.g. "allowChild" or "isTransparent"
	* @return mixed
	*/
	public function offsetGet($k)
	{
		return $this->items[$k];
	}

	/**
	* Not supported
	*/
	public function offsetSet($k, $v)
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
	public function offsetUnset($k)
	{
		return $this->remove($k);
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
		// the allowed bitfields
		unset($config['allowChild']);
		unset($config['allowDescendant']);
		unset($config['denyChild']);
		unset($config['denyDescendant']);
		unset($config['requireParent']);

		// Pack boolean rules into a bitfield
		$bitValues = [
			'autoClose'                   => Parser::RULE_AUTO_CLOSE,
			'autoReopen'                  => Parser::RULE_AUTO_REOPEN,
			'breakParagraph'              => Parser::RULE_BREAK_PARAGRAPH,
			'createParagraphs'            => Parser::RULE_CREATE_PARAGRAPHS,
			'disableAutoLineBreaks'       => Parser::RULE_DISABLE_AUTO_BR,
			'enableAutoLineBreaks'        => Parser::RULE_ENABLE_AUTO_BR,
			'ignoreSurroundingWhitespace' => Parser::RULE_IGNORE_WHITESPACE,
			'ignoreTags'                  => Parser::RULE_IGNORE_TAGS,
			'ignoreText'                  => Parser::RULE_IGNORE_TEXT,
			'isTransparent'               => Parser::RULE_IS_TRANSPARENT,
			'preventLineBreaks'           => Parser::RULE_PREVENT_BR,
			'suspendAutoLineBreaks'       => Parser::RULE_SUSPEND_AUTO_BR,
			'trimFirstLine'               => Parser::RULE_TRIM_FIRST_LINE
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
				$config[$ruleName] = new Dictionary($targets);
			}
		}

		// Add the bitfield to the config
		$config['flags'] = $bitfield;

		return $config;
	}

	/**
	* Merge a set of rules into this collection
	*
	* @param array|Ruleset $rules     2D array of rule definitions, or instance of Ruleset
	* @param bool          $overwrite Whether to overwrite scalar rules (e.g. boolean rules)
	*/
	public function merge($rules, $overwrite = true)
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
			elseif ($overwrite || !isset($this->items[$action]))
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
		if (preg_match('(^default(?:Child|Descendant)Rule)', $type))
		{
			throw new InvalidArgumentException('Cannot remove ' . $type);
		}

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
	* Add a boolean rule
	*
	* @param  string $ruleName Name of the rule
	* @param  bool   $bool     Whether to enable or disable the rule
	* @return self
	*/
	protected function addBooleanRule($ruleName, $bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException($ruleName . '() expects a boolean');
		}

		$this->items[$ruleName] = $bool;
	}

	/**
	* Add a targeted rule
	*
	* @param  string $ruleName Name of the rule
	* @param  string $tagName  Name of the target tag
	* @return self
	*/
	protected function addTargetedRule($ruleName, $tagName)
	{
		$this->items[$ruleName][] = TagName::normalize($tagName);
	}
}