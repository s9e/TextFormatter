<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;

abstract class RegexpBuilder
{
	/**
	* @var CharacterClassBuilder
	*/
	protected static $characterClassBuilder;

	/**
	* Create a regexp pattern that matches a list of words
	*
	* @param  array  $words   Words to sort (must be UTF-8)
	* @param  array  $options
	* @return string
	*/
	public static function fromList(array $words, array $options = [])
	{
		if (empty($words))
		{
			return '';
		}

		$options += [
			'delimiter'       => '/',
			'caseInsensitive' => false,
			'specialChars'    => [],
			'unicode'         => true,
			'useLookahead'    => false
		];

		// Normalize ASCII if the regexp is meant to be case-insensitive
		if ($options['caseInsensitive'])
		{
			foreach ($words as &$word)
			{
				$word = strtr(
					$word,
					'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
					'abcdefghijklmnopqrstuvwxyz'
				);
			}
			unset($word);
		}

		// Deduplicate words in advance because some routines such as mergeChains() make assumptions
		// based on the size of some chains without deduplicating them first
		$words = array_unique($words);

		// Sort the words in order to produce the same regexp regardless of the words' order
		sort($words);

		// Used to store the first character of each word so that we can generate the lookahead
		// assertion
		$initials = [];

		// Used to store the escaped representation of each character, e.g. "a"=>"a", "."=>"\\."
		// Also used to give a special meaning to some characters, e.g. "*" => ".*?"
		$esc  = $options['specialChars'];
		$esc += [$options['delimiter'] => '\\' . $options['delimiter']];

		// preg_quote() errs on the safe side when escaping characters that could have a special
		// meaning in some situations. Since we're building the regexp in a controlled environment,
		// we don't have to escape those characters.
		$esc += [
			'!' => '!',
			'-' => '-',
			':' => ':',
			'<' => '<',
			'=' => '=',
			'>' => '>',
			'}' => '}'
		];

		// List of words, split by character
		$splitWords = [];

		foreach ($words as $word)
		{
			$regexp = ($options['unicode']) ? '(.)us' : '(.)s';
			if (preg_match_all($regexp, $word, $matches) === false)
			{
				throw new RuntimeException("Invalid UTF-8 string '" . $word . "'");
			}

			$splitWord = [];
			foreach ($matches[0] as $pos => $c)
			{
				if (!isset($esc[$c]))
				{
					$esc[$c] = preg_quote($c);
				}

				if ($pos === 0)
				{
					// Store the initial for later
					$initials[] = $esc[$c];
				}

				$splitWord[] = $esc[$c];
			}

			$splitWords[] = $splitWord;
		}

		self::$characterClassBuilder            = new CharacterClassBuilder;
		self::$characterClassBuilder->delimiter = $options['delimiter'];
		$regexp = self::assemble([self::mergeChains($splitWords)]);

		if ($options['useLookahead']
		 && count($initials) > 1
		 && $regexp[0] !== '[')
		{
			$useLookahead = true;

			foreach ($initials as $initial)
			{
				if (!self::canBeUsedInCharacterClass($initial))
				{
					$useLookahead = false;
					break;
				}
			}

			if ($useLookahead)
			{
				$regexp = '(?=' . self::generateCharacterClass($initials) . ')' . $regexp;
			}
		}

		return $regexp;
	}

	/**
	* Merge a 2D array of split words into a 1D array of expressions
	*
	* Each element in the passed array is called a "chain". It starts as an array where each element
	* is a character (a sort of UTF-8 aware str_split()) but successive iterations replace
	* individual characters with an equivalent expression.
	*
	* How it works:
	*
	* 1. Remove the longest prefix shared by all the chains
	* 2. Remove the longest suffix shared by all the chains
	* 3. Group each chain by their first element, e.g. all the chains that start with "a" (or in
	*    some cases, "[xy]") are grouped together
	* 4. If no group has more than 1 chain, we assemble them in a regexp, such as (aa|bb). If any
	*    group has more than 1 chain, for each group we merge the chains from that group together so
	*    that no group has more than 1 chain. When we're done, we remerge all the chains together.
	*
	* @param  array $chains
	* @return array
	*/
	protected static function mergeChains(array $chains)
	{
		// If there's only one chain, there's nothing to merge
		if (!isset($chains[1]))
		{
			return $chains[0];
		}

		// The merged chain starts with the chains' common prefix
		$mergedChain = self::removeLongestCommonPrefix($chains);

		if (!isset($chains[0][0])
		 && !array_filter($chains))
		{
			// The chains are empty, either they were already empty or they were identical and their
			// content was removed as their prefix. Nothing left to merge
			return $mergedChain;
		}

		// Remove the longest common suffix and save it for later
		$suffix = self::removeLongestCommonSuffix($chains);

		// Optimize the joker thing
		if (isset($chains[1]))
		{
			self::optimizeDotChains($chains);
			self::optimizeCatchallChains($chains);
		}

		// Whether one of the chain has been completely optimized away by prefix/suffix removal.
		// Signals that the middle part of the regexp is optional, e.g. (prefix)(foo)?(suffix)
		$endOfChain = false;

		// Whether these chains need to be remerged
		$remerge = false;

		// Here we group chains by their first atom (head of chain)
		$groups = [];
		foreach ($chains as $chain)
		{
			if (!isset($chain[0]))
			{
				$endOfChain = true;
				continue;
			}

			$head = $chain[0];

			if (isset($groups[$head]))
			{
				// More than one chain in a group means that we need to remerge
				$remerge = true;
			}

			$groups[$head][] = $chain;
		}

		// See if we can replace single characters with a character class
		$characterClass = [];
		foreach ($groups as $head => $groupChains)
		{
			$head = (string) $head;

			if ($groupChains === [[$head]]
			 && self::canBeUsedInCharacterClass($head))
			{
				// The whole chain is composed of exactly one token, a token that can be used in a
				// character class
				$characterClass[$head] = $head;
			}
		}

		// Sort the characters and reset their keys
		sort($characterClass);

		// Test whether there is more than 1 character in the character class
		if (isset($characterClass[1]))
		{
			// Remove each of those characters from the groups
			foreach ($characterClass as $char)
			{
				unset($groups[$char]);
			}

			// Create a new group for this character class
			$head = self::generateCharacterClass($characterClass);
			$groups[$head][] = [$head];

			// Ensure that the character class is first in the alternation. Not only it looks nice
			// and might be more performant, it's also how assemble() does it, so normalizing it
			// might help with generating identical regexps (or subpatterns that would then be
			// optimized away as a prefix/suffix)
			$groups = [$head => $groups[$head]]
			        + $groups;
		}

		if ($remerge)
		{
			// Merge all chains sharing the same head together
			$mergedChains = [];
			foreach ($groups as $head => $groupChains)
			{
				$mergedChains[] = self::mergeChains($groupChains);
			}

			// Merge the tails of all chains if applicable. Helps with [ab][xy] (two chains with
			// identical tails)
			self::mergeTails($mergedChains);

			// Now merge all chains together and append it to our merged chain
			$regexp = implode('', self::mergeChains($mergedChains));

			if ($endOfChain)
			{
				$regexp = self::makeRegexpOptional($regexp);
			}

			$mergedChain[] = $regexp;
		}
		else
		{
			self::mergeTails($chains);
			$mergedChain[] = self::assemble($chains);
		}

		// Add the common suffix
		foreach ($suffix as $atom)
		{
			$mergedChain[] = $atom;
		}

		return $mergedChain;
	}

	/**
	* Merge the tails of an array of chains wherever applicable
	*
	* This method optimizes (a[xy]|b[xy]|c) into ([ab][xy]|c). The expression [xy] is not a suffix
	* to every branch of the alternation (common suffix), so it's not automatically removed. What we
	* do here is group chains by their last element (their tail) and then try to merge them together
	* group by group. This method should only be called AFTER chains have been group-merged by head.
	*
	* @param array &$chains
	*/
	protected static function mergeTails(array &$chains)
	{
		// (a[xy]|b[xy]|c) => ([ab][xy]|c)
		self::mergeTailsCC($chains);

		// (axx|ayy|bbxx|bbyy|c) => ((a|bb)(xx|yy)|c)
		self::mergeTailsAltern($chains);

		// Don't forget to reset the keys
		$chains = array_values($chains);
	}

	/**
	* Merge the tails of an array of chains if their head can become a character class
	*
	* @param array &$chains
	*/
	protected static function mergeTailsCC(array &$chains)
	{
		$groups = [];

		foreach ($chains as $k => $chain)
		{
			if (isset($chain[1])
			 && !isset($chain[2])
			 && self::canBeUsedInCharacterClass($chain[0]))
			{
				$groups[$chain[1]][$k] = $chain;
			}
		}

		foreach ($groups as $groupChains)
		{
			if (count($groupChains) < 2)
			{
				// Only 1 element, skip this group
				continue;
			}

			// Remove this group's chains from the original list
			$chains = array_diff_key($chains, $groupChains);

			// Merge this group's chains and add the result to the list
			$chains[] = self::mergeChains(array_values($groupChains));
		}
	}

	/**
	* Merge the tails of an array of chains if it makes the end result shorter
	*
	* This kind of merging used to be specifically avoided due to performance concerns but some
	* light benchmarking showed that there isn't any measurable difference in performance between
	*   (?:c|a(?:xx|yy)|bb(?:xx|yy))
	* and
	*   (?:c|(?:a|bb)(?:xx|yy))
	*
	* @param array &$chains
	*/
	protected static function mergeTailsAltern(array &$chains)
	{
		$groups = [];
		foreach ($chains as $k => $chain)
		{
			if (!empty($chain))
			{
				$tail = array_slice($chain, -1);
				$groups[$tail[0]][$k] = $chain;
			}
		}

		foreach ($groups as $tail => $groupChains)
		{
			if (count($groupChains) < 2)
			{
				// Only 1 element, skip this group
				continue;
			}

			// Create a single chain for this group
			$mergedChain = self::mergeChains(array_values($groupChains));

			// Test whether the merged chain is shorter than the sum of its components
			$oldLen = 0;
			foreach ($groupChains as $groupChain)
			{
				$oldLen += array_sum(array_map('strlen', $groupChain));
			}

			if ($oldLen <= array_sum(array_map('strlen', $mergedChain)))
			{
				continue;
			}

			// Remove this group's chains from the original list
			$chains = array_diff_key($chains, $groupChains);

			// Merge this group's chains and add the result to the list
			$chains[] = $mergedChain;
		}
	}

	/**
	* Remove the longest common prefix from an array of chains
	*
	* @param  array &$chains
	* @return array          Removed elements
	*/
	protected static function removeLongestCommonPrefix(array &$chains)
	{
		// Length of longest common prefix
		$pLen = 0;

		while (1)
		{
			// $c will be used to store the character we're matching against
			$c = null;

			foreach ($chains as $chain)
			{
				if (!isset($chain[$pLen]))
				{
					// Reached the end of a word
					break 2;
				}

				if (!isset($c))
				{
					$c = $chain[$pLen];
					continue;
				}

				if ($chain[$pLen] !== $c)
				{
					// Does not match -- don't increment sLen and break out of the loop
					break 2;
				}
			}

			// We have confirmed that all the words share a same prefix of at least ($pLen + 1)
			++$pLen;
		}

		if (!$pLen)
		{
			return [];
		}

		// Store prefix
		$prefix = array_slice($chains[0], 0, $pLen);

		// Remove prefix from each word
		foreach ($chains as &$chain)
		{
			$chain = array_slice($chain, $pLen);
		}
		unset($chain);

		return $prefix;
	}

	/**
	* Remove the longest common suffix from an array of chains
	*
	* NOTE: this method is meant to be called after removeLongestCommonPrefix(). If it's not, then
	*       the longest match return may be off by 1.
	*
	* @param  array &$chains
	* @return array          Removed elements
	*/
	protected static function removeLongestCommonSuffix(array &$chains)
	{
		// Cache the length of every word
		$chainsLen = array_map('count', $chains);

		// Length of the longest possible suffix
		$maxLen = min($chainsLen);

		// If all the words are the same length, the longest suffix is 1 less than the length of the
		// words because we've already extracted the longest prefix
		if (max($chainsLen) === $maxLen)
		{
			--$maxLen;
		}

		// Length of longest common suffix
		$sLen = 0;

		// Try to find the longest common suffix
		while ($sLen < $maxLen)
		{
			// $c will be used to store the character we're matching against
			$c = null;

			foreach ($chains as $k => $chain)
			{
				$pos = $chainsLen[$k] - ($sLen + 1);

				if (!isset($c))
				{
					$c = $chain[$pos];
					continue;
				}

				if ($chain[$pos] !== $c)
				{
					// Does not match -- don't increment sLen and break out of the loop
					break 2;
				}
			}

			// We have confirmed that all the words share a same suffix of at least ($sLen + 1)
			++$sLen;
		}

		if (!$sLen)
		{
			return [];
		}

		// Store suffix
		$suffix = array_slice($chains[0], -$sLen);

		// Remove suffix from each word
		foreach ($chains as &$chain)
		{
			$chain = array_slice($chain, 0, -$sLen);
		}
		unset($chain);

		return $suffix;
	}

	/**
	* Assemble an array of chains into one expression
	*
	* @param  array  $chains
	* @return string
	*/
	protected static function assemble(array $chains)
	{
		$endOfChain = false;

		$regexps        = [];
		$characterClass = [];

		foreach ($chains as $chain)
		{
			if (empty($chain))
			{
				$endOfChain = true;
				continue;
			}

			if (!isset($chain[1])
			 && self::canBeUsedInCharacterClass($chain[0]))
			{
				$characterClass[$chain[0]] = $chain[0];
			}
			else
			{
				$regexps[] = implode('', $chain);
			}
		}

		if (!empty($characterClass))
		{
			// Sort the characters and reset their keys
			sort($characterClass);

			// Use a character class if there are more than 1 characters in it
			$regexp = (isset($characterClass[1]))
					? self::generateCharacterClass($characterClass)
					: $characterClass[0];

			// Prepend the character class to the list of regexps
			array_unshift($regexps, $regexp);
		}

		if (empty($regexps))
		{
			return '';
		}

		if (isset($regexps[1]))
		{
			// There are several branches, coalesce them
			$regexp = implode('|', $regexps);

			// Put the expression in a subpattern
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		}
		else
		{
			$regexp = $regexps[0];
		}

		// If we've reached the end of a chain, it means that the branches are optional
		if ($endOfChain)
		{
			$regexp = self::makeRegexpOptional($regexp);
		}

		return $regexp;
	}

	/**
	* Make an entire regexp optional through the use of the ? quantifier
	*
	* @param  string $regexp
	* @return string
	*/
	protected static function makeRegexpOptional($regexp)
	{
		// .+ and .+? become .* and .*?
		if (preg_match('#^\\.\\+\\??$#', $regexp))
		{
			return str_replace('+', '*', $regexp);
		}

		// Special case: xx? becomes x?x?, \w\w? becomes \w?\w?
		// It covers only the most common case of repetition, it's not a panacea
		if (preg_match('#^(\\\\?.)((?:\\1\\?)+)$#Du', $regexp, $m))
		{
			return $m[1] . '?' . $m[2];
		}

		// Optional assertions are a no-op
		if (preg_match('#^(?:[$^]|\\\\[bBAZzGQEK])$#', $regexp))
		{
			return '';
		}

		// One single character, optionally escaped
		if (preg_match('#^\\\\?.$#Dus', $regexp))
		{
			$isAtomic = true;
		}
		// At least two characters, but it's not a subpattern or a character class
		elseif (preg_match('#^[^[(].#s', $regexp))
		{
			$isAtomic = false;
		}
		else
		{
			$def    = RegexpParser::parse('#' . $regexp . '#');
			$tokens = $def['tokens'];

			switch (count($tokens))
			{
				// One character class
				case 1:
					$startPos = $tokens[0]['pos'];
					$len      = $tokens[0]['len'];

					$isAtomic = (bool) ($startPos === 0 && $len === strlen($regexp));

					// If the regexp is [..]+ it becomes [..]* (to which a ? will be appended)
					if ($isAtomic && $tokens[0]['type'] === 'characterClass')
					{
						$regexp = rtrim($regexp, '+*?');

						if (!empty($tokens[0]['quantifiers']) && $tokens[0]['quantifiers'] !== '?')
						{
							$regexp .= '*';
						}
					}
					break;

				// One subpattern covering the entire regexp
				case 2:
					if ($tokens[0]['type'] === 'nonCapturingSubpatternStart'
					 && $tokens[1]['type'] === 'nonCapturingSubpatternEnd')
					{
						$startPos = $tokens[0]['pos'];
						$len      = $tokens[1]['pos'] + $tokens[1]['len'];

						$isAtomic = (bool) ($startPos === 0 && $len === strlen($regexp));

						// If the tokens are not a non-capturing subpattern, we let it fall through
						break;
					}
					// no break; here

				default:
					$isAtomic = false;
			}
		}

		if (!$isAtomic)
		{
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		}

		$regexp .= '?';

		return $regexp;
	}

	/**
	* Generate a character class from an array of characters
	*
	* @param  array  $chars
	* @return string
	*/
	protected static function generateCharacterClass(array $chars)
	{
		return self::$characterClassBuilder->fromList($chars);
	}

	/**
	* Test whether a given expression (usually one character) can be used in a character class
	*
	* @param  string $char
	* @return bool
	*/
	protected static function canBeUsedInCharacterClass($char)
	{
		/**
		* Encoded non-printable characters and generic character classes are allowed
		* @link http://docs.php.net/manual/en/regexp.reference.escape.php
		*/
		if (preg_match('#^\\\\[aefnrtdDhHsSvVwW]$#D', $char))
		{
			return true;
		}

		// Escaped literals are allowed (escaped sequences excluded)
		if (preg_match('#^\\\\[^A-Za-z0-9]$#Dus', $char))
		{
			return true;
		}

		// More than 1 character => cannot be used in a character class
		if (preg_match('#..#Dus', $char))
		{
			return false;
		}

		// Special characters such as $ or ^ are rejected, but we need to check for characters that
		// get escaped by preg_quote() even though it's not necessary, such as ! or =
		if (preg_quote($char) !== $char
		 && !preg_match('#^[-!:<=>}]$#D', $char))
		{
			return false;
		}

		return true;
	}

	/**
	* Remove chains that overlap with dot chains
	*
	* Will remove chains that are made redundant by the use of the dot metacharacter, e.g.
	* ["a","b","c"] and ["a",".","c"] or ["a","b","c"], ["a","c"] and ["a",".?","c"]
	*
	* @param  array &$chains
	* @return void
	*/
	protected static function optimizeDotChains(array &$chains)
	{
		/**
		* @var array List of valid atoms that should be matched by a dot but happen to be
		*            represented by more than one character
		*/
		$validAtoms = [
			// Escape sequences
			'\\d' => 1, '\\D' => 1, '\\h' => 1, '\\H' => 1,
			'\\s' => 1, '\\S' => 1, '\\v' => 1, '\\V' => 1,
			'\\w' => 1, '\\W' => 1,

			// Special chars that need to be escaped in order to be used as literals
			'\\^' => 1, '\\$' => 1, '\\.' => 1, '\\?' => 1,
			'\\[' => 1, '\\]' => 1, '\\(' => 1, '\\)' => 1,
			'\\+' => 1, '\\*' => 1, '\\\\' => 1
		];

		// First we replace chains such as ["a",".?","b"] with ["a",".","b"] and ["a","b"]
		do
		{
			$hasMoreDots = false;
			foreach ($chains as $k1 => $dotChain)
			{
				$dotKeys = array_keys($dotChain, '.?', true);

				if (!empty($dotKeys))
				{
					// Replace the .? atom in the original chain with a .
					$dotChain[$dotKeys[0]] = '.';
					$chains[$k1] = $dotChain;

					// Create a new chain without the atom
					array_splice($dotChain, $dotKeys[0], 1);
					$chains[] = $dotChain;

					if (isset($dotKeys[1]))
					{
						$hasMoreDots = true;
					}
				}
			}
		}
		while ($hasMoreDots);

		foreach ($chains as $k1 => $dotChain)
		{
			$dotKeys = array_keys($dotChain, '.', true);

			if (empty($dotKeys))
			{
				continue;
			}

			foreach ($chains as $k2 => $tmpChain)
			{
				if ($k2 === $k1)
				{
					continue;
				}

				foreach ($dotKeys as $dotKey)
				{
					if (!isset($tmpChain[$dotKey]))
					{
						// The chain is too short to match, skip this chain
						continue 2;
					}

					// Skip if the dot is neither a literal nor a valid atom
					if (!preg_match('#^.$#Du', preg_quote($tmpChain[$dotKey]))
					 && !isset($validAtoms[$tmpChain[$dotKey]]))
					{
						continue 2;
					}

					// Replace the atom with a dot
					$tmpChain[$dotKey] = '.';
				}

				if ($tmpChain === $dotChain)
				{
					// The chain matches our dot chain, which means we can remove it
					unset($chains[$k2]);
				}
			}
		}
	}

	/**
	* Remove chains that overlap with chains that contain a catchall expression such as .*
	*
	* NOTE: cannot handle possessive expressions such as .++ because we don't know whether that
	*       chain had its suffix/tail stashed by an earlier iteration
	*
	* @param  array &$chains
	* @return void
	*/
	protected static function optimizeCatchallChains(array &$chains)
	{
		// This is how catchall expressions will trump each other in our routine. For instance,
		// instead of (?:.*|.+) we will emit (?:.*). Zero-or-more trumps one-or-more and greedy
		// trumps non-greedy. In some cases, (?:.+|.*?) might be preferable to (?:.*?) but it does
		// not seem like a common enough case to warrant the extra logic
		$precedence = [
			'.*'  => 3,
			'.*?' => 2,
			'.+'  => 1,
			'.+?' => 0
		];

		$tails = [];

		foreach ($chains as $k => $chain)
		{
			if (!isset($chain[0]))
			{
				continue;
			}

			$head = $chain[0];

			// Test whether the head is a catchall expression by looking up its precedence
			if (!isset($precedence[$head]))
			{
				continue;
			}

			$tail = implode('', array_slice($chain, 1));
			if (!isset($tails[$tail])
			 || $precedence[$head] > $tails[$tail]['precedence'])
			{
				$tails[$tail] = [
					'key'        => $k,
					'precedence' => $precedence[$head]
				];
			}
		}

		$catchallChains = [];
		foreach ($tails as $tail => $info)
		{
			$catchallChains[$info['key']] = $chains[$info['key']];
		}

		foreach ($catchallChains as $k1 => $catchallChain)
		{
			$headExpr = $catchallChain[0];
			$tailExpr = false;
			$match    = array_slice($catchallChain, 1);

			// Test whether the catchall chain has another catchall expression at the end
			if (isset($catchallChain[1])
			 && isset($precedence[end($catchallChain)]))
			{
				// Remove the catchall expression from the end
				$tailExpr = array_pop($match);
			}

			$matchCnt = count($match);

			foreach ($chains as $k2 => $chain)
			{
				if ($k2 === $k1)
				{
					continue;
				}

				/**
				* @var integer Offset of the first atom we're trying to match the tail against
				*/
				$start = 0;

				/**
				* @var integer
				*/
				$end = count($chain);

				// If the catchall at the start of the chain must match at least one character, we
				// ensure the chain has at least one character at its beginning
				if ($headExpr[1] === '+')
				{
					$found = false;

					foreach ($chain as $start => $atom)
					{
						if (self::matchesAtLeastOneCharacter($atom))
						{
							$found = true;
							break;
						}
					}

					if (!$found)
					{
						continue;
					}
				}

				// Test whether the catchall chain has another catchall expression at the end
				if ($tailExpr === false)
				{
					$end = $start;
				}
				else
				{
					// If the expression must match at least one character, we ensure that the
					// chain satisfy the requirement and we adjust $end accordingly so the same atom
					// isn't used twice (e.g. used by two consecutive .+ expressions)
					if ($tailExpr[1] === '+')
					{
						$found = false;

						while (--$end > $start)
						{
							if (self::matchesAtLeastOneCharacter($chain[$end]))
							{
								$found = true;
								break;
							}
						}

						if (!$found)
						{
							continue;
						}
					}

					// Now, $start should point to the first atom we're trying to match the catchall
					// chain against, and $end should be equal to the index right after the last
					// atom we can match against. We adjust $end to point to the last position our
					// match can start at
					$end -= $matchCnt;
				}

				while ($start <= $end)
				{
					if (array_slice($chain, $start, $matchCnt) === $match)
					{
						unset($chains[$k2]);
						break;
					}

					++$start;
				}
			}
		}
	}

	/**
	* Test whether a given expression can never match an empty space
	*
	* Only basic checks are performed and it only returns true if we're certain the expression
	* will always match/consume at least one character. For instance, it doesn't properly recognize
	* the expression [ab]+ as matching at least one character.
	*
	* @param  string $expr
	* @return bool
	*/
	protected static function matchesAtLeastOneCharacter($expr)
	{
		if (preg_match('#^[$*?^]$#', $expr))
		{
			return false;
		}

		// A single character should be fine
		if (preg_match('#^.$#u', $expr))
		{
			return true;
		}

		// Matches anything that starts with ".+", "a+", etc...
		if (preg_match('#^.\\+#u', $expr))
		{
			return true;
		}

		// Matches anything that starts with "\d", "\+", "\d+", etc... We avoid matching back
		// references as we can't be sure they matched at least one character themselves
		if (preg_match('#^\\\\[^bBAZzGQEK1-9](?![*?])#', $expr))
		{
			return true;
		}

		// Anything else is either too complicated and too circumstancial to investigate further so
		// we'll just return false
		return false;
	}

	/**
	* Test whether given expression can be safely used with atomic grouping
	*
	* @param  string $expr
	* @return bool
	*/
	protected static function canUseAtomicGrouping($expr)
	{
		if (preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\.#', $expr))
		{
			// An unescaped dot should disable atomic grouping. Technically, we could still allow it
			// depending on what comes next in the regexp but it's a lot of work for something very
			// situational
			return false;
		}

		if (preg_match('#(?<!\\\\)(?>\\\\\\\\)*[+*]#', $expr))
		{
			// A quantifier disables atomic grouping. Again, this could be enabled depending on the
			// context
			return false;
		}

		if (preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\(?(?<!\\()\\?#', $expr))
		{
			// An unescaped ? is a quantifier, unless it's preceded by an unescaped (
			return false;
		}

		if (preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\\\[a-z0-9]#', $expr))
		{
			// Escape sequences disable atomic grouping because they might overlap with another
			// branch
			return false;
		}

		// The regexp looks harmless enough to enable atomic grouping
		return true;
	}
}