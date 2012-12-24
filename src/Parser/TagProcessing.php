<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait TagProcessing
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
	* @var array Stack of open tags (instances of Tag)
	*/
	protected $openTags;

	/**
	* @var integer Position of the cursor in the original text
	*/
	protected $pos;

	/**
	* 
	*
	* @return void
	*/
	protected function processTags()
	{
		// Reset some internal vars
		$this->pos       = 0;
		$this->cntOpen   = array();
		$this->cntTotal  = array();
		$this->openTags  = array();
		$this->context   = $this->rootContext;
		unset($this->currentTag);

		// Initialize the count tables
		foreach (array_keys($this->tagsConfig) as $tagName)
		{
			$this->cntOpen[$tagName]  = 0;
			$this->cntTotal[$tagName] = 0;
		}

		while (!empty($this->tagStack))
		{
			$this->currentTag = array_pop($this->tagStack);
			$this->processCurrentTag();
		}

		// Close tags that were left open
		while (!empty($this->openTags))
		{
			$this->currentTag = new Tag(
				Tag::END_TAG,
				end($this->openTags)->getName(),
				$this->textLen,
				0
			);
			$this->processCurrentTag();
		}

		$this->finalizeOutput();
	}

	/**
	* 
	*
	* @return void
	*/
	protected function processCurrentTag()
	{
		if ($this->currentTag->isInvalid())
		{
			return;
		}

		$tagPos = $this->currentTag->getPos();
		$tagLen = $this->currentTag->getLen();

		// Test whether the cursor passed this tag's position already
		if ($this->pos > $tagPos)
		{
			// Test whether this tag is paired with a start tag and this tag is still open
			$startTag = $this->currentTag->getStartTag();

			if ($startTag && in_array($startTag, $this->openTags, true))
			{
				// Create an end tag that matches current tag's start tag, which consumes as much of
				// the same text as current tag and is paired with the same start tag
				$this->addEndTag(
					$startTag->getName(),
					$this->pos,
					max(0, $tagPos + $tagLen - $this->pos)
				)->pairWith($startTag);

				// Note that current tag is not invalidated, it's merely replaced
				return;
			}

			// If this is an ignore tag, try to ignore as much as the remaining text as possible
			if ($this->currentTag->isIgnoreTag())
			{
				$ignoreLen = $tagPos + $tagLen - $this->pos;

				if ($ignoreLen > 0)
				{
					// Create a new ignore tag and move on
					$this->addIgnoreTag($this->pos, $ignoreLen);

					return;
				}
			}

			// Skipped tags are invalidated
			$this->currentTag->invalidate();

			return;
		}

		// Test whether this tag is out of bounds
		if ($tagPos + $tagLen > $this->textLen)
		{
			$this->currentTag->invalidate();

			return;
		}

		if ($this->currentTag->isIgnoreTag())
		{
			$this->outputIgnoreTag($this->currentTag);
		}
		elseif ($this->currentTag->isBrTag())
		{
			$this->outputBrTag($this->currentTag);
		}
		elseif ($this->currentTag->isStartTag())
		{
			$this->processStartTag($this->currentTag);
		}
		else
		{
			$this->processEndTag($this->currentTag);
		}
	}

	/**
	* 
	*
	* @return void
	*/
	protected function processStartTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		// 1. Check that this tag has not reached its global limit tagLimit
		// 2. Execute this tag's filterChain, which will filter/validate its attributes
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
		 || !$this->filterTag($tag))
		{
			// This tag is invalid
			$tag->invalidate();

			return;
		}

		if ($this->closeParent($tagName)
		 || $this->closeAncestor($tagName))
		{
			// This tag parent/ancestor needs to be closed, we just return (the tag is still valid)
			return;
		}

		if ($this->cntOpen[$tagName] >= $tagConfig['nestingLimit']
		 || $this->requireAncestor($tagName)
		 || !$this->tagIsAllowed($tagName))
		{
			// This tag is invalid
			$tag->invalidate();

			return;
		}

		// This tag is valid, output it and update the context
		$this->outputTag($tag);
		$this->pushContext($tag);
	}

	/**
	* 
	*
	* @return void
	*/
	protected function processEndTag(Tag $tag)
	{
		$tagName = $tag->getName();

		/**
		* @var array List of tags need to be closed before given tag
		*/
		$closeTags = array();

		// Iterate through all open tags from last to first to find a match for our tag
		$i = count($this->openTags);
		while (--$i >= 0)
		{
			$openTag  = $this->openTags[$i];

			// Test whether this open tag could be a match for our tag
			if ($tagName === $openTag->getName())
			{
				// Test whether this open tag is paired and if so, if it's paired to our tag
				$pairedTag = $openTag->getEndTag();
				if ($pairedTag)
				{
					if ($tag === $pairedTag)
					{
						// Pair found
						break;
					}
				}
				elseif (!$tag->getStartTag())
				{
					// If neither tag is paired and they have the same name, we got a match
					break;
				}
			}

			$closeTags[] = $openTag;
		}

		if ($i < 0)
		{
			// Did not find a matching tag
			$this->logger->debug('Skipping end tag with no start tag', array('tag' => $tag));

			return;
		}

		$keepReopening = true;
		$reopenTags    = array();
		foreach ($closeTags as $openTag)
		{
			$openTagName = $openTag->getName();

			// Test whether this tag should be reopened automatically
			if ($keepReopening)
			{
				if ($this->tagsConfig[$openTagName]['rules']['flags'] & self::RULE_AUTO_REOPEN)
				{
					$reopenTags[] = $openTag;
				}
				else
				{
					$keepReopening = false;
				}
			}

			// Output an end tag to close this start tag, then update the context
			$this->outputTag(new Tag(Tag::END_TAG, $openTagName, $tag->getPos(), 0));
			$this->popContext();
		}

		// Output our tag, moving the cursor past it, then update the context
		$this->outputTag($tag);
		$this->popContext();

		// Re-add tags that need to be reopened, at current cursor position
		foreach ($reopenTags as $startTag)
		{
			$newTag = $this->addStartTag($startTag->getName(), $this->pos, 0);

			// Re-pair the new tag
			$endTag = $startTag->getEndTag();
			if ($endTag)
			{
				$newTag->pairWith($endTag);
			}
		}
	}

	/**
	* 
	*
	* @return void
	*/
	protected function popContext()
	{
		$tag = array_pop($this->openTags);
		--$this->cntOpen[$tag->getName()];
		$this->context = $this->context['parentContext'];
	}

	/**
	* 
	*
	* @return void
	*/
	protected function pushContext(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		++$this->cntTotal[$tagName];

		// If this is a self-closing tag, we don't need to do anything else; The context remains the
		// same
		if ($tag->isSelfClosingTag())
		{
			return;
		}

		++$this->cntOpen[$tagName];
		$this->openTags[] = $tag;

		// If the tag is transparent, we keep the same allowedChildren bitfield, otherwise
		// we use this tag's allowedChildren bitfield
		$allowedChildren = ($tagConfig['rules']['flags'] & self::RULE_IS_TRANSPARENT)
						 ? $this->context['allowedChildren']
						 : $tagConfig['allowedChildren'];

		// The allowedDescendants bitfield is restricted by this tag's
		$allowedDescendants = $this->context['allowedDescendants']
							& $tagConfig['allowedDescendants'];

		// Ensure that disallowed descendants are not allowed as children
		$allowedChildren &= $allowedDescendants;

		// Use this tag's flags except for noBrDescendant, which is inherited
		$flags = $tagConfig['rules']['flags']
			   | ($this->context['flags'] & self::RULE_NO_BR_DESCENDANT);

		// noBrDescendant is replicated onto noBrChild
		if ($flags & self::RULE_NO_BR_DESCENDANT)
		{
			$flags |= self::RULE_NO_BR_CHILD;
		}

		$this->context = array(
			'allowedChildren'    => $allowedChildren,
			'allowedDescendants' => $allowedDescendants,
			'flags'              => $flags,
			'parentContext'      => $this->context
		);
	}
}