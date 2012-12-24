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
		/**
		* @todo ignore/br tags that were manually added result in a <rt> tag even if there's no other tag
		*/
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
		while ($trimAfter && $ignorePos < $this->textLen && $this->text[$ignorePos] === "\n")
		{
			// Decrement the number of lines to trim
			--$trimAfter;

			// Move the cursor past the newline
			++$ignorePos;
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
			$this->output .= '<i>' . $catchupText . '</i>';
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
			$ignoreText  = '<i>' . substr($catchupText, -$ignoreLen) . '</i>';
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

		// Move the cursor past this tag
		$this->pos = $tagPos + $tagLen;
	}
}