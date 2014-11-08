<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineInferredValues extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//xsl:if | //xsl:when';

		foreach ($xpath->query($query) as $node)
		{
			$map = TemplateParser::parseEqualityExpr($node->getAttribute('test'));

			if ($map === \false || \count($map) !== 1 || \count($map[\key($map)]) !== 1)
				continue;

			$var   = \key($map);
			$value = \end($map[$var]);

			$query = './/xsl:value-of[@select="' . $var . '"]';
			foreach ($xpath->query($query, $node) as $valueOf)
				$valueOf->parentNode->replaceChild(
					$dom->createTextNode($value),
					$valueOf
				);

			$query = './/*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{' . $var . '}")]';
			foreach ($xpath->query($query, $node) as $attribute)
			{
				AVTHelper::replace(
					$attribute,
					function ($token) use ($value, $var)
					{
						if ($token[0] === 'expression' && $token[1] === $var)
							$token = ['literal', $value];

						return $token;
					}
				);
			}
		}
	}
}