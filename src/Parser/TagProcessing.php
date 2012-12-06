<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait TagAccumulator
{
	/**
	* @var array Number of open tags for each tag name
	*/
	protected $cntOpen;

	/**
	* @var array Number of times each tag has been used
	*/
	protected $cntTotal;

	/**
	* @var Tag Current tag being processed
	*/
	protected $currentTag;

	/**
	* @var integer Position of the cursor in the original text
	*/
	protected $pos;

	/**
	* Return current tag
	*
	* @return Tag|bool Current tag if applicable, FALSE otherwise
	*/
	public function getCurrentTag()
	{
		return (isset($this->currentTag)) ? $this->currentTag : false;
	}

	/**
	* 
	*
	* @return void
	*/
	protected function processTags()
	{
		$this->context = $this->rootContext;

		while (!empty($this->tagStack))
		{
			$this->currentTag = array_pop($this->tagStack);
			$this->processCurrentTag();
		}

		// Close unclosed tags

		if ($this->pos < $this->textLen)
		{
			$catchupText = htmlspecialchars(substr($this->text, $this->pos));

			if ($this->context->convertNewlines())
			{
				$catchupText = nl2br($catchupText);
			}

			// Append the catchup text (and the ignored whitespace) to the output
			$this->output .= $catchupText . $ignoredText;
		}
	}

	/**
	* 
	*
	* @return void
	*/
	protected function processTag()
	{
		if ($this->currentTag->shouldBeSkipped($this->pos))
		{
			return;
		}

		if ($this->currentTag->isIgnoreTag())
		{
			$this->processCurrentIgnoreTag();
		}
		elseif ($this->currentTag->isBrTag())
		{
			$this->processCurrentBrTag();
		}
		elseif ($this->currentTag->isStartTag())
		{
			$this->processCurrentStartTag();
		}
		else
		{
			$this->processCurrentEndTag();
		}
	}

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

		$trimWhitespace = (bool) ($tagConfig['rules']['flags'] & self::RULE_TRIM_WHITESPACE);

		// Maintain counters and update the context
		if ($tag->isStartTag())
		{
			++$this->cntTotal[$tagName];

			if (!$tag->isEndTag())
			{
				++$this->cntOpen[$tagName];
				$this->openTags[] = $tag;

				// Create a new context
				$allowedChildren = (empty($tagConfig['isTransparent']))
				                 ? $tagConfig['allowedChildren']
				                 : $this->context['allowedChildren'];

				$allowedDescendants = $this->context['allowedDescendants']
				                    & $tagConfig['allowedDescendants'];

				$allowedChildren &= $allowedDescendants;

				$this->context = array(
					'allowedChildren'    => $allowedChildren,
					'allowedDescendants' => $allowedDescendants,
					'parentContext'      => $this->context
				);
			}
		}
		else
		{
			--$this->cntOpen[$tagName];
			$this->context = $this->context['parentContext'];

			// update $this->openTags
		}

		if ($this->pos < $tagPos)
		{
			/**
			* @var string Text between the parser's last position and current tag's position
			*/
			$catchupText = htmlspecialchars(substr($this->text, $this->pos, $tagPos - $this->pos));

			/**
			* @var string Whitespace removed from the end of $catchupText
			*/
			$ignoredText = '';

			// Trim whitespace before this tag
			if ($trimWhitespace)
			{
				// Capture two lines of whitespace if it's a start tag (including self-closing tags)
				// or one line if it's an end tag
				if ($this->currentTag->isStartTag())
				{
					preg_match('#(?>(?:\\n\\r?|\\r\\n?)?[ \\t]*){1,2}$#D', $catchupText, $m);
				}
				else
				{
					preg_match('#(?:\\n\\r?|\\r\\n?)?[ \\t]*$#D', $catchupText, $m);
				}

				// Get the amount of whitespace captured (can be 0)
				$len = strlen($m[0]);

				if ($len)
				{
					// Remove the trailing whitespace from $catchupText and put it inside an ignore
					// tag
					$catchupText = substr($catchupText, 0, -$len);
					$ignoredText = '<i>' . $m[0] . '</i>';
				}
			}

			if ($this->context->convertNewlines())
			{
				$catchupText = nl2br($catchupText);
			}

			// Append the catchup text (and the ignored whitespace) to the output
			$this->output .= $catchupText . $ignoredText;
		}

		// Output current tag and move the cursor
		if ($this->currentTag->isStartTag())
		{
			$this->output .= '<' . $tagName;
			foreach ($this->currentTag->getAttributes() as $attrName => $attrValue)
			{
				$this->output .= ' ' . $attrName . '="' . htmlspecialchars($attrValue) . '"';
			}
			$this->output .= '>';
		}
		else
		{
			$this->output .= '</' . $tagName . '>';
		}
		$this->pos = $tagPos + $tagLen;

		// Trim whitespace after this tag
		if ($trimWhitespace)
		{
			// Capture two lines after end tags (including self-closing tags) or one line after
			// start tags
			if ($this->currentTag->isEndTag())
			{
				preg_match('#(?>[ \\t]*(?:\\n\\r?|\\r\\n?)?){1,2}#A', $this->text, $m, 0, $this->pos);
			}
			else
			{
				preg_match('#[ \\t]*(?:\\n\\r?|\\r\\n?)?#A', $catchupText, $m, 0, $this->pos);
			}

			// Get the amount of whitespace captured (can be 0)
			$len = strlen($m[0]);

			if ($len)
			{
				$this->output .= '<i>' . substr($this->text, $this->pos, $len) . '</i>';
				$this->pos += $len;
			}
		}
	}

	/**
	* 
	*
	* @return void
	*/
	protected function processCurrentIgnoreTag()
	{
	}

	/**
	* 
	*
	* @return void
	*/
	protected function processCurrentStartTag()
	{
		$tagName   = $this->currentTag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		// 1. Check that this tag has not reached its global limit tagLimit
		// 2. Filter this tag's attributes and check for missing attributes
		// 3. Apply closeParent and closeAncestor rules
		// 4. Check for nestingLimit
		// 5. Apply requireAncestor rules
		//
		// This order ensures that the tag is valid and within the set limits before we attempt to
		// close parents or ancestors. We need to close ancestors before we can check for nesting
		// limits, whether this tag is allowed within current context (the context may change
		// as ancestors are closed) or whether the required ancestors are still there (they might
		// have been closed by a rule.)
		if ($this->cntTotal[$tagName] >= $tagConfig['tagLimit']
		 || !$this->filterAttributes()
		 || $this->closeParent()
		 || $this->closeAncestor()
		 || $this->cntOpen[$tagName]  >= $tagConfig['nestingLimit']
		 || $this->requireAncestor()
		 || !$this->tagIsAllowed($tagName))
		{
			return;
		}
	}
}