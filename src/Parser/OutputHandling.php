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
	* 
	*
	* @return void
	*/
	protected function outputCurrentTag()
	{
		$tagName   = $this->currentTag->getName();
		$tagPos    = $this->currentTag->getPos();
		$tagLen    = $this->currentTag->getLen();
		$tagConfig = $this->tagsConfig[$tagName];

		if ($tagPos > $this->pos)
		{
			if ($tagConfig['rules']['flags'] & self::RULE_TRIM_WHITESPACE)
			{
				$trimBefore = ($this->currentTag->isStartTag()) ? 2 : 1;
				$trimAfter  = ($this->currentTag->isEndTag())   ? 2 : 1;
			}
			else
			{
				$trimBefore = $trimAfter = 0;
			}

			$this->outputText($this->pos, $tagPos, $trimBefore);
		}

		// Capture the text consumed by the tag
		$tagText = htmlspecialchars($this->text, $tagPos, $tagLen)

		// Output current tag
		if ($this->currentTag->isStartTag())
		{
			// Open the start tag and add its attributes, but don't close the tag
			$this->output .= '<' . $tagName;
			foreach ($this->currentTag->getAttributes() as $attrName => $attrValue)
			{
				$this->output .= ' ' . $attrName . '="' . htmlspecialchars($attrValue) . '"';
			}

			if ($this->currentTag->isSelfClosingTag())
			{
				if (!$tagLen)
				{
					$this->output .= '>' . $tagText . '</' . $tagName . '>';
				}
				else
				{
					$this->output .= '/>';
				}
			}
			else
			{
				$this->output .= '><st>' . $tagText . '</st>';
			}
		}
		else
		{
			$this->output .= '<et>' . $tagText . '</et></' . $tagName . '>';
		}

		// Move the cursor past the tag
		$this->pos = $tagPos + $tagLen;

		// Trim newlines (no other whitespace) after this tag
		$ignorePos = $this->pos;
		while ($trimAfter && $ignorePos < $this->textLen)
		{
			$c = $this->text[$ignorePos];

			if ($c === "\n" || $c === "\r")
			{
				// Decrement the number of lines to trim
				--$trimAfter;

				// Move the cursor past the newline
				++$ignorePos;

				// Test whether this is a \r\n or \n\r combo
				if ($ignorePos < $this->textLen)
				{
					if (($c === "\n" && $this->text[$ignorePos + 1] === "\r")
					 || ($c === "\r" && $this->text[$ignorePos + 1] === "\n"))
					{
						++$ignorePos;
					}
				}
			}
			else
			{
				break;
			}
		}

		if ($ignorePos !== $this->pos)
		{
			$this->output .= '<i>' . substr($this->text, $this->pos, $ignorePos - $this->pos) . '</i>';
			$this->pos = $ignorePos;
		}
	}

	/**
	* 
	*
	* @param  integer $catchupPos Position we're catching up to
	* @param  integer $maxLines   Maximum number of lines to trim at the end of the text
	* @return void
	*/
	protected function catchupText($catchupPos, $maxLines)
	{
		$catchupLen  = $catchupPos + 1 - $this->pos;
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


		$catchupText = htmlspecialchars($catchupText);
		if (!($this->context['flags'] & self::RULE_NO_BR_CHILD))
		{
			$catchupText = nl2br($catchupText);
		}

		$this->output .= $catchupText . $ignoreText;
	}

	/**
	* 
	*
	* @return void
	*/
	protected function outputBrTag()
	{
		$catchupText = htmlspecialchars(substr($this->text, $this->pos, $tagPos - $this->pos));

		if ($this->context->convertNewlines())
		{
			$catchupText = nl2br($catchupText);
		}
	}
}