<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Helpers;

use s9e\TextFormatter\ConfigBuilder\Collections\TagCollection;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;

abstract class RulesHelper
{
	/**
	* Generate the allowedChildren and allowedDescendants bitfields for every tag and for the root context
	*
	* @param  TagCollection $tags
	* @return array
	*/
	public static function getBitfields(TagCollection $tags)
	{
		$ret = array(
			'rootContext' => array(
				'allowedChildren'    => '',
				'allowedDescendants' => ''
			)
		);

		// Compute the allowed children/descendants for each tag
		list($allowedChildren, $allowedDescendants) = self::unrollRules($tags);

		// Remove targets that don't exist and tags that aren't allowed anywhere
		$allowedChildren    = self::cleanUpPermissions($allowedChildren, $tags);
		$allowedDescendants = self::cleanUpPermissions($allowedDescendants, $tags);

		// Order the tags by name to keep their order consistent
		ksort($allowedChildren);
		ksort($allowedDescendants);

		// Group tags by bitfield, in order to group tags that are allowed in the exact same
		// contexts together
		$groupedTags = array();
		foreach ($tags as $tagName => $tag)
		{
			if (!isset($allowedChildren[$tagName]))
			{
				// This tag has been removed already
				continue;
			}

			// We start with whether this tag is allowed at the root.
			$k = (self::isAllowedAtRoot($tag)) ? '1' : '0';

			// Then we append the bitfield that represents which parents allow this tag
			foreach ($allowedChildren as $targets)
			{
				$k .= $targets[$tagName];
			}

			// Then we append the bitfield that represents which ancestors allow this tag
			foreach ($allowedDescendants as $targets)
			{
				$k .= $targets[$tagName];
			}

			$groupedTags[$k][] = $tagName;
		}

		// Now replace the bitfield (used as key) with the corresponding bit number
		$groupedTags = array_values($groupedTags);

		// Assign a bit number for every tag. Tags with the same set of permissions get to share the
		// same bit number
		foreach ($groupedTags as $bitNumber => $tagNames)
		{
			foreach ($tagNames as $tagName)
			{
				$ret['tags'][$tagName] = array(
					'bitNumber'          => $bitNumber,
					'allowedChildren'    => '',
					'allowedDescendants' => ''
				);
			}

			// Fill in the root context's bitfields
			$ret['rootContext']['allowedChildren'] .= self::isAllowedAtRoot($tags[$tagName]);

			// Denied descendants are removed from the list, so we know this tag is allowed
			$ret['rootContext']['allowedDescendants'] .= '1';
		}

		// Finalize the root context's bitfields
		$ret['rootContext'] = array_map(array('self', 'bin2raw'), $ret['rootContext']);

		// Now fill in each tag's bitfields
		foreach ($ret['tags'] as $tagName => &$config)
		{
			foreach ($groupedTags as $tagNames)
			{
				$targetName = $tagNames[0];

				$config['allowedChildren']    .= $allowedChildren[$tagName][$targetName];
				$config['allowedDescendants'] .= $allowedDescendants[$tagName][$targetName];
			}

			$config['allowedChildren']    = self::bin2raw($config['allowedChildren']);
			$config['allowedDescendants'] = self::bin2raw($config['allowedDescendants']);
		}
		unset($config);

		return $ret;
	}

	/**
	* @param  TagCollection $tags
	* @return array
	*/
	protected static function unrollRules(TagCollection $tags)
	{
		$allowedChildren    = array();
		$allowedDescendants = array();

		// Save the tag names so we don't have to iterate over TagCollection twice
		$tagNames = array_keys(iterator_to_array($tags));

		// First we seed the list with default values
		foreach ($tags as $tagName => $tag)
		{
			if (isset($tag->rules['defaultChildRule']))
			{
				$defaultChildValue = (int) ($tag->rules['defaultChildRule'] === 'allow');
			}
			else
			{
				$defaultChildValue = 1;
			}

			if (isset($tag->rules['defaultDescendantRule']))
			{
				$defaultDescendantValue = (int) ($tag->rules['defaultDescendantRule'] === 'allow');
			}
			else
			{
				$defaultDescendantValue = 1;
			}

			foreach ($tagNames as $targetName)
			{
				$allowedChildren[$tagName][$targetName]    = $defaultChildValue;
				$allowedDescendants[$tagName][$targetName] = $defaultDescendantValue;
			}
		}

		// Then we apply "allow" rules to grant usage, overwriting the default settings
		foreach ($tags as $tagName => $tag)
		{
			if (isset($tag->rules['allowChild']))
			{
				foreach ($tag->rules['allowChild'] as $targetName)
				{
					$allowedChildren[$tagName][$targetName] = 1;
				}
			}

			if (isset($tag->rules['allowDescendant']))
			{
				foreach ($tag->rules['allowDescendant'] as $targetName)
				{
					$allowedDescendants[$tagName][$targetName] = 1;
				}
			}
		}

		// Then we apply "deny" rules (as well as "requireParent"), overwriting "allow" rules
		foreach ($tags as $tagName => $tag)
		{
			if (isset($tag->rules['denyChild']))
			{
				foreach ($tag->rules['denyChild'] as $targetName)
				{
					$allowedChildren[$tagName][$targetName] = 0;
				}
			}

			if (isset($tag->rules['denyDescendant']))
			{
				foreach ($tag->rules['denyDescendant'] as $targetName)
				{
					$allowedDescendants[$tagName][$targetName] = 0;

					// Carry the rule to children as well
					$allowedChildren[$tagName][$targetName] = 0;
				}
			}

			if (isset($tag->rules['requireParent']))
			{
				// Every parent that isn't in the "requireParent" list will explicitely deny the
				// child tag
				foreach ($tagNames as $parentName)
				{
					if (!in_array($parentName, $tag->rules['requireParent'], true))
					{
						$allowedChildren[$parentName][$tagName] = 0;
					}
				}
			}
		}

		// We still need to ensure that denied descendants override allowed children
		foreach ($allowedDescendants as $tagName => $targets)
		{
			foreach ($targets as $targetName => $isAllowed)
			{
				if (!$isAllowed)
				{
					$allowedChildren[$tagName][$targetName] = 0;
				}
			}
		}

		return array($allowedChildren, $allowedDescendants);
	}

	/**
	* 
	*
	* @param  array         $permissions
	* @param  TagCollection $tags
	* @return array
	*/
	protected static function cleanUpPermissions(array $permissions, TagCollection $tags)
	{
		/**
		* @var array List of tags that are allowed anywhere in a text
		*/
		$keepTags = array();

		foreach ($tags as $tagName => $tag)
		{
			// Test whether this tag is allowed at the root
			if (self::isAllowedAtRoot($tag))
			{
				$keepTags[] = $tagName;
				continue;
			}

			foreach ($permissions as $parentName => $targets)
			{
				// Test whether this tag is allowed by any other tag than itself
				if ($targets[$tagName] && $parentName !== $tagName)
				{
					$keepTags[] = $tagName;
					continue 2;
				}
			}
		}

		// Flip the list of tags for convenience
		$keepTags = array_flip($keepTags);

		// Discard unused tags from the lists
		$permissions = array_intersect_key(
			$permissions,
			$keepTags
		);

		foreach ($permissions as &$targets)
		{
			$targets = array_intersect_key(
				$targets,
				$keepTags
			);
		}
		unset($targets);

		return $permissions;
	}

	/**
	* Convert a binary representation such as "101011" to raw bytes
	*
	* @param  string $bin "10000010"
	* @return string      "\x82"
	*/
	protected static function bin2raw($bin)
	{
		return implode('', array_map('chr', array_map('bindec', array_map('strrev', str_split($bin, 8)))));
	}

	/**
	* Test whether a tag is allowed at the root of a text
	*
	* @param  Tag  $tag
	* @return bool
	*/
	protected static function isAllowedAtRoot(Tag $tag)
	{
		return (empty($tag->rules['disallowAtRoot'])
			 && empty($tag->rules['requireParent'])
			 && empty($tag->rules['requireAncestor']));
	}
}