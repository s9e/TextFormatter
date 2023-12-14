<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use RuntimeException;

class DisallowUnsupportedXSL extends AbstractXSLSupportCheck
{
	protected $supportedElements = ['apply-templates', 'attribute', 'choose', 'comment', 'copy-of', 'element', 'for-each', 'if', 'number', 'otherwise', 'processing-instruction', 'sort', 'text', 'value-of', 'variable', 'when'];

	protected $supportedFunctions = ['boolean', 'ceiling', 'concat', 'contains', 'count', 'current', 'document', 'element-available', 'false', 'floor', 'format-number', 'function-available', 'generate-id', 'id', 'key', 'lang', 'last', 'local-name', 'name', 'namespace-uri', 'normalize-space', 'not', 'number', 'position', 'round', 'starts-with', 'string', 'string-length', 'substring', 'substring-after', 'substring-before', 'sum', 'system-property', 'translate', 'true', 'unparsed-entity-uri'];

	protected function checkXslApplyTemplatesElement(DOMElement $applyTemplates): void
	{
		if ($applyTemplates->hasAttribute('mode'))
		{
			throw new RuntimeException('xsl:apply-templates elements do not support the mode attribute');
		}
	}

	protected function checkXslAttributeElement(DOMElement $attribute): void
	{
		$this->requireAttribute($attribute, 'name');

		// https://html.spec.whatwg.org/multipage/syntax.html#attributes-2
		// Simplified for convenience
		$regexp = '(^(?=[{\\pL])(?:\\{[^\\}]++\\}|[^\\pC\\s"-,/;-@\\x{FDD0}-\\x{FDEF}\\x{FFFE}\\x{FFFF}\\x{1FFFE}\\x{1FFFF}\\x{2FFFE}\\x{2FFFF}\\x{3FFFE}\\x{3FFFF}\\x{4FFFE}\\x{4FFFF}\\x{5FFFE}\\x{5FFFF}\\x{6FFFE}\\x{6FFFF}\\x{7FFFE}\\x{7FFFF}\\x{8FFFE}\\x{8FFFF}\\x{9FFFE}\\x{9FFFF}\\x{AFFFE}\\x{AFFFF}\\x{BFFFE}\\x{BFFFF}\\x{CFFFE}\\x{CFFFF}\\x{DFFFE}\\x{DFFFF}\\x{EFFFE}\\x{EFFFF}\\x{FFFFE}\\x{FFFFF}\\x{10FFFE}\\x{10FFFF}])++$)Du';

		$attrName = $attribute->getAttribute('name');
		if (!preg_match($regexp, $attrName))
		{
			throw new RuntimeException("Unsupported xsl:attribute name '" . $attrName . "'");
		}
	}

	protected function checkXslCopyOfElement(DOMElement $copyOf): void
	{
		$this->requireAttribute($copyOf, 'select');
	}

	protected function checkXslElementElement(DOMElement $element): void
	{
		$this->requireAttribute($element, 'name');

		$elName = $element->getAttribute('name');
		if (!preg_match('(^(?:\\{[^\\}]++\\}|[-.\\pL])++(?::(?:\\{[^\\}]++\\}|[-.\\pL])++)?$)Du', $elName))
		{
			throw new RuntimeException("Unsupported xsl:element name '" . $elName . "'");
		}
	}

	protected function checkXslIfElement(DOMElement $if): void
	{
		$this->requireAttribute($if, 'test');
	}

	protected function checkXslValueOfElement(DOMElement $valueOf): void
	{
		$this->requireAttribute($valueOf, 'select');
	}

	protected function checkXslVariableElement(DOMElement $variable): void
	{
		$this->requireAttribute($variable, 'name');
	}

	protected function checkXslWhenElement(DOMElement $when): void
	{
		$this->requireAttribute($when, 'test');
	}

	protected function requireAttribute(DOMElement $element, string $attrName)
	{
		if (!$element->hasAttribute($attrName))
		{
			throw new RuntimeException('xsl:' . $element->localName . ' elements require a ' . $attrName . ' attribute');
		}
	}
}