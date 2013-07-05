<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait OutputHandling
{
	/**
	* @var bool Whether the output contains "rich" tags, IOW any tag that is not <i> or <br/>
	*/
	protected $isRich;

	/**
	* @var array Associative array of namespace prefixes in use in document (prefixes used as key)
	*/
	protected $namespaces;

	/**
	* @var string This parser's output
	*/
	protected $output;

	/**
	* Finalize the output by appending the rest of the unprocessed text and create the root node
	*
	* @return void
	*/
	protected function finalizeOutput()
	{
		// Output the rest of the text
		if ($this->pos < $this->textLen)
		{
			$this->outputText($this->textLen, 0, true);
		}

		// Close the last paragraph if applicable
		$this->outputParagraphEnd();

		// Remove empty tag pairs, e.g. <I><U></U></I> as well as empty paragraphs
		do
		{
			$this->output = preg_replace(
				'#<([\\w:]+)[^>]*></\\1>#',
				'',
				$this->output,
				-1,
				$cnt
			);
		}
		while ($cnt);

		// Merge consecutive <i> tags
		if (strpos($this->output, '</i><i>') !== false)
		{
			$this->output = str_replace('</i><i>', '', $this->output);
		}

		// Use a <rt> root if the text is rich, or <pt> for plain text (including <p></p> and <br/>)
		$tagName = ($this->isRich) ? 'rt' : 'pt';

		// Prepare the root node with all the namespace declarations
		$tmp = '<' . $tagName;
		foreach (array_keys($this->namespaces) as $prefix)
		{
			$tmp .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		$this->output = $tmp . '>' . $this->output . '</' . $tagName . '>';
	}

	/**
	* Append a tag to the output
	*
	* @param  Tag  $tag Tag to append
	* @return void
	*/
	protected function outputTag(Tag $tag)
	{
		$this->isRich = true;

		$tagName   = $tag->getName();
		$tagPos    = $tag->getPos();
		$tagLen    = $tag->getLen();
		$tagConfig = $this->tagsConfig[$tagName];

		if ($tagConfig['rules']['flags'] & self::RULE_TRIM_WHITESPACE)
		{
			$skipBefore = ($tag->isStartTag()) ? 2 : 1;
			$skipAfter  = ($tag->isEndTag())   ? 2 : 1;
		}
		else
		{
			$skipBefore = $skipAfter = 0;
		}

		// Current paragraph must end before the tag if:
		//  - the tag is a start (or self-closing) tag and it breaks paragraphs, or
		//  - the tag is an end tag (but not self-closing)
		$closeParagraph = false;
		if ($tag->isStartTag())
		{
			if ($tagConfig['rules']['flags'] & self::RULE_BREAK_PARAGRAPH)
			{
				$closeParagraph = true;
			}
		}
		else
		{
			$closeParagraph = true;
		}

		// Let the cursor catch up with this tag's position
		$this->outputText($tagPos, $skipBefore, $closeParagraph);

		// Capture the text consumed by the tag
		$tagText = ($tagLen)
		         ? htmlspecialchars(substr($this->text, $tagPos, $tagLen), ENT_NOQUOTES, 'UTF-8')
		         : '';

		// Output current tag
		if ($tag->isStartTag())
		{
			// Handle paragraphs before opening the tag
			if ($tagConfig['rules']['flags'] & self::RULE_BREAK_PARAGRAPH)
			{
				$this->outputParagraphEnd();
			}
			else
			{
				$this->outputParagraphStart($tagPos);
			}

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
			// Close current paragraph if applicable
			$this->outputParagraphEnd();

			if ($tagLen)
			{
				$this->output .= '<et>' . $tagText . '</et>';
			}

			$this->output .= '</' . $tagName . '>';
		}

		// Move the cursor past the tag
		$this->pos = $tagPos + $tagLen;

		// Skip newlines (no other whitespace) after this tag
		$ignorePos = $this->pos;
		while ($skipAfter && $ignorePos < $this->textLen && $this->text[$ignorePos] === "\n")
		{
			// Decrement the number of lines to skip
			--$skipAfter;

			// Move the cursor past the newline
			++$ignorePos;
		}

		if ($ignorePos !== $this->pos)
		{
			$this->output .= substr($this->text, $this->pos, $ignorePos - $this->pos);
			$this->pos = $ignorePos;
		}
	}

	/**
	* Output the text between the cursor's position (included) and given position (not included)
	*
	* @param  integer $catchupPos     Position we're catching up to
	* @param  integer $maxLines       Maximum number of lines to ignore at the end of the text
	* @param  bool    $closeParagraph Whether to close the paragraph at the end, if applicable
	* @return void
	*/
	protected function outputText($catchupPos, $maxLines, $closeParagraph)
	{
		if ($this->pos >= $catchupPos)
		{
			// We're already there
			return;
		}

		// Test whether we're even supposed to output anything
		if ($this->context['flags'] & self::RULE_IGNORE_TEXT)
		{
			$catchupLen  = $catchupPos - $this->pos;
			$catchupText = substr($this->text, $this->pos, $catchupLen);

			// If the catchup text is not entirely composed of whitespace, we put it inside ignore
			// tags
			if (strspn($catchupText, " \n\t") < $catchupLen)
			{
				$catchupText = '<i>' . $catchupText . '</i>';
			}

			$this->output .= $catchupText;
			$this->pos = $catchupPos;

			return;
		}

		// Start a paragraph if applicable
		$this->outputParagraphStart($catchupPos);

		// Capture the catchup text after the paragraph has been opened (and the cursor moved)
		$catchupLen  = $catchupPos - $this->pos;
		$catchupText = substr($this->text, $this->pos, $catchupLen);

		// Test whether we have to handle paragraphs
		if ($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS)
		{
			// Look for a paragraph break in this text
			$pbPos = strpos($catchupText, "\n\n");

			if ($pbPos !== false)
			{
				// If there's a break, we split up the remaining text
				$this->outputText($this->pos + $pbPos, 0, true);
				$this->outputText($catchupPos, $maxLines, $closeParagraph);

				return;
			}
		}

		// Compute the amount of text to ignore at the end of the output
		$ignorePos = $catchupLen;
		$ignoreLen = 0;

		if ($closeParagraph && $this->context['inParagraph'])
		{
			while (--$ignorePos >= 0 && $catchupText[$ignorePos] === "\n")
			{
				++$ignoreLen;
			}
		}

		while ($maxLines && --$ignorePos >= 0)
		{
			$c = $catchupText[$ignorePos];
			if (strpos(" \n\t", $c) === false)
			{
				break;
			}

			if ($c === "\n")
			{
				--$maxLines;
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

		// Escape the output
		$catchupText = htmlspecialchars($catchupText, ENT_NOQUOTES, 'UTF-8');

		// Format line breaks if applicable
		if (!($this->context['flags'] & self::RULE_NO_BR_CHILD))
		{
			$catchupText = str_replace("\n", "<br/>\n", $catchupText);
		}

		// Append to the output, close the paragraph, add the ignored text and move the cursor
		$this->output .= $catchupText;
		$this->outputParagraphEnd();
		$this->output .= $ignoreText;
		$this->pos     = $catchupPos;
	}

	/**
	* Output a linebreak tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function outputBrTag(Tag $tag)
	{
		$this->outputText($tag->getPos(), 0, false);
		$this->output .= '<br/>';
	}

	/**
	* Output an ignore tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function outputIgnoreTag(Tag $tag)
	{
		$tagPos = $tag->getPos();
		$tagLen = $tag->getLen();

		// Capture the text to ignore
		$ignoreText = substr($this->text, $tagPos, $tagLen);

		// Catch up with the tag's position then output the tag
		$this->outputText($tagPos, 0, false);
		$this->output .= '<i>' . htmlspecialchars($ignoreText, ENT_NOQUOTES, 'UTF-8') . '</i>';
		$this->isRich = true;

		// Move the cursor past this tag
		$this->pos = $tagPos + $tagLen;
	}

	/**
	* Start a paragraph between current position and given position, if applicable
	*
	* @param  integer $maxPos Rightmost position at which the paragraph can be opened
	* @return void
	*/
	protected function outputParagraphStart($maxPos)
	{
		// Do nothing if we're already in a paragraph, or if we don't use paragraphs
		if ($this->context['inParagraph']
		 || !($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS))
		{
			return;
		}

		// Output the whitespace between $this->pos and $maxPos if applicable
		if ($maxPos > $this->pos)
		{
			$spn = strspn($this->text, self::WHITESPACE, $this->pos, $maxPos - $this->pos);

			if ($spn)
			{
				$this->output .= substr($this->text, $this->pos, $spn);
				$this->pos += $spn;
			}
		}

		// Open the paragraph, but only if it's not at the very end of the text
		if ($this->pos < $this->textLen)
		{
			$this->output .= '<p>';
			$this->context['inParagraph'] = true;
		}
	}

	/**
	* Close current paragraph at current position if applicable
	*
	* @return void
	*/
	protected function outputParagraphEnd()
	{
		// Do nothing if we're not in a paragraph
		if (!$this->context['inParagraph'])
		{
			return;
		}

		$this->output .= '</p>';
		$this->context['inParagraph'] = false;
	}
}