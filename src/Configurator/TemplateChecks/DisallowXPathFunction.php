<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
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
	* @return void
	*/
	public function __construct($funcName)
	{
		$this->funcName = $funcName;
	}

	/**
	* Test for the presence of given XPath function
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag     $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$check = [];

		foreach ($xpath->query('//@*') as $attribute)
		{
			if ($attribute->parentNode->namespaceURI === 'http://www.w3.org/1999/XSL/Transform')
			{
				// Attribute of an XSL element. May or may not use XPath, but it shouldn't produce
				// false-positives
				$check[] = [$attribute, $attribute->value];
			}
			else
			{
				// Attribute of an HTML (or otherwise) element -- Look for inline expressions
				foreach (TemplateHelper::parseAttributeValueTemplate($attribute->value) as $token)
				{
					if ($token[0] === 'expression')
					{
						$check[] = [$attribute, $token[1]];
					}
				}
			}
		}

		// Regexp that matches the function call
		$regexp = '#(?!<\\pL)' . preg_quote($this->funcName, '#') . '\\s*\\(#iu';

		// Allow whitespace around colons (NOTE: colons are unnecessarily escaped by preg_quote())
		$regexp = str_replace('\\:', '\\s*:\\s*', $regexp);

		foreach ($check as list($attribute, $expr))
		{
			// Remove string literals from the expression
			$expr = preg_replace('#([\'"]).*?\\1#s', '', $expr);

			// Test whether the expression contains a document() call
			if (preg_match($regexp, $expr))
			{
				throw new UnsafeTemplateException('An XPath expression uses the ' . $this->funcName . '() function', $attribute);
			}
		}
	}
}