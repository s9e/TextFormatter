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
	* Process the captured tags
	*
	* Removes overlapping tags, filter tags with invalid attributes, tags used in illegal places,
	* applies rules
	*
	* @return void
	*/
	protected function processTags()
	{
		// Test whether there's any tags to be processed
		if (empty($this->unprocessedTags))
		{
			return '<pt>' . htmlspecialchars($this->text) . '</pt>';
		}

		// Set up the root context
		$this->context = $this->rootContext;

		// Seed the tag counters with 0 for each tag
		$this->cntTotal = array_fill_keys(array_keys($this->tagsConfig), 0);
		$this->cntOpen  = $this->cntTotal;

		// Reset the cursor
		$this->textPos = 0;

		// Iterate over unprocessed tags
		while ($this->nextTag())
		{
			$this->processCurrentTag();
		}

		// Close tags that were left open
		while ($this->openTags)
		{
			$this->currentTag = $this->createEndTag(
				end($this->openTags),
				$this->textLen
			);
			$this->processCurrentTag();
		}

		// Append the leftover text and finalize the output
		$this->finalizeOutput();
	}

	/**
	* Process current tag
	*/
	protected function processCurrentTag()
	{
		// Try to be less greedy with whitespace before current tag if it would make it overlap
		// with previous tag
		if (!empty($this->currentTag['trimBefore'])
		 && $this->textPos > $this->currentTag['pos'])
		{
			// This is how much the tags overlap
			$spn = $this->textPos - $this->currentTag['pos'];

			if ($spn <= $this->currentTag['trimBefore'])
			{
				// All of the overlap is whitespace, therefore we can reduce it to make the tags fit
				$this->currentTag['pos']        += $spn;
				$this->currentTag['len']        -= $spn;
				$this->currentTag['trimBefore'] -= $spn;
			}
		}

		// Test whether the current tag overlaps with previous tag
		if ($this->textPos > $this->currentTag['pos'])
		{
			$this->log('debug', array(
				'msg' => 'Tag skipped'
			));
			return;
		}

		if ($this->currentTagRequiresMissingTag())
		{
			$this->log('debug', array(
				'msg' => 'Tag skipped due to missing dependency'
			));
			return;
		}

		if ($this->currentTag['type'] & self::START_TAG)
		{
			$this->processCurrentStartTag();
		}
		else
		{
			$this->processCurrentEndTag();
		}
	}

	/**
	* Process current tag, which is a START_TAG
	*/
	protected function processCurrentStartTag()
	{
		$tagName   = $this->currentTag['name'];
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

		// If this tag must remain empty and it's not a self-closing tag, we peek at the next
		// tag before turning our start tag into a self-closing tag
		/**
		* @todo keep or ditch?
		*/
		if (!empty($tagConfig['isEmpty'])
		 && $this->currentTag['type'] === self::START_TAG)
		{
			$nextTag = $this->peekNextTag();

			if ($nextTag
			 && $nextTag['type'] === self::END_TAG
			 && $nextTag['tagMate'] === $this->currentTag['tagMate']
			 && $nextTag['pos'] === $this->currentTag['pos'] + $this->currentTag['len'])
			{
				// Next tag is a match to current tag, pop it out of the unprocessedTags stack and
				// consume its text
				$this->popNextTag();
				$this->currentTag['len'] += $nextTag['len'];
			}

			$this->currentTag['type'] = self::SELF_CLOSING_TAG;
		}

		// We have a valid tag, let's append it to the list of processed tags
		$this->appendTag($this->currentTag);
	}

	/**
	* Process current tag, which is a END_TAG
	*/
	protected function processCurrentEndTag()
	{
		if (empty($this->openStartTags[$this->currentTag['tagMate']]))
		{
			// This is an end tag but there's no matching start tag
			$this->log('debug', array(
				'msg'    => 'Could not find a matching start tag for %s',
				'params' => array($this->currentTag['tagMate'])
			));
			return;
		}

		/**
		* @var array List of tags to be reopened due to autoReopen rules
		*/
		$reopenTags = array();

		// Iterate through open tags, and for each start tag that we find that is not the tagMate of
		// current end tag, we create and append a matching end tag
		while (1)
		{
			$lastOpenTag = array_pop($this->openTags);
			$this->context = $lastOpenTag['context'];

			// Test whether we're closing the last open tag
			if ($lastOpenTag['tagMate'] === $this->currentTag['tagMate'])
			{
				break;
			}

			// Create an end tag for the last open tag
			$this->appendTag($this->createEndTag($lastOpenTag, $this->currentTag['pos']));

			// Test whether the last open tag needs to be reopened afterwards
			$tagConfig = $this->tagsConfig[$this->currentTag['name']];
			if (isset($tagConfig['rules']['reopenChild'][$lastOpenTag['name']]))
			{
				// Position the reopened tag after current tag
				$pos = $this->currentTag['pos'] + $this->currentTag['len'];

				// Ensure the tag is not out of bounds
				if ($pos < $this->textLen)
				{
					$this->unprocessedTags[] = $this->createStartTag($lastOpenTag, $pos);
				}
			}
		}

		$this->appendTag($this->currentTag);
	}

	/**
	* Append a tag to the output
	*
	* Takes care of maintaining counters and updating the context
	*
	* @param  array $tag
	* @return void
	*/
	protected function appendTag(array $tag)
	{
		// Append the text between last tag and this one
		if ($tag['pos'] > $this->textPos)
		{
			$this->output .= htmlspecialchars(substr(
				$this->text,
				$this->textPos,
				$tag['pos'] - $this->textPos
			));
		}

		// Output the tag then move the cursor
		$this->outputTag($tag);
		$this->textPos = $tag['pos'] + $tag['len'];

		// Maintain counters
		if ($tag['type'] & self::START_TAG)
		{
			++$this->cntTotal[$tag['name']];

			if ($tag['type'] === self::START_TAG)
			{
				++$this->cntOpen[$tag['name']];

				if (isset($this->openStartTags[$tag['tagMate']]))
				{
					++$this->openStartTags[$tag['tagMate']];
				}
				else
				{
					$this->openStartTags[$tag['tagMate']] = 1;
				}
			}
		}
		elseif ($tag['type'] & self::END_TAG)
		{
			--$this->cntOpen[$tag['name']];
			--$this->openStartTags[$tag['tagMate']];
		}

		// Update the context
		if ($tag['type'] === self::START_TAG)
		{
			$tagConfig = $this->tagsConfig[$tag['name']];

			$this->openTags[] = array(
				'name'       => $tag['name'],
				'pluginName' => $tag['pluginName'],
				'tagMate'    => $tag['tagMate'],
				'attributes' => $tag['attributes'],
				'context'    => $this->context
			);

			if (empty($tagConfig['isTransparent']))
			{
				$this->context['allowedChildren'] = $tagConfig['allowedChildren'];
			}

			$this->context['allowedDescendants'] &= $tagConfig['allowedDescendants'];
			$this->context['allowedChildren']    &= $this->context['allowedDescendants'];
		}
	}

	/**
	* Append a tag's representation to the output
	*
	* @param  array $tag
	* @return void
	*/
	protected function outputTag(array $tag)
	{
		// Capture the part of the text that belongs to this tag
		$tagText  = substr($this->text, $tag['pos'], $tag['len']);

		// Handle whitespace
		$wsBefore = '';
		$wsAfter  = '';

		if (!empty($tag['trimBefore']))
		{
			$wsBefore = substr($tagText, 0, $tag['trimBefore']);
			$tagText  = substr($tagText, $tag['trimBefore']);
		}

		if (!empty($tag['trimAfter']))
		{
			$wsAfter = substr($tagText, -$tag['trimAfter']);
			$tagText = substr($tagText, 0, -$tag['trimAfter']);
		}

		if ($wsBefore > '')
		{
			$this->output .= htmlspecialchars($wsBefore);
		}

		if ($tag['type'] & self::START_TAG)
		{
			$this->output .= '<' . $tag['name'];
			foreach ($tag['attrs'] as $attrName => $attrValue)
			{
				$this->output .= ' ' . $attrName . '="' . htmlspecialchars($attrValue) . '"';
			}
			$this->output .= '>';

			if ($tag['type'] & self::END_TAG)
			{
				$this->output .= htmlspecialchars($tagText) . '</' . $tag['name'] . '>';
			}
			elseif ($tagText > '')
			{
				$this->output .= '<st>' . htmlspecialchars($tagText) . '</st>';
			}
		}
		else
		{
			if ($tagText > '')
			{
				$this->output .= '<et>' . htmlspecialchars($tagText) . '</et>';
			}

			$this->output .= '</' . $tag['name'] . '>';
		}

		if ($wsAfter > '')
		{
			$this->output .= htmlspecialchars($wsAfter);
		}
	}

	/**
	* Finish formatting the output after all the tags have been processed
	*
	* @return void
	*/
	protected function finalizeOutput()
	{
		// Append the text after the last tag
		if ($this->textPos < $this->textLen)
		{
			$this->output .= htmlspecialchars(substr($this->text, $this->textPos));
		}

		// Collect the namespaces used by processed tags
		$namespaces = array();
		foreach ($this->cntTotal as $tagName => $cnt)
		{
			if ($cnt)
			{
				$pos = strpos($tagName, ':');

				if ($pos > -1)
				{
					$namespaces[substr($tagName, 0, $pos)] = 1;
				}
			}
		}

		// Build the formatted text's opening tag with the necessary namespace declarations
		$tmp = '<rt';
		foreach ($namespaces as $prefix => $void)
		{
			$tmp .= ' ' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		// Now add the tags to the output
		$this->output = $tmp . '>' . $this->output . '</rt>';
	}
}