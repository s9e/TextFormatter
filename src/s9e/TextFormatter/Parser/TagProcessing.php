<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

use RuntimeException;

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
	* @var array Current context
	*/
	protected $context;

	/**
	* @var integer How hard the parser has worked on fixing bad markup so far
	*/
	protected $currentFixingCost;

	/**
	* @var Tag Current tag being processed
	*/
	protected $currentTag;

	/**
	* @var integer How hard the parser should work on fixing bad markup
	*/
	public $maxFixingCost = 1000;

	/**
	* @var array Stack of open tags (instances of Tag)
	*/
	protected $openTags;

	/**
	* @var integer Position of the cursor in the original text
	*/
	protected $pos;

	/**
	* @var array Root context, used at the root of the document
	*/
	protected $rootContext;

	/**
	* Process all tags in the stack
	*
	* @return void
	*/
	protected function processTags()
	{
		// Reset some internal vars
		$this->pos       = 0;
		$this->cntOpen   = [];
		$this->cntTotal  = [];
		$this->openTags  = [];
		unset($this->currentTag);

		// Initialize the root context
		$this->context = $this->rootContext;
		$this->context['inParagraph'] = false;

		// Initialize the count tables
		foreach (array_keys($this->tagsConfig) as $tagName)
		{
			$this->cntOpen[$tagName]  = 0;
			$this->cntTotal[$tagName] = 0;
		}

		while (!empty($this->tagStack))
		{
			if (!$this->tagStackIsSorted)
			{
				$this->sortTags();
			}

			$this->currentTag = array_pop($this->tagStack);
			$this->processCurrentTag();
		}

		// Close tags that were left open
		while (!empty($this->openTags))
		{
			// Get the last open tag
			$openTag = end($this->openTags);

			// Create a tag paired to the last open tag
			$endTag = new Tag(
				Tag::END_TAG,
				$openTag->getName(),
				$this->textLen,
				0
			);
			$openTag->pairWith($endTag);

			// Now process the end tag
			$this->processEndTag($endTag);
		}

		$this->finalizeOutput();
	}

	/**
	* Process current tag
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

		// Test whether this tag is out of bounds
		if ($tagPos + $tagLen > $this->textLen)
		{
			$this->currentTag->invalidate();

			return;
		}

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

		if ($this->currentTag->isIgnoreTag())
		{
			$this->outputIgnoreTag($this->currentTag);
		}
		else if ($this->currentTag->isBrTag())
		{
			$this->outputBrTag($this->currentTag);
		}
		else if ($this->currentTag->isStartTag())
		{
			$this->processStartTag($this->currentTag);
		}
		else
		{
			$this->processEndTag($this->currentTag);
		}
	}

	/**
	* Process given start tag (including self-closing tags) at current position
	*
	* @param  Tag  $tag Start tag (including self-closing)
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

		if ($this->closeParent($tag) || $this->closeAncestor($tag))
		{
			// This tag parent/ancestor needs to be closed, we just return (the tag is still valid)
			return;
		}

		if ($this->cntOpen[$tagName] >= $tagConfig['nestingLimit']
		 || $this->requireAncestor($tag)
		 || !$this->tagIsAllowed($tagName))
		{
			// This tag is invalid
			$tag->invalidate();

			return;
		}

		// If this tag has an autoClose rule and it's not paired with an end tag, we replace it
		// with a self-closing tag with the same properties
		if ($tagConfig['rules']['flags'] & self::RULE_AUTO_CLOSE
		 && !$tag->getEndTag())
		{
			$newTag = new Tag(Tag::SELF_CLOSING_TAG, $tagName, $tag->getPos(), $tag->getLen());
			$newTag->setAttributes($tag->getAttributes());

			$tag = $newTag;
		}

		// This tag is valid, output it and update the context
		$this->outputTag($tag);
		$this->pushContext($tag);
	}

	/**
	* Process given end tag at current position
	*
	* @param  Tag  $tag end tag
	* @return void
	*/
	protected function processEndTag(Tag $tag)
	{
		$tagName = $tag->getName();

		if (empty($this->cntOpen[$tagName]))
		{
			// This is an end tag with no start tag
			return;
		}

		/**
		* @var array List of tags need to be closed before given tag
		*/
		$closeTags = [];

		// Iterate through all open tags from last to first to find a match for our tag
		$i = count($this->openTags);
		while (--$i >= 0)
		{
			$openTag = $this->openTags[$i];

			if ($tag->canClose($openTag))
			{
				break;
			}

			if (++$this->currentFixingCost > $this->maxFixingCost)
			{
				throw new RuntimeException('Fixing cost exceeded');
			}

			$closeTags[] = $openTag;
		}

		if ($i < 0)
		{
			// Did not find a matching tag
			$this->logger->debug('Skipping end tag with no start tag', ['tag' => $tag]);

			return;
		}

		// Only reopen tags if we haven't exceeded our "fixing" budget
		$keepReopening = (bool) ($this->currentFixingCost < $this->maxFixingCost);

		$reopenTags    = [];
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

		if ($reopenTags)
		{
			// Filter out tags that would immediately be closed
			$upcomingEndTags = [];

			/**
			* @var integer Rightmost position of the portion of text to ignore
			*/
			$ignorePos = $this->pos;

			$i = count($this->tagStack);
			while (--$i >= 0 && $this->currentFixingCost < $this->maxFixingCost)
			{
				$upcomingTag = $this->tagStack[$i];

				// Test whether the upcoming tag is positioned at current "ignore" position and it's
				// strictly an end tag (not a start tag or a self-closing tag)
				if ($upcomingTag->getPos() > $ignorePos
				 || $upcomingTag->isStartTag())
				{
					break;
				}

				// Test whether this tag would close any of the tags we're about to reopen
				$j = count($reopenTags);

				while (--$j >= 0)
				{
					++$this->currentFixingCost;

					if ($upcomingTag->canClose($reopenTags[$j]))
					{
						// Remove the tag from the list of tags to reopen and keep the keys in order
						array_splice($reopenTags, $j, 1);

						// Extend the ignored text to cover this tag
						$ignorePos = max(
							$ignorePos,
							$upcomingTag->getPos() + $upcomingTag->getLen()
						);

						break;
					}
				}
			}

			if ($ignorePos > $this->pos)
			{
				/**
				* @todo have a method that takes (pos,len) rather than a Tag
				*/
				$this->outputIgnoreTag(new Tag(Tag::SELF_CLOSING_TAG, 'i', $this->pos, $ignorePos - $this->pos));
			}

			// Re-add tags that need to be reopened, at current cursor position
			foreach ($reopenTags as $startTag)
			{
				$newTag = $this->addStartTag($startTag->getName(), $this->pos, 0);

				// Copy the original tag's attributes
				$newTag->setAttributes($startTag->getAttributes());

				// Re-pair the new tag
				$endTag = $startTag->getEndTag();
				if ($endTag)
				{
					$newTag->pairWith($endTag);
				}
			}
		}
	}

	/**
	* Update counters and replace current context with its parent context
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
	* Update counters and replace current context with a new context based on given tag
	*
	* If given tag is a self-closing tag, the context won't change
	*
	* @param  Tag  $tag Start tag (including self-closing)
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

		$allowedChildren = $tagConfig['allowedChildren'];

		// If the tag is transparent, we restrict its allowed children to the same set as its
		// parent, minus this tag's own disallowed children
		if ($tagConfig['rules']['flags'] & self::RULE_IS_TRANSPARENT)
		{
			$allowedChildren &= $this->context['allowedChildren'];
		}

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

		$this->context = [
			'allowedChildren'    => $allowedChildren,
			'allowedDescendants' => $allowedDescendants,
			'flags'              => $flags,
			'inParagraph'        => false,
			'parentContext'      => $this->context
		];
	}

	/**
	* Return whether given tag is allowed in current context
	*
	* @param  string $tagName
	* @return bool
	*/
	protected function tagIsAllowed($tagName)
	{
		$n = $this->tagsConfig[$tagName]['bitNumber'];

		return (bool) (ord($this->context['allowedChildren'][$n >> 8]) & (1 << ($n & 7)));
	}
}