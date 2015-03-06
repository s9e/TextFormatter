<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		$textLen = \strlen($text);

		foreach ($matches as $m)
		{
			$bbcodeName = \strtoupper($m[1][0]);
			if (!isset($this->config['bbcodes'][$bbcodeName]))
				continue;
			$bbcodeConfig = $this->config['bbcodes'][$bbcodeName];

			$tagName = (isset($bbcodeConfig['tagName']))
			         ? $bbcodeConfig['tagName']
			         : $bbcodeName;

			$lpos = $m[0][1];

			$rpos = $lpos + \strlen($m[0][0]);

			if ($text[$rpos] === ':')
			{
				$spn      = 1 + \strspn($text, '0123456789', 1 + $rpos);
				$bbcodeId = \substr($text, $rpos, $spn);

				$rpos += $spn;
			}
			else
				$bbcodeId = '';

			if ($text[$lpos + 1] === '/')
			{
				if ($text[$rpos] === ']' && $bbcodeId === '')
					$this->parser->addEndTag($tagName, $lpos, 1 + $rpos - $lpos);

				continue;
			}

			$type       = Tag::START_TAG;
			$attributes = (isset($bbcodeConfig['predefinedAttributes']))
			            ? $bbcodeConfig['predefinedAttributes']
			            : array();
			$wellFormed = \false;
			$firstPos   = $rpos;

			while ($rpos < $textLen)
			{
				$c = $text[$rpos];

				if ($c === ' ')
				{
					++$rpos;
					continue;
				}

				if ($c === ']' || $c === '/')
				{
					if ($c === '/')
					{
						$type = Tag::SELF_CLOSING_TAG;
						++$rpos;

						if ($rpos === $textLen || $text[$rpos] !== ']')
							continue 2;
					}

					$wellFormed = \true;

					++$rpos;

					break;
				}

				$spn = \strspn($text, 'abcdefghijklmnopqrstuvwxyz_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-', $rpos);

				if ($spn)
				{
					if ($rpos + $spn >= $textLen)
						continue 2;

					$attrName = \strtolower(\substr($text, $rpos, $spn));
					$rpos += $spn;

					if ($text[$rpos] !== '=')
						continue;
				}
				elseif ($c === '=' && $rpos === $firstPos)
					if (isset($bbcodeConfig['defaultAttribute']))
						$attrName = $bbcodeConfig['defaultAttribute'];
					else
						$attrName = \strtolower($bbcodeName);
				else
					continue 2;

				if (++$rpos >= $textLen)
					continue 2;

				$c = $text[$rpos];

				if ($c === '"' || $c === "'")
				{
					$valuePos = $rpos + 1;

					while (1)
					{
						++$rpos;

						$rpos = \strpos($text, $c, $rpos);

						if ($rpos === \false)
							continue 3;

						$n = 0;
						while ($text[$rpos - ++$n] === '\\');

						if ($n % 2)
							break;
					}

					$attrValue = \preg_replace(
						'#\\\\([\\\\\'"])#',
						'$1',
						\substr($text, $valuePos, $rpos - $valuePos)
					);

					++$rpos;
				}
				else
				{
					if (!\preg_match('#[^\\]]*?(?=\\s*(?: /)?\\]|\\s+[-a-z_0-9]+=)#i', $text, $m, \null, $rpos))
						continue;

					$attrValue  = $m[0];
					$rpos  += \strlen($attrValue);
				}

				$attributes[$attrName] = $attrValue;
			}

			if (!$wellFormed)
				continue;

			if ($type === Tag::START_TAG)
			{
				$contentAttributes = array();

				if (isset($bbcodeConfig['contentAttributes']))
					foreach ($bbcodeConfig['contentAttributes'] as $attrName)
						if (!isset($attributes[$attrName]))
							$contentAttributes[] = $attrName;

				$endTag = \null;
				if (!empty($contentAttributes) || $bbcodeId || !empty($bbcodeConfig['forceLookahead']))
				{
					$match     = '[/' . $bbcodeName . $bbcodeId . ']';
					$endTagPos = \stripos($text, $match, $rpos);

					if ($endTagPos === \false)
					{
						if ($bbcodeId || !empty($bbcodeConfig['forceLookahead']))
							continue;
					}
					else
					{
						foreach ($contentAttributes as $attrName)
							$attributes[$attrName] = \substr($text, $rpos, $endTagPos - $rpos);

						$endTag = $this->parser->addEndTag($tagName, $endTagPos, \strlen($match));
					}
				}

				$tag = $this->parser->addStartTag($tagName, $lpos, $rpos - $lpos);

				if ($endTag)
					$tag->pairWith($endTag);
			}
			else
				$tag = $this->parser->addSelfClosingTag($tagName, $lpos, $rpos - $lpos);

			$tag->setAttributes($attributes);
		}
	}
}