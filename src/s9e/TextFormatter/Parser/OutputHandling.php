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
			$this->outputText($this->textLen, 0);
		}

		// Remove empty tag pairs, e.g. <I><U></U></I>
		do
		{
			$this->output = preg_replace(
				'#<((?:\\w+:)?\\w+)[^>]*></\\1>#',
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

		// Use a <rt> root if the text is rich, or <pt> for plain text (including <br/>)
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
		while ($trimAfter && $ignorePos < $this->textLen && $this->text[$ignorePos] === "\n")
		{
			// Decrement the number of lines to trim
			--$trimAfter;

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
		$this->pos   = $catchupPos;

		if ($this->context['flags'] & self::RULE_IGNORE_TEXT)
		{
			// If the catchup text is not entirely composed of whitespace, we put it inside ignore
			// tags
			if (strspn($catchupText, " \n\t") < $catchupLen)
			{
				$catchupText = '<i>' . $catchupText . '</i>';
			}

			$this->output .= $catchupText;

			return;
		}

		$ignorePos = $catchupLen;
		$ignoreLen = 0;
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

		$catchupText = htmlspecialchars($catchupText, ENT_NOQUOTES, 'UTF-8');
		if (!($this->context['flags'] & self::RULE_NO_BR_CHILD))
		{
			$catchupText = str_replace("\n", "<br/>\n", $catchupText);
		}

		$this->output .= $catchupText . $ignoreText;
	}

	/**
	* Output a linebreak tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function outputBrTag(Tag $tag)
	{
		$this->outputText($tag->getPos(), 0);
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
		$this->outputText($tagPos, 0);
		$this->output .= '<i>' . htmlspecialchars($ignoreText, ENT_NOQUOTES, 'UTF-8') . '</i>';
		$this->isRich = true;

		// Move the cursor past this tag
		$this->pos = $tagPos + $tagLen;
	}
}