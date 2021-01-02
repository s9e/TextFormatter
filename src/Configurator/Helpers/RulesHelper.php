<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;

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
		$rules = ['*root*' => iterator_to_array($rootRules)];
		foreach ($tags as $tagName => $tag)
		{
			$rules[$tagName] = iterator_to_array($tag->rules);
		}

		// Create a matrix that contains all of the tags and whether every other tag is allowed as
		// a child and as a descendant
		$matrix = self::unrollRules($rules);

		// Remove unusable tags from the matrix
		self::pruneMatrix($matrix);

		// Group together tags are allowed in the exact same contexts
		$groupedTags = [];
		foreach (array_keys($matrix) as $tagName)
		{
			if ($tagName === '*root*')
			{
				continue;
			}

			$k = '';
			foreach ($matrix as $tagMatrix)
			{
				$k .= $tagMatrix['allowedChildren'][$tagName];
				$k .= $tagMatrix['allowedDescendants'][$tagName];
			}

			$groupedTags[$k][] = $tagName;
		}

		// Record the bit number of each tag, and the name of a tag for each bit
		$bitTag     = [];
		$bitNumber  = 0;
		$tagsConfig = [];
		foreach ($groupedTags as $tagNames)
		{
			foreach ($tagNames as $tagName)
			{
				$tagsConfig[$tagName]['bitNumber'] = $bitNumber;
				$bitTag[$bitNumber] = $tagName;
			}

			++$bitNumber;
		}

		// Build the bitfields of each tag, including the *root* pseudo-tag
		foreach ($matrix as $tagName => $tagMatrix)
		{
			$allowedChildren    = '';
			$allowedDescendants = '';
			foreach ($bitTag as $targetName)
			{
				$allowedChildren    .= $tagMatrix['allowedChildren'][$targetName];
				$allowedDescendants .= $tagMatrix['allowedDescendants'][$targetName];
			}

			$tagsConfig[$tagName]['allowed'] = self::pack($allowedChildren, $allowedDescendants);
		}

		// Prepare the return value
		$return = [
			'root' => $tagsConfig['*root*'],
			'tags' => $tagsConfig
		];
		unset($return['tags']['*root*']);

		return $return;
	}

	/**
	* Initialize a matrix of settings
	*
	* @param  array $rules Rules for each tag
	* @return array        Multidimensional array of [tagName => [scope => [targetName => setting]]]
	*/
	protected static function initMatrix(array $rules)
	{
		$matrix   = [];
		$tagNames = array_keys($rules);

		foreach ($rules as $tagName => $tagRules)
		{
			$matrix[$tagName]['allowedChildren']    = array_fill_keys($tagNames, 0);
			$matrix[$tagName]['allowedDescendants'] = array_fill_keys($tagNames, 0);
		}

		return $matrix;
	}

	/**
	* Apply given rule from each applicable tag
	*
	* For each tag, if the rule has any target we set the corresponding value for each target in the
	* matrix
	*
	* @param  array  &$matrix   Settings matrix
	* @param  array   $rules    Rules for each tag
	* @param  string  $ruleName Rule name
	* @param  string  $key      Key in the matrix
	* @param  integer $value    Value to be set
	* @return void
	*/
	protected static function applyTargetedRule(array &$matrix, $rules, $ruleName, $key, $value)
	{
		foreach ($rules as $tagName => $tagRules)
		{
			if (!isset($tagRules[$ruleName]))
			{
				continue;
			}

			foreach ($tagRules[$ruleName] as $targetName)
			{
				$matrix[$tagName][$key][$targetName] = $value;
			}
		}
	}

	/**
	* @param  array $rules
	* @return array
	*/
	protected static function unrollRules(array $rules)
	{
		// Initialize the matrix with default values
		$matrix = self::initMatrix($rules);

		// Convert ignoreTags and requireParent to denyDescendant and denyChild rules
		$tagNames = array_keys($rules);
		foreach ($rules as $tagName => $tagRules)
		{
			if (!empty($tagRules['ignoreTags']))
			{
				$rules[$tagName]['denyChild']      = $tagNames;
				$rules[$tagName]['denyDescendant'] = $tagNames;
			}

			if (!empty($tagRules['requireParent']))
			{
				$denyParents = array_diff($tagNames, $tagRules['requireParent']);
				foreach ($denyParents as $parentName)
				{
					$rules[$parentName]['denyChild'][] = $tagName;
				}
			}
		}

		// Apply "allow" rules to grant usage, overwriting the default settings
		self::applyTargetedRule($matrix, $rules, 'allowChild',      'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedDescendants', 1);

		// Apply "deny" rules to remove usage
		self::applyTargetedRule($matrix, $rules, 'denyChild',      'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedDescendants', 0);

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
		$usableTags = ['*root*' => 1];

		// Start from the root and keep digging
		$parentTags = $usableTags;
		do
		{
			$nextTags = [];
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
		while (!empty($parentTags));

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
	* Convert a binary representation such as "101011" to an array of integer
	*
	* Each bitfield is split in groups of 8 bits, then converted to a 16-bit integer where the
	* allowedChildren bitfield occupies the least significant bits and the allowedDescendants
	* bitfield occupies the most significant bits
	*
	* @param  string    $allowedChildren
	* @param  string    $allowedDescendants
	* @return integer[]
	*/
	protected static function pack($allowedChildren, $allowedDescendants)
	{
		$allowedChildren    = str_split($allowedChildren,    8);
		$allowedDescendants = str_split($allowedDescendants, 8);

		$allowed = [];
		foreach (array_keys($allowedChildren) as $k)
		{
			$allowed[] = bindec(sprintf(
				'%1$08s%2$08s',
				strrev($allowedDescendants[$k]),
				strrev($allowedChildren[$k])
			));
		}

		return $allowed;
	}
}