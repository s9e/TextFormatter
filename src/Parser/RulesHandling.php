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
	* Reopen tags that were closed by an end tag that is not their tagMate
	*/
	protected function autoReopen()
	{
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
	* Apply closeParent rules from current tag
	*
	* @return bool Whether a new tag has been added
	*/
	protected function closeParent()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (!empty($this->openTags)
		 && !empty($tagConfig['rules']['closeParent']))
		{
			$parentTag     = end($this->openTags);
			$parentTagName = $parentTag['name'];

			if (isset($tagConfig['rules']['closeParent'][$parentTagName]))
			{
				/**
				* We have to close that parent. First we reinsert current tag...
				*/
				$this->unprocessedTags[] = $this->currentTag;

				/**
				* ...then we create a new end tag which we put on top of the stack
				*/
				$this->currentTag = $this->createEndTag(
					$parentTag,
					$this->currentTag['pos']
				);

				$this->unprocessedTags[] = $this->currentTag;

				return true;
			}
		}

		return false;
	}

	/**
	* Apply closeAncestor rules from current tag
	*
	* @return bool Whether a new tag has been added
	*/
	protected function closeAncestor()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (!empty($tagConfig['rules']['closeAncestor']))
		{
			$i = count($this->openTags);

			while (--$i >= 0)
			{
				$ancestorTag     = $this->openTags[$i];
				$ancestorTagName = $ancestorTag['name'];

				if (isset($tagConfig['rules']['closeAncestor'][$ancestorTagName]))
				{
					/**
					* We have to close this ancestor. First we reinsert current tag...
					*/
					$this->unprocessedTags[] = $this->currentTag;

					/**
					* ...then we create a new end tag which we put on top of the stack
					*/
					$this->currentTag = $this->createEndTag(
						$ancestorTag,
						$this->currentTag['pos']
					);

					$this->unprocessedTags[] = $this->currentTag;

					return true;
				}
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
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (isset($tagConfig['rules']['requireAncestor']))
		{
			foreach ($tagConfig['rules']['requireAncestor'] as $ancestor)
			{
				if (!empty($this->cntOpen[$ancestor]))
				{
					return false;
				}
			}

			$msg = (count($tagConfig['rules']['requireAncestor']) === 1)
				 ? 'Tag %1$s requires %2$s as ancestor'
				 : 'Tag %1$s requires as ancestor any of: %2$s';

			$this->log('error', array(
				'msg'    => $msg,
				'params' => array(
					$this->currentTag['name'],
					implode(', ', $tagConfig['rules']['requireAncestor'])
				)
			));

			return true;
		}

		return false;
	}
}