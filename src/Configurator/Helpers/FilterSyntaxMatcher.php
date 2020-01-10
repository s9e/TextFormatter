<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\RecursiveParser\AbstractRecursiveMatcher;

class FilterSyntaxMatcher extends AbstractRecursiveMatcher
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		return [
			'Array' => [
				'groups' => ['FilterArg', 'Literal'],
				'regexp' => '\\[ ((?&ArrayElements))? \\]',
			],
			'ArrayElement' => [
				'regexp' => '(?:((?&Scalar)) => )?((?&Literal))',
			],
			'ArrayElements' => [
				'regexp' => '((?&ArrayElement))(?: , ((?&ArrayElements)))?',
			],
			'DoubleQuotedString' => [
				'groups' => ['FilterArg', 'Literal', 'Scalar'],
				'regexp' => '"((?:[^\\\\"]|\\\\.)*)"',
			],
			'False' => [
				'groups' => ['FilterArg', 'Literal', 'Scalar'],
				'regexp' => '[Ff][Aa][Ll][Ss][Ee]',
			],
			'FilterArgs' => [
				'regexp' => '((?&FilterArg))(?: , ((?&FilterArgs)))?',
			],
			'FilterCallback' => [
				'regexp' => '([#:\\\\\\w]+)(?: \\( ((?&FilterArgs)?) \\))?',
			],
			'Float' => [
				'groups' => ['FilterArg', 'Literal', 'Scalar'],
				'regexp' => '([-+]?(?:\\.[0-9]+(?:_[0-9]+)*|[0-9]+(?:_[0-9]+)*\\.(?!_)[0-9]*(?:_[0-9]+)*|[0-9]+(?:_[0-9]+)*(?=[Ee]))(?:[Ee]-?[0-9]+(?:_[0-9]+)*)?)',
			],
			'Integer' => [
				'groups' => ['FilterArg', 'Literal', 'Scalar'],
				'regexp' => '(-?(?:0[Bb][01]+(?:_[01]+)*|0[Xx][0-9A-Fa-f]+(?:_[0-9A-Fa-f]+)*|[0-9]+(?:_[0-9]+)*))',
			],
			'Null' => [
				'groups' => ['FilterArg', 'Literal', 'Scalar'],
				'regexp' => '[Nn][Uu][Ll][Ll]',
			],
			'Param' => [
				'groups' => ['FilterArg'],
				'regexp' => '\\$(\\w+(?:\\.\\w+)*)',
			],
			'Regexp' => [
				'groups' => ['FilterArg', 'Literal'],
				'regexp' => '(/(?:[^\\\\/]|\\\\.)*/)([Sgimsu]*)',
			],
			'SingleQuotedString' => [
				'groups' => ['FilterArg', 'Literal', 'Scalar'],
				'regexp' => "'((?:[^\\\\']|\\\\.)*)'",
			],
			'True' => [
				'groups' => ['FilterArg', 'Literal', 'Scalar'],
				'regexp' => '[Tt][Rr][Uu][Ee]'
			]
		];
	}

	/**
	* @param  string $elements
	* @return array
	*/
	public function parseArray(string $elements = ''): array
	{
		$array = [];
		if ($elements !== '')
		{
			foreach ($this->recurse($elements, 'ArrayElements') as $element)
			{
				if (array_key_exists('key', $element))
				{
					$array[$element['key']] = $element['value'];
				}
				else
				{
					$array[] = $element['value'];
				}
			}
		}

		return $array;
	}

	/**
	* @param  string $key
	* @param  string $value
	* @return array
	*/
	public function parseArrayElement(string $key, string $value): array
	{
		$element = ['value' => $this->recurse($value, 'Literal')];
		if ($key !== '')
		{
			$element['key'] = $this->recurse($key, 'Scalar');
		}

		return $element;
	}

	/**
	* @param  string $firstElement
	* @param  string $otherElements
	* @return array
	*/
	public function parseArrayElements(string $firstElement, string $otherElements = null)
	{
		$elements = [$this->recurse($firstElement, 'ArrayElement')];
		if (isset($otherElements))
		{
			$elements = array_merge($elements, $this->recurse($otherElements, 'ArrayElements'));
		}

		return $elements;
	}

	/**
	* @param  string $str
	* @return string
	*/
	public function parseDoubleQuotedString(string $str): string
	{
		/**
		* @link https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.double
		*/
		return preg_replace_callback(
			'(\\\\([nrtvef\\\\$"]|[0-7]{1,3}|x[0-9A-Fa-f]{1,2}|u\\{[0-9A-Fa-f]+\\}))',
			function ($m)
			{
				if ($m[1] === 'e')
				{
					return "\e";
				}
				if ($m[1][0] === 'u')
				{
					return html_entity_decode('&#x' . substr($m[1], 2, -1) . ';', ENT_QUOTES, 'utf-8');
				}

				return stripcslashes($m[0]);
			},
			$str
		);
	}

	/**
	* @return bool
	*/
	public function parseFalse(): bool
	{
		return false;
	}

	/**
	* @param  string $callback
	* @param  string $args
	* @return array
	*/
	public function parseFilterCallback(string $callback, string $args = null): array
	{
		$config = ['filter' => $callback];
		if (isset($args))
		{
			$config['params'] = ($args === '') ? [] : $this->recurse($args, 'FilterArgs');
		}

		return $config;
	}

	/**
	* @param  string $firstArg
	* @param  string $otherArgs
	* @return array
	*/
	public function parseFilterArgs(string $firstArg, string $otherArgs = null)
	{
		$parsedArg = $this->parser->parse($firstArg, 'FilterArg');

		$type = ($parsedArg['match'] === 'Param') ? 'Name' : 'Value';
		$args = [[$type, $parsedArg['value']]];
		if (isset($otherArgs))
		{
			$args = array_merge($args, $this->recurse($otherArgs, 'FilterArgs'));
		}

		return $args;
	}

	/**
	* @return null
	*/
	public function parseNull()
	{
		return null;
	}

	/**
	* @param  string $str
	* @return float
	*/
	public function parseFloat(string $str): float
	{
		return (float) str_replace('_', '', $str);
	}

	/**
	* @param  string $str
	* @return integer
	*/
	public function parseInteger(string $str): int
	{
		return intval(str_replace('_', '', $str), 0);
	}

	/**
	* @param  string $str
	* @return string
	*/
	public function parseParam(string $str): string
	{
		return $str;
	}

	/**
	* @param  string $regexp
	* @param  string $flags
	* @return Regexp
	*/
	public function parseRegexp(string $regexp, string $flags): Regexp
	{
		$regexp .= str_replace('g', '', $flags);

		return new Regexp($regexp, true);
	}

	/**
	* @param  string $str
	* @return string
	*/
	public function parseSingleQuotedString(string $str): string
	{
		return preg_replace("(\\\\([\\\\']))", '$1', $str);
	}

	/**
	* @return bool
	*/
	public function parseTrue(): bool
	{
		return true;
	}
}