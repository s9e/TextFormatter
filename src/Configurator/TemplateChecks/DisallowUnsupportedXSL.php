<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
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

	protected function checkXslCopyOfElement(DOMElement $copyOf): void
	{
		$this->requireAttribute($copyOf, 'select');
	}

	protected function checkXslAttributeElement(DOMElement $attribute): void
	{
		$this->requireAttribute($attribute, 'name');

		$attrName = $attribute->getAttribute('name');
		if (!preg_match('(^(?:\\{[^\\}]++\\}|[-.\\pL])++$)Du', $attrName))
		{
			throw new RuntimeException("Unsupported xsl:attribute name '" . $attrName . "'");
		}
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