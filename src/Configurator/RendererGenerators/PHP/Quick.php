<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
class Quick
{
	public static function getSource(array $compiledTemplates)
	{
		$map = array();
		$tagNames = array();
		$unsupported = array();
		foreach ($compiledTemplates as $tagName => $php)
		{
			if (\preg_match('(^(?:br|[ieps])$)', $tagName))
				continue;
			$rendering = self::getRenderingStrategy($php);
			if ($rendering === \false)
			{
				$unsupported[] = $tagName;
				continue;
			}
			foreach ($rendering as $i => $_562c18b7)
			{
				list($strategy, $replacement) = $_562c18b7;
				$match = (($i) ? '/' : '') . $tagName;
				$map[$strategy][$match] = $replacement;
			}
			if (!isset($rendering[1]))
				$tagNames[] = $tagName;
		}
		$php = array();
		if (isset($map['static']))
			$php[] = '	private static $static=' . self::export($map['static']) . ';';
		if (isset($map['dynamic']))
			$php[] = '	private static $dynamic=' . self::export($map['dynamic']) . ';';
		if (isset($map['php']))
		{
			list($quickBranches, $quickSource) = self::generateBranchTable('$qb', $map['php']);
			$php[] = '	private static $attributes;';
			$php[] = '	private static $quickBranches=' . self::export($quickBranches) . ';';
		}
		if (!empty($unsupported))
		{
			$regexp = '(<' . RegexpBuilder::fromList($unsupported, array('useLookahead' => \true)) . '[ />])';
			$php[] = '	public $quickRenderingTest=' . \var_export($regexp, \true) . ';';
		}
		$php[] = '';
		$php[] = '	protected function renderQuick($xml)';
		$php[] = '	{';
		$php[] = '		$xml = $this->decodeSMP($xml);';
		if (isset($map['php']))
			$php[] = '		self::$attributes = array();';
		$regexp  = '(<(?:(?!/)(';
		$regexp .= ($tagNames) ? RegexpBuilder::fromList($tagNames) : '(?!)';
		$regexp .= ')(?: [^>]*)?>.*?</\\1|(/?(?!br/|p>)[^ />]+)[^>]*?(/)?)>)';
		$php[] = '		$html = preg_replace_callback(';
		$php[] = '			' . \var_export($regexp, \true) . ',';
		$php[] = "			array(\$this, 'quick'),";
		$php[] = '			preg_replace(';
		$php[] = "				'(<[eis]>[^<]*</[eis]>)',";
		$php[] = "				'',";
		$php[] = '				substr($xml, 1 + strpos($xml, \'>\'), -4)';
		$php[] = '			)';
		$php[] = '		);';
		$php[] = '';
		$php[] = "		return str_replace('<br/>', '<br>', \$html);";
		$php[] = '	}';
		$php[] = '';
		$php[] = '	protected function quick($m)';
		$php[] = '	{';
		$php[] = '		if (isset($m[2]))';
		$php[] = '		{';
		$php[] = '			$id = $m[2];';
		$php[] = '';
		$php[] = '			if (isset($m[3]))';
		$php[] = '			{';
		$php[] = '				unset($m[3]);';
		$php[] = '';
		$php[] = '				$m[0] = substr($m[0], 0, -2) . \'>\';';
		$php[] = '				$html = $this->quick($m);';
		$php[] = '';
		$php[] = '				$m[0] = \'</\' . $id . \'>\';';
		$php[] = '				$m[2] = \'/\' . $id;';
		$php[] = '				$html .= $this->quick($m);';
		$php[] = '';
		$php[] = '				return $html;';
		$php[] = '			}';
		$php[] = '		}';
		$php[] = '		else';
		$php[] = '		{';
		$php[] = '			$id = $m[1];';
		$php[] = '';
		$php[] = '			$lpos = 1 + strpos($m[0], \'>\');';
		$php[] = '			$rpos = strrpos($m[0], \'<\');';
		$php[] = '			$textContent = substr($m[0], $lpos, $rpos - $lpos);';
		$php[] = '';
		$php[] = '			if (strpos($textContent, \'<\') !== false)';
		$php[] = '			{';
		$php[] = '				throw new \\RuntimeException;';
		$php[] = '			}';
		$php[] = '';
		$php[] = '			$textContent = htmlspecialchars_decode($textContent);';
		$php[] = '		}';
		$php[] = '';
		if (isset($map['static']))
		{
			$php[] = '		if (isset(self::$static[$id]))';
			$php[] = '		{';
			$php[] = '			return self::$static[$id];';
			$php[] = '		}';
			$php[] = '';
		}
		if (isset($map['dynamic']))
		{
			$php[] = '		if (isset(self::$dynamic[$id]))';
			$php[] = '		{';
			$php[] = '			list($match, $replace) = self::$dynamic[$id];';
			$php[] = '			return preg_replace($match, $replace, $m[0], 1, $cnt);';
			$php[] = '		}';
			$php[] = '';
		}
		if (isset($map['php']))
		{
			$php[] = '		if (!isset(self::$quickBranches[$id]))';
			$php[] = '		{';
		}
		$condition = "\$id[0] === '!' || \$id[0] === '?'";
		if (!empty($unsupported))
		{
			$regexp = '(^/?' . RegexpBuilder::fromList($unsupported) . '$)';
			$condition .= ' || preg_match(' . \var_export($regexp, \true) . ', $id)';
		}
		$php[] = '			if (' . $condition . ')';
		$php[] = '			{';
		$php[] = '				throw new \\RuntimeException;';
		$php[] = '			}';
		$php[] = "			return '';";
		if (isset($map['php']))
		{
			$php[] = '		}';
			$php[] = '';
			$php[] = '		$attributes = array();';
			$php[] = '		if (strpos($m[0], \'="\') !== false)';
			$php[] = '		{';
			$php[] = '			preg_match_all(\'(([^ =]++)="([^"]*))S\', substr($m[0], 0, strpos($m[0], \'>\')), $matches);';
			$php[] = '			foreach ($matches[1] as $i => $attrName)';
			$php[] = '			{';
			$php[] = '				$attributes[$attrName] = $matches[2][$i];';
			$php[] = '			}';
			$php[] = '		}';
			$php[] = '';
			$php[] = '		$qb = self::$quickBranches[$id];';
			$php[] = '		' . $quickSource;
			$php[] = '';
			$php[] = '		return $html;';
		}
		$php[] = '	}';
		return \implode("\n", $php);
	}
	protected static function export(array $arr)
	{
		\ksort($arr);
		$entries = array();
		$naturalKey = 0;
		foreach ($arr as $k => $v)
		{
			$entries[] = (($k === $naturalKey) ? '' : \var_export($k, \true) . '=>')
			           . ((\is_array($v)) ? self::export($v) : \var_export($v, \true));
			$naturalKey = $k + 1;
		}
		return 'array(' . \implode(',', $entries) . ')';
	}
	public static function getRenderingStrategy($php)
	{
		$chunks = \explode('$this->at($node);', $php);
		$renderings = array();
		if (\count($chunks) <= 2)
		{
			foreach ($chunks as $k => $chunk)
			{
				$rendering = self::getStaticRendering($chunk);
				if ($rendering !== \false)
				{
					$renderings[$k] = array('static', $rendering);
					continue;
				}
				if ($k === 0)
				{
					$rendering = self::getDynamicRendering($chunk);
					if ($rendering !== \false)
					{
						$renderings[$k] = array('dynamic', $rendering);
						continue;
					}
				}
				$renderings[$k] = \false;
			}
			if (!\in_array(\false, $renderings, \true))
				return $renderings;
		}
		$phpRenderings = self::getQuickRendering($php);
		if ($phpRenderings === \false)
			return \false;
		foreach ($phpRenderings as $i => $phpRendering)
			if (!isset($renderings[$i]) || $renderings[$i] === \false)
				$renderings[$i] = array('php', $phpRendering);
		return $renderings;
	}
	protected static function getQuickRendering($php)
	{
		if (\preg_match('(\\$this->at\\((?!\\$node\\);))', $php))
			return \false;
		$tokens   = \token_get_all('<?php ' . $php);
		$tokens[] = array(0, '');
		\array_shift($tokens);
		$cnt = \count($tokens);
		$branch = array(
			'braces'      => -1,
			'branches'    => array(),
			'head'        => '',
			'passthrough' => 0,
			'statement'   => '',
			'tail'        => ''
		);
		$braces = 0;
		$i = 0;
		do
		{
			if ($tokens[$i    ][0] === \T_VARIABLE
			 && $tokens[$i    ][1] === '$this'
			 && $tokens[$i + 1][0] === \T_OBJECT_OPERATOR
			 && $tokens[$i + 2][0] === \T_STRING
			 && $tokens[$i + 2][1] === 'at'
			 && $tokens[$i + 3]    === '('
			 && $tokens[$i + 4][0] === \T_VARIABLE
			 && $tokens[$i + 4][1] === '$node'
			 && $tokens[$i + 5]    === ')'
			 && $tokens[$i + 6]    === ';')
			{
				if (++$branch['passthrough'] > 1)
					return \false;
				$i += 6;
				continue;
			}
			$key = ($branch['passthrough']) ? 'tail' : 'head';
			$branch[$key] .= (\is_array($tokens[$i])) ? $tokens[$i][1] : $tokens[$i];
			if ($tokens[$i] === '{')
			{
				++$braces;
				continue;
			}
			if ($tokens[$i] === '}')
			{
				--$braces;
				if ($branch['braces'] === $braces)
				{
					$branch[$key] = \substr($branch[$key], 0, -1);
					$branch =& $branch['parent'];
					$j = $i;
					while ($tokens[++$j][0] === \T_WHITESPACE);
					if ($tokens[$j][0] !== \T_ELSEIF
					 && $tokens[$j][0] !== \T_ELSE)
					{
						$passthroughs = self::getBranchesPassthrough($branch['branches']);
						if ($passthroughs === array(0))
						{
							foreach ($branch['branches'] as $child)
								$branch['head'] .= $child['statement'] . '{' . $child['head'] . '}';
							$branch['branches'] = array();
							continue;
						}
						if ($passthroughs === array(1))
						{
							++$branch['passthrough'];
							continue;
						}
						return \false;
					}
				}
				continue;
			}
			if ($branch['passthrough'])
				continue;
			if ($tokens[$i][0] === \T_IF
			 || $tokens[$i][0] === \T_ELSEIF
			 || $tokens[$i][0] === \T_ELSE)
			{
				$branch[$key] = \substr($branch[$key], 0, -\strlen($tokens[$i][1]));
				$branch['branches'][] = array(
					'braces'      => $braces,
					'branches'    => array(),
					'head'        => '',
					'parent'      => &$branch,
					'passthrough' => 0,
					'statement'   => '',
					'tail'        => ''
				);
				$branch =& $branch['branches'][\count($branch['branches']) - 1];
				do
				{
					$branch['statement'] .= (\is_array($tokens[$i])) ? $tokens[$i][1] : $tokens[$i];
				}
				while ($tokens[++$i] !== '{');
				++$braces;
			}
		}
		while (++$i < $cnt);
		list($head, $tail) = self::buildPHP($branch['branches']);
		$head  = $branch['head'] . $head;
		$tail .= $branch['tail'];
		self::convertPHP($head, $tail, (bool) $branch['passthrough']);
		if (\preg_match('((?<!-)->(?!params\\[))', $head . $tail))
			return \false;
		return ($branch['passthrough']) ? array($head, $tail) : array($head);
	}
	protected static function convertPHP(&$head, &$tail, $passthrough)
	{
		$saveAttributes = (bool) \preg_match('(\\$node->(?:get|has)Attribute)', $tail);
		\preg_match_all(
			"(\\\$node->getAttribute\\('([^']+)'\\))",
			\preg_replace_callback(
				'(if\\(\\$node->hasAttribute\\(([^\\)]+)[^}]+)',
				function ($m)
				{
					return \str_replace('$node->getAttribute(' . $m[1] . ')', '', $m[0]);
				},
				$head . $tail
			),
			$matches
		);
		$attrNames = \array_unique($matches[1]);
		self::replacePHP($head);
		self::replacePHP($tail);
		if (!$passthrough)
			$head = \str_replace('$node->textContent', '$textContent', $head);
		if (!empty($attrNames))
		{
			\ksort($attrNames);
			$head = "\$attributes+=array('" . \implode("'=>null,'", $attrNames) . "'=>null);" . $head;
		}
		if ($saveAttributes)
		{
			$head .= 'self::$attributes[]=$attributes;';
			$tail  = '$attributes=array_pop(self::$attributes);' . $tail;
		}
	}
	protected static function replacePHP(&$php)
	{
		if ($php === '')
			return;
		$php = \str_replace('$this->out', '$html', $php);
		$getAttribute = "\\\$node->getAttribute\\(('[^']+')\\)";
		$php = \preg_replace(
			'(htmlspecialchars\\(' . $getAttribute . ',' . \ENT_NOQUOTES . '\\))',
			"str_replace('&quot;','\"',\$attributes[\$1])",
			$php
		);
		$php = \preg_replace(
			'(htmlspecialchars\\(' . $getAttribute . ',' . \ENT_COMPAT . '\\))',
			'$attributes[$1]',
			$php
		);
		$php = \preg_replace(
			'(htmlspecialchars\\(strtr\\(' . $getAttribute . ",('[^\"&\\\\';<>aglmopqtu]+'),('[^\"&\\\\'<>]+')\\)," . \ENT_COMPAT . '\\))',
			'strtr($attributes[$1],$2,$3)',
			$php
		);
		$php = \preg_replace(
			'(' . $getAttribute . '(!?=+)' . $getAttribute . ')',
			'$attributes[$1]$2$attributes[$3]',
			$php
		);
		$php = \preg_replace_callback(
			'(' . $getAttribute . "==='(.*?(?<!\\\\)(?:\\\\\\\\)*)')s",
			function ($m)
			{
				return '$attributes[' . $m[1] . "]==='" . \htmlspecialchars(\stripslashes($m[2]), \ENT_QUOTES) . "'";
			},
			$php
		);
		$php = \preg_replace_callback(
			"('(.*?(?<!\\\\)(?:\\\\\\\\)*)'===" . $getAttribute . ')s',
			function ($m)
			{
				return "'" . \htmlspecialchars(\stripslashes($m[1]), \ENT_QUOTES) . "'===\$attributes[" . $m[2] . ']';
			},
			$php
		);
		$php = \preg_replace_callback(
			'(strpos\\(' . $getAttribute . ",'(.*?(?<!\\\\)(?:\\\\\\\\)*)'\\)([!=]==(?:0|false)))s",
			function ($m)
			{
				return 'strpos($attributes[' . $m[1] . "],'" . \htmlspecialchars(\stripslashes($m[2]), \ENT_QUOTES) . "')" . $m[3];
			},
			$php
		);
		$php = \preg_replace_callback(
			"(strpos\\('(.*?(?<!\\\\)(?:\\\\\\\\)*)'," . $getAttribute . '\\)([!=]==(?:0|false)))s',
			function ($m)
			{
				return "strpos('" . \htmlspecialchars(\stripslashes($m[1]), \ENT_QUOTES) . "',\$attributes[" . $m[2] . '])' . $m[3];
			},
			$php
		);
		$php = \preg_replace(
			'(' . $getAttribute . '(?=(?:==|[-+*])\\d+))',
			'$attributes[$1]',
			$php
		);
		$php = \preg_replace(
			'((?<!\\w)(\\d+(?:==|[-+*]))' . $getAttribute . ')',
			'$1$attributes[$2]',
			$php
		);
		$php = \preg_replace(
			"(empty\\(\\\$node->getAttribute\\(('[^']+')\\)\\))",
			'empty($attributes[$1])',
			$php
		);
		$php = \preg_replace(
			"(\\\$node->hasAttribute\\(('[^']+')\\))",
			'isset($attributes[$1])',
			$php
		);
		$php = \preg_replace(
			"(\\\$node->getAttribute\\(('[^']+')\\))",
			'htmlspecialchars_decode($attributes[$1])',
			$php
		);
		if (\substr($php, 0, 7) === '$html.=')
			$php = '$html=' . \substr($php, 7);
		else
			$php = "\$html='';" . $php;
	}
	protected static function buildPHP(array $branches)
	{
		$return = array('', '');
		foreach ($branches as $branch)
		{
			$return[0] .= $branch['statement'] . '{' . $branch['head'];
			$return[1] .= $branch['statement'] . '{';
			if ($branch['branches'])
			{
				list($head, $tail) = self::buildPHP($branch['branches']);
				$return[0] .= $head;
				$return[1] .= $tail;
			}
			$return[0] .= '}';
			$return[1] .= $branch['tail'] . '}';
		}
		return $return;
	}
	protected static function getBranchesPassthrough(array $branches)
	{
		$values = array();
		foreach ($branches as $branch)
			$values[] = $branch['passthrough'];
		if ($branch['statement'] !== 'else')
			$values[] = 0;
		return \array_unique($values);
	}
	protected static function getDynamicRendering($php)
	{
		$rendering = '';
		$literal   = "(?<literal>'((?>[^'\\\\]+|\\\\['\\\\])*)')";
		$attribute = "(?<attribute>htmlspecialchars\\(\\\$node->getAttribute\\('([^']+)'\\),2\\))";
		$value     = "(?<value>$literal|$attribute)";
		$output    = "(?<output>\\\$this->out\\.=$value(?:\\.(?&value))*;)";
		$copyOfAttribute = "(?<copyOfAttribute>if\\(\\\$node->hasAttribute\\('([^']+)'\\)\\)\\{\\\$this->out\\.=' \\g-1=\"'\\.htmlspecialchars\\(\\\$node->getAttribute\\('\\g-1'\\),2\\)\\.'\"';\\})";
		$regexp = '(^(' . $output . '|' . $copyOfAttribute . ')*$)';
		if (!\preg_match($regexp, $php, $m))
			return \false;
		$copiedAttributes = array();
		$usedAttributes = array();
		$regexp = '(' . $output . '|' . $copyOfAttribute . ')A';
		$offset = 0;
		while (\preg_match($regexp, $php, $m, 0, $offset))
			if ($m['output'])
			{
				$offset += 12;
				while (\preg_match('(' . $value . ')A', $php, $m, 0, $offset))
				{
					if ($m['literal'])
					{
						$str = \stripslashes(\substr($m[0], 1, -1));
						$rendering .= \preg_replace('([\\\\$](?=\\d))', '\\\\$0', $str);
					}
					else
					{
						$attrName = \end($m);
						if (!isset($usedAttributes[$attrName]))
							$usedAttributes[$attrName] = \uniqid($attrName, \true);
						$rendering .= $usedAttributes[$attrName];
					}
					$offset += 1 + \strlen($m[0]);
				}
			}
			else
			{
				$attrName = \end($m);
				if (!isset($copiedAttributes[$attrName]))
					$copiedAttributes[$attrName] = \uniqid($attrName, \true);
				$rendering .= $copiedAttributes[$attrName];
				$offset += \strlen($m[0]);
			}
		$attrNames = \array_keys($copiedAttributes + $usedAttributes);
		\sort($attrNames);
		$remainingAttributes = \array_combine($attrNames, $attrNames);
		$regexp = '(^[^ ]+';
		$index  = 0;
		foreach ($attrNames as $attrName)
		{
			$regexp .= '(?> (?!' . RegexpBuilder::fromList($remainingAttributes) . '=)[^=]+="[^"]*")*';
			unset($remainingAttributes[$attrName]);
			$regexp .= '(';
			if (isset($copiedAttributes[$attrName]))
				self::replacePlaceholder($rendering, $copiedAttributes[$attrName], ++$index);
			else
				$regexp .= '?>';
			$regexp .= ' ' . $attrName . '="';
			if (isset($usedAttributes[$attrName]))
			{
				$regexp .= '(';
				self::replacePlaceholder($rendering, $usedAttributes[$attrName], ++$index);
			}
			$regexp .= '[^"]*';
			if (isset($usedAttributes[$attrName]))
				$regexp .= ')';
			$regexp .= '")?';
		}
		$regexp .= '.*)s';
		return array($regexp, $rendering);
	}
	protected static function getStaticRendering($php)
	{
		if ($php === '')
			return '';
		$regexp = "(^\\\$this->out\.='((?>[^'\\\\]+|\\\\['\\\\])*)';\$)";
		if (!\preg_match($regexp, $php, $m))
			return \false;
		return \stripslashes($m[1]);
	}
	protected static function replacePlaceholder(&$str, $uniqid, $index)
	{
		$str = \preg_replace_callback(
			'(' . \preg_quote($uniqid) . '(.))',
			function ($m) use ($index)
			{
				if (\is_numeric($m[1]))
					return '${' . $index . '}' . $m[1];
				else
					return '$' . $index . $m[1];
			},
			$str
		);
	}
	public static function generateConditionals($expr, array $statements)
	{
		$keys = \array_keys($statements);
		$cnt  = \count($statements);
		$min  = (int) $keys[0];
		$max  = (int) $keys[$cnt - 1];
		if ($cnt <= 4)
		{
			if ($cnt === 1)
				return \end($statements);
			$php = '';
			$k = $min;
			do
			{
				$php .= 'if(' . $expr . '===' . $k . '){' . $statements[$k] . '}else';
			}
			while (++$k < $max);
			$php .= '{' . $statements[$max] . '}';
			
			return $php;
		}
		$cutoff = \ceil($cnt / 2);
		$chunks = \array_chunk($statements, $cutoff, \true);
		return 'if(' . $expr . '<' . \key($chunks[1]) . '){' . self::generateConditionals($expr, \array_slice($statements, 0, $cutoff, \true)) . '}else' . self::generateConditionals($expr, \array_slice($statements, $cutoff, \null, \true));
	}
	public static function generateBranchTable($expr, array $statements)
	{
		$branchTable = array();
		$branchIds = array();
		\ksort($statements);
		foreach ($statements as $value => $statement)
		{
			if (!isset($branchIds[$statement]))
				$branchIds[$statement] = \count($branchIds);
			$branchTable[$value] = $branchIds[$statement];
		}
		return array($branchTable, self::generateConditionals($expr, \array_keys($branchIds)));
	}
}