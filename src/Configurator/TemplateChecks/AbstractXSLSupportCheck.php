<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

abstract class AbstractXSLSupportCheck extends TemplateCheck
{
	/**
	* @var string[] List of supported XSL elements (local name only)
	*/
	protected $supportedElements = [];

	/**
	* @var string[]  List of supported XPath functions
	*/
	protected $supportedFunctions = [];

	/**
	* Check for elements not supported by the PHP renderer
	*
	* @param DOMElement $template <xsl:template/> node
	* @param Tag        $tag      Tag this template belongs to
	*/
	public function check(DOMElement $template, Tag $tag): void
	{
		$this->checkXslElements($template);
		$this->checkXPathExpressions($template);
	}

	/**
	* Check given XPath expression
	*/
	protected function checkXPathExpression(string $expr): void
	{
		preg_match_all('("[^"]*+"|\'[^\']*+\'|(*:fn)[-\\w]++(?=\\s*\\())', $expr, $m);
		if (!empty($m['MARK']))
		{
			$funcNames = array_intersect_key($m[0], $m['MARK']);
			foreach ($funcNames as $funcName)
			{
				if (!in_array($funcName, $this->supportedFunctions, true))
				{
					throw new RuntimeException('XPath function ' . $funcName . '() is not supported');
				}
			}
		}
	}

	/**
	* Check all XPath expressions in given template
	*/
	protected function checkXPathExpressions(DOMElement $template): void
	{
		foreach ($this->getXPathExpressions($template) as $expr)
		{
			$this->checkXPathExpression($expr);
		}
	}

	/**
	* Check all XSL elements in given template
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

	/**
	* Return all XPath expressions in given template
	*/
	protected function getXPathExpressions(DOMElement $template): array
	{
		$exprs = [];
		$xpath = new DOMXPath($template->ownerDocument);

		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			foreach (AVTHelper::parse($attribute->value) as [$type, $content])
			{
				if ($type === 'expression')
				{
					$exprs[] = $content;
				}
			}
		}

		$query = '//xsl:if/@test | //xsl:value-of/@select | //xsl:when/@test';
		foreach ($xpath->query($query) as $attribute)
		{
			$exprs[] = $attribute->value;
		}

		return $exprs;
	}
}