<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

use LogicException;
use RuntimeException;

class XPathConvertor
{
	/**
	* @var string PCRE version
	*/
	public $pcreVersion;

	/**
	* @var string Regexp used to match XPath expressions
	*/
	protected $regexp;

	/**
	* @var bool Whether to use the mbstring functions as a replacement for XPath expressions
	*/
	public $useMultibyteStringFunctions = false;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->pcreVersion = PCRE_VERSION;
	}

	/**
	* Convert an XPath expression (used in a condition) into PHP code
	*
	* This method is similar to convertXPath() but it selectively replaces some simple conditions
	* with the corresponding DOM method for performance reasons
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	public function convertCondition($expr)
	{
		$expr = trim($expr);

		// XSL: <xsl:if test="@foo">
		// PHP: if ($node->hasAttribute('foo'))
		if (preg_match('#^@([-\\w]+)$#', $expr, $m))
		{
			return '$node->hasAttribute(' . var_export($m[1], true) . ')';
		}

		// XSL: <xsl:if test="not(@foo)">
		// PHP: if (!$node->hasAttribute('foo'))
		if (preg_match('#^not\\(@([-\\w]+)\\)$#', $expr, $m))
		{
			return '!$node->hasAttribute(' . var_export($m[1], true) . ')';
		}

		// XSL: <xsl:if test="$foo">
		// PHP: if (!empty($this->params['foo']))
		if (preg_match('#^\\$(\\w+)$#', $expr, $m))
		{
			return '!empty($this->params[' . var_export($m[1], true) . '])';
		}

		// XSL: <xsl:if test="not($foo)">
		// PHP: if (empty($this->params['foo']))
		if (preg_match('#^not\\(\\$(\\w+)\\)$#', $expr, $m))
		{
			return 'empty($this->params[' . var_export($m[1], true) . '])';
		}

		// XSL: <xsl:if test="@foo > 1">
		// PHP: if ($node->getAttribute('foo') > 1)
		if (preg_match('#^([$@][-\\w]+)\\s*([<>])\\s*(\\d+)$#', $expr, $m))
		{
			return $this->convertXPath($m[1]) . $m[2] . $m[3];
		}

		// If the condition does not seem to contain a relational expression, or start with a
		// function call, we wrap it inside of a boolean() call
		if (!preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\(#', $expr))
		{
			// XSL: <xsl:if test="parent::foo">
			// PHP: if ($this->xpath->evaluate("boolean(parent::foo)",$node))
			$expr = 'boolean(' . $expr . ')';
		}

		// XSL: <xsl:if test="@foo='bar'">
		// PHP: if ($this->xpath->evaluate("@foo='bar'",$node))
		return $this->convertXPath($expr);
	}

	/**
	* Convert an XPath expression (used as value) into PHP code
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	public function convertXPath($expr)
	{
		$expr = trim($expr);

		$this->generateXPathRegexp();
		if (preg_match($this->regexp, $expr, $m))
		{
			$methodName = null;
			foreach ($m as $k => $v)
			{
				if (is_numeric($k) || $v === '' || !method_exists($this, $k))
				{
					continue;
				}

				$methodName = $k;
				break;
			}

			if (isset($methodName))
			{
				// Default argument is the whole matched string
				$args = [$m[$methodName]];

				// Overwrite the default arguments with the named captures
				$i = 0;
				while (isset($m[$methodName . $i]))
				{
					$args[$i] = $m[$methodName . $i];
					++$i;
				}

				return call_user_func_array([$this, $methodName], $args);
			}
		}

		// If the condition does not seem to contain a relational expression, or start with a
		// function call, we wrap it inside of a string() call
		if (!preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\(#', $expr))
		{
			$expr = 'string(' . $expr . ')';
		}

		// Replace parameters in the expression
		return '$this->xpath->evaluate(' . $this->exportXPath($expr) . ',$node)';
	}

	protected function attr($attrName)
	{
		return '$node->getAttribute(' . var_export($attrName, true) . ')';
	}

	protected function dot()
	{
		return '$node->textContent';
	}

	protected function param($paramName)
	{
		return '$this->params[' . var_export($paramName, true) . ']';
	}

	protected function string($string)
	{
		return var_export(substr($string, 1, -1), true);
	}

	protected function lname()
	{
		return '$node->localName';
	}

	protected function name()
	{
		return '$node->nodeName';
	}

	protected function number($number)
	{
		return "'" . $number . "'";
	}

	protected function strlen($expr)
	{
		if ($expr === '')
		{
			$expr = '.';
		}

		$php = $this->convertXPath($expr);

		return ($this->useMultibyteStringFunctions)
			? 'mb_strlen(' . $php . ",'utf-8')"
			: "strlen(preg_replace('(.)us','.'," . $php . '))';
	}

	protected function contains($haystack, $needle)
	{
		return '(strpos(' . $this->convertXPath($haystack) . ',' . $this->convertXPath($needle) . ')!==false)';
	}

	protected function startswith($string, $substring)
	{
		return '(strpos(' . $this->convertXPath($string) . ',' . $this->convertXPath($substring) . ')===0)';
	}

	protected function not($expr)
	{
		return '!(' . $this->convertCondition($expr) . ')';
	}

	protected function notcontains($haystack, $needle)
	{
		return '(strpos(' . $this->convertXPath($haystack) . ',' . $this->convertXPath($needle) . ')===false)';
	}

	protected function substr($exprString, $exprPos, $exprLen = null)
	{
		if (!$this->useMultibyteStringFunctions)
		{
			$expr = 'substring(' . $exprString . ',' . $exprPos;
			if (isset($exprLen))
			{
				$expr .= ',' . $exprLen;
			}
			$expr .= ')';

			return '$this->xpath->evaluate(' . $this->exportXPath($expr) . ',$node)';
		}

		// NOTE: negative values for the second argument do not produce the same result as
		//       specified in XPath if the argument is not a literal number
		$php = 'mb_substr(' . $this->convertXPath($exprString) . ',';

		// Hardcode the value if possible
		if (is_numeric($exprPos))
		{
			$php .= max(0, $exprPos - 1);
		}
		else
		{
			$php .= 'max(0,' . $this->convertXPath($exprPos) . '-1)';
		}

		$php .= ',';

		if (isset($exprLen))
		{
			if (is_numeric($exprLen))
			{
				// Handles substring(0,2) as per XPath
				if (is_numeric($exprPos) && $exprPos < 1)
				{
					$php .= max(0, $exprPos + $exprLen - 1);
				}
				else
				{
					$php .= max(0, $exprLen);
				}
			}
			else
			{
				$php .= 'max(0,' . $this->convertXPath($exprLen) . ')';
			}
		}
		else
		{
			$php .= 'null';
		}

		$php .= ",'utf-8')";

		return $php;
	}

	protected function substringafter($expr, $str)
	{
		return 'substr(strstr(' . $this->convertXPath($expr) . ',' . $this->convertXPath($str) . '),' . (strlen($str) - 2) . ')';
	}

	protected function substringbefore($expr1, $expr2)
	{
		return 'strstr(' . $this->convertXPath($expr1) . ',' . $this->convertXPath($expr2) . ',true)';
	}

	protected function cmp($expr1, $operator, $expr2)
	{
		$operands  = [];
		$operators = [
			'='  => '===',
			'!=' => '!==',
			'>'  => '>',
			'>=' => '>=',
			'<'  => '<',
			'<=' => '<='
		];

		// If either operand is a number, represent it as a PHP number and replace the identity
		// identity operators
		foreach ([$expr1, $expr2] as $expr)
		{
			if (is_numeric($expr))
			{
				$operators['=']  = '==';
				$operators['!='] = '!=';

				$operands[] = preg_replace('(^0(.+))', '$1', $expr);
			}
			else
			{
				$operands[] = $this->convertXPath($expr);
			}
		}

		return implode($operators[$operator], $operands);
	}

	protected function bool($expr1, $operator, $expr2)
	{
		$operators = [
			'and' => '&&',
			'or'  => '||'
		];

		return $this->convertCondition($expr1) . $operators[$operator] . $this->convertCondition($expr2);
	}

	protected function parens($expr)
	{
		return '(' . $this->convertXPath($expr) . ')';
	}

	protected function translate($str, $from, $to)
	{
		preg_match_all('(.)su', substr($from, 1, -1), $matches);
		$from = $matches[0];

		preg_match_all('(.)su', substr($to, 1, -1), $matches);
		$to = $matches[0];

		// We adjust $to to match the number of elements in $from, either by truncating it
		// or by padding it with empty strings
		if (count($to) > count($from))
		{
			$to = array_slice($to, 0, count($from));
		}
		else
		{
			// NOTE: we don't use array_merge() because of potential side-effects when
			//       translating digits
			while (count($from) > count($to))
			{
				$to[] = '';
			}
		}

		// Remove duplicates in $from, as well as the corresponding elements in $to
		$from = array_unique($from);
		$to   = array_intersect_key($to, $from);

		// Start building the strtr() call
		$php = 'strtr(' . $this->convertXPath($str) . ',';

		// Test whether all elements in $from and $to are exactly 1 byte long, meaning they
		// are ASCII and with no empty strings. If so, we can use the scalar version of
		// strtr(), otherwise we have to use the array version
		if ([1] === array_unique(array_map('strlen', $from))
		 && [1] === array_unique(array_map('strlen', $to)))
		{
			$php .= var_export(implode('', $from), true) . ',' . var_export(implode('', $to), true);
		}
		else
		{
			$php .= '[';

			$cnt = count($from);
			for ($i = 0; $i < $cnt; ++$i)
			{
				if ($i)
				{
					$php .= ',';
				}

				$php .= var_export($from[$i], true) . '=>' . var_export($to[$i], true);
			}

			$php .= ']';
		}

		$php .= ')';

		return $php;
	}

	protected function math($expr1, $operator, $expr2)
	{
		if (!is_numeric($expr1))
		{
			$expr1 = $this->convertXPath($expr1);
		}

		if (!is_numeric($expr2))
		{
			$expr2 = $this->convertXPath($expr2);
		}

		if ($operator === 'div')
		{
			$operator = '/';
		}

		return $expr1 . $operator . $expr2;
	}

	/**
	* Export an XPath expression as PHP with special consideration for XPath variables
	*
	* Will return PHP source representing the XPath expression, with special consideration for XPath
	* variables which are returned as a method call to $this->getParamAsXPath()
	*
	* @param  string $expr XPath expression
	* @return string       PHP representation of the expression
	*/
	protected function exportXPath($expr)
	{
		$phpTokens = [];
		$pos = 0;
		$len = strlen($expr);
		while ($pos < $len)
		{
			// If we have a string literal, capture it and add its PHP representation
			if ($expr[$pos] === "'" || $expr[$pos] === '"')
			{
				$nextPos = strpos($expr, $expr[$pos], 1 + $pos);
				if ($nextPos === false)
				{
					throw new RuntimeException('Unterminated string literal in XPath expression ' . var_export($expr, true));
				}

				// Capture the string
				$phpTokens[] = var_export(substr($expr, $pos, $nextPos + 1 - $pos), true);

				// Move the cursor past the string
				$pos = $nextPos + 1;

				continue;
			}

			// Variables in XPath expressions have to be resolved at runtime via getParamAsXPath()
			if ($expr[$pos] === '$' && preg_match('/\\$(\\w+)/', $expr, $m, 0, $pos))
			{
				$phpTokens[] = '$this->getParamAsXPath(' . var_export($m[1], true) . ')';
				$pos += strlen($m[0]);

				continue;
			}

			// Capture everything up to the next interesting character
			$spn = strcspn($expr, '\'"$', $pos);
			if ($spn)
			{
				$phpTokens[] = var_export(substr($expr, $pos, $spn), true);
				$pos += $spn;
			}
		}

		return implode('.', $phpTokens);
	}

	/**
	* Generate a regexp used to parse XPath expressions
	*
	* @return void
	*/
	protected function generateXPathRegexp()
	{
		if (isset($this->regexp))
		{
			return;
		}

		$patterns = [
			'attr'      => ['@', '(?<attr0>[-\\w]+)'],
			'dot'       => '\\.',
			'name'      => 'name\\(\\)',
			'lname'     => 'local-name\\(\\)',
			'param'     => ['\\$', '(?<param0>\\w+)'],
			'string'    => '"[^"]*"|\'[^\']*\'',
			'number'    => ['-?', '\\d++'],
			'strlen'    => ['string-length', '\\(', '(?<strlen0>(?&value)?)', '\\)'],
			'contains'  => [
				'contains',
				'\\(',
				'(?<contains0>(?&value))',
				',',
				'(?<contains1>(?&value))',
				'\\)'
			],
			'translate' => [
				'translate',
				'\\(',
				'(?<translate0>(?&value))',
				',',
				'(?<translate1>(?&string))',
				',',
				'(?<translate2>(?&string))',
				'\\)'
			],
			'substr' => [
				'substring',
				'\\(',
				'(?<substr0>(?&value))',
				',',
				'(?<substr1>(?&value))',
				'(?:, (?<substr2>(?&value)))?',
				'\\)'
			],
			'substringafter' => [
				'substring-after',
				'\\(',
				'(?<substringafter0>(?&value))',
				',',
				'(?<substringafter1>(?&string))',
				'\\)'
			],
			'substringbefore' => [
				'substring-before',
				'\\(',
				'(?<substringbefore0>(?&value))',
				',',
				'(?<substringbefore1>(?&value))',
				'\\)'
			],
			'startswith' => [
				'starts-with',
				'\\(',
				'(?<startswith0>(?&value))',
				',',
				'(?<startswith1>(?&value))',
				'\\)'
			],
			'math' => [
				'(?<math0>(?&attr)|(?&number)|(?&param))',
				'(?<math1>[-+*]|div)',
				'(?<math2>(?&math)|(?&math0))'
			],
			'notcontains' => [
				'not',
				'\\(',
				'contains',
				'\\(',
				'(?<notcontains0>(?&value))',
				',',
				'(?<notcontains1>(?&value))',
				'\\)',
				'\\)'
			]
		];

		$exprs = [];
		if (version_compare($this->pcreVersion, '8.13', '>='))
		{
			// Create a regexp that matches a comparison such as "@foo = 1"
			// NOTE: cannot support < or > because of NaN -- (@foo<5) returns false if @foo=''
			$exprs[] = '(?<cmp>(?<cmp0>(?&value)) (?<cmp1>!?=) (?<cmp2>(?&value)))';

			// Create a regexp that matches a parenthesized expression
			// NOTE: could be expanded to support any expression
			$exprs[] = '(?<parens>\\( (?<parens0>(?&bool)|(?&cmp)|(?&math)) \\))';

			// Create a regexp that matches boolean operations
			$exprs[] = '(?<bool>(?<bool0>(?&cmp)|(?&not)|(?&value)|(?&parens)) (?<bool1>and|or) (?<bool2>(?&bool)|(?&cmp)|(?&not)|(?&value)|(?&parens)))';

			// Create a regexp that matches not() expressions
			$exprs[] = '(?<not>not \\( (?<not0>(?&bool)|(?&value)) \\))';

			// Modify the math pattern to accept parenthesized expressions
			$patterns['math'][0] = str_replace('))', ')|(?&parens))', $patterns['math'][0]);
			$patterns['math'][1] = str_replace('))', ')|(?&parens))', $patterns['math'][1]);
		}

		// Create a regexp that matches values, such as "@foo" or "42"
		$valueExprs = [];
		foreach ($patterns as $name => $pattern)
		{
			if (is_array($pattern))
			{
				$pattern = implode(' ', $pattern);
			}

			if (strpos($pattern, '?&') === false || version_compare($this->pcreVersion, '8.13', '>='))
			{
				$valueExprs[] = '(?<' . $name . '>' . $pattern . ')';
			}
		}
		array_unshift($exprs, '(?<value>' . implode('|', $valueExprs) . ')');


		// Assemble the final regexp
		$regexp = '#^(?:' . implode('|', $exprs) . ')$#S';

		// Replace spaces with any amount of whitespace
		$regexp = str_replace(' ', '\\s*', $regexp);

		$this->regexp = $regexp;
	}
}