<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait RulesHandling
{
	/**
	* Apply closeAncestor rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function closeAncestor(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['closeAncestor']))
			{
				$i = count($this->openTags);

				while (--$i >= 0)
				{
					$ancestor     = $this->openTags[$i];
					$ancestorName = $ancestor->getName();

					if (isset($tagConfig['rules']['closeAncestor'][$ancestorName]))
					{
						// We have to close this ancestor. First we reinsert this tag...
						$this->tagStack[] = $tag;

						// ...then we add a new end tag for it
						$this->addMagicEndTag($ancestor, $tag->getPos());

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	* Apply closeParent rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function closeParent(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['closeParent']))
			{
				$parent     = end($this->openTags);
				$parentName = $parent->getName();

				if (isset($tagConfig['rules']['closeParent'][$parentName]))
				{
					// We have to close that parent. First we reinsert the tag...
					$this->tagStack[] = $tag;

					// ...then we add a new end tag for it
					$this->addMagicEndTag($parent, $tag->getPos());

					return true;
				}
			}
		}

		return false;
	}

	/**
	* Apply fosterParent rules associated with given tag
	*
	* NOTE: this rule has the potential for creating an unbounded loop, either if a tag tries to
	*       foster itself or two or more tags try to foster each other in a loop. We mitigate the
	*       risk by preventing a tag from creating a child of itself (the parent still gets closed)
	*       and by checking and increasing the currentFixingCost so that a loop of multiple tags
	*       do not run indefinitely. The default tagLimit and nestingLimit also serve to prevent the
	*       loop from running indefinitely
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function fosterParent(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['fosterParent']))
			{
				$parent     = end($this->openTags);
				$parentName = $parent->getName();

				if (isset($tagConfig['rules']['fosterParent'][$parentName]))
				{
					if ($parentName !== $tagName && $this->currentFixingCost < $this->maxFixingCost)
					{
						// Add a 0-width copy of the parent tag right after this tag, and make it
						// depend on this tag
						$child = $this->addCopyTag($parent, $tag->getPos() + $tag->getLen(), 0);
						$tag->cascadeInvalidationTo($child);
					}

					++$this->currentFixingCost;

					// Reinsert current tag
					$this->tagStack[] = $tag;

					// And finally close its parent
					$this->addMagicEndTag($parent, $tag->getPos());

					return true;
				}
			}
		}

		return false;
	}

	/**
	* Apply requireAncestor rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether this tag has an unfulfilled requireAncestor requirement
	*/
	protected function requireAncestor(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		if (isset($tagConfig['rules']['requireAncestor']))
		{
			foreach ($tagConfig['rules']['requireAncestor'] as $ancestorName)
			{
				if (!empty($this->cntOpen[$ancestorName]))
				{
					return false;
				}
			}

			$this->logger->err('Tag requires an ancestor', [
				'requireAncestor' => implode(',', $tagConfig['rules']['requireAncestor']),
				'tag'             => $tag
			]);

			return true;
		}

		return false;
	}
}