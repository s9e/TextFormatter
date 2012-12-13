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
	* @var array Number of open tags for each plugin/tag combination. Used as a lookup table that
	*            determines whether an end tag has a matching start tag open, but doesn't take tag
	*            pairs into account
	*/
	protected $openIndex;

	/**
	* @var array Stack of open tags (instances of Tag)
	*/
	protected $openTags;

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
		// Reset some internal vars
		$this->pos       = 0;
		$this->cntOpen   = array();
		$this->cntTotal  = array();
		$this->openIndex = array();
		$this->openTags  = array();
		$this->context   = $this->rootContext;
		unset($this->currentTag);

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
		if ($this->currentTag->getPos() < $this->pos)
		{
			$this->currentTag->invalidate();
		}

		if ($this->currentTag->shouldBeSkipped())
		{
			// TODO: paired end tag should still close start tag
			if ($this->currentTag->getTagMate())
			{
				// test whether its tagmate is in $this->openTags and add a matching end tag at
				// current position
			}

			return;
		}

		if ($this->currentTag->isIgnoreTag())
		{
			$this->outputIgnoreTag();
		}
		elseif ($this->currentTag->isBrTag())
		{
			$this->outputBrTag();
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
	protected function processCurrentStartTag()
	{
		$tagName   = $this->currentTag->getName();
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
		 || !$this->filterTag($this->currentTag))
		{
			// This tag is invalid
			$this->currentTag->invalidate();

			return;
		}

		if ($this->closeParent()
		 || $this->closeAncestor())
		{
			// This tag parent/ancestor needs to be closed, we just return (the tag is still valid)
			return;
		}

		if ($this->cntOpen[$tagName] >= $tagConfig['nestingLimit']
		 || $this->requireAncestor()
		 || !$this->tagIsAllowed($tagName))
		{
			// This tag is invalid
			$this->currentTag->invalidate();

			return;
		}
	}

	/**
	* 
	*
	* @return void
	*/
	protected function popContext(Tag $tag)
	{
		--$this->cntOpen[$tagName];
		$this->context = $this->context['parentContext'];

		// update $this->openTags
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

		if (!$tag->isSelfClosingTag())
		{
			++$this->cntOpen[$tagName];
			$this->openTags[] = $tag;

			// If the tag is transparent, we keep the same allowedChildren bitfield, otherwise
			// we use this tag's allowedChildren bitfield
			$allowedChildren = ($tagConfig['flags'] & self::RULE_IS_TRANSPARENT)
							 ? $this->context['allowedChildren']
							 : $tagConfig['allowedChildren'];

			// The allowedDescendants bitfield is restricted by this tag's
			$allowedDescendants = $this->context['allowedDescendants']
								& $tagConfig['allowedDescendants'];

			// Ensure that disallowed descendants are not allowed as children
			$allowedChildren &= $allowedDescendants;

			// Use this tag's flags except for noBrDescendant, which is inherited
			$flags = $tagConfig['flags']
				   | ($this->context['flags'] & self::RULE_NO_BR_DESCENDANT);

			// noBrDescendant is replicated onto noBrChild
			if ($flags & self::RULE_NO_BR_DESCENDANT)
			{
				$flags |= self::RULE_NO_BR_CHILD;
			}

			$this->context = array(
				'allowedChildren'    => $allowedChildren,
				'allowedDescendants' => $allowedDescendants,
				'flags'              => $flags
				'parentContext'      => $this->context
			);
		}
	}
}