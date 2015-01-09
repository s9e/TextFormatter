<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

class InlineXPathLiterals extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$_this = $this;

		$xpath = new DOMXPath($template->ownerDocument);

		foreach ($xpath->query('//xsl:value-of') as $valueOf)
		{
			$textContent = $this->getTextContent($valueOf->getAttribute('select'));

			if ($textContent !== \false)
				$this->replaceElement($valueOf, $textContent);
		}

		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			AVTHelper::replace(
				$attribute,
				function ($token) use ($_this)
				{
					if ($token[0] === 'expression')
					{
						$textContent = $_this->getTextContent($token[1]);
						if ($textContent !== \false)
							$token = array('literal', $textContent);
					}

					return $token;
				}
			);
		}
	}

	public function getTextContent($expr)
	{
		$expr = \trim($expr);

		if (\preg_match('(^(?:\'[^\']*\'|"[^"]*")$)', $expr))
			return \substr($expr, 1, -1);

		if (\preg_match('(^0*([0-9]+)$)', $expr, $m))
			return $m[1];

		return \false;
	}

	protected function replaceElement(DOMElement $valueOf, $textContent)
	{
		$valueOf->parentNode->replaceChild(
			$valueOf->ownerDocument->createTextNode($textContent),
			$valueOf
		);
	}
}