<?php declare(strict_types=1);
/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use RuntimeException;
use s9e\TextFormatter\Configurator\RecursiveParser\MatcherInterface;
class RecursiveParser
{
	protected $callbacks = array();
	protected $groupMatches = array();
	protected $matchGroups = array();
	protected $regexp;
	public function parse(string $str, string $name = '')
	{
		$regexp = $this->regexp;
		if ($name !== '')
		{
			$restrict = (isset($this->groupMatches[$name])) ? \implode('|', $this->groupMatches[$name]) : $name;
			$regexp   = \preg_replace('(\\(\\?<(?!(?:' . $restrict . '|\\w+\\d+)>))', '(*F)$0', $regexp);
		}
		\preg_match($regexp, $str, $m);
		if (!isset($m['MARK']))
			throw new RuntimeException('Cannot parse ' . \var_export($str, \true));
		$name = $m['MARK'];
		$args = $this->getArguments($m, $name);
		return array(
			'groups' => $this->matchGroups[$name] ?? array(),
			'match'  => $name,
			'value'  => \call_user_func_array($this->callbacks[$name], $args)
		);
	}
	public function setMatchers(array $matchers): void
	{
		$matchRegexps       = array();
		$this->groupMatches = array();
		$this->matchGroups  = array();
		foreach ($this->getMatchersConfig($matchers) as $matchName => $matchConfig)
		{
			foreach ($matchConfig['groups'] as $group)
				$this->groupMatches[$group][] = $matchName;
			$regexp = $matchConfig['regexp'];
			$regexp = $this->insertCaptureNames($matchName , $regexp);
			$regexp = \str_replace(' ', '\\s*+', $regexp);
			$regexp = '(?<' . $matchName  . '>' . $regexp . ')(*:' . $matchName  . ')';
			$matchRegexps[]                = $regexp;
			$this->callbacks[$matchName]   = $matchConfig['callback'];
			$this->matchGroups[$matchName] = $matchConfig['groups'];
		}
		$groupRegexps = array();
		foreach ($this->groupMatches as $group => $names)
			$groupRegexps[] = '(?<' . $group . '>(?&' . \implode(')|(?&', $names) . '))';
		$this->regexp = '((?(DEFINE)' . \implode('', $groupRegexps). ')^(?:' . \implode('|', $matchRegexps) . ')$)s';
	}
	protected function getArguments(array $matches, string $name): array
	{
		$args = array();
		$i    = 0;
		while (isset($matches[$name . $i]))
		{
			$args[] = $matches[$name . $i];
			++$i;
		}
		return $args;
	}
	protected function getMatchersConfig(array $matchers): array
	{
		$matchersConfig = array();
		foreach ($matchers as $matcher)
			foreach ($matcher->getMatchers() as $matchName => $matchConfig)
			{
				if (\is_string($matchConfig))
					$matchConfig = array('regexp' => $matchConfig);
				$parts       = \explode(':', $matchName);
				$matchName   = \array_pop($parts);
				$matchConfig += array(
					'callback' => array($matcher, 'parse' . $matchName),
					'groups'   => array(),
					'order'    => 0
				);
				$matchConfig['name']   = $matchName;
				$matchConfig['groups'] = \array_unique(\array_merge($matchConfig['groups'], $parts));
				\sort($matchConfig['groups']);
				$matchersConfig[$matchName] = $matchConfig;
			}
		\uasort($matchersConfig, 'static::sortMatcherConfig');
		return $matchersConfig;
	}
	protected function insertCaptureNames(string $name, string $regexp): string
	{
		$i = 0;
		return \preg_replace_callback(
			'((?<!\\\\)\\((?!\\?))',
			function ($m) use (&$i, $name)
			{
				return '(?<' . $name . $i++ . '>';
			},
			$regexp
		);
	}
	protected static function sortMatcherConfig(array $a, array $b): int
	{
		if ($a['order'] !== $b['order'])
			return $a['order'] - $b['order'];
		return \strcmp($a['name'], $b['name']);
	}
}