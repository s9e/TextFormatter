<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;

class Serializer
{
	/**
	* @var XPathConvertor XPath-to-PHP convertor
	*/
	public $convertor;

	/**
	* @var array Value of the "void" attribute of all elements, using the element's "id" as key
	*/
	protected $isVoid;

	/**
	* @var DOMXPath
	*/
	protected $xpath;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->convertor = new XPathConvertor;
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
		return $this->convertor->convertCondition($expr);
	}

	/**
	* Convert an XPath expression (used as value) into PHP code
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	public function convertXPath($expr)
	{
		$php = $this->convertor->convertXPath($expr);
		if (is_numeric($php))
		{
			$php = "'" . $php . "'";
		}

		return $php;
	}

	/**
	* Serialize the internal representation of a template into PHP
	*
	* @param  DOMElement $ir Internal representation
	* @return string
	*/
	public function serialize(DOMElement $ir)
	{
		$this->xpath  = new DOMXPath($ir->ownerDocument);
		$this->isVoid = [];
		foreach ($this->xpath->query('//element') as $element)
		{
			$this->isVoid[$element->getAttribute('id')] = $element->getAttribute('void');
		}

		return $this->serializeChildren($ir);
	}

	/**
	* Convert an attribute value template into PHP
	*
	* NOTE: escaping must be performed by the caller
	*
	* @link https://www.w3.org/TR/1999/REC-xslt-19991116#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value template
	* @return string
	*/
	protected function convertAttributeValueTemplate($attrValue)
	{
		$phpExpressions = [];
		foreach (AVTHelper::parse($attrValue) as $token)
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
	* Escape given literal
	*
	* @param  string $text    Literal
	* @param  string $context Either "raw", "text" or "attribute"
	* @return string          Escaped literal
	*/
	protected function escapeLiteral($text, $context)
	{
		if ($context === 'raw')
		{
			return $text;
		}

		$escapeMode = ($context === 'attribute') ? ENT_COMPAT : ENT_NOQUOTES;

		return htmlspecialchars($text, $escapeMode);
	}

	/**
	* Escape the output of given PHP expression
	*
	* @param  string $php     PHP expression
	* @param  string $context Either "raw", "text" or "attribute"
	* @return string          PHP expression, including escaping mechanism
	*/
	protected function escapePHPOutput($php, $context)
	{
		if ($context === 'raw')
		{
			return $php;
		}

		$escapeMode = ($context === 'attribute') ? ENT_COMPAT : ENT_NOQUOTES;

		return 'htmlspecialchars(' . $php . ',' . $escapeMode . ')';
	}

	/**
	* Test whether given switch has more than one non-default case
	*
	* @param  DOMElement $switch <switch/> node
	* @return bool
	*/
	protected function hasMultipleCases(DOMElement $switch)
	{
		return $this->xpath->evaluate('count(case[@test]) > 1', $switch);
	}

	/**
	* Test whether given attribute declaration is a minimizable boolean attribute
	*
	* The test is case-sensitive and only covers attribute that are minimized by libxslt
	*
	* @param  string $attrName Attribute name
	* @param  string $php      Attribute content, in PHP
	* @return boolean
	*/
	protected function isBooleanAttribute(string $attrName, string $php): bool
	{
		$attrNames = ['checked', 'compact', 'declare', 'defer', 'disabled', 'ismap', 'multiple', 'nohref', 'noresize', 'noshade', 'nowrap', 'readonly', 'selected'];
		if (!in_array($attrName, $attrNames, true))
		{
			return false;
		}

		return ($php === '' || $php === "\$this->out.='" . $attrName . "';");
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

		$php     = "\$this->out.=' '." . $phpAttrName;
		$content = $this->serializeChildren($attribute);
		if (!$this->isBooleanAttribute($attrName, $content))
		{
			$php .= ".'=\"';" . $content . "\$this->out.='\"'";
		}
		$php .= ';';

		return $php;
	}

	/**
	* Serialize all the children of given node into PHP
	*
	* @param  DOMElement $ir Internal representation
	* @return string
	*/
	protected function serializeChildren(DOMElement $ir)
	{
		$php = '';
		foreach ($ir->childNodes as $node)
		{
			if ($node instanceof DOMElement)
			{
				$methodName = 'serialize' . ucfirst($node->localName);
				$php .= $this->$methodName($node);
			}
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
		$php = "\$this->out.='>';";
		$id  = $closeTag->getAttribute('id');

		if ($closeTag->hasAttribute('set'))
		{
			$php .= '$t' . $id . '=1;';
		}

		if ($closeTag->hasAttribute('check'))
		{
			$php = 'if(!isset($t' . $id . ')){' . $php . '}';
		}

		if ($this->isVoid[$id] === 'maybe')
		{
			// Check at runtime whether this element is not void
			$php .= 'if(!$v' . $id . '){';
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
		if ($isVoid === 'maybe')
		{
			$php .= '$v' . $id . '=preg_match(' . var_export(TemplateParser::$voidRegexp, true) . ',' . $phpElName . ');';
		}

		// Open the start tag
		$php .= "\$this->out.='<'." . $phpElName . ';';

		// Serialize this element's content
		$php .= $this->serializeChildren($element);

		// Close that element unless we know it's void
		if ($isVoid !== 'yes')
		{
			$php .= "\$this->out.='</'." . $phpElName . ".'>';";
		}

		// If this element was maybe void, serializeCloseTag() has put its content within an if
		// block. We need to close that block
		if ($isVoid === 'maybe')
		{
			$php .= '}';
		}

		return $php;
	}

	/**
	* Serialize a <switch/> node that has a branch-key attribute
	*
	* @param  DOMElement $switch <switch/> node
	* @return string
	*/
	protected function serializeHash(DOMElement $switch)
	{
		$statements = [];
		foreach ($this->xpath->query('case[@branch-values]', $switch) as $case)
		{
			foreach (unserialize($case->getAttribute('branch-values')) as $value)
			{
				$statements[$value] = $this->serializeChildren($case);
			}
		}
		if (!isset($case))
		{
			throw new RuntimeException;
		}

		$defaultCase = $this->xpath->query('case[not(@branch-values)]', $switch)->item(0);
		$defaultCode = ($defaultCase instanceof DOMElement) ? $this->serializeChildren($defaultCase) : '';
		$expr        = $this->convertXPath($switch->getAttribute('branch-key'));

		return SwitchStatement::generate($expr, $statements, $defaultCode);
	}

	/**
	* Serialize an <output/> node
	*
	* @param  DOMElement $output <output/> node
	* @return string
	*/
	protected function serializeOutput(DOMElement $output)
	{
		$context = $output->getAttribute('escape');

		$php = '$this->out.=';
		if ($output->getAttribute('type') === 'xpath')
		{
			$php .= $this->escapePHPOutput($this->convertXPath($output->textContent), $context);
		}
		else
		{
			$php .= var_export($this->escapeLiteral($output->textContent, $context), true);
		}
		$php .= ';';

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
		// Use a specialized branch table if the minimum number of branches is reached
		if ($switch->hasAttribute('branch-key') && $this->hasMultipleCases($switch))
		{
			return $this->serializeHash($switch);
		}

		$php   = '';
		$if    = 'if';
		foreach ($this->xpath->query('case', $switch) as $case)
		{
			if ($case->hasAttribute('test'))
			{
				$php .= $if . '(' . $this->convertCondition($case->getAttribute('test')) . ')';
			}
			else
			{
				$php .= 'else';
			}

			$php .= '{' . $this->serializeChildren($case) . '}';
			$if   = 'elseif';
		}

		return $php;
	}
}