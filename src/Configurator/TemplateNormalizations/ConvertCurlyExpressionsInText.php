<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Text;

/**
* Convert simple expressions in curly brackets in text into xsl:value-of elements
*
* Will replace
*     <span>{$FOO}{@bar}</span>
* with
*     <span><xsl:value-of value="$FOO"/><xsl:value-of value="@bar"/></span>
*/
class ConvertCurlyExpressionsInText extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/text()[contains(., "{@") or contains(., "{$")]'];

	/**
	* Insert a text node before given node
	*/
	protected function insertTextBefore(string $text, Text $node): void
	{
		if ($text > '')
		{
			$node->before($this->createPolymorphicText($text));
		}
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeText(Text $node): void
	{
		preg_match_all(
			'#\\{([$@][-\\w]+)\\}#',
			$node->textContent,
			$matches,
			PREG_SET_ORDER | PREG_OFFSET_CAPTURE
		);

		$lastPos = 0;
		foreach ($matches as $m)
		{
			$pos = $m[0][1];

			// Catch up to current position
			if ($pos > $lastPos)
			{
				$text = substr($node->textContent, $lastPos, $pos - $lastPos);
				$this->insertTextBefore($text, $node);
			}
			$lastPos = $pos + strlen($m[0][0]);

			// Add the xsl:value-of element
			$node->beforeXslValueOf($m[1][0]);
		}

		// Append the rest of the text
		$text = substr($node->textContent, $lastPos);
		$this->insertTextBefore($text, $node);

		// Now remove the old text node
		$node->remove();
	}
}