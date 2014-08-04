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
	* @var array Branch tables created during last serialization
	*/
	public $branchTables = [];

	/**
	* @var Convertor XPath-to-PHP convertor
	*/
	public $convertor;

	/**
	* @var string Output method
	*/
	public $outputMethod = 'html';

	/**
	* @var bool Whether to use the mbstring functions as a replacement for XPath expressions
	*/
	public $useMultibyteStringFunctions = false;

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->convertor = new XPathConvertor;
	}

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
		return $this->convertor->convertCondition($expr, $this->useMultibyteStringFunctions);
	}

	/**
	* Convert an XPath expression (used as value) into PHP code
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	public function convertXPath($expr)
	{
		return $this->convertor->convertXPath($expr, $this->useMultibyteStringFunctions);
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
	* Serialize the internal representation of a template into PHP
	*
	* @param  DOMElement $ir Internal representation
	* @return string
	*/
	public function serialize(DOMElement $ir)
	{
		$this->branchTables = [];

		return $this->serializeChildren($ir);
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

		if (!($element instanceof DOMElement))
		{
			throw new RuntimeException;
		}

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
	* Serialize a <switch/> node that has a branch-key attribute
	*
	* @param  DOMElement $switch <switch/> node
	* @return string
	*/
	protected function serializeHash(DOMElement $switch)
	{
		$statements = [];
		foreach ($switch->getElementsByTagName('case') as $case)
		{
			if (!$case->parentNode->isSameNode($switch))
			{
				continue;
			}

			if ($case->hasAttribute('branch-values'))
			{
				$php = $this->serializeChildren($case);
				foreach (json_decode($case->getAttribute('branch-values')) as $value)
				{
					$statements[$value] = $php;
				}
			}
		}

		if (!isset($case))
		{
			throw new RuntimeException;
		}

		list($branchTable, $php) = Quick::generateBranchTable('$n', $statements);

		// The name of the branching table is based on its content
		$varName = 'bt' . sprintf('%08X', crc32(serialize($branchTable)));
		$expr = 'self::$' . $varName . '[' . $this->convertXPath($switch->getAttribute('branch-key')) . ']';
		$php = 'if(isset(' . $expr . ')){$n=' . $expr . ';' . $php . '}';

		// Test whether the last case has a branch-values. If not, it's the default case
		if (!$case->hasAttribute('branch-values'))
		{
			$php .= 'else{' . $this->serializeChildren($case) . '}';
		}

		// Save the branching table
		$this->branchTables[$varName] = $branchTable;

		return $php;
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
		$escapeMode = ($output->getAttribute('escape') === 'attribute')
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
		// Use a specialized branch table only if there are more than 7 elements because of the
		// overhead of setting it up
		if ($switch->hasAttribute('branch-key')
		 && $switch->childNodes->length > 7)
		{
			return $this->serializeHash($switch);
		}

		$php  = '';
		$else = '';

		foreach ($switch->getElementsByTagName('case') as $case)
		{
			if (!$case->parentNode->isSameNode($switch))
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