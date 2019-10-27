<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use Closure;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
class Quick
{
	public static function getSource(array $compiledTemplates)
	{
		$map         = array('dynamic' => array(), 'php' => array(), 'static' => array());
		$tagNames    = array();
		$unsupported = array();
		unset($compiledTemplates['br']);
		unset($compiledTemplates['e']);
		unset($compiledTemplates['i']);
		unset($compiledTemplates['p']);
		unset($compiledTemplates['s']);
		foreach ($compiledTemplates as $tagName => $php)
		{
			$renderings = self::getRenderingStrategy($php);
			if (empty($renderings))
			{
				$unsupported[] = $tagName;
				continue;
			}
			foreach ($renderings as $i => $_562c18b7)
			{
				list($strategy, $replacement) = $_562c18b7;
				$match = (($i) ? '/' : '') . $tagName;
				$map[$strategy][$match] = $replacement;
			}
			if (!isset($renderings[1]))
				$tagNames[] = $tagName;
		}
		$php = array();
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	public $enableQuickRenderer=true;';
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	protected $static=' . self::export($map['static']) . ';';
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	protected $dynamic=' . self::export($map['dynamic']) . ';';
		$quickSource = '';
		if (!empty($map['php']))
			$quickSource = SwitchStatement::generate('$id', $map['php']);
		$regexp  = '(<(?:(?!/)(';
		$regexp .= ($tagNames) ? RegexpBuilder::fromList($tagNames) : '(?!)';
		$regexp .= ')(?: [^>]*)?>.*?</\\1|(/?(?!br/|p>)[^ />]+)[^>]*?(/)?)>)s';
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	protected $quickRegexp=' . \var_export($regexp, \true) . ';';
		if (!empty($unsupported))
		{
			$regexp = '(<(?:[!?]|' . RegexpBuilder::fromList($unsupported) . '[ />]))';
			$php[]  = '	/** {@inheritdoc} */';
			$php[]  = '	protected $quickRenderingTest=' . \var_export($regexp, \true) . ';';
		}
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	protected function renderQuickTemplate($id, $xml)';
		$php[] = '	{';
		$php[] = '		$attributes=$this->matchAttributes($xml);';
		$php[] = "		\$html='';" . $quickSource;
		$php[] = '';
		$php[] = '		return $html;';
		$php[] = '	}';
		return \implode("\n", $php);
	}
	protected static function export(array $arr)
	{
		$exportKeys = (\array_keys($arr) !== \range(0, \count($arr) - 1));
		\ksort($arr);
		$entries = array();
		foreach ($arr as $k => $v)
			$entries[] = (($exportKeys) ? \var_export($k, \true) . '=>' : '')
			           . ((\is_array($v)) ? self::export($v) : \var_export($v, \true));
		return 'array(' . \implode(',', $entries) . ')';
	}
	public static function getRenderingStrategy($php)
	{
		$phpRenderings = self::getQuickRendering($php);
		if (empty($phpRenderings))
			return array();
		$renderings = self::getStringRenderings($php);
		foreach ($phpRenderings as $i => $phpRendering)
			if (!isset($renderings[$i]) || \strpos($phpRendering, '$this->attributes[]') !== \false)
				$renderings[$i] = array('php', $phpRendering);
		return $renderings;
	}
	protected static function getQuickRendering($php)
	{
		if (\preg_match('(\\$this->at\\((?!\\$node\\);))', $php))
			return array();
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
					return array();
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
					if ($tokens[$j][0] !== \T_ELSEIF && $tokens[$j][0] !== \T_ELSE)
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
						return array();
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
		if (\preg_match('((?<!-|\\$this)->)', $head . $tail))
			return array();
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
		if (!$passthrough && \strpos($head, '$node->textContent') !== \false)
			$head = '$textContent=$this->getQuickTextContent($xml);' . \str_replace('$node->textContent', '$textContent', $head);
		if (!empty($attrNames))
		{
			\ksort($attrNames);
			$head = "\$attributes+=array('" . \implode("'=>null,'", $attrNames) . "'=>null);" . $head;
		}
		if ($saveAttributes)
		{
			$head .= '$this->attributes[]=$attributes;';
			$tail  = '$attributes=array_pop($this->attributes);' . $tail;
		}
	}
	protected static function replacePHP(&$php)
	{
		$getAttribute = "\\\$node->getAttribute\\(('[^']+')\\)";
		$string       = "'(?:[^\\\\']|\\\\.)*+'";
		$replacements = array(
			'$this->out' => '$html',
			'(htmlspecialchars\\(' . $getAttribute . ',' . \ENT_NOQUOTES . '\\))'
				=> "str_replace('&quot;','\"',\$attributes[\$1])",
			'(htmlspecialchars\\((' . $getAttribute . '(?:\\.' . $getAttribute . ')*),' . \ENT_COMPAT . '\\))'
				=> function ($m) use ($getAttribute)
				{
					return \preg_replace('(' . $getAttribute . ')', '$attributes[$1]', $m[1]);
				},
			'(htmlspecialchars\\(strtr\\(' . $getAttribute . ",('[^\"&\\\\';<>aglmopqtu]+'),('[^\"&\\\\'<>]+')\\)," . \ENT_COMPAT . '\\))'
				=> 'strtr($attributes[$1],$2,$3)',
			'(' . $getAttribute . '(!?=+)' . $getAttribute . ')'
				=> '$attributes[$1]$2$attributes[$3]',
			'(' . $getAttribute . '===(' . $string . '))s'
				=> function ($m)
				{
					return '$attributes[' . $m[1] . ']===' . \htmlspecialchars($m[2], \ENT_COMPAT);
				},
			'((' . $string . ')===' . $getAttribute . ')s'
				=> function ($m)
				{
					return \htmlspecialchars($m[1], \ENT_COMPAT) . '===$attributes[' . $m[2] . ']';
				},
			'(strpos\\(' . $getAttribute . ',(' . $string . ')\\)([!=]==(?:0|false)))s'
				=> function ($m)
				{
					return 'strpos($attributes[' . $m[1] . "]," . \htmlspecialchars($m[2], \ENT_COMPAT) . ')' . $m[3];
				},
			'(strpos\\((' . $string . '),' . $getAttribute . '\\)([!=]==(?:0|false)))s'
				=> function ($m)
				{
					return 'strpos(' . \htmlspecialchars($m[1], \ENT_COMPAT) . ',$attributes[' . $m[2] . '])' . $m[3];
				},
			'(' . $getAttribute . '(?=(?:==|[-+*])\\d+))'  => '$attributes[$1]',
			'(\\b(\\d+(?:==|[-+*]))' . $getAttribute . ')' => '$1$attributes[$2]',
			'(empty\\(' . $getAttribute . '\\))'           => 'empty($attributes[$1])',
			"(\\\$node->hasAttribute\\(('[^']+')\\))"      => 'isset($attributes[$1])',
			'if($node->attributes->length)'                => 'if($this->hasNonNullValues($attributes))',
			'(' . $getAttribute . ')' => 'htmlspecialchars_decode($attributes[$1])'
		);
		foreach ($replacements as $match => $replace)
			if ($replace instanceof Closure)
				$php = \preg_replace_callback($match, $replace, $php);
			elseif ($match[0] === '(')
				$php = \preg_replace($match, $replace, $php);
			else
				$php = \str_replace($match, $replace, $php);
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
		$regexp = "(^\\\$this->out\.='((?>[^'\\\\]|\\\\['\\\\])*+)';\$)";
		if (\preg_match($regexp, $php, $m))
			return \stripslashes($m[1]);
		return \false;
	}
	protected static function getStringRenderings($php)
	{
		$chunks = \explode('$this->at($node);', $php);
		if (\count($chunks) > 2)
			return array();
		$renderings = array();
		foreach ($chunks as $k => $chunk)
		{
			$rendering = self::getStaticRendering($chunk);
			if ($rendering !== \false)
				$renderings[$k] = array('static', $rendering);
			elseif ($k === 0)
			{
				$rendering = self::getDynamicRendering($chunk);
				if ($rendering !== \false)
					$renderings[$k] = array('dynamic', $rendering);
			}
		}
		return $renderings;
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
}