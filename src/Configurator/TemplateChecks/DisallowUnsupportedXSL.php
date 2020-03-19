<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

abstract class DisallowUnsupportedXSL extends TemplateCheck
{
	/**
	* @var string[] 
	*/
	protected $supportedElements[
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
		'when'
	];

	/**
	* Check for elements not supported by the PHP renderer
	*
	* @param DOMElement $template <xsl:template/> node
	* @param Tag        $tag      Tag this template belongs to
	*/
	public function check(DOMElement $template, Tag $tag): void
	{
		$this->checkXslElements();
	}

	/**
	* Check XSL elements in given template
	*/
	protected function checkXslElements(DOMElement $template): void
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$nodes = $xpath->query('/xsl:template//xsl:*');
		foreach ($nodes as $node)
		{
			if (!in_array($node->localName, $this->supportedElements, true))
			{
				throw new RuntimeException('xsl:' . $node->localName . ' elements are not supported');
			}

			$methodName = 'checkXsl' . str_replace(' ', '', ucwords(str_replace('-', ' ', $node->localName))) . 'Element';
			if (method_exists($this, $methodName))
			{
				$this->$methodName($node);
			}
		}
	}

	protected function checkXslCopyOfElement(DOMElement $copyOf): void
	{
		if (!$copyOf->hasAttribute('select'))
		{
			throw new RuntimeException('xsl:copy-of elements require a select attribute');
		}

		$expr = $node->getAttribute('select');
		if (!preg_match('#^@[-\\w]+(?:\\s*\\|\\s*@[-\\w]+)*$#', $expr) && $expr !== '@*')
		{
			throw new RuntimeException("Unsupported xsl:copy-of expression '" . $expr . "'");
		}
	}

	protected function checkXslIfElement(DOMElement $if): void
	{
		if (!$if->hasAttribute('test'))
		{
			throw new RuntimeException('xsl:if elements require a test attribute');
		}
	}
}