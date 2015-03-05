<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Parser;

class Ruleset extends Collection implements ArrayAccess, ConfigProvider
{
	public function __construct()
	{
		$this->defaultChildRule('allow');
		$this->defaultDescendantRule('allow');
	}

	public function offsetExists($k)
	{
		return isset($this->items[$k]);
	}

	public function offsetGet($k)
	{
		return $this->items[$k];
	}

	public function offsetSet($k, $v)
	{
		throw new RuntimeException('Not supported');
	}

	public function offsetUnset($k)
	{
		return $this->remove($k);
	}

	public function asConfig()
	{
		$config = $this->items;

		unset($config['allowChild']);
		unset($config['allowDescendant']);
		unset($config['defaultChildRule']);
		unset($config['defaultDescendantRule']);
		unset($config['denyChild']);
		unset($config['denyDescendant']);
		unset($config['requireParent']);

		$bitValues = [
			'autoClose'                   => Parser::RULE_AUTO_CLOSE,
			'autoReopen'                  => Parser::RULE_AUTO_REOPEN,
			'breakParagraph'              => Parser::RULE_BREAK_PARAGRAPH,
			'createParagraphs'            => Parser::RULE_CREATE_PARAGRAPHS,
			'disableAutoLineBreaks'       => Parser::RULE_DISABLE_AUTO_BR,
			'enableAutoLineBreaks'        => Parser::RULE_ENABLE_AUTO_BR,
			'ignoreSurroundingWhitespace' => Parser::RULE_TRIM_WHITESPACE,
			'ignoreTags'                  => Parser::RULE_IGNORE_TAGS,
			'ignoreText'                  => Parser::RULE_IGNORE_TEXT,
			'isTransparent'               => Parser::RULE_IS_TRANSPARENT,
			'preventLineBreaks'           => Parser::RULE_PREVENT_BR,
			'suspendAutoLineBreaks'       => Parser::RULE_SUSPEND_AUTO_BR
		];

		$bitfield = 0;
		foreach ($bitValues as $ruleName => $bitValue)
		{
			if (!empty($config[$ruleName]))
				$bitfield |= $bitValue;

			unset($config[$ruleName]);
		}

		foreach (['closeAncestor', 'closeParent', 'fosterParent'] as $ruleName)
			if (isset($config[$ruleName]))
			{
				$targets = \array_fill_keys($config[$ruleName], 1);
				$config[$ruleName] = new Dictionary($targets);
			}

		$config['flags'] = $bitfield;

		return $config;
	}

	public function merge($rules, $overwrite = \true)
	{
		if (!\is_array($rules)
		 && !($rules instanceof self))
			throw new InvalidArgumentException('merge() expects an array or an instance of Ruleset');

		foreach ($rules as $action => $value)
			if (\is_array($value))
				foreach ($value as $tagName)
					$this->$action($tagName);
			elseif ($overwrite || !isset($this->items[$action]))
				$this->$action($value);
	}

	public function remove($type, $tagName = \null)
	{
		if (\preg_match('(^default(?:Child|Descendant)Rule)', $type))
			throw new RuntimeException('Cannot remove ' . $type);

		if (isset($tagName))
		{
			$tagName = TagName::normalize($tagName);

			if (isset($this->items[$type]))
			{
				$this->items[$type] = \array_diff(
					$this->items[$type],
					[$tagName]
				);

				if (empty($this->items[$type]))
					unset($this->items[$type]);
				else
					$this->items[$type] = \array_values($this->items[$type]);
			}
		}
		else
			unset($this->items[$type]);
	}

	protected function addBooleanRule($ruleName, $bool)
	{
		if (!\is_bool($bool))
			throw new InvalidArgumentException($ruleName . '() expects a boolean');

		$this->items[$ruleName] = $bool;

		return $this;
	}

	protected function addTargetedRule($ruleName, $tagName)
	{
		$this->items[$ruleName][] = TagName::normalize($tagName);

		return $this;
	}

	public function allowChild($tagName)
	{
		return $this->addTargetedRule('allowChild', $tagName);
	}

	public function allowDescendant($tagName)
	{
		return $this->addTargetedRule('allowDescendant', $tagName);
	}

	public function autoClose($bool = \true)
	{
		return $this->addBooleanRule('autoClose', $bool);
	}

	public function autoReopen($bool = \true)
	{
		return $this->addBooleanRule('autoReopen', $bool);
	}

	public function breakParagraph($bool = \true)
	{
		return $this->addBooleanRule('breakParagraph', $bool);
	}

	public function closeAncestor($tagName)
	{
		return $this->addTargetedRule('closeAncestor', $tagName);
	}

	public function closeParent($tagName)
	{
		return $this->addTargetedRule('closeParent', $tagName);
	}

	public function createParagraphs($bool = \true)
	{
		return $this->addBooleanRule('createParagraphs', $bool);
	}

	public function defaultChildRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
			throw new InvalidArgumentException("defaultChildRule() only accepts 'allow' or 'deny'");

		$this->items['defaultChildRule'] = $rule;

		return $this;
	}

	public function defaultDescendantRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
			throw new InvalidArgumentException("defaultDescendantRule() only accepts 'allow' or 'deny'");

		$this->items['defaultDescendantRule'] = $rule;

		return $this;
	}

	public function denyChild($tagName)
	{
		return $this->addTargetedRule('denyChild', $tagName);
	}

	public function denyDescendant($tagName)
	{
		return $this->addTargetedRule('denyDescendant', $tagName);
	}

	public function disableAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('disableAutoLineBreaks', $bool);
	}

	public function enableAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('enableAutoLineBreaks', $bool);
	}

	public function fosterParent($tagName)
	{
		return $this->addTargetedRule('fosterParent', $tagName);
	}

	public function ignoreSurroundingWhitespace($bool = \true)
	{
		return $this->addBooleanRule('ignoreSurroundingWhitespace', $bool);
	}

	public function ignoreTags($bool = \true)
	{
		return $this->addBooleanRule('ignoreTags', $bool);
	}

	public function ignoreText($bool = \true)
	{
		return $this->addBooleanRule('ignoreText', $bool);
	}

	public function isTransparent($bool = \true)
	{
		return $this->addBooleanRule('isTransparent', $bool);
	}

	public function preventLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('preventLineBreaks', $bool);
	}

	public function requireParent($tagName)
	{
		return $this->addTargetedRule('requireParent', $tagName);
	}

	public function requireAncestor($tagName)
	{
		return $this->addTargetedRule('requireAncestor', $tagName);
	}

	public function suspendAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('suspendAutoLineBreaks', $bool);
	}
}