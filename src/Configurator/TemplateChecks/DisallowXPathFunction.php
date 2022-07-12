<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowXPathFunction extends TemplateCheck
{
	/**
	* @var string Name of the disallowed function
	*/
	public $funcName;

	/**
	* Constructor
	*
	* @param  string $funcName Name of the disallowed function
	*/
	public function __construct($funcName)
	{
		$this->funcName = $funcName;
	}

	/**
	* Test for the presence of given XPath function
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		// Regexp that matches the function call
		$regexp = '#(?!<\\pL)' . preg_quote($this->funcName, '#') . '\\s*\\(#iu';

		// Allow whitespace around colons (NOTE: colons are unnecessarily escaped by preg_quote())
		$regexp = str_replace('\\:', '\\s*:\\s*', $regexp);

		foreach ($this->getExpressions($template) as $expr => $node)
		{
			// Remove string literals from the expression
			$expr = preg_replace('#([\'"]).*?\\1#s', '', $expr);

			// Test whether the expression contains a document() call
			if (preg_match($regexp, $expr))
			{
				throw new UnsafeTemplateException('An XPath expression uses the ' . $this->funcName . '() function', $node);
			}
		}
	}

	/**
	* Get all the potential XPath expressions used in given template
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return array                XPath expression as key, reference node as value
	*/
	protected function getExpressions(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$exprs = [];

		foreach ($xpath->query('//@*') as $attribute)
		{
			if ($attribute->parentNode->namespaceURI === self::XMLNS_XSL)
			{
				// Attribute of an XSL element. May or may not use XPath, but it shouldn't produce
				// false-positives
				$expr = $attribute->value;
				$exprs[$expr] = $attribute;
			}
			else
			{
				// Attribute of an HTML (or otherwise) element -- Look for inline expressions
				foreach (AVTHelper::parse($attribute->value) as $token)
				{
					if ($token[0] === 'expression')
					{
						$exprs[$token[1]] = $attribute;
					}
				}
			}
		}

		return $exprs;
	}
}