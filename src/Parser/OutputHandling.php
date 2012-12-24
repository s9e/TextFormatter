<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait OutputHandling
{
	/**
	* @var array Associative array of namespace prefixes in use in document (prefixes used as key)
	*/
	protected $namespaces;

	/**
	* 
	*
	* @return void
	*/
	protected function finalizeOutput()
	{
		if ($this->output === '')
		{
			$this->output = '<pt>';
			$this->outputText($this->textLen, 0);
			$this->output .= '</pt>';

			return;
		}

		if ($this->pos < $this->textLen)
		{
			$this->outputText($this->textLen, 0);
		}

		$tmp = '<rt';
		foreach (array_keys($this->namespaces) as $prefix)
		{
			$tmp .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		$this->output = $tmp . '>' . $this->output . '</rt>';
	}

	/**
	* 
	*
	* @return void
	*/
	protected function outputTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagPos    = $tag->getPos();
		$tagLen    = $tag->getLen();
		$tagConfig = $this->tagsConfig[$tagName];

		if ($tagConfig['rules']['flags'] & self::RULE_TRIM_WHITESPACE)
		{
			$trimBefore = ($tag->isStartTag()) ? 2 : 1;
			$trimAfter  = ($tag->isEndTag())   ? 2 : 1;
		}
		else
		{
			$trimBefore = $trimAfter = 0;
		}

		// Let the cursor catch up with this tag's position
		$this->outputText($tagPos, $trimBefore);

		// Capture the text consumed by the tag
		$tagText = ($tagLen)
		         ? htmlspecialchars(substr($this->text, $tagPos, $tagLen), ENT_NOQUOTES, 'UTF-8')
		         : '';

		// Output current tag
		if ($tag->isStartTag())
		{
			// Record this tag's namespace, if applicable
			$colonPos = strpos($tagName, ':');
			if ($colonPos)
			{
				$this->namespaces[substr($tagName, 0, $colonPos)] = 0;
			}

			// Open the start tag and add its attributes, but don't close the tag
			$this->output .= '<' . $tagName;
			foreach ($tag->getAttributes() as $attrName => $attrValue)
			{
				$this->output .= ' ' . $attrName . '="' . htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8') . '"';
			}

			if ($tag->isSelfClosingTag())
			{
				if ($tagLen)
				{
					$this->output .= '>' . $tagText . '</' . $tagName . '>';
				}
				else
				{
					$this->output .= '/>';
				}
			}
			elseif ($tagLen)
			{
				$this->output .= '><st>' . $tagText . '</st>';
			}
			else
			{
				$this->output .= '>';
			}
		}
		else
		{
			if ($tagLen)
			{
				$this->output .= '<et>' . $tagText . '</et>';
			}

			$this->output .= '</' . $tagName . '>';
		}

		// Move the cursor past the tag
		$this->pos = $tagPos + $tagLen;

		// Trim newlines (no other whitespace) after this tag
		$ignorePos = $this->pos;
		while ($trimAfter && $ignorePos < $this->textLen)
		{
			$c = $this->text[$ignorePos];

			if ($c !== "\n" && $c !== "\r")
			{
				break;
			}

			// Decrement the number of lines to trim
			--$trimAfter;

			// Move the cursor past the newline
			++$ignorePos;

			// Test whether this is a \r\n or \n\r combo
			if ($ignorePos < $this->textLen)
			{
				if (($c === "\r" && $this->text[$ignorePos + 1] === "\n")
				 || ($c === "\n" && $this->text[$ignorePos + 1] === "\r"))
				{
					++$ignorePos;
				}
			}
		}

		if ($ignorePos !== $this->pos)
		{
			$this->output .= '<i>' . substr($this->text, $this->pos, $ignorePos - $this->pos) . '</i>';
			$this->pos = $ignorePos;
		}
	}

	/**
	* Output the text between the cursor's position (included) and given position (not included)
	*
	* NOTE: does not move the cursor
	*
	* @param  integer $catchupPos Position we're catching up to
	* @param  integer $maxLines   Maximum number of lines to trim at the end of the text
	* @return void
	*/
	protected function outputText($catchupPos, $maxLines)
	{
		if ($this->pos >= $catchupPos)
		{
			// We're already there
			return;
		}

		$catchupLen  = $catchupPos - $this->pos;
		$catchupText = substr($this->text, $this->pos, $catchupLen);

		if ($this->context['flags'] & self::RULE_IGNORE_TEXT)
		{
			$this->output .= '<i>' . $catchupText . '</i>';
			return;
		}

		$ignorePos = $catchupLen;
		$ignoreLen = 0;
		while ($maxLines && --$ignorePos >= 0)
		{
			$c = $catchupText[$ignorePos];
			if (strpos(" \n\r\t\0\x0B", $c) === false)
			{
				break;
			}

			if ($c === "\n")
			{
				--$maxLines;

				if ($ignorePos)
				{
					if (($c === "\n" && $catchupText[$ignorePos - 1] === "\r")
					 || ($c === "\r" && $catchupText[$ignorePos - 1] === "\n"))
					{
						--$ignorePos;
						++$ignoreLen;
					}
				}
			}

			++$ignoreLen;
		}

		if ($ignoreLen)
		{
			$ignoreText  = substr($catchupText, -$ignoreLen);
			$catchupText = substr($catchupText, 0, $catchupLen - $ignoreLen);
		}
		else
		{
			$ignoreText = '';
		}

		$catchupText = htmlspecialchars($catchupText, ENT_NOQUOTES, 'UTF-8');
		if (!($this->context['flags'] & self::RULE_NO_BR_CHILD))
		{
			$catchupText = nl2br($catchupText);
		}

		$this->output .= $catchupText . $ignoreText;
	}

	/**
	* Output a linebreak tag
	*
	* NOTE: using <br /> rather than <br/> to remain consistent with nl2br()'s output
	*
	* @return void
	*/
	protected function outputBrTag()
	{
		$this->output .= '<br />';
	}

	/**
	* Output an ignore tag
	*
	* @param  integer $ignoreLen Length of text to consume
	* @return void
	*/
	protected function outputIgnoreTag($ignoreLen)
	{
		$this->output .= '<i>' . htmlspecialchars(substr($this->text, $this->pos, $ignoreLen), ENT_NOQUOTES, 'UTF-8') . '</i>';

		$this->pos += $ignoreLen;
	}
}