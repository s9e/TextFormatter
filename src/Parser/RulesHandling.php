<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait RulesHandling
{
	/**
	* Apply closeAncestor rules from current tag
	*
	* @return bool Whether a new tag has been added
	*/
	protected function closeAncestor()
	{
		$tagName   = $this->currentTag->getName();
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
					// We have to close this ancestor. First we reinsert current tag...
					$this->tagStack[] = $this->currentTag;

					// ...then we create a new end tag which we put on top of the stack
					$this->tagStack[] = new Tag(
						Tag::END_TAG,
						$ancestorName,
						$this->currentTag->getPos(),
						0
					);

					return true;
				}
			}
		}

		return false;
	}

	/**
	* Apply closeParent rules from current tag
	*
	* @return bool Whether a new tag has been added
	*/
	protected function closeParent()
	{
		$tagName   = $this->currentTag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		if (!empty($this->openTags)
		 && !empty($tagConfig['rules']['closeParent']))
		{
			$parent     = end($this->openTags);
			$parentName = $parent->getName();

			if (isset($tagConfig['rules']['closeParent'][$parentName]))
			{
				// We have to close that parent. First we reinsert current tag...
				$this->tagStack[] = $this->currentTag;

				// ...then we create a new end tag which we put on top of the stack
				$this->tagStack[] = new Tag(
					Tag::END_TAG,
					$parentName,
					$this->currentTag->getPos(),
					0
				);

				return true;
			}
		}

		return false;
	}

	/**
	* Apply requireAncestor rules from current tag
	*
	* @return bool Whether current tag is invalid
	*/
	protected function requireAncestor()
	{
		$tagName   = $this->currentTag->getName();
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

			$this->logger->err('Tag requires an ancestor', array(
				'requireAncestor' => implode(',', $tagConfig['rules']['requireAncestor'])
			));

			return true;
		}

		return false;
	}
}