<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use RuntimeException;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* @var array Attributes of the BBCode being parsed
	*/
	protected $attributes;

	/**
	* @var array Configuration for the BBCode being parsed
	*/
	protected $bbcodeConfig;

	/**
	* @var string Name of the BBCode being parsed
	*/
	protected $bbcodeName;

	/**
	* @var string Suffix of the BBCode being parsed, including its colon
	*/
	protected $bbcodeSuffix;

	/**
	* @var integer Position of the cursor in the original text
	*/
	protected $pos;

	/**
	* @var integer Position of the start of the BBCode being parsed
	*/
	protected $startPos;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* @var integer Length of the text being parsed
	*/
	protected $textLen;

	/**
	* @var string Text being parsed, normalized to uppercase
	*/
	protected $uppercaseText;

	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$this->text          = $text;
		$this->textLen       = strlen($text);
		$this->uppercaseText = '';
		foreach ($matches as $m)
		{
			$this->bbcodeName = strtoupper($m[1][0]);
			if (!isset($this->config['bbcodes'][$this->bbcodeName]))
			{
				continue;
			}
			$this->bbcodeConfig = $this->config['bbcodes'][$this->bbcodeName];
			$this->startPos     = $m[0][1];
			$this->pos          = $this->startPos + strlen($m[0][0]);

			try
			{
				$this->parseBBCode();
			}
			catch (RuntimeException $e)
			{
				// Do nothing
			}
		}
	}

	/**
	* Add the end tag that matches current BBCode
	*
	* @return Tag
	*/
	protected function addBBCodeEndTag()
	{
		return $this->parser->addEndTag($this->getTagName(), $this->startPos, $this->pos - $this->startPos);
	}

	/**
	* Add the self-closing tag that matches current BBCode
	*
	* @return Tag
	*/
	protected function addBBCodeSelfClosingTag()
	{
		$tag = $this->parser->addSelfClosingTag($this->getTagName(), $this->startPos, $this->pos - $this->startPos);
		$tag->setAttributes($this->attributes);

		return $tag;
	}

	/**
	* Add the start tag that matches current BBCode
	*
	* @return Tag
	*/
	protected function addBBCodeStartTag()
	{
		$prio = ($this->bbcodeSuffix !== '') ? -10 : 0;
		$tag  = $this->parser->addStartTag($this->getTagName(), $this->startPos, $this->pos - $this->startPos, $prio);
		$tag->setAttributes($this->attributes);

		return $tag;
	}

	/**
	* Parse the end tag that matches given BBCode name and suffix starting at current position
	*
	* @return Tag|null
	*/
	protected function captureEndTag()
	{
		if (empty($this->uppercaseText))
		{
			$this->uppercaseText = strtoupper($this->text);
		}
		$match     = '[/' . $this->bbcodeName . $this->bbcodeSuffix . ']';
		$endTagPos = strpos($this->uppercaseText, $match, $this->pos);
		if ($endTagPos === false)
		{
			return;
		}

		return $this->parser->addEndTag($this->getTagName(), $endTagPos, strlen($match));
	}

	/**
	* Get the tag name for current BBCode
	*
	* @return string
	*/
	protected function getTagName()
	{
		// Use the configured tagName if available, or reuse the BBCode's name otherwise
		return (isset($this->bbcodeConfig['tagName']))
		     ? $this->bbcodeConfig['tagName']
		     : $this->bbcodeName;
	}

	/**
	* Parse attributes starting at current position
	*
	* @return void
	*/
	protected function parseAttributes()
	{
		$firstPos = $this->pos;
		$this->attributes = [];
		while ($this->pos < $this->textLen)
		{
			$c = $this->text[$this->pos];
			if (strpos(" \n\t", $c) !== false)
			{
				++$this->pos;
				continue;
			}
			if (strpos('/]', $c) !== false)
			{
				return;
			}

			// Capture the attribute name
			$spn = strspn($this->text, 'abcdefghijklmnopqrstuvwxyz_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-', $this->pos);
			if ($spn)
			{
				$attrName = strtolower(substr($this->text, $this->pos, $spn));
				$this->pos += $spn;
				if ($this->pos >= $this->textLen)
				{
					// The attribute name extends to the end of the text
					throw new RuntimeException;
				}
				if ($this->text[$this->pos] !== '=')
				{
					// It's an attribute name not followed by an equal sign, ignore it
					continue;
				}
			}
			elseif ($c === '=' && $this->pos === $firstPos)
			{
				// This is the default param, e.g. [quote=foo]
				$attrName = (isset($this->bbcodeConfig['defaultAttribute']))
				          ? $this->bbcodeConfig['defaultAttribute']
				          : strtolower($this->bbcodeName);
			}
			else
			{
				throw new RuntimeException;
			}

			// Move past the = and make sure we're not at the end of the text
			if (++$this->pos >= $this->textLen)
			{
				throw new RuntimeException;
			}

			$this->attributes[$attrName] = $this->parseAttributeValue();
		}
	}

	/**
	* Parse the attribute value starting at current position
	*
	* @return string
	*/
	protected function parseAttributeValue()
	{
		// Test whether the value is in quotes
		if ($this->text[$this->pos] === '"' || $this->text[$this->pos] === "'")
		{
			return $this->parseQuotedAttributeValue();
		}

		// Capture everything up to whichever comes first:
		//  - an endline
		//  - whitespace followed by a slash and a closing bracket
		//  - a closing bracket, optionally preceded by whitespace
		//  - whitespace followed by another attribute (name followed by equal sign)
		//
		// NOTE: this is for compatibility with some forums (such as vBulletin it seems)
		//       that do not put attribute values in quotes, e.g.
		//       [quote=John Smith;123456] (quoting "John Smith" from post #123456)
		preg_match('((?:[^\\s\\]]|[ \\t](?!\\s*(?:[-\\w]+=|/?\\])))*)', $this->text, $m, 0, $this->pos);

		$attrValue  = $m[0];
		$this->pos += strlen($attrValue);

		return $attrValue;
	}

	/**
	* Parse current BBCode
	*
	* @return void
	*/
	protected function parseBBCode()
	{
		$this->parseBBCodeSuffix();

		// Test whether this is an end tag
		if ($this->text[$this->startPos + 1] === '/')
		{
			// Test whether the tag is properly closed and whether this tag has an identifier.
			// We skip end tags that carry an identifier because they're automatically added
			// when their start tag is processed
			if (substr($this->text, $this->pos, 1) === ']' && $this->bbcodeSuffix === '')
			{
				++$this->pos;
				$this->addBBCodeEndTag();
			}

			return;
		}

		// Parse attributes
		$this->parseAttributes();

		// Test whether the tag is properly closed
		if (substr($this->text, $this->pos, 1) === ']')
		{
			++$this->pos;
		}
		else
		{
			// Test whether this is a self-closing tag
			if (substr($this->text, $this->pos, 2) === '/]')
			{
				$this->pos += 2;
				$this->addBBCodeSelfClosingTag();
			}

			return;
		}

		// Record the names of attributes that need the content of this tag
		$contentAttributes = [];
		if (isset($this->bbcodeConfig['contentAttributes']))
		{
			foreach ($this->bbcodeConfig['contentAttributes'] as $attrName)
			{
				if (!isset($this->attributes[$attrName]))
				{
					$contentAttributes[] = $attrName;
				}
			}
		}

		// Look ahead and parse the end tag that matches this tag, if applicable
		$requireEndTag = ($this->bbcodeSuffix || !empty($this->bbcodeConfig['forceLookahead']));
		$endTag = ($requireEndTag || !empty($contentAttributes)) ? $this->captureEndTag() : null;
		if (isset($endTag))
		{
			foreach ($contentAttributes as $attrName)
			{
				$this->attributes[$attrName] = substr($this->text, $this->pos, $endTag->getPos() - $this->pos);
			}
		}
		elseif ($requireEndTag)
		{
			return;
		}

		// Create this start tag
		$tag = $this->addBBCodeStartTag();

		// If an end tag was created, pair it with this start tag
		if (isset($endTag))
		{
			$tag->pairWith($endTag);
		}
	}

	/**
	* Parse the BBCode suffix starting at current position
	*
	* Used to explicitly pair specific tags together, e.g.
	*   [code:123][code]type your code here[/code][/code:123]
	*
	* @return void
	*/
	protected function parseBBCodeSuffix()
	{
		$this->bbcodeSuffix = '';
		if ($this->text[$this->pos] === ':')
		{
			// Capture the colon and the (0 or more) digits following it
			$spn = 1 + strspn($this->text, '0123456789', 1 + $this->pos);
			$this->bbcodeSuffix = substr($this->text, $this->pos, $spn);

			// Move past the suffix
			$this->pos += $spn;
		}
	}

	/**
	* Parse a quoted attribute value that starts at current offset
	*
	* @return string
	*/
	protected function parseQuotedAttributeValue()
	{
		$quote    = $this->text[$this->pos];
		$valuePos = $this->pos + 1;
		do
		{
			// Look for the next quote
			$this->pos = strpos($this->text, $quote, $this->pos + 1);
			if ($this->pos === false)
			{
				// No matching quote. Apparently that string never ends...
				throw new RuntimeException;
			}

			// Test for an odd number of backslashes before this character
			$n = 1;
			while ($this->text[$this->pos - $n] === '\\')
			{
				++$n;
			}
		}
		while ($n % 2 === 0);

		$attrValue = substr($this->text, $valuePos, $this->pos - $valuePos);
		if (strpos($attrValue, '\\') !== false)
		{
			$attrValue = strtr($attrValue, ['\\\\' => '\\', '\\"' => '"', "\\'" => "'"]);
		}

		// Skip past the closing quote
		++$this->pos;

		return $attrValue;
	}
}