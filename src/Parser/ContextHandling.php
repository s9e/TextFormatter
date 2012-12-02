<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait ContextHandling
{
	/**
	* 
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
}