<?php declare(strict_types=1);
/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\RecursiveParser\AbstractRecursiveMatcher;
class FilterSyntaxMatcher extends AbstractRecursiveMatcher
{
	public function getMatchers(): array
	{
		return array(
			'Array' => array(
				'groups' => array('FilterArg', 'Literal'),
				'regexp' => '\\[ ((?&ArrayElements))? \\]',
			),
			'ArrayElement' => array(
				'regexp' => '(?:((?&Scalar)) => )?((?&Literal))',
			),
			'ArrayElements' => array(
				'regexp' => '((?&ArrayElement))(?: , ((?&ArrayElements)))?',
			),
			'DoubleQuotedString' => array(
				'groups' => array('FilterArg', 'Literal', 'Scalar'),
				'regexp' => '"((?:[^\\\\"]|\\\\.)*)"',
			),
			'False' => array(
				'groups' => array('FilterArg', 'Literal', 'Scalar'),
				'regexp' => '[Ff][Aa][Ll][Ss][Ee]',
			),
			'FilterArgs' => array(
				'regexp' => '((?&FilterArg))(?: , ((?&FilterArgs)))?',
			),
			'FilterCallback' => array(
				'regexp' => '([#:\\\\\\w]+)(?: \\( ((?&FilterArgs)?) \\))?',
			),
			'Float' => array(
				'groups' => array('FilterArg', 'Literal', 'Scalar'),
				'regexp' => '([-+]?(?:\\.[0-9]+|[0-9]+\\.[0-9]*|[0-9]+(?=[Ee]))(?:[Ee]-?[0-9]+)?)',
			),
			'Integer' => array(
				'groups' => array('FilterArg', 'Literal', 'Scalar'),
				'regexp' => '(-?(?:0[Bb][01]+|0[Xx][0-9A-Fa-f]+|[0-9]+))',
			),
			'Null' => array(
				'groups' => array('FilterArg', 'Literal', 'Scalar'),
				'regexp' => '[Nn][Uu][Ll][Ll]',
			),
			'Param' => array(
				'groups' => array('FilterArg'),
				'regexp' => '\\$(\\w+(?:\\.\\w+)*)',
			),
			'Regexp' => array(
				'groups' => array('FilterArg', 'Literal'),
				'regexp' => '(/(?:[^\\\\/]|\\\\.)*/)([Sgimsu]*)',
			),
			'SingleQuotedString' => array(
				'groups' => array('FilterArg', 'Literal', 'Scalar'),
				'regexp' => "'((?:[^\\\\']|\\\\.)*)'",
			),
			'True' => array(
				'groups' => array('FilterArg', 'Literal', 'Scalar'),
				'regexp' => '[Tt][Rr][Uu][Ee]'
			)
		);
	}
	public function parseArray(string $elements = ''): array
	{
		$array = array();
		if ($elements !== '')
			foreach ($this->recurse($elements, 'ArrayElements') as $element)
				if (\array_key_exists('key', $element))
					$array[$element['key']] = $element['value'];
				else
					$array[] = $element['value'];
		return $array;
	}
	public function parseArrayElement(string $key, string $value): array
	{
		$element = array('value' => $this->recurse($value, 'Literal'));
		if ($key !== '')
			$element['key'] = $this->recurse($key, 'Scalar');
		return $element;
	}
	public function parseArrayElements(string $firstElement, string $otherElements = \null)
	{
		$elements = array($this->recurse($firstElement, 'ArrayElement'));
		if (isset($otherElements))
			$elements = \array_merge($elements, $this->recurse($otherElements, 'ArrayElements'));
		return $elements;
	}
	public function parseDoubleQuotedString(string $str): string
	{
		return \stripcslashes($str);
	}
	public function parseFalse(): bool
	{
		return \false;
	}
	public function parseFilterCallback(string $callback, string $args = \null): array
	{
		$config = array('filter' => $callback);
		if (isset($args))
			$config['params'] = ($args === '') ? array() : $this->recurse($args, 'FilterArgs');
		return $config;
	}
	public function parseFilterArgs(string $firstArg, string $otherArgs = \null)
	{
		$parsedArg = $this->parser->parse($firstArg, 'FilterArg');
		$type = ($parsedArg['match'] === 'Param') ? 'Name' : 'Value';
		$args = array(array($type, $parsedArg['value']));
		if (isset($otherArgs))
			$args = \array_merge($args, $this->recurse($otherArgs, 'FilterArgs'));
		return $args;
	}
	public function parseNull()
	{
		return \null;
	}
	public function parseFloat(string $str): float
	{
		return (float) $str;
	}
	public function parseInteger(string $str): int
	{
		return \intval($str, 0);
	}
	public function parseParam(string $str): string
	{
		return $str;
	}
	public function parseRegexp(string $regexp, string $flags): Regexp
	{
		$regexp .= \str_replace('g', '', $flags);
		return new Regexp($regexp, \true);
	}
	public function parseSingleQuotedString(string $str): string
	{
		return \preg_replace("(\\\\([\\\\']))", '$1', $str);
	}
	public function parseTrue(): bool
	{
		return \true;
	}
}