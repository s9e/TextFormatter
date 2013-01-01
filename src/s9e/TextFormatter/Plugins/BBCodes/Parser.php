<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$textLen = strlen($text);

		foreach ($matches as $m)
		{
			$bbcodeName = strtoupper($m[1][0]);

			// BBCodes with no custom setting may not appear in the config. We only know they exist
			// because the regexp matches exact names
			$bbcodeConfig = (isset($this->config['bbcodes'][$bbcodeName]))
			              ? $this->config['bbcodes'][$bbcodeName]
			              : array();

			// Use the configured tagName if available, or reuse the BBCode's name otherwise
			$tagName = (isset($bbcodeConfig['tagName']))
			         ? $bbcodeConfig['tagName']
			         : $bbcodeName;

			/**
			* @var integer Position of the first character of current BBCode, which should be a [
			*/
			$lpos = $m[0][1];

			/**
			* @var integer  Position of the last character of current BBCode, starts as the position
			*               of the "]", " ", "=", ":" or "/" character as per the plugin's regexp,
			*               then advances towards the right as the BBCode is being parsed
			*/
			$rpos = $lpos + strlen($m[0][0]);

			// Check for an identifier
			//
			// Used to explicitly pair specific tags together, e.g.
			//   [code:123][code]type your code here[/code][/code:123]
			if ($text[$rpos] === ':')
			{
				// Move past the colon
				++$rpos;

				// Capture the digits following it (potentially empty)
				$spn       = strspn($text, '0123456789', $rpos);
				$bbcodeId  = substr($text, $rpos, $spn);

				// Move past the number
				$rpos     += $spn;
			}
			else
			{
				$bbcodeId  = '';
			}

			// Test whether this is an end tag
			if ($text[$lpos + 1] === '/')
			{
				// Test whether the tag is properly closed and whether this tag has an identifier.
				// We skip end tags that carry an identifier because they're automatically added
				// when their start tag is processed
				if ($text[$rpos] === ']' && $bbcodeId === '')
				{
					$this->parser->addEndTag($tagName, $lpos, 1 + $rpos - $lpos);
				}

				continue;
			}

			// This is a start tag, now we'll parse attributes
			$type       = Tag::START_TAG;
			$attributes = (isset($bbcodeConfig['predefinedAttributes']))
			            ? $bbcodeConfig['predefinedAttributes']
			            : array();
			$wellFormed = false;
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
					// We're closing this tag
					if ($c === '/')
					{
						// Self-closing tag, e.g. [foo/]
						$type = Tag::SELF_CLOSING_TAG;
						++$rpos;

						if ($rpos === $textLen || $text[$rpos] !== ']')
						{
							// There isn't a closing bracket after the slash, e.g. [foo/
							continue 2;
						}
					}

					// This tag is well-formed
					$wellFormed = true;

					// Move past the right bracket
					++$rpos;

					break;
				}

				// Capture the attribute name
				$spn = strspn($text, 'abcdefghijklmnopqrstuvwxyz_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-', $rpos);

				if ($spn)
				{
					if ($rpos + $spn >= $textLen)
					{
						// The attribute name extends to the end of the text
						continue 2;
					}

					$attrName = strtolower(substr($text, $rpos, $spn));
					$rpos += $spn;

					if ($text[$rpos] !== '=')
					{
						// It's an attribute name not followed by an equal sign, ignore it
						continue;
					}
				}
				elseif ($c === '=' && $rpos === $firstPos)
				{
					// This is the default param, e.g. [quote=foo]. If there's no default attribute
					// set, we reuse the BBCode's name instead
					if (isset($bbcodeConfig['defaultAttribute']))
					{
						$attrName = $bbcodeConfig['defaultAttribute'];
					}
					else
					{
						$attrName = strtolower($bbcodeName);
					}
				}
				else
				{
					continue 2;
				}

				// Move past the = and make sure we're not at the end of the text
				if (++$rpos >= $textLen)
				{
					continue 2;
				}

				// Grab the first character after the equal sign
				$c = $text[$rpos];

				// Test whether the value is in quotes
				if ($c === '"' || $c === "'")
				{
					// This is where the actual value starts
					$valuePos = $rpos + 1;

					while (1)
					{
						// Move past the quote
						++$rpos;

						// Look for the next quote
						$rpos = strpos($text, $c, $rpos);

						if ($rpos === false)
						{
							// No matching quote. Apparently that string never ends...
							continue 3;
						}

						// Test for an odd number of backslashes before this character
						$n = 0;
						while ($text[$rpos - ++$n] === '\\');

						if ($n % 2)
						{
							// If $n is odd, it means there's an even number of backslashes so
							// we can exit this loop
							break;
						}
					}

					// Unescape special characters ' " and \
					$attrValue = preg_replace(
						'#\\\\([\\\\\'"])#',
						'$1',
						substr($text, $valuePos, $rpos - $valuePos)
					);

					// Skip past the closing quote
					++$rpos;
				}
				else
				{
					// Capture everything after the equal sign up to whichever comes first:
					//  - a closing bracket
					//  - whitespace followed by another attribute (name followed by equal sign)
					//
					// NOTE: this is for compatibility with some forums (such as vBulletin it seems)
					//       that do not put attribute values in quotes, e.g.
					//       [quote=John Smith;123456] (quoting "John Smith" from post #123456)
					if (!preg_match('#[^\\]]*?(?=\\]|\\s+[-a-z_0-9]+=)#i', $text, $m, null, $rpos))
					{
						continue;
					}

					$attrValue  = $m[0];
					$rpos  += strlen($attrValue);
				}

				$attributes[$attrName] = $attrValue;
			}

			if (!$wellFormed)
			{
				continue;
			}

			// We're done parsing the tag, we can add it to the list
			if ($type === Tag::START_TAG)
			{
				// If this is a start tag with an identifier, look for its end tag now
				$endTagPos = false;
				if ($bbcodeId !== '')
				{
					$match = '[/' . $bbcodeName . ':' . $bbcodeId . ']';
					$endTagPos = stripos($text, $match, $rpos);

					if ($endTagPos === false)
					{
						// No matching end tag, so we skip this start tag
						continue;
					}

					$endTag = $this->parser->addEndTag($tagName, $endTagPos, strlen($match));
				}

				// Use this tag's content for attributes that require it
				if (isset($bbcodeConfig['contentAttributes']))
				{
					foreach ($bbcodeConfig['contentAttributes'] as $attrName)
					{
						if (isset($attributes[$attrName]))
						{
							continue;
						}

						// Find the position of its end tag if we don't already know it
						if ($endTagPos === false)
						{
							$endTagPos = stripos($text, '[/' . $bbcodeName . ']', $rpos);

							if ($endTagPos === false)
							{
								// No end tag for this start tag
								break;
							}
						}

						$attributes[$attrName] = substr($text, $rpos, $endTagPos - $rpos);
					}
				}

				$tag = $this->parser->addStartTag($tagName, $lpos, $rpos - $lpos);

				if ($bbcodeId !== '')
				{
					$tag->pairWith($endTag);
				}
			}
			else
			{
				$tag = $this->parser->addSelfClosingTag($tagName, $lpos, $rpos - $lpos);
			}

			// Add all attributes to the tag
			$tag->setAttributes($attributes);
		}
	}
}