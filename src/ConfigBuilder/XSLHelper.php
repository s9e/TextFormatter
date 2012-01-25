<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use DOMDocument,
    DOMXPath,
    RuntimeException,
    XSLTProcessor;

abstract class XSLHelper
{
	/**
	* @var bool Whether to allow unsafe templates to be used
	*/
	static public $allowUnsafeTemplates = false;

	/**
	* Normalize the XSL of a template
	*
	* Checks for well-formedness, checks for unsafe script tags then removes whitespace and performs
	* some optimizations.
	*
	* @param  string $template Content of the template. A root node is not required
	* @return string           Normalized template
	*/
	protected function normalizeTemplate($template)
	{
		// Put the template inside of a <xsl:template/> node
		$xsl = '<?xml version="1.0" encoding="utf-8" ?>'
		     . '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';

		// Enable libxml's internal errors while we load the template
		$useInternalErrors = libxml_use_internal_errors(true);

		$dom = new DOMDocument;
		$res = $dom->loadXML($xsl);

		libxml_use_internal_errors($useInternalErrors);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Invalid XML - error was: ' . $error->message);
		}

		// Check for unsafe markup
		if (!self::$allowUnsafeTemplates)
		{
			$this->checkUnsafe($dom);
		}

		// Optimize template
		$this->optimizeTemplate($dom);

		// Serialize the XML then remove the outer node
		$xml = $dom->saveXML($dom->documentElement);

		$pos = 1 + strpos($xml, '>');
		$len = strrpos($xml, '<') - $pos;

		$xml = substr($xml, $pos, $len);

		return $xml;
	}

	/**
	* Check an XSL template for unsafe markup
	*
	* @todo Possible additions: unsafe <object> and <embed>, unfiltered attributes in href or
	*       other attributes that expect a URL and may receive a "javascript:" or "data:" URI,
	*       unfiltered @style attributes. Those require to be aware of attributes' filters. Also
	*       <style> tags.
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function checkUnsafe(DOMDocument $dom)
	{
		$this->checkUnsafeScriptTags($dom);
		$this->checkDisableOutputEscaping($dom);
		$this->checkUnsafeEventAttributes($dom);
	}

	/**
	* Check a template for script tags using user-supplied data
	*
	* Looks for <script> tags with a dynamic value in @src, or with any descendant that is a
	* <xsl:value-of>, <xsl:attribute> or <xsl:apply-templates> node.
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function checkUnsafeScriptTags(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		$hasUnsafeScript = (bool) $xpath->evaluate(
			'count(
				//*[translate(name(), "SCRIPT", "script") = "script"]
				   [
					   @*[translate(name(), "SRC", "src") = "src"][contains(., "{")]
					or .//xsl:value-of
					or .//xsl:attribute
					or .//xsl:apply-templates
				   ]
			)'
		);

		if ($hasUnsafeScript)
		{
			throw new RuntimeException('It seems that your template contains a <script> tag that uses user-supplied information. Those can be unsafe and are disabled by default. Please set XSLHelper::$allowUnsafeTemplates to true to enable it.');
		}
	}

	/**
	* Check a template for any tag using @disable-output-escaping
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function checkDisableOutputEscaping(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		if ($xpath->evaluate('count(//@disable-output-escaping)'))
		{
			throw new RuntimeException("It seems that your template contains a 'disable-output-escaping' attribute. Those can be unsafe and are disabled by default. Please set XSLHelper::\$allowUnsafeTemplates to true to enable it.");
		}
	}

	/**
	* Check a template for any tag with a javascript event attribute using dynamic data
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function checkUnsafeEventAttributes(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		// Check for <b onclick="{@foo}"/>
		$attrs = $xpath->query(
			'//@*[starts-with(translate(name(), "ON", "on"), "on")][contains(., "{")]'
		);

		foreach ($attrs as $attr)
		{
			// test for false-positives, IOW escaped brackets
			preg_match_all('#\\{.#', $attr->value, $matches);

			foreach ($matches[0] as $m)
			{
				if ($m !== '{{')
				{
					throw new RuntimeException("It seems that your template contains at least one attribute named '" . $attr->name . "' using user-supplied content. Those can be unsafe and are disabled by default. Please set XSLHelper::\$allowUnsafeTemplates to true to enable it.");
				}
			}
		}

		// Check for <b><xsl:attribute name="onclick"><xsl:value-of .../></xsl:attribute></b>
		// and <b><xsl:attribute name="onclick"><xsl:apply-templates /></xsl:attribute></b>
		$attrs = $xpath->query(
			'//xsl:attribute
				[starts-with(translate(@name, "ON", "on"), "on")]
				[//xsl:value-of or //xsl:apply-templates]'
		);

		foreach ($attrs as $attr)
		{
			throw new RuntimeException("It seems that your template contains at least one attribute named '" . $attr->getAttribute('name') . "' that is created dynamically. Those can be unsafe and are disabled by default. Please set XSLHelper::\$allowUnsafeTemplates to true to enable it.");
		}
	}

	/**
	* Optimize a template
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function optimizeTemplate(DOMDocument $dom)
	{
		// Save single-space nodes then reload the template without whitespace
		$this->preserveSingleSpaces($dom);
		$dom->preserveWhiteSpace = false;
		$dom->normalizeDocument();

		$this->inlineAttributes($dom);
		$this->optimizeConditionalAttributes($dom);

		// Replace <xsl:text/> elements, which will restore single spaces to their original form
		$this->inlineTextElements($dom);
	}

	/**
	* Preserve single space characters by replacing them with a <xsl:text/> node
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function preserveSingleSpaces(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//text()[. = " "]') as $textNode)
		{
			$newNode = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:text');
			$newNode->textContent = ' ';

			$textNode->parentNode->replaceChild($newNode, $textNode);
		}
	}

	/**
	* Inline the attribute declarations of a template
	*
	* Will replace <xsl:attribute/> nodes with inline attributes wherever applicable.
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function inlineAttributes(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		// Inline attributes
		$query = 'xsl:template[@match]'
		       . '//*[namespace-uri() = ""]'
		       . '/xsl:attribute[count(descendant::node()) = 1]'
		       . '/xsl:value-of[@select]';

		foreach ($xpath->query($query) as $valueOf)
		{
			$attribute = $valueOf->parentNode;

			$attribute->parentNode->setAttribute(
				$attribute->getAttribute('name'),
				'{' . $valueOf->getAttribute('select') . '}'
			);

			$attribute->parentNode->removeChild($attribute);
		}
	}

	/**
	* Optimize conditional attributes
	*
	* Will replace conditional attributes with a <xsl:copy-of/>, e.g.
	*	<xsl:if test="@foo">
	*		<xsl:attribute name="foo">
	*			<xsl:value-of select="@foo" />
	*		</xsl:attribute>
	*	</xsl:if>
	* into
	*	<xsl:copy-of select="@foo"/>
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function optimizeConditionalAttributes(DOMDocument $dom)
	{
		$query = 'xsl:template[@match]'
		       . '//xsl:if'
		       . "[starts-with(@test, '@')]"
		       . '[count(descendant::node()) = 2]'
		       . '[xsl:attribute[@name = substring(../@test, 2)][xsl:value-of[@select = ../../@test]]]';

		foreach ($xpath->query($query) as $if)
		{
			$copyOf = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:copy-of');
			$copyOf->setAttribute('select', $if->getAttribute('test'));

			$if->parentNode->replaceChild($copyOf, $if);
		}
	}

	/**
	* Replace <xsl:text/> nodes with a Text node
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected function inlineTextElements(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//xsl:text') as $xslNode)
		{
			$xslNode->parentNode->replaceChild(
				$dom->createTextNode($xslNode->textContent),
				$xslNode
			);
		}
	}
}