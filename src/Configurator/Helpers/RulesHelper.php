<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Items\Tag;

abstract class RulesHelper
{
	/**
	* Generate the allowedChildren and allowedDescendants bitfields for every tag and for the root context
	*
	* @param  TagCollection $tags
	* @param  Ruleset       $rootRules
	* @return array
	*/
	public static function getBitfields(TagCollection $tags, Ruleset $rootRules)
	{
		$rules = array('*root*' => $rootRules);
		foreach ($tags as $tagName => $tag)
		{
			$rules[$tagName] = $tag->rules;
		}

		// Create a matrix that contains all of the tags and whether every other tag is allowed as
		// a child and as a descendant
		$matrix = self::unrollRules($rules);

		// Remove unusable tags from the matrix
		self::pruneMatrix($matrix);

		// Group together tags are allowed in the exact same contexts
		$groupedTags = array();
		foreach (array_keys($matrix) as $tagName)
		{
			if ($tagName === '*root*')
			{
				continue;
			}

			$k = '';

			// Look into each matrix whether current tag is allowed as child/descendant
			foreach ($matrix as $tagMatrix)
			{
				$k .= $tagMatrix['allowedChildren'][$tagName];
				$k .= $tagMatrix['allowedDescendants'][$tagName];
			}

			$groupedTags[$k][] = $tagName;
		}

		// Prepare the return value
		$return = array();

		// Record the bit number of each tag, and the name of a tag for each bit
		$bitTag     = array();
		$bitNumber  = 0;
		foreach ($groupedTags as $k => $tagNames)
		{
			foreach ($tagNames as $tagName)
			{
				$return['tags'][$tagName]['bitNumber'] = $bitNumber;
			}

			$bitTag[] = $tagName;
			++$bitNumber;
		}

		// Build the bitfields of each tag, including the *root* pseudo-tag
		foreach ($matrix as $tagName => $tagMatrix)
		{
			foreach (array('allowedChildren', 'allowedDescendants') as $fieldName)
			{
				$bitfield = '';
				foreach ($bitTag as $targetName)
				{
					$bitfield .= $tagMatrix[$fieldName][$targetName];
				}

				$return['tags'][$tagName][$fieldName] = $bitfield;
			}
		}

		// Pack the binary representations into raw bytes
		foreach ($return['tags'] as $tag => &$bitfields)
		{
			$bitfields['allowedChildren']    = self::pack($bitfields['allowedChildren']);
			$bitfields['allowedDescendants'] = self::pack($bitfields['allowedDescendants']);
		}
		unset($bitfields);

		// Remove the *root* pseudo-tag from the list of tags and move it to its own entry
		$return['root'] = $return['tags']['*root*'];
		unset($return['tags']['*root*']);

		return $return;
	}

	/**
	* @param  array $rules
	* @return array
	*/
	protected static function unrollRules(array $rules)
	{
		$matrix = array();

		// Keep a list of tag names for easy access
		$tagNames = array_keys($rules);

		// First we seed the list with default values
		foreach ($rules as $tagName => $tagRules)
		{
			if (isset($tagRules['defaultChildRule']))
			{
				$defaultChildValue = (int) ($tagRules['defaultChildRule'] === 'allow');
			}
			else
			{
				$defaultChildValue = 1;
			}

			if (isset($tagRules['defaultDescendantRule']))
			{
				$defaultDescendantValue = (int) ($tagRules['defaultDescendantRule'] === 'allow');
			}
			else
			{
				$defaultDescendantValue = 1;
			}

			foreach ($tagNames as $targetName)
			{
				$matrix[$tagName]['allowedChildren'][$targetName]    = $defaultChildValue;
				$matrix[$tagName]['allowedDescendants'][$targetName] = $defaultDescendantValue;
			}
		}

		// Then we apply "allow" rules to grant usage, overwriting the default settings
		foreach ($rules as $tagName => $tagRules)
		{
			if (isset($tagRules['allowChild']))
			{
				foreach ($tagRules['allowChild'] as $targetName)
				{
					$matrix[$tagName]['allowedChildren'][$targetName] = 1;
				}
			}

			if (isset($tagRules['allowDescendant']))
			{
				foreach ($tagRules['allowDescendant'] as $targetName)
				{
					$matrix[$tagName]['allowedDescendants'][$targetName] = 1;
				}
			}
		}

		// Then we apply "deny" rules (as well as "requireParent"), overwriting "allow" rules
		foreach ($rules as $tagName => $tagRules)
		{
			if (!empty($tagRules['denyAll']))
			{
				$matrix[$tagName]['allowedChildren']    = array_fill_keys($tagNames, 0);
				$matrix[$tagName]['allowedDescendants'] = array_fill_keys($tagNames, 0);

				continue;
			}

			if (isset($tagRules['denyChild']))
			{
				foreach ($tagRules['denyChild'] as $targetName)
				{
					$matrix[$tagName]['allowedChildren'][$targetName] = 0;
				}
			}

			if (isset($tagRules['denyDescendant']))
			{
				foreach ($tagRules['denyDescendant'] as $targetName)
				{
					$matrix[$tagName]['allowedDescendants'][$targetName] = 0;

					// Carry the rule to children as well
					$matrix[$tagName]['allowedChildren'][$targetName] = 0;
				}
			}

			if (isset($tagRules['requireParent']))
			{
				// Every parent that isn't in the "requireParent" list will explicitely deny the
				// child tag
				foreach ($tagNames as $parentName)
				{
					if (!in_array($parentName, $tagRules['requireParent'], true))
					{
						$matrix[$parentName]['allowedChildren'][$tagName] = 0;
					}
				}
			}
		}

		// We still need to ensure that denied descendants override allowed children
		foreach ($matrix as $tagName => $tagMatrix)
		{
			foreach ($tagMatrix['allowedDescendants'] as $targetName => $isAllowed)
			{
				if (!$isAllowed)
				{
					$matrix[$tagName]['allowedChildren'][$targetName] = 0;
				}
			}
		}

		return $matrix;
	}

	/**
	* Remove unusable tags from the matrix
	*
	* @param  array &$matrix
	* @return void
	*/
	protected static function pruneMatrix(array &$matrix)
	{
		$usableTags = array('*root*' => 1);

		// Start from the root and keep digging
		$parentTags = $usableTags;
		do
		{
			$nextTags = array();
			foreach (array_keys($parentTags) as $tagName)
			{
				// Accumulate the names of tags that are allowed as children of our parent tags
				$nextTags += array_filter($matrix[$tagName]['allowedChildren']);
			}

			// Keep only the tags that are in the matrix but aren't in the usable array yet, then
			// add them to the array
			$parentTags  = array_diff_key($nextTags, $usableTags);
			$parentTags  = array_intersect_key($parentTags, $matrix);
			$usableTags += $parentTags;
		}
		while ($parentTags);

		// Remove unusable tags from the matrix
		$matrix = array_intersect_key($matrix, $usableTags);
		unset($usableTags['*root*']);

		// Remove unusable tags from the targets
		foreach ($matrix as $tagName => &$tagMatrix)
		{
			$tagMatrix['allowedChildren'] 
				= array_intersect_key($tagMatrix['allowedChildren'], $usableTags);

			$tagMatrix['allowedDescendants']
				= array_intersect_key($tagMatrix['allowedDescendants'], $usableTags);
		}
		unset($tagMatrix);
	}

	/**
	* Convert a binary representation such as "101011" to raw bytes
	*
	* @param  string $bitfield "10000010"
	* @return string           "\x82"
	*/
	protected static function pack($bitfield)
	{
		return implode('', array_map('chr', array_map('bindec', array_map('strrev', str_split($bitfield, 8)))));
	}
}