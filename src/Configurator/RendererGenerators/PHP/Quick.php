<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

use Closure;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;

class Quick
{
	/**
	* Generate the Quick renderer's source
	*
	* @param  array  $compiledTemplates Array of tagName => compiled template
	* @return string
	*/
	public static function getSource(array $compiledTemplates)
	{
		$map         = ['dynamic' => [], 'php' => [], 'static' => []];
		$tagNames    = [];
		$unsupported = [];

		// Ignore system tags
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

			foreach ($renderings as $i => list($strategy, $replacement))
			{
				$match = (($i) ? '/' : '') . $tagName;
				$map[$strategy][$match] = $replacement;
			}

			// Record the names of tags whose template does not contain a passthrough
			if (!isset($renderings[1]))
			{
				$tagNames[] = $tagName;
			}
		}

		$php = [];
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	public $enableQuickRenderer=true;';
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	protected $static=' . self::export($map['static']) . ';';
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	protected $dynamic=' . self::export($map['dynamic']) . ';';

		$quickSource = '';
		if (!empty($map['php']))
		{
			$quickSource = SwitchStatement::generate('$id', $map['php']);
		}

		// Build a regexp that matches all the tags
		$regexp  = '(<(?:(?!/)(';
		$regexp .= ($tagNames) ? RegexpBuilder::fromList($tagNames) : '(?!)';
		$regexp .= ')(?: [^>]*)?>.*?</\\1|(/?(?!br/|p>)[^ />]+)[^>]*?(/)?)>)s';
		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	protected $quickRegexp=' . var_export($regexp, true) . ';';

		// Build a regexp that matches tags that cannot be rendered with the Quick renderer
		if (!empty($unsupported))
		{
			$regexp = '((?<=<)(?:[!?]|' . RegexpBuilder::fromList($unsupported) . '[ />]))';
			$php[]  = '	/** {@inheritdoc} */';
			$php[]  = '	protected $quickRenderingTest=' . var_export($regexp, true) . ';';
		}

		$php[] = '	/** {@inheritdoc} */';
		$php[] = '	protected function renderQuickTemplate($id, $xml)';
		$php[] = '	{';
		$php[] = '		$attributes=$this->matchAttributes($xml);';
		$php[] = "		\$html='';" . $quickSource;
		$php[] = '';
		$php[] = '		return $html;';
		$php[] = '	}';

		return implode("\n", $php);
	}

	/**
	* Export an array as PHP
	*
	* @param  array  $arr
	* @return string
	*/
	protected static function export(array $arr)
	{
		$exportKeys = (array_keys($arr) !== range(0, count($arr) - 1));
		ksort($arr);

		$entries = [];
		foreach ($arr as $k => $v)
		{
			$entries[] = (($exportKeys) ? var_export($k, true) . '=>' : '')
			           . ((is_array($v)) ? self::export($v) : var_export($v, true));
		}

		return '[' . implode(',', $entries) . ']';
	}

	/**
	* Compute the rendering strategy for a compiled template
	*
	* @param  string  $php Template compiled for the PHP renderer
	* @return array[]      An array containing 0 to 2 pairs of [<rendering type>, <replacement>]
	*/
	public static function getRenderingStrategy($php)
	{
		$phpRenderings = self::getQuickRendering($php);
		if (empty($phpRenderings))
		{
			return [];
		}
		$renderings = self::getStringRenderings($php);

		// Keep string rendering where possible, use PHP rendering wherever else
		foreach ($phpRenderings as $i => $phpRendering)
		{
			if (!isset($renderings[$i]) || strpos($phpRendering, '$this->attributes[]') !== false)
			{
				$renderings[$i] = ['php', $phpRendering];
			}
		}

		return $renderings;
	}

	/**
	* Generate the code for rendering a compiled template with the Quick renderer
	*
	* Parse and record every code path that contains a passthrough. Parse every if-else structure.
	* When the whole structure is parsed, there are 2 possible situations:
	*  - no code path contains a passthrough, in which case we discard the data
	*  - all the code paths including the mandatory "else" branch contain a passthrough, in which
	*    case we keep the data
	*
	* @param  string     $php Template compiled for the PHP renderer
	* @return string[]        An array containing one or two strings of PHP, or an empty array
	*                         if the PHP cannot be converted
	*/
	protected static function getQuickRendering($php)
	{
		// xsl:apply-templates elements with a select expression are not supported
		if (preg_match('(\\$this->at\\((?!\\$node\\);))', $php))
		{
			return [];
		}

		// Tokenize the PHP and add an empty token as terminator
		$tokens   = token_get_all('<?php ' . $php);
		$tokens[] = [0, ''];

		// Remove the first token, which is a T_OPEN_TAG
		array_shift($tokens);
		$cnt = count($tokens);

		// Prepare the main branch
		$branch = [
			// We purposefully use a value that can never match
			'braces'      => -1,
			'branches'    => [],
			'head'        => '',
			'passthrough' => 0,
			'statement'   => '',
			'tail'        => ''
		];

		$braces = 0;
		$i = 0;
		do
		{
			// Test whether we've reached a passthrough
			if ($tokens[$i    ][0] === T_VARIABLE
			 && $tokens[$i    ][1] === '$this'
			 && $tokens[$i + 1][0] === T_OBJECT_OPERATOR
			 && $tokens[$i + 2][0] === T_STRING
			 && $tokens[$i + 2][1] === 'at'
			 && $tokens[$i + 3]    === '('
			 && $tokens[$i + 4][0] === T_VARIABLE
			 && $tokens[$i + 4][1] === '$node'
			 && $tokens[$i + 5]    === ')'
			 && $tokens[$i + 6]    === ';')
			{
				if (++$branch['passthrough'] > 1)
				{
					// Multiple passthroughs are not supported
					return [];
				}

				// Skip to the semi-colon
				$i += 6;

				continue;
			}

			$key = ($branch['passthrough']) ? 'tail' : 'head';
			$branch[$key] .= (is_array($tokens[$i])) ? $tokens[$i][1] : $tokens[$i];

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
					// Remove the last brace from the branch's content
					$branch[$key] = substr($branch[$key], 0, -1);

					// Jump back to the parent branch
					$branch =& $branch['parent'];

					// Copy the current index to look ahead
					$j = $i;

					// Skip whitespace
					while ($tokens[++$j][0] === T_WHITESPACE);

					// Test whether this is the last brace of an if-else structure by looking for
					// an additional elseif/else case
					if ($tokens[$j][0] !== T_ELSEIF && $tokens[$j][0] !== T_ELSE)
					{
						$passthroughs = self::getBranchesPassthrough($branch['branches']);
						if ($passthroughs === [0])
						{
							// No branch was passthrough, move their PHP source back to this branch
							// then discard the data
							foreach ($branch['branches'] as $child)
							{
								$branch['head'] .= $child['statement'] . '{' . $child['head'] . '}';
							}

							$branch['branches'] = [];
							continue;
						}

						if ($passthroughs === [1])
						{
							// All branches were passthrough, so their parent is passthrough
							++$branch['passthrough'];

							continue;
						}

						// Mixed branches (with/out passthrough) are not supported
						return [];
					}
				}

				continue;
			}

			// We don't have to record child branches if we know that current branch is passthrough.
			// If a child branch contains a passthrough, it will be treated as a multiple
			// passthrough and we will abort
			if ($branch['passthrough'])
			{
				continue;
			}

			if ($tokens[$i][0] === T_IF
			 || $tokens[$i][0] === T_ELSEIF
			 || $tokens[$i][0] === T_ELSE)
			{
				// Remove the statement from the branch's content
				$branch[$key] = substr($branch[$key], 0, -strlen($tokens[$i][1]));

				// Create a new branch
				$branch['branches'][] = [
					'braces'      => $braces,
					'branches'    => [],
					'head'        => '',
					'parent'      => &$branch,
					'passthrough' => 0,
					'statement'   => '',
					'tail'        => ''
				];

				// Jump to the new branch
				$branch =& $branch['branches'][count($branch['branches']) - 1];

				// Record the PHP statement
				do
				{
					$branch['statement'] .= (is_array($tokens[$i])) ? $tokens[$i][1] : $tokens[$i];
				}
				while ($tokens[++$i] !== '{');

				// Account for the brace in the statement
				++$braces;
			}
		}
		while (++$i < $cnt);

		list($head, $tail) = self::buildPHP($branch['branches']);
		$head  = $branch['head'] . $head;
		$tail .= $branch['tail'];

		// Convert the PHP renderer source to the format used in the Quick renderer
		self::convertPHP($head, $tail, (bool) $branch['passthrough']);

		// Test whether any method call was left unconverted. If so, we cannot render this template
		if (preg_match('((?<!-|\\$this)->)', $head . $tail))
		{
			return [];
		}

		return ($branch['passthrough']) ? [$head, $tail] : [$head];
	}

	/**
	* Convert the two sides of a compiled template to quick rendering
	*
	* @param  string &$head
	* @param  string &$tail
	* @param  bool    $passthrough
	* @return void
	*/
	protected static function convertPHP(&$head, &$tail, $passthrough)
	{
		// Test whether the attributes must be saved when rendering the head because they're needed
		// when rendering the tail
		$saveAttributes = (bool) preg_match('(\\$node->(?:get|has)Attribute)', $tail);

		// Collect the names of all the attributes so that we can initialize them with a null value
		// to avoid undefined variable notices. We exclude attributes that seem to be in an if block
		// that tests its existence beforehand. This last part is not an accurate process as it
		// would be much more expensive to do it accurately but where it fails the only consequence
		// is we needlessly add the attribute to the list. There is no difference in functionality
		preg_match_all(
			"(\\\$node->getAttribute\\('([^']+)'\\))",
			preg_replace_callback(
				'(if\\(\\$node->hasAttribute\\(([^\\)]+)[^}]+)',
				function ($m)
				{
					return str_replace('$node->getAttribute(' . $m[1] . ')', '', $m[0]);
				},
				$head . $tail
			),
			$matches
		);
		$attrNames = array_unique($matches[1]);

		// Replace the source in $head and $tail
		self::replacePHP($head);
		self::replacePHP($tail);

		if (!$passthrough && strpos($head, '$node->textContent') !== false)
		{
			$head = '$textContent=$this->getQuickTextContent($xml);' . str_replace('$node->textContent', '$textContent', $head);
		}

		if (!empty($attrNames))
		{
			ksort($attrNames);
			$head = "\$attributes+=['" . implode("'=>null,'", $attrNames) . "'=>null];" . $head;
		}

		if ($saveAttributes)
		{
			$head .= '$this->attributes[]=$attributes;';
			$tail  = '$attributes=array_pop($this->attributes);' . $tail;
		}
	}

	/**
	* Replace the PHP code used in a compiled template to be used by the Quick renderer
	*
	* @param  string &$php
	* @return void
	*/
	protected static function replacePHP(&$php)
	{
		// Expression that matches a $node->getAttribute() call and captures its string argument
		$getAttribute = "\\\$node->getAttribute\\(('[^']+')\\)";

		// Expression that matches a single-quoted string literal
		$string       = "'(?:[^\\\\']|\\\\.)*+'";

		$replacements = [
			'$this->out' => '$html',

			// An attribute value escaped as ENT_NOQUOTES. We only need to unescape quotes
			'(htmlspecialchars\\(' . $getAttribute . ',' . ENT_NOQUOTES . '\\))'
				=> "str_replace('&quot;','\"',\$attributes[\$1])",

			// One or several attribute values escaped as ENT_COMPAT can be used as-is
			'(htmlspecialchars\\((' . $getAttribute . '(?:\\.' . $getAttribute . ')*),' . ENT_COMPAT . '\\))'
				=> function ($m) use ($getAttribute)
				{
					return preg_replace('(' . $getAttribute . ')', '$attributes[$1]', $m[1]);
				},

			// Character replacement can be performed directly on the escaped value provided that it
			// is then escaped as ENT_COMPAT and that replacements do not interfere with the escaping
			// of the characters &<>" or their representation &amp;&lt;&gt;&quot;
			'(htmlspecialchars\\(strtr\\(' . $getAttribute . ",('[^\"&\\\\';<>aglmopqtu]+'),('[^\"&\\\\'<>]+')\\)," . ENT_COMPAT . '\\))'
				=> 'strtr($attributes[$1],$2,$3)',

			// A comparison between two attributes. No need to unescape
			'(' . $getAttribute . '(!?=+)' . $getAttribute . ')'
				=> '$attributes[$1]$2$attributes[$3]',

			// A comparison between an attribute and a literal string. Rather than unescape the
			// attribute value, we escape the literal. This applies to comparisons using XPath's
			// contains() as well (translated to PHP's strpos())
			'(' . $getAttribute . '===(' . $string . '))s'
				=> function ($m)
				{
					return '$attributes[' . $m[1] . ']===' . htmlspecialchars($m[2], ENT_COMPAT);
				},

			'((' . $string . ')===' . $getAttribute . ')s'
				=> function ($m)
				{
					return htmlspecialchars($m[1], ENT_COMPAT) . '===$attributes[' . $m[2] . ']';
				},

			'(strpos\\(' . $getAttribute . ',(' . $string . ')\\)([!=]==(?:0|false)))s'
				=> function ($m)
				{
					return 'strpos($attributes[' . $m[1] . "]," . htmlspecialchars($m[2], ENT_COMPAT) . ')' . $m[3];
				},

			'(strpos\\((' . $string . '),' . $getAttribute . '\\)([!=]==(?:0|false)))s'
				=> function ($m)
				{
					return 'strpos(' . htmlspecialchars($m[1], ENT_COMPAT) . ',$attributes[' . $m[2] . '])' . $m[3];
				},

			// An attribute value used in an arithmetic comparison or operation does not need to be
			// unescaped. The same applies to empty(), isset() and conditionals
			'(' . $getAttribute . '(?=(?:==|[-+*])\\d+))'  => '$attributes[$1]',
			'(\\b(\\d+(?:==|[-+*]))' . $getAttribute . ')' => '$1$attributes[$2]',
			'(empty\\(' . $getAttribute . '\\))'           => 'empty($attributes[$1])',
			"(\\\$node->hasAttribute\\(('[^']+')\\))"      => 'isset($attributes[$1])',
			'if($node->attributes->length)'                => 'if($this->hasNonNullValues($attributes))',

			// In all other situations, unescape the attribute value before use
			'(' . $getAttribute . ')' => 'htmlspecialchars_decode($attributes[$1])'
		];

		foreach ($replacements as $match => $replace)
		{
			if ($replace instanceof Closure)
			{
				$php = preg_replace_callback($match, $replace, $php);
			}
			elseif ($match[0] === '(')
			{
				$php = preg_replace($match, $replace, $php);
			}
			else
			{
				$php = str_replace($match, $replace, $php);
			}
		}
	}

	/**
	* Build the source for the two sides of a templates based on the structure extracted from its
	* original source
	*
	* @param  array    $branches
	* @return string[]
	*/
	protected static function buildPHP(array $branches)
	{
		$return = ['', ''];
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

	/**
	* Get the unique values for the "passthrough" key of given branches
	*
	* @param  array     $branches
	* @return integer[]
	*/
	protected static function getBranchesPassthrough(array $branches)
	{
		$values = [];
		foreach ($branches as $branch)
		{
			$values[] = $branch['passthrough'];
		}

		// If the last branch isn't an "else", we act as if there was an additional branch with no
		// passthrough
		if ($branch['statement'] !== 'else')
		{
			$values[] = 0;
		}

		return array_unique($values);
	}

	/**
	* Get a string suitable as a preg_replace() replacement for given PHP code
	*
	* @param  string     $php Original code
	* @return array|bool      Array of [regexp, replacement] if possible, or FALSE otherwise
	*/
	protected static function getDynamicRendering($php)
	{
		$rendering = '';

		$literal   = "(?<literal>'((?>[^'\\\\]+|\\\\['\\\\])*)')";
		$attribute = "(?<attribute>htmlspecialchars\\(\\\$node->getAttribute\\('([^']+)'\\),2\\))";
		$value     = "(?<value>$literal|$attribute)";
		$output    = "(?<output>\\\$this->out\\.=$value(?:\\.(?&value))*;)";

		$copyOfAttribute = "(?<copyOfAttribute>if\\(\\\$node->hasAttribute\\('([^']+)'\\)\\)\\{\\\$this->out\\.=' \\g-1=\"'\\.htmlspecialchars\\(\\\$node->getAttribute\\('\\g-1'\\),2\\)\\.'\"';\\})";

		$regexp = '(^(' . $output . '|' . $copyOfAttribute . ')*$)';
		if (!preg_match($regexp, $php, $m))
		{
			return false;
		}

		// Attributes that are copied in the replacement
		$copiedAttributes = [];

		// Attributes whose value is used in the replacement
		$usedAttributes = [];

		$regexp = '(' . $output . '|' . $copyOfAttribute . ')A';
		$offset = 0;
		while (preg_match($regexp, $php, $m, 0, $offset))
		{
			// Test whether it's normal output or a copy of attribute
			if ($m['output'])
			{
				// 12 === strlen('$this->out.=')
				$offset += 12;

				while (preg_match('(' . $value . ')A', $php, $m, 0, $offset))
				{
					// Test whether it's a literal or an attribute value
					if ($m['literal'])
					{
						// Unescape the literal
						$str = stripslashes(substr($m[0], 1, -1));

						// Escape special characters
						$rendering .= preg_replace('([\\\\$](?=\\d))', '\\\\$0', $str);
					}
					else
					{
						$attrName = end($m);

						// Generate a unique ID for this attribute name, we'll use it as a
						// placeholder until we have the full list of captures and we can replace it
						// with the capture number
						if (!isset($usedAttributes[$attrName]))
						{
							$usedAttributes[$attrName] = uniqid($attrName, true);
						}

						$rendering .= $usedAttributes[$attrName];
					}

					// Skip the match plus the next . or ;
					$offset += 1 + strlen($m[0]);
				}
			}
			else
			{
				$attrName = end($m);

				if (!isset($copiedAttributes[$attrName]))
				{
					$copiedAttributes[$attrName] = uniqid($attrName, true);
				}

				$rendering .= $copiedAttributes[$attrName];
				$offset += strlen($m[0]);
			}
		}

		// Gather the names of the attributes used in the replacement either by copy or by value
		$attrNames = array_keys($copiedAttributes + $usedAttributes);

		// Sort them alphabetically
		sort($attrNames);

		// Keep a copy of the attribute names to be used in the fillter subpattern
		$remainingAttributes = array_combine($attrNames, $attrNames);

		// Prepare the final regexp
		$regexp = '(^[^ ]+';
		$index  = 0;
		foreach ($attrNames as $attrName)
		{
			// Add a subpattern that matches (and skips) any attribute definition that is not one of
			// the remaining attributes we're trying to match
			$regexp .= '(?> (?!' . RegexpBuilder::fromList($remainingAttributes) . '=)[^=]+="[^"]*")*';
			unset($remainingAttributes[$attrName]);

			$regexp .= '(';

			if (isset($copiedAttributes[$attrName]))
			{
				self::replacePlaceholder($rendering, $copiedAttributes[$attrName], ++$index);
			}
			else
			{
				$regexp .= '?>';
			}

			$regexp .= ' ' . $attrName . '="';

			if (isset($usedAttributes[$attrName]))
			{
				$regexp .= '(';

				self::replacePlaceholder($rendering, $usedAttributes[$attrName], ++$index);
			}

			$regexp .= '[^"]*';

			if (isset($usedAttributes[$attrName]))
			{
				$regexp .= ')';
			}

			$regexp .= '")?';
		}

		$regexp .= '.*)s';

		return [$regexp, $rendering];
	}

	/**
	* Get a string suitable as a str_replace() replacement for given PHP code
	*
	* @param  string      $php Original code
	* @return bool|string      Static replacement if possible, or FALSE otherwise
	*/
	protected static function getStaticRendering($php)
	{
		if ($php === '')
		{
			return '';
		}

		$regexp = "(^\\\$this->out\.='((?>[^'\\\\]|\\\\['\\\\])*+)';\$)";
		if (preg_match($regexp, $php, $m))
		{
			return stripslashes($m[1]);
		}

		return false;
	}

	/**
	* Get string rendering strategies for given chunks
	*
	* @param  string $php
	* @return array
	*/
	protected static function getStringRenderings($php)
	{
		$chunks = explode('$this->at($node);', $php);
		if (count($chunks) > 2)
		{
			// Can't use string replacements if there are more than one xsl:apply-templates
			return [];
		}

		$renderings = [];
		foreach ($chunks as $k => $chunk)
		{
			// Try a static replacement first
			$rendering = self::getStaticRendering($chunk);
			if ($rendering !== false)
			{
				$renderings[$k] = ['static', $rendering];
			}
			elseif ($k === 0)
			{
				// If this is the first chunk, we can try a dynamic replacement. This wouldn't work
				// for the second chunk because we wouldn't have access to the attribute values
				$rendering = self::getDynamicRendering($chunk);
				if ($rendering !== false)
				{
					$renderings[$k] = ['dynamic', $rendering];
				}
			}
		}

		return $renderings;
	}

	/**
	* Replace all instances of a uniqid with a PCRE replacement in a string
	*
	* @param  string  &$str    PCRE replacement
	* @param  string   $uniqid Unique ID
	* @param  integer  $index  Capture index
	* @return void
	*/
	protected static function replacePlaceholder(&$str, $uniqid, $index)
	{
		$str = preg_replace_callback(
			'(' . preg_quote($uniqid) . '(.))',
			function ($m) use ($index)
			{
				// Replace with $1 where unambiguous and ${1} otherwise
				if (is_numeric($m[1]))
				{
					return '${' . $index . '}' . $m[1];
				}
				else
				{
					return '$' . $index . $m[1];
				}
			},
			$str
		);
	}
}