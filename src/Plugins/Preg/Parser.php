<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Preg;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		foreach ($this->config['generics'] as list($tagName, $regexp, $passthroughIdx, $map))
		{
			preg_match_all($regexp, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

			foreach ($matches as $m)
			{
				$startTagPos = $m[0][1];
				$matchLen    = strlen($m[0][0]);

				if ($passthroughIdx && isset($m[$passthroughIdx]) && $m[$passthroughIdx][0] !== '')
				{
					// Compute the position and length of the start tag, end tag, and the content in
					// between. PREG_OFFSET_CAPTURE gives us the position of the content, and we
					// know its length. Everything before is considered part of the start tag, and
					// everything after is considered part of the end tag
					$contentPos  = $m[$passthroughIdx][1];
					$contentLen  = strlen($m[$passthroughIdx][0]);
					$startTagLen = $contentPos - $startTagPos;
					$endTagPos   = $contentPos + $contentLen;
					$endTagLen   = $matchLen - ($startTagLen + $contentLen);

					$tag = $this->parser->addTagPair($tagName, $startTagPos, $startTagLen, $endTagPos, $endTagLen, -100);
				}
				else
				{
					$tag = $this->parser->addSelfClosingTag($tagName, $startTagPos, $matchLen, -100);
				}

				foreach ($map as $i => $attrName)
				{
					if ($attrName && isset($m[$i]) && $m[$i][0] !== '')
					{
						$tag->setAttribute($attrName, $m[$i][0]);
					}
				}
			}
		}
	}
}