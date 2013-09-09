<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MarkdownLite;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$lines = explode("\n", $text);
		foreach ($lines as $line)
		{
			$spn = strspn($line, ' -+*#>0123456789.');

			if (!$spn)
			{
				continue;
			}

			// Blockquote: ">" or "> "
			// List item:  "* " preceded by any number of spaces
			// List item:  "- " preceded by any number of spaces
			// List item:  "+ " preceded by any number of spaces
			// List item:  at least one digit followed by ". "
			// HR:         "* * *" or "- - -" or "***" or "---"
		}

		// Inline links
		if (strpos($text, '[') !== false)
		{
			preg_match_all(
				'/\\[(.*?)\\]\\((.*?)(?<!\\\\)(?:\\\\\\\\)*\\)/',
				$text,
				$matches,
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			foreach ($matches as $m)
			{
				$contentLen  = strlen($m[1][0]);
				$startTagPos = $m[0][1];
				$startTagLen = 1;
				$endTagPos   = $startTagPos + 1 + $contentLen;
				$endTagLen   = strlen($m[0][0]) - 1 - $contentLen;
				$url         = stripslashes($m[2][0]);

				$this->parser->addTagPair('URL', $startTagPos, $startTagLen, $endTagPos, $endTagLen)
				             ->setAttribute('url', $url);
			}
		}
	}
}