<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$textLen  = strlen($text);

		/**
		* @var array Array of start tags that were identified with a suffix. The key is made of the
		*            BBCode name followed by a "#" character followed by the suffix, e.g. "B#123"
		*/
		$tagMates = array();

		foreach ($matches as $m)
		{
			$bbcodeName = strtoupper($m[1][0]);

			if (!isset($this->config['bbcodes'][$bbcodeName]))
			{
				// Not a known BBCode
				continue;
			}

			$bbcodeConfig = $this->config['bbcodes'][$bbcodeName];
			$tagName      = $bbcodeConfig['tagName'];

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

			// Check for a BBCode suffix
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
				// Test whether the tag is properly closed -- NOTE: this will fail on "[/foo ]"
				if ($text[$rpos] === ']')
				{
					$tag = $this->parser->addEndTag($tagName, $lpos, 1 + $rpos - $lpos);

					// Test whether this end tag is being paired with a start tag
					$tagMateId = $bbcodeName . '#' . $bbcodeId;
					if (isset($tagMates[$tagMateId]))
					{
						$tag->pairWith($tagMates[$tagMateId]);

						// Free up the start tag now, it shouldn't be reused
						unset($tagMates[$tagMateId]);
					}
				}

				continue;
			}

			// This is a start tag, now we'll parse attributes
			$type       = Tag::START_TAG;
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

						if ($rpos === $textLen
						 || $text[$rpos] !== ']')
						{
							// There isn't a closing bracket after the slash, e.g. [foo/
							continue 2;
						}
					}

					$wellFormed = true;
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
					$value = preg_replace(
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
					if (!preg_match('#[^\\]]*(?=\\]|\\s+[-a-z_0-9]+=)#i', $text, $m, null, $rpos))
					{
						continue;
					}

					$value  = $m[0];
					$rpos  += strlen($value);
				}

				$attributes[$attrName] = $value;
			}

			if (!$wellFormed)
			{
				continue;
			}

			// We're done parsing the tag, we can add it to the list
			$len = 1 + $rpos - $lpos;
			$tag = ($type === Tag::START_TAG)
			     ? $this->parser->addStartTag($tagName, $lpos, $len)
			     : $this->parser->addSelfClosingTag($tagName, $lpos, $len);

			// Add attributes
			foreach ($attributes as $attrName => $value)
			{
				$tag->setAttribute($attrName, $value);
			}

			if ($type === Tag::START_TAG)
			{
				if ($bbcodeId !== '')
				{
					$tagMates[$tagName . '#' . $bbcodeId] = $tag;
				}

				// Some attributes use the content of a tag if no value is specified
				if (isset($bbcodeConfig['contentAttributes']))
				{
					$value = false;
					foreach ($bbcodeConfig['contentAttributes'] as $attrName)
					{
						if (isset($attributes[$attrName]))
						{
							continue;
						}

						if ($value === false)
						{
							// Move the right cursor past the closing bracket
							++$rpos;

							// Search for an end tag that matches our start tag
							$match = '[/' . $bbcodeName;
							if ($bbcodeId !== '')
							{
								$match .= ':' . $bbcodeId;
							}
							$match .= ']';

							$pos = stripos($text, $match, $rpos);

							if ($pos === false)
							{
								// No end tag for this start tag
								break;
							}

							$value = substr($text, $rpos, $pos - $rpos);
						}

						$tag->setAttribute($attrName, $value);
					}
				}
			}
		}
	}
}