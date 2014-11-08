<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use RuntimeException;

abstract class AVTHelper
{
	public static function parse($attrValue)
	{
		$tokens  = array();
		$attrLen = \strlen($attrValue);

		$pos = 0;
		while ($pos < $attrLen)
		{
			if ($attrValue[$pos] === '{')
			{
				if (\substr($attrValue, $pos, 2) === '{{')
				{
					$tokens[] = array('literal', '{');
					$pos += 2;

					continue;
				}

				++$pos;

				$expr = '';
				while ($pos < $attrLen)
				{
					$spn = \strcspn($attrValue, '\'"}', $pos);
					if ($spn)
					{
						$expr .= \substr($attrValue, $pos, $spn);
						$pos += $spn;
					}

					if ($pos >= $attrLen)
						throw new RuntimeException('Unterminated XPath expression');

					$c = $attrValue[$pos];
					++$pos;

					if ($c === '}')
						break;

					$quotePos = \strpos($attrValue, $c, $pos);
					if ($quotePos === \false)
						throw new RuntimeException('Unterminated XPath expression');

					$expr .= $c . \substr($attrValue, $pos, $quotePos + 1 - $pos);
					$pos = 1 + $quotePos;
				}

				$tokens[] = array('expression', $expr);
			}

			$spn = \strcspn($attrValue, '{', $pos);
			if ($spn)
			{
				$str = \substr($attrValue, $pos, $spn);

				$str = \str_replace('}}', '}', $str);

				$tokens[] = array('literal', $str);
				$pos += $spn;
			}
		}

		return $tokens;
	}

	public static function replace(DOMAttr $attribute, $callback)
	{
		$tokens = self::parse($attribute->value);
		foreach ($tokens as $k => $token)
			$tokens[$k] = $callback($token);

		$attribute->value = \htmlspecialchars(self::serialize($tokens), \ENT_NOQUOTES, 'UTF-8');
	}

	public static function serialize(array $tokens)
	{
		$attrValue = '';
		foreach ($tokens as $token)
			if ($token[0] === 'literal')
				$attrValue .= \preg_replace('([{}])', '$0$0', $token[1]);
			elseif ($token[0] === 'expression')
				$attrValue .= '{' . $token[1] . '}';
			else
				throw new RuntimeException('Unknown token type');

		return $attrValue;
	}
}