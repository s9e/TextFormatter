<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
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
class Ruleset extends Collection implements ArrayAccess, ConfigProvider
{
	protected $rules = array(
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
	);
	public function __call($methodName, array $args)
	{
		if (!isset($this->rules[$methodName]))
			throw new BadMethodCallException("Undefined method '" . $methodName . "'");
		\array_unshift($args, $methodName);
		\call_user_func_array(array($this, $this->rules[$methodName]), $args);
		return $this;
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
		unset($config['denyChild']);
		unset($config['denyDescendant']);
		unset($config['requireParent']);
		$bitValues = array(
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
		);
		$bitfield = 0;
		foreach ($bitValues as $ruleName => $bitValue)
		{
			if (!empty($config[$ruleName]))
				$bitfield |= $bitValue;
			unset($config[$ruleName]);
		}
		foreach (array('closeAncestor', 'closeParent', 'fosterParent') as $ruleName)
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
			throw new InvalidArgumentException('Cannot remove ' . $type);
		if (isset($tagName))
		{
			$tagName = TagName::normalize($tagName);
			if (isset($this->items[$type]))
			{
				$this->items[$type] = \array_diff(
					$this->items[$type],
					array($tagName)
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
	protected function addBooleanRule($ruleName, $bool = \true)
	{
		if (!\is_bool($bool))
			throw new InvalidArgumentException($ruleName . '() expects a boolean');
		$this->items[$ruleName] = $bool;
	}
	protected function addTargetedRule($ruleName, $tagName)
	{
		$this->items[$ruleName][] = TagName::normalize($tagName);
	}
}