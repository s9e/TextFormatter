<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use RuntimeException;
use s9e\TextFormatter\Configurator\RecursiveParser\MatcherInterface;

class RecursiveParser
{
	/**
	* @var array Callback associated with each match name
	*/
	protected $callbacks = [];

	/**
	* @var array Match names associated with each group
	*/
	protected $groupMatches = [];

	/**
	* @var array Groups associated with each match name
	*/
	protected $matchGroups = [];

	/**
	* @var string Regexp used to match input
	*/
	protected $regexp;

	/**
	* Parse given string
	*
	* @param  string $str
	* @param  string $name Allowed match, either match name or group name (default: allow all)
	* @return mixed
	*/
	public function parse(string $str, string $name = '')
	{
		$regexp = $this->regexp;
		if ($name !== '')
		{
			$restrict = (isset($this->groupMatches[$name])) ? implode('|', $this->groupMatches[$name]) : $name;
			$regexp   = preg_replace('(\\(\\?<(?!(?:' . $restrict . '|\\w+\\d+)>))', '(*F)$0', $regexp);
		}

		preg_match($regexp, $str, $m);
		if (!isset($m['MARK']))
		{
			throw new RuntimeException('Cannot parse ' . var_export($str, true));
		}

		$name = $m['MARK'];
		$args = $this->getArguments($m, $name);

		return [
			'groups' => $this->matchGroups[$name] ?? [],
			'match'  => $name,
			'value'  => call_user_func_array($this->callbacks[$name], $args)
		];
	}

	/**
	* Set the list of matchers used by this parser
	*
	* @param  MatcherInterface[]
	* @return void
	*/
	public function setMatchers(array $matchers): void
	{
		$matchRegexps       = [];
		$this->groupMatches = [];
		$this->matchGroups  = [];
		foreach ($this->getMatchersConfig($matchers) as $matchName => $matchConfig)
		{
			foreach ($matchConfig['groups'] as $group)
			{
				$this->groupMatches[$group][] = $matchName;
			}

			$regexp = $matchConfig['regexp'];
			$regexp = $this->insertCaptureNames($matchName , $regexp);
			$regexp = str_replace(' ', '\\s*+', $regexp);
			$regexp = '(?<' . $matchName  . '>' . $regexp . ')(*:' . $matchName  . ')';

			$matchRegexps[]                = $regexp;
			$this->callbacks[$matchName]   = $matchConfig['callback'];
			$this->matchGroups[$matchName] = $matchConfig['groups'];
		}

		$groupRegexps = [];
		foreach ($this->groupMatches as $group => $names)
		{
			$groupRegexps[] = '(?<' . $group . '>(?&' . implode(')|(?&', $names) . '))';
		}

		$this->regexp = '((?(DEFINE)' . implode('', $groupRegexps). ')'
		              . '^(?:' . implode('|', $matchRegexps) . ')$)s';
	}

	/**
	* Get the list of arguments produced by a regexp's match
	*
	* @param  string[] $matches Regexp matches
	* @param  string   $name    Regexp name
	* @return string[]
	*/
	protected function getArguments(array $matches, string $name): array
	{
		$args = [];
		$i    = 0;
		while (isset($matches[$name . $i]))
		{
			$args[] = $matches[$name . $i];
			++$i;
		}

		return $args;
	}

	/**
	* Collect, normalize, sort and return the config for all matchers
	*
	* @param  MatcherInterface[] $matchers
	* @return array
	*/
	protected function getMatchersConfig(array $matchers): array
	{
		$matchersConfig = [];
		foreach ($matchers as $matcher)
		{
			foreach ($matcher->getMatchers() as $matchName => $matchConfig)
			{
				if (is_string($matchConfig))
				{
					$matchConfig = ['regexp' => $matchConfig];
				}
				$parts       = explode(':', $matchName);
				$matchName   = array_pop($parts);
				$matchConfig += [
					'callback' => [$matcher, 'parse' . $matchName],
					'groups'   => [],
					'order'    => 0
				];
				$matchConfig['name']   = $matchName;
				$matchConfig['groups'] = array_unique(array_merge($matchConfig['groups'], $parts));
				sort($matchConfig['groups']);

				$matchersConfig[$matchName] = $matchConfig;
			}
		}
		uasort($matchersConfig, static::class . '::sortMatcherConfig');

		return $matchersConfig;
	}

	/**
	* Insert capture names into given regexp
	*
	* @param  string $name   Name of the regexp, used to name captures
	* @param  string $regexp Original regexp
	* @return string         Modified regexp
	*/
	protected function insertCaptureNames(string $name, string $regexp): string
	{
		$i = 0;

		return preg_replace_callback(
			'((?<!\\\\)\\((?!\\?))',
			function ($m) use (&$i, $name)
			{
				return '(?<' . $name . $i++ . '>';
			},
			$regexp
		);
	}

	/**
	* Compare two matchers' config
	*
	* @param  array $a
	* @param  array $b
	* @return integer
	*/
	protected static function sortMatcherConfig(array $a, array $b): int
	{
		if ($a['order'] !== $b['order'])
		{
			return $a['order'] - $b['order'];
		}

		return strcmp($a['name'], $b['name']);
	}
}