<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;

class DisallowUnsupportedXSL extends AbstractXSLSupportCheck
{
	/**
	* @var string[] 
	*/
	protected $supportedElements = [
		'apply-templates',
		'attribute',
		'choose',
		'comment',
		'copy-of',
		'element',
		'if',
		'otherwise',
		'text',
		'value-of',
		'variable',
		'when'
	];

	protected function checkXslApplyTemplatesElement(DOMElement $applyTemplates): void
	{
		if ($applyTemplates->hasAttribute('mode'))
		{
			throw new RuntimeException('xsl:apply-templates elements do not support the mode attribute');
		}
	}

	protected function checkXslCopyOfElement(DOMElement $copyOf): void
	{
		if (!$copyOf->hasAttribute('select'))
		{
			throw new RuntimeException('xsl:copy-of elements require a select attribute');
		}
	}

	protected function checkXslCopyOfElement(DOMElement $copyOf): void
	{
		if (!$copyOf->hasAttribute('select'))
		{
			throw new RuntimeException('xsl:copy-of elements require a select attribute');
		}
	}

	protected function checkXslIfElement(DOMElement $if): void
	{
		if (!$if->hasAttribute('test'))
		{
			throw new RuntimeException('xsl:if elements require a test attribute');
		}
	}

	protected function checkXslValueOfElement(DOMElement $valueOf): void
	{
		if (!$valueOf->hasAttribute('select'))
		{
			throw new RuntimeException('xsl:value-of elements require a select attribute');
		}
	}

	protected function checkXslVariable(DOMElement $variable): void
	{
		if (!$variable->hasAttribute('name'))
		{
			throw new RuntimeException('xsl:variable elements require a name attribute');
		}
	}

	protected function checkXslWhenElement(DOMElement $when): void
	{
		if (!$when->hasAttribute('test'))
		{
			throw new RuntimeException('xsl:when elements require a test attribute');
		}
	}
}