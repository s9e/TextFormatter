<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMAttr;
use RuntimeException;
abstract class AVTHelper
{
	public static function parse($attrValue)
	{
		\preg_match_all('((*MARK:literal)(?:[^{]|\\{\\{)++|(*MARK:expression)\\{(?:[^}"\']|"[^"]*+"|\'[^\']*+\')++\\}|(*MARK:junk).++)s', $attrValue, $matches);
		$tokens  = array();
		foreach ($matches[0] as $i => $str)
			if ($matches['MARK'][$i] === 'expression')
				$tokens[] = array('expression', \substr($str, 1, -1));
			else
				$tokens[] = array('literal', \strtr($str, array('{{' => '{', '}}' => '}')));
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
	public static function toXSL($attrValue)
	{
		$xsl = '';
		foreach (self::parse($attrValue) as $_f6b3b659)
		{
			list($type, $content) = $_f6b3b659;
			if ($type === 'expression')
				$xsl .= '<xsl:value-of select="' . \htmlspecialchars($content, \ENT_COMPAT, 'UTF-8') . '"/>';
			elseif (\trim($content) !== $content)
				$xsl .= '<xsl:text>' . \htmlspecialchars($content, \ENT_NOQUOTES, 'UTF-8') . '</xsl:text>';
			else
				$xsl .= \htmlspecialchars($content, \ENT_NOQUOTES, 'UTF-8');
		}
		return $xsl;
	}
}