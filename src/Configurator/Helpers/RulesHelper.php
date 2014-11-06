<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;

abstract class RulesHelper
{
	public static function getBitfields(TagCollection $tags, Ruleset $rootRules)
	{
		$rules = ['*root*' => \iterator_to_array($rootRules)];
		foreach ($tags as $tagName => $tag)
			$rules[$tagName] = \iterator_to_array($tag->rules);

		$matrix = self::unrollRules($rules);

		self::pruneMatrix($matrix);

		$groupedTags = [];
		foreach (\array_keys($matrix) as $tagName)
		{
			if ($tagName === '*root*')
				continue;

			$k = '';

			foreach ($matrix as $tagMatrix)
			{
				$k .= $tagMatrix['allowedChildren'][$tagName];
				$k .= $tagMatrix['allowedDescendants'][$tagName];
			}

			$groupedTags[$k][] = $tagName;
		}

		$return = [];

		$bitTag    = [];
		$bitNumber = 0;
		foreach ($groupedTags as $tagNames)
		{
			foreach ($tagNames as $tagName)
			{
				$return['tags'][$tagName]['bitNumber'] = $bitNumber;
				$bitTag[$bitNumber] = $tagName;
			}

			++$bitNumber;
		}

		foreach ($matrix as $tagName => $tagMatrix)
			foreach (['allowedChildren', 'allowedDescendants'] as $fieldName)
			{
				$bitfield = '';
				foreach ($bitTag as $targetName)
					$bitfield .= $tagMatrix[$fieldName][$targetName];

				$return['tags'][$tagName][$fieldName] = $bitfield;
			}

		foreach ($return['tags'] as &$bitfields)
		{
			$bitfields['allowedChildren']    = self::pack($bitfields['allowedChildren']);
			$bitfields['allowedDescendants'] = self::pack($bitfields['allowedDescendants']);
		}
		unset($bitfields);

		$return['root'] = $return['tags']['*root*'];
		unset($return['tags']['*root*']);

		return $return;
	}

	protected static function initMatrix(array $rules)
	{
		$matrix   = [];
		$tagNames = \array_keys($rules);

		foreach ($rules as $tagName => $tagRules)
		{
			if ($tagRules['defaultDescendantRule'] === 'allow')
			{
				$childValue      = (int) ($tagRules['defaultChildRule'] === 'allow');
				$descendantValue = 1;
			}
			else
			{
				$childValue      = 0;
				$descendantValue = 0;
			}

			$matrix[$tagName]['allowedChildren']    = \array_fill_keys($tagNames, $childValue);
			$matrix[$tagName]['allowedDescendants'] = \array_fill_keys($tagNames, $descendantValue);
		}

		return $matrix;
	}

	protected static function applyTargetedRule(array &$matrix, $rules, $ruleName, $key, $value)
	{
		foreach ($rules as $tagName => $tagRules)
		{
			if (!isset($tagRules[$ruleName]))
				continue;

			foreach ($tagRules[$ruleName] as $targetName)
				$matrix[$tagName][$key][$targetName] = $value;
		}
	}

	protected static function unrollRules(array $rules)
	{
		$matrix = self::initMatrix($rules);

		$tagNames = \array_keys($rules);
		foreach ($rules as $tagName => $tagRules)
		{
			if (!empty($tagRules['ignoreTags']))
				$rules[$tagName]['denyDescendant'] = $tagNames;

			if (!empty($tagRules['requireParent']))
			{
				$denyParents = \array_diff($tagNames, $tagRules['requireParent']);
				foreach ($denyParents as $parentName)
					$rules[$parentName]['denyChild'][] = $tagName;
			}
		}

		self::applyTargetedRule($matrix, $rules, 'allowChild',      'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedDescendants', 1);

		self::applyTargetedRule($matrix, $rules, 'denyChild',      'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedDescendants', 0);

		return $matrix;
	}

	protected static function pruneMatrix(array &$matrix)
	{
		$usableTags = ['*root*' => 1];

		$parentTags = $usableTags;
		do
		{
			$nextTags = [];
			foreach (\array_keys($parentTags) as $tagName)
				$nextTags += \array_filter($matrix[$tagName]['allowedChildren']);

			$parentTags  = \array_diff_key($nextTags, $usableTags);
			$parentTags  = \array_intersect_key($parentTags, $matrix);
			$usableTags += $parentTags;
		}
		while ($parentTags);

		$matrix = \array_intersect_key($matrix, $usableTags);
		unset($usableTags['*root*']);

		foreach ($matrix as $tagName => &$tagMatrix)
		{
			$tagMatrix['allowedChildren']
				= \array_intersect_key($tagMatrix['allowedChildren'], $usableTags);

			$tagMatrix['allowedDescendants']
				= \array_intersect_key($tagMatrix['allowedDescendants'], $usableTags);
		}
		unset($tagMatrix);
	}

	protected static function pack($bitfield)
	{
		return \implode('', \array_map('chr', \array_map('bindec', \array_map('strrev', \str_split($bitfield, 8)))));
	}
}