<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;

abstract class RegexpBuilder
{
	public static function fromList(array $words, array $options = [])
	{
		if (empty($words))
			return '';

		$options += [
			'delimiter'       => '/',
			'caseInsensitive' => \false,
			'specialChars'    => [],
			'useLookahead'    => \false
		];

		if ($options['caseInsensitive'])
		{
			foreach ($words as &$word)
				$word = \strtr(
					$word,
					'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
					'abcdefghijklmnopqrstuvwxyz'
				);
			unset($word);
		}

		$words = \array_unique($words);

		\sort($words);

		$initials = [];

		$esc  = $options['specialChars'];
		$esc += [$options['delimiter'] => '\\' . $options['delimiter']];

		$esc += [
			'!' => '!',
			'-' => '-',
			':' => ':',
			'<' => '<',
			'=' => '=',
			'>' => '>',
			'}' => '}'
		];

		$splitWords = [];

		foreach ($words as $word)
		{
			if (\preg_match_all('#.#us', $word, $matches) === \false)
				throw new RuntimeException("Invalid UTF-8 string '" . $word . "'");

			$splitWord = [];
			foreach ($matches[0] as $pos => $c)
			{
				if (!isset($esc[$c]))
					$esc[$c] = \preg_quote($c);

				if ($pos === 0)
					$initials[] = $esc[$c];

				$splitWord[] = $esc[$c];
			}

			$splitWords[] = $splitWord;
		}

		$regexp = self::assemble([self::mergeChains($splitWords)]);

		if ($options['useLookahead']
		 && \count($initials) > 1
		 && $regexp[0] !== '[')
		{
			$useLookahead = \true;

			foreach ($initials as $initial)
				if (!self::canBeUsedInCharacterClass($initial))
				{
					$useLookahead = \false;
					break;
				}

			if ($useLookahead)
				$regexp = '(?=' . self::generateCharacterClass($initials) . ')' . $regexp;
		}

		return $regexp;
	}

	protected static function mergeChains(array $chains)
	{
		if (!isset($chains[1]))
			return $chains[0];

		$mergedChain = self::removeLongestCommonPrefix($chains);

		if (!isset($chains[0][0])
		 && !\array_filter($chains))
			return $mergedChain;

		$suffix = self::removeLongestCommonSuffix($chains);

		if (isset($chains[1]))
		{
			self::optimizeDotChains($chains);
			self::optimizeCatchallChains($chains);
		}

		$endOfChain = \false;

		$remerge = \false;

		$groups = [];
		foreach ($chains as $chain)
		{
			if (!isset($chain[0]))
			{
				$endOfChain = \true;
				continue;
			}

			$head = $chain[0];

			if (isset($groups[$head]))
				$remerge = \true;

			$groups[$head][] = $chain;
		}

		$characterClass = [];
		foreach ($groups as $head => $groupChains)
		{
			$head = (string) $head;

			if ($groupChains === [[$head]]
			 && self::canBeUsedInCharacterClass($head))
				$characterClass[$head] = $head;
		}

		\sort($characterClass);

		if (isset($characterClass[1]))
		{
			foreach ($characterClass as $char)
				unset($groups[$char]);

			$head = self::generateCharacterClass($characterClass);
			$groups[$head][] = [$head];

			$groups = [$head => $groups[$head]]
			        + $groups;
		}

		if ($remerge)
		{
			$mergedChains = [];
			foreach ($groups as $head => $groupChains)
				$mergedChains[] = self::mergeChains($groupChains);

			self::mergeTails($mergedChains);

			$regexp = \implode('', self::mergeChains($mergedChains));

			if ($endOfChain)
				$regexp = self::makeRegexpOptional($regexp);

			$mergedChain[] = $regexp;
		}
		else
		{
			self::mergeTails($chains);
			$mergedChain[] = self::assemble($chains);
		}

		foreach ($suffix as $atom)
			$mergedChain[] = $atom;

		return $mergedChain;
	}

	protected static function mergeTails(array &$chains)
	{
		self::mergeTailsCC($chains);

		self::mergeTailsAltern($chains);

		$chains = \array_values($chains);
	}

	protected static function mergeTailsCC(array &$chains)
	{
		$groups = [];

		foreach ($chains as $k => $chain)
			if (isset($chain[1])
			 && !isset($chain[2])
			 && self::canBeUsedInCharacterClass($chain[0]))
				$groups[$chain[1]][$k] = $chain;

		foreach ($groups as $groupChains)
		{
			if (\count($groupChains) < 2)
				continue;

			$chains = \array_diff_key($chains, $groupChains);

			$chains[] = self::mergeChains(\array_values($groupChains));
		}
	}

	protected static function mergeTailsAltern(array &$chains)
	{
		$groups = [];
		foreach ($chains as $k => $chain)
			if (!empty($chain))
			{
				$tail = \array_slice($chain, -1);
				$groups[$tail[0]][$k] = $chain;
			}

		foreach ($groups as $tail => $groupChains)
		{
			if (\count($groupChains) < 2)
				continue;

			$mergedChain = self::mergeChains(\array_values($groupChains));

			$oldLen = 0;
			foreach ($groupChains as $groupChain)
				$oldLen += \array_sum(\array_map('strlen', $groupChain));

			if ($oldLen <= \array_sum(\array_map('strlen', $mergedChain)))
				continue;

			$chains = \array_diff_key($chains, $groupChains);

			$chains[] = $mergedChain;
		}
	}

	protected static function removeLongestCommonPrefix(array &$chains)
	{
		$pLen = 0;

		while (1)
		{
			$c = \null;

			foreach ($chains as $chain)
			{
				if (!isset($chain[$pLen]))
					break 2;

				if (!isset($c))
				{
					$c = $chain[$pLen];
					continue;
				}

				if ($chain[$pLen] !== $c)
					break 2;
			}

			++$pLen;
		}

		if (!$pLen)
			return [];

		$prefix = \array_slice($chains[0], 0, $pLen);

		foreach ($chains as &$chain)
			$chain = \array_slice($chain, $pLen);
		unset($chain);

		return $prefix;
	}

	protected static function removeLongestCommonSuffix(array &$chains)
	{
		$chainsLen = \array_map('count', $chains);

		$maxLen = \min($chainsLen);

		if (\max($chainsLen) === $maxLen)
			--$maxLen;

		$sLen = 0;

		while ($sLen < $maxLen)
		{
			$c = \null;

			foreach ($chains as $k => $chain)
			{
				$pos = $chainsLen[$k] - ($sLen + 1);

				if (!isset($c))
				{
					$c = $chain[$pos];
					continue;
				}

				if ($chain[$pos] !== $c)
					break 2;
			}

			++$sLen;
		}

		if (!$sLen)
			return [];

		$suffix = \array_slice($chains[0], -$sLen);

		foreach ($chains as &$chain)
			$chain = \array_slice($chain, 0, -$sLen);
		unset($chain);

		return $suffix;
	}

	protected static function assemble(array $chains)
	{
		$endOfChain = \false;

		$regexps        = [];
		$characterClass = [];

		foreach ($chains as $chain)
		{
			if (empty($chain))
			{
				$endOfChain = \true;
				continue;
			}

			if (!isset($chain[1])
			 && self::canBeUsedInCharacterClass($chain[0]))
				$characterClass[$chain[0]] = $chain[0];
			else
				$regexps[] = \implode('', $chain);
		}

		if (!empty($characterClass))
		{
			\sort($characterClass);

			$regexp = (isset($characterClass[1]))
					? self::generateCharacterClass($characterClass)
					: $characterClass[0];

			\array_unshift($regexps, $regexp);
		}

		if (empty($regexps))
			return '';

		if (isset($regexps[1]))
		{
			$regexp = \implode('|', $regexps);

			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		}
		else
			$regexp = $regexps[0];

		if ($endOfChain)
			$regexp = self::makeRegexpOptional($regexp);

		return $regexp;
	}

	protected static function makeRegexpOptional($regexp)
	{
		if (\preg_match('#^\\.\\+\\??$#', $regexp))
			return \str_replace('+', '*', $regexp);

		if (\preg_match('#^(\\\\?.)((?:\\1\\?)+)$#Du', $regexp, $m))
			return $m[1] . '?' . $m[2];

		if (\preg_match('#^(?:[$^]|\\\\[bBAZzGQEK])$#', $regexp))
			return '';

		if (\preg_match('#^\\\\?.$#Dus', $regexp))
			$isAtomic = \true;
		elseif (\preg_match('#^[^[(].#s', $regexp))
			$isAtomic = \false;
		else
		{
			$def    = RegexpParser::parse('#' . $regexp . '#');
			$tokens = $def['tokens'];

			switch (\count($tokens))
			{
				case 1:
					$startPos = $tokens[0]['pos'];
					$len      = $tokens[0]['len'];

					$isAtomic = (bool) ($startPos === 0 && $len === \strlen($regexp));

					if ($isAtomic && $tokens[0]['type'] === 'characterClass')
					{
						$regexp = \rtrim($regexp, '+*?');

						if (!empty($tokens[0]['quantifiers']) && $tokens[0]['quantifiers'] !== '?')
							$regexp .= '*';
					}
					break;

				case 2:
					if ($tokens[0]['type'] === 'nonCapturingSubpatternStart'
					 && $tokens[1]['type'] === 'nonCapturingSubpatternEnd')
					{
						$startPos = $tokens[0]['pos'];
						$len      = $tokens[1]['pos'] + $tokens[1]['len'];

						$isAtomic = (bool) ($startPos === 0 && $len === \strlen($regexp));

						break;
					}
					default:
					$isAtomic = \false;
			}
		}

		if (!$isAtomic)
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';

		$regexp .= '?';

		return $regexp;
	}

	protected static function generateCharacterClass(array $chars)
	{
		$chars = \array_flip($chars);

		$unescape = \str_split('$()*+.?[{|^', 1);

		foreach ($unescape as $c)
			if (isset($chars['\\' . $c]))
			{
				unset($chars['\\' . $c]);
				$chars[$c] = 1;
			}

		\ksort($chars);

		if (isset($chars['-']))
			$chars = ['-' => 1] + $chars;

		if (isset($chars['^']))
		{
			unset($chars['^']);
			$chars['^'] = 1;
		}

		return '[' . \implode('', \array_keys($chars)) . ']';
	}

	protected static function canBeUsedInCharacterClass($char)
	{
		if (\preg_match('#^\\\\[aefnrtdDhHsSvVwW]$#D', $char))
			return \true;

		if (\preg_match('#^\\\\[^A-Za-z0-9]$#Dus', $char))
			return \true;

		if (\preg_match('#..#Dus', $char))
			return \false;

		if (\preg_quote($char) !== $char
		 && !\preg_match('#^[-!:<=>}]$#D', $char))
			return \false;

		return \true;
	}

	protected static function optimizeDotChains(array &$chains)
	{
		$validAtoms = [
			'\\d' => 1, '\\D' => 1, '\\h' => 1, '\\H' => 1,
			'\\s' => 1, '\\S' => 1, '\\v' => 1, '\\V' => 1,
			'\\w' => 1, '\\W' => 1,

			'\\^' => 1, '\\$' => 1, '\\.' => 1, '\\?' => 1,
			'\\[' => 1, '\\]' => 1, '\\(' => 1, '\\)' => 1,
			'\\+' => 1, '\\*' => 1, '\\\\' => 1
		];

		do
		{
			$hasMoreDots = \false;
			foreach ($chains as $k1 => $dotChain)
			{
				$dotKeys = \array_keys($dotChain, '.?', \true);

				if (!empty($dotKeys))
				{
					$dotChain[$dotKeys[0]] = '.';
					$chains[$k1] = $dotChain;

					\array_splice($dotChain, $dotKeys[0], 1);
					$chains[] = $dotChain;

					if (isset($dotKeys[1]))
						$hasMoreDots = \true;
				}
			}
		}
		while ($hasMoreDots);

		foreach ($chains as $k1 => $dotChain)
		{
			$dotKeys = \array_keys($dotChain, '.', \true);

			if (empty($dotKeys))
				continue;

			foreach ($chains as $k2 => $tmpChain)
			{
				if ($k2 === $k1)
					continue;

				foreach ($dotKeys as $dotKey)
				{
					if (!isset($tmpChain[$dotKey]))
						continue 2;

					if (!\preg_match('#^.$#Du', \preg_quote($tmpChain[$dotKey]))
					 && !isset($validAtoms[$tmpChain[$dotKey]]))
						continue 2;

					$tmpChain[$dotKey] = '.';
				}

				if ($tmpChain === $dotChain)
					unset($chains[$k2]);
			}
		}
	}

	protected static function optimizeCatchallChains(array &$chains)
	{
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
				continue;

			$head = $chain[0];

			if (!isset($precedence[$head]))
				continue;

			$tail = \implode('', \array_slice($chain, 1));
			if (!isset($tails[$tail])
			 || $precedence[$head] > $tails[$tail]['precedence'])
				$tails[$tail] = [
					'key'        => $k,
					'precedence' => $precedence[$head]
				];
		}

		$catchallChains = [];
		foreach ($tails as $tail => $info)
			$catchallChains[$info['key']] = $chains[$info['key']];

		foreach ($catchallChains as $k1 => $catchallChain)
		{
			$headExpr = $catchallChain[0];
			$tailExpr = \false;
			$match    = \array_slice($catchallChain, 1);

			if (isset($catchallChain[1])
			 && isset($precedence[\end($catchallChain)]))
				$tailExpr = \array_pop($match);

			$matchCnt = \count($match);

			foreach ($chains as $k2 => $chain)
			{
				if ($k2 === $k1)
					continue;

				$start = 0;

				$end = \count($chain);

				if ($headExpr[1] === '+')
				{
					$found = \false;

					foreach ($chain as $start => $atom)
						if (self::matchesAtLeastOneCharacter($atom))
						{
							$found = \true;
							break;
						}

					if (!$found)
						continue;
				}

				if ($tailExpr === \false)
					$end = $start;
				else
				{
					if ($tailExpr[1] === '+')
					{
						$found = \false;

						while (--$end > $start)
							if (self::matchesAtLeastOneCharacter($chain[$end]))
							{
								$found = \true;
								break;
							}

						if (!$found)
							continue;
					}

					$end -= $matchCnt;
				}

				while ($start <= $end)
				{
					if (\array_slice($chain, $start, $matchCnt) === $match)
					{
						unset($chains[$k2]);
						break;
					}

					++$start;
				}
			}
		}
	}

	protected static function matchesAtLeastOneCharacter($expr)
	{
		if (\preg_match('#^[$*?^]$#', $expr))
			return \false;

		if (\preg_match('#^.$#u', $expr))
			return \true;

		if (\preg_match('#^.\\+#u', $expr))
			return \true;

		if (\preg_match('#^\\\\[^bBAZzGQEK1-9](?![*?])#', $expr))
			return \true;

		return \false;
	}

	protected static function canUseAtomicGrouping($expr)
	{
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\.#', $expr))
			return \false;

		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*[+*]#', $expr))
			return \false;

		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\(?(?<!\\()\\?#', $expr))
			return \false;

		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\\\[a-z0-9]#', $expr))
			return \false;

		return \true;
	}
}