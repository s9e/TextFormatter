<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;

class Serializer
{
	/**
	* @var array Custom XPath representations as [xpath => php]
	*/
	protected $customXPath = [
		// BBcodes: LIST
		"contains('upperlowerdecim',substring(@type,1,5))"
			=> "strpos('upperlowerdecim',substr(\$node->getAttribute('type'),0,5))!==false",

		// MediaEmbed: Bandcamp
		'120-78*boolean(@track_id|@track_num)'
			=> "(\$node->hasAttribute('track_id')||\$node->hasAttribute('track_num')?42:120)",

		// MediaEmbed: Grooveshark
		"substring('songWw',6-5*boolean(@songid),5)"
			=> "(\$node->hasAttribute('songid')?'songW':'w')",

		"250-210*boolean(@songid)"
			=> "(\$node->hasAttribute('songid')?40:250)",

		// MediaEmbed: Spotify
		"380-300*(contains(@uri,':track:')orcontains(@path,'/track/'))"
			=> "(strpos(\$node->getAttribute('uri'),':track:')!==false||strpos(\$node->getAttribute('path'),'/track/')!==false?80:380)",

		// MediaEmbed: Twitch
		"substring('archl',5-4*boolean(@archive_id|@chapter_id),4)"
			=> "(\$node->hasAttribute('archive_id')||\$node->hasAttribute('chapter_id')?'arch':'l')"
	];

	/**
	* @var string Output method
	*/
	public $outputMethod = 'html';

	/**
	* Convert an attribute value template into PHP
	*
	* NOTE: escaping must be performed by the caller
	*
	* @link http://www.w3.org/TR/xslt#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value template
	* @return string
	*/
	protected function convertAttributeValueTemplate($attrValue)
	{
		$phpExpressions = [];
		foreach (TemplateHelper::parseAttributeValueTemplate($attrValue) as $token)
		{
			if ($token[0] === 'literal')
			{
				$phpExpressions[] = var_export($token[1], true);
			}
			else
			{
				$phpExpressions[] = $this->convertXPath($token[1]);
			}
		}

		return implode('.', $phpExpressions);
	}

	/**
	* Convert an XPath condition into a PHP condition
	*
	* This method is similar to convertXPath() but it selectively replaces some simple conditions
	* with the corresponding DOM method for performance reasons
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	protected function convertCondition($expr)
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
	* Convert an XPath expression into PHP code
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	protected function convertXPath($expr)
	{
		static $regexp;

		$expr = trim($expr);

		// Use the custom representation if applicable
		if (isset($this->customXPath[$expr]))
		{
			return $this->customXPath[$expr];
		}

		if (!isset($regexp))
		{
			$patterns = [
				'attr'      => ['@', '(?<attrName>[-\\w]+)'],
				'dot'       => '\\.',
				'not'       => ['not', '\\(', '(?&value)', '\\)'],
				'name'      => 'name\\(\\)',
				'lname'     => 'local-name\\(\\)',
				'param'     => ['\\$', '(?<paramName>\\w+)'],
				'string'    => '"[^"]*"|\'[^\']*\'',
				'number'    => ['-?', '\\d++'],
				'strlen'    => ['string-length', '\\(', '(?<strlen0>(?&value))?', '\\)'],
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
				'substr'    => [
					'substring',
					'\\(',
					'(?<substr0>(?&value))',
					',',
					'(?<substr1>(?&value))',
					'(?:, (?<substr2>(?&value)))?',
					'\\)'
				]
			];

			$exprs = [];

			// Create a regexp that matches values, such as "@foo" or "42"
			$valueExprs = [];
			foreach ($patterns as $name => $pattern)
			{
				if (is_array($pattern))
				{
					$pattern = implode(' ', $pattern);
				}

				$valueExprs[] = '(?<' . $name . '>' . $pattern . ')';
			}
			$exprs[] = '(?<value>' . implode('|', $valueExprs) . ')';

			// Create a regexp that matches a comparison such as "@foo = 1"
			// NOTE: cannot support < or > because of NaN -- (@foo<5) returns false if @foo=''
			$exprs[] = '(?<cmp>(?<cmp0>(?&value)) (?<cmp1>!?=) (?<cmp2>(?&value)))';

			// Match parenthesized expressions on PCRE >= 8.13, previous versions segfault
			// because of the mutual references
			$parensMatch = '';
			if (version_compare(PCRE_VERSION, '8.13', '>='))
			{
				$parensMatch = '|(?&parens)';

				// Create a regexp that matches a parenthesized expression
				// NOTE: could be expanded to support any expression
				$exprs[] = '(?<parens>\\( (?<parens0>(?&bool)|(?&cmp)) \\))';
			}

			// Create a regexp that matches boolean operations
			$exprs[] = '(?<bool>(?<bool0>(?&cmp)|(?&value)' . $parensMatch . ') (?<bool1>and|or) (?<bool2>(?&cmp)|(?&value)|(?&bool)' . $parensMatch . '))';

			// Assemble the final regexp
			$regexp = '#^(?:' . implode('|', $exprs) . ')$#S';

			// Replace spaces with any amount of whitespace
			$regexp = str_replace(' ', '\\s*', $regexp);
		}

		if (preg_match($regexp, $expr, $m))
		{
			if (!empty($m['attrName']))
			{
				// XSL: <xsl:value-of select="@foo"/>
				// PHP: $this->out .= $node->getAttribute('foo');
				return '$node->getAttribute(' . var_export($m['attrName'], true) . ')';
			}

			// XSL: <xsl:value-of select="."/>
			// PHP: $this->out .= $node->textContent;
			if (!empty($m['dot']))
			{
				return '$node->textContent';
			}

			// XSL: <xsl:value-of select="$foo"/>
			// PHP: $this->out .= $this->params['foo'];
			if (!empty($m['paramName']))
			{
				return '$this->params[' . var_export($m['paramName'], true) . ']';
			}

			// XSL: <xsl:value-of select="'foo'"/>
			// XSL: <xsl:value-of select='"foo"'/>
			// PHP: $this->out .= 'foo';
			if (!empty($m['string']))
			{
				return var_export(substr($m['string'], 1, -1), true);
			}

			// XSL: <xsl:value-of select="local-name()"/>
			// PHP: $this->out .= $node->localName;
			if (!empty($m['lname']))
			{
				return '$node->localName';
			}

			// XSL: <xsl:value-of select="name()"/>
			// PHP: $this->out .= $node->nodeName;
			if (!empty($m['name']))
			{
				return '$node->nodeName';
			}

			// XSL: <xsl:value-of select="3"/>
			// PHP: $this->out .= '3';
			if (!empty($m['number']))
			{
				return "'" . $expr . "'";
			}

			// XSL: <xsl:value-of select="string-length(@foo)"/>
			// PHP: $this->out .= mb_strlen($node->getAttribute('foo'),'utf-8');
			if (!empty($m['strlen']) && $this->useMultibyteStringFunctions)
			{
				if (!isset($m['strlen0']))
				{
					$m['strlen0'] = '.';
				}

				return 'mb_strlen(' . $this->convertXPath($m['strlen0']) . ",'utf-8')";
			}

			// XSL: <xsl:value-of select="substring(@foo, 1, 2)"/>
			// PHP: $this->out .= mb_substring($node->getAttribute('foo'),0,2,'utf-8');
			//
			// NOTE: negative values for the second argument do not produce the same result as
			//       specified in XPath if the argument is not a literal number
			if (!empty($m['substr']) && $this->useMultibyteStringFunctions)
			{
				$php = 'mb_substr(' . $this->convertXPath($m['substr0']) . ',';

				// Hardcode the value if possible
				if (preg_match('#^\\d+$#D', $m['substr1']))
				{
					$php .= max(0, $m['substr1'] - 1);
				}
				else
				{
					$php .= 'max(0,' . $this->convertXPath($m['substr1']) . '-1)';
				}

				$php .= ',';

				if (isset($m['substr2']))
				{
					if (preg_match('#^\\d+$#D', $m['substr2']))
					{
						// Handles substring(0,2) as per XPath
						if (preg_match('#^\\d+$#D', $m['substr1']) && $m['substr1'] < 1)
						{
							$php .= max(0, $m['substr1'] + $m['substr2'] - 1);
						}
						else
						{
							$php .= max(0, $m['substr2']);
						}
					}
					else
					{
						$php .= 'max(0,' . $this->convertXPath($m['substr2']) . ')';
					}
				}
				else
				{
					$php .= 'null';
				}

				$php .= ",'utf-8')";

				return $php;
			}

			if (!empty($m['contains']))
			{
				return '(strpos(' . $this->convertXPath($m['contains0']) . ',' . $this->convertXPath($m['contains1']) . ')!==false)';
			}

			if (!empty($m['cmp1']))
			{
				$operators = [
					'='  => '===',
					'!=' => '!==',
					'>'  => '>',
					'>=' => '>=',
					'<'  => '<',
					'<=' => '<='
				];

				// If either operand is a number, represent it as a PHP number and replace the
				// identity operators
				foreach (['cmp0', 'cmp2'] as $k)
				{
					if (preg_match('#^\\d+$#', $m[$k]))
					{
						$operators['=']  = '==';
						$operators['!='] = '!=';

						$m[$k] = ltrim($m[$k], '0');
					}
					else
					{
						$m[$k] = $this->convertXPath($m[$k]);
					}
				}

				return $m['cmp0'] . $operators[$m['cmp1']] . $m['cmp2'];
			}

			if (!empty($m['bool1']))
			{
				$operators = [
					'and' => '&&',
					'or'  => '||'
				];

				return $this->convertCondition($m['bool0']) . $operators[$m['bool1']] . $this->convertCondition($m['bool2']);
			}

			if (!empty($m['parens']))
			{
				return '(' . $this->convertXPath($m['parens0']) . ')';
			}

			if (!empty($m['translate']))
			{
				preg_match_all('/./u', substr($m['translate1'], 1, -1), $matches);
				$from = $matches[0];

				preg_match_all('/./u', substr($m['translate2'], 1, -1), $matches);
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
				$php = 'strtr(' . $this->convertXPath($m['translate0']) . ',';

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
		}

		// If the condition does not seem to contain a relational expression, or start with a
		// function call, we wrap it inside of a string() call
		if (!preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\(#', $expr))
		{
			$expr = 'string(' . $expr . ')';
		}

		// Parse the expression for variables
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

		return '$this->xpath->evaluate(' . implode('.', $phpTokens) . ',$node)';
	}

	/**
	* Serialize an <applyTemplates/> node
	*
	* @param  DOMElement $applyTemplates <applyTemplates/> node
	* @return string
	*/
	protected function serializeApplyTemplates(DOMElement $applyTemplates)
	{
		$php = '$this->at($node';
		if ($applyTemplates->hasAttribute('select'))
		{
			$php .= ',' . var_export($applyTemplates->getAttribute('select'), true);
		}
		$php .= ');';

		return $php;
	}

	/**
	* Serialize an <attribute/> node
	*
	* @param  DOMElement $attribute <attribute/> node
	* @return string
	*/
	protected function serializeAttribute(DOMElement $attribute)
	{
		$attrName = $attribute->getAttribute('name');

		// PHP representation of this attribute's name
		$phpAttrName = $this->convertAttributeValueTemplate($attrName);

		// NOTE: the attribute name is escaped by default to account for dynamically-generated names
		$phpAttrName = 'htmlspecialchars(' . $phpAttrName . ',' . ENT_QUOTES . ')';

		return "\$this->out.=' '." . $phpAttrName . ".'=\"';"
		     . $this->serializeChildren($attribute)
		     . "\$this->out.='\"';";
	}

	/**
	* Serialize all the children of given node into PHP
	*
	* @param  DOMElement $ir Internal representation
	* @return string
	*/
	public function serializeChildren(DOMElement $ir)
	{
		$php = '';
		foreach ($ir->childNodes as $node)
		{
			$methodName = 'serialize' . ucfirst($node->localName);
			$php .= $this->$methodName($node);
		}

		return $php;
	}

	/**
	* Serialize a <closeTag/> node
	*
	* @param  DOMElement $closeTag <closeTag/> node
	* @return string
	*/
	protected function serializeCloseTag(DOMElement $closeTag)
	{
		$php = '';
		$id  = $closeTag->getAttribute('id');

		if ($closeTag->hasAttribute('check'))
		{
			$php .= 'if(!isset($t' . $id . ')){';
		}

		if ($closeTag->hasAttribute('set'))
		{
			$php .= '$t' . $id . '=1;';
		}

		// Get the element that's being closed
		$xpath   = new DOMXPath($closeTag->ownerDocument);
		$element = $xpath->query('ancestor::element[@id="' . $id . '"]', $closeTag)->item(0);
		$isVoid  = $element->getAttribute('void');
		$isEmpty = $element->getAttribute('empty');

		if ($this->outputMethod === 'html')
		{
			$php .= "\$this->out.='>';";

			if ($isVoid === 'maybe')
			{
				// Check at runtime whether this element is not void
				$php .= 'if(!$v' . $id . '){';
			}
		}
		else
		{
			// In XML mode, we only care about whether this element is empty
			if ($isEmpty === 'yes')
			{
				// Definitely empty, use a self-closing tag
				$php .= "\$this->out.='/>';";
			}
			else
			{
				// Since it's not definitely empty, we'll close this start tag normally
				$php .= "\$this->out.='>';";

				if ($isEmpty === 'maybe')
				{
					// Maybe empty, record the length of the output and if it doesn't grow we'll
					// change the start tag into a self-closing tag
					$php .= '$l' . $id . '=strlen($this->out);';
				}
			}
		}

		if ($closeTag->hasAttribute('check'))
		{
			$php .= '}';
		}

		return $php;
	}

	/**
	* Serialize a <comment/> node
	*
	* @param  DOMElement $comment <comment/> node
	* @return string
	*/
	protected function serializeComment(DOMElement $comment)
	{
		return "\$this->out.='<!--';"
		     . $this->serializeChildren($comment)
		     . "\$this->out.='-->';";
	}

	/**
	* Serialize a <copyOfAttributes/> node
	*
	* @param  DOMElement $copyOfAttributes <copyOfAttributes/> node
	* @return string
	*/
	protected function serializeCopyOfAttributes(DOMElement $copyOfAttributes)
	{
		return 'foreach($node->attributes as $attribute)'
		     . '{'
		     . "\$this->out.=' ';"
		     . "\$this->out.=\$attribute->name;"
		     . "\$this->out.='=\"';"
		     . "\$this->out.=htmlspecialchars(\$attribute->value," . ENT_COMPAT . ");"
		     . "\$this->out.='\"';"
		     . '}';
	}

	/**
	* Serialize an <element/> node
	*
	* @param  DOMElement $element <element/> node
	* @return string
	*/
	protected function serializeElement(DOMElement $element)
	{
		$php     = '';
		$elName  = $element->getAttribute('name');
		$id      = $element->getAttribute('id');
		$isVoid  = $element->getAttribute('void');
		$isEmpty = $element->getAttribute('empty');

		// Test whether this element name is dynamic
		$isDynamic = (bool) (strpos($elName, '{') !== false);

		// PHP representation of this element's name
		$phpElName = $this->convertAttributeValueTemplate($elName);

		// NOTE: the element name is escaped by default to account for dynamically-generated names
		$phpElName = 'htmlspecialchars(' . $phpElName . ',' . ENT_QUOTES . ')';

		// If the element name is dynamic, we cache its name for convenience and performance
		if ($isDynamic)
		{
			$varName = '$e' . $id;

			// Add the var declaration to the source
			$php .= $varName . '=' . $phpElName . ';';

			// Replace the element name with the var
			$phpElName = $varName;
		}

		// Test whether this element is void if we need this information
		if ($this->outputMethod === 'html' && $isVoid === 'maybe')
		{
			$php .= '$v' . $id . '=preg_match(' . var_export(TemplateParser::$voidRegexp, true) . ',' . $phpElName . ');';
		}

		// Open the start tag
		$php .= "\$this->out.='<'." . $phpElName . ';';

		// Serialize this element's content
		$php .= $this->serializeChildren($element);

		// If we're in XHTML mode and the element is or may be empty, we may not need to close it at
		// all
		if ($this->outputMethod === 'xhtml')
		{
			// If this element is definitely empty, it has already been closed with a self-closing
			// tag in serializeCloseTag()
			if ($isEmpty === 'yes')
			{
				return $php;
			}

			// If this element may be empty, we need to check at runtime whether we turn its start
			// tag into a self-closing tag or append an end tag
			if ($isEmpty === 'maybe')
			{
				$php .= 'if($l' . $id . '===strlen($this->out)){';
				$php .= "\$this->out=substr(\$this->out,0,-1).'/>';";
				$php .= '}else{';
				$php .= "\$this->out.='</'." . $phpElName . ".'>';";
				$php .= '}';

				return $php;
			}
		}

		// Close that element, unless we're in HTML mode and we know it's void
		if ($this->outputMethod !== 'html' || $isVoid !== 'yes')
		{
			$php .= "\$this->out.='</'." . $phpElName . ".'>';";
		}

		// If this element was maybe void, serializeCloseTag() has put its content within an if
		// block. We need to close that block
		if ($this->outputMethod === 'html' && $isVoid === 'maybe')
		{
			$php .= '}';
		}

		return $php;
	}

	/**
	* Unused
	* @todo Remove
	*/
	protected function serializeMatch()
	{
		return '';
	}

	/**
	* Serialize an <output/> node
	*
	* @param  DOMElement $output <output/> node
	* @return string
	*/
	protected function serializeOutput(DOMElement $output)
	{
		$php        = '';
		$xpath      = new DOMXPath($output->ownerDocument);
		$escapeMode = ($xpath->evaluate('count(ancestor::attribute)', $output))
		            ? ENT_COMPAT
		            : ENT_NOQUOTES;

		if ($output->getAttribute('type') === 'xpath')
		{
			$php .= '$this->out.=htmlspecialchars(';
			$php .= $this->convertXPath($output->textContent);
			$php .= ',' . $escapeMode . ');';
		}
		else
		{
			// Literal
			$php .= '$this->out.=';
			$php .= var_export(htmlspecialchars($output->textContent, $escapeMode), true);
			$php .= ';';
		}

		return $php;
	}

	/**
	* Serialize a <switch/> node
	*
	* @param  DOMElement $switch <switch/> node
	* @return string
	*/
	protected function serializeSwitch(DOMElement $switch)
	{
		$php  = '';
		$else = '';

		foreach ($switch->getElementsByTagName('case') as $case)
		{
			if ($case->parentNode !== $switch)
			{
				continue;
			}

			if ($case->hasAttribute('test'))
			{
				$php .= $else . 'if(' . $this->convertCondition($case->getAttribute('test')) . ')';
			}
			else
			{
				$php .= 'else';
			}

			$else = 'else';

			$php .= '{';
			$php .= $this->serializeChildren($case);
			$php .= '}';
		}

		return $php;
	}
}