<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

use RuntimeException;
use s9e\TextFormatter\Configurator\RecursiveParser\AbstractRecursiveMatcher;

class BBCodeDefinitionMatcher extends AbstractRecursiveMatcher
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		return [
			'BBCodeContent'        => [
				'callback' => [$this, 'parseContent'],
				'regexp'   => "([^\\[{]*+(?:(?&Token)(?-1))?)"
			],
			'BBCodeDefinition'     => '((?&BBCodeStartTag)) (?:((?&BBCodeContent)?) ((?&BBCodeEndTag)))?',
			'BBCodeEndTag'         => '\\[/((?&BBCodeName))\\]',
			'BBCodeName'           => '\\*|\\w[-\\w]*+',
			'BBCodeStartTag'       => '\\[((?&BBCodeName))(=(?&TagAttributeValue))? ((?&BaseDeclarations))? /?\\]',
			'BaseDeclarations'     => '((?&BaseDeclaration))(?:\\s++((?&BaseDeclarations)))?',
			'CommaSeparatedValues' => '([-#\\w]++(?:,[-#\\w]++)*)',
			'ContentLiteral'       => '[^{[]*?',
			'LiteralOrUnquoted'    => '((?&Literal)(?![^\\s;\\]}])|(?&UnquotedString))',
			'MixedContent'         => '([^{]*+)(?:((?&Token))((?&MixedContent)))?',
			'Rule'                 => [
				'groups' => ['BaseDeclaration'],
				'regexp' => '#(\\w+)(?:=((?&RuleValue)))?'
			],
			'RuleValue'            => '((?&Literal)(?![^\\s;\\]}])|(?&CommaSeparatedValues))',
			'TagAttribute'         => [
				'groups' => ['BaseDeclaration'],
				'regexp' => '([a-zA-Z][-\\w]*)=((?&TagAttributeValue))'
			],
			'TagAttributeDoubleQuoted' => [
				'callback' => [$this, 'parseContent'],
				'groups'   => ['TagAttributeValue'],
				'regexp'   => '"([^{"]*+(?:(?&Token)(?-1))?)"'
			],
			'TagAttributeSingleQuoted' => [
				'callback' => [$this, 'parseContent'],
				'groups'   => ['TagAttributeValue'],
				'regexp'   => "'([^{']*+(?:(?&Token)(?-1))?)'"
			],
			'TagAttributeUnquoted'     => [
				'callback' => [$this, 'parseContent'],
				'groups'   => ['TagAttributeValue'],
				'regexp'   => '(?!["\'])([^\\s\\]{]*+(?:(?&Token)(?-1))?)'
			],
			'TagFilter'            => [
				'groups' => ['BaseDeclaration'],
				'order'  => -1,
				'regexp' => '\\$filterChain\\.(append|prepend)=((?&FilterCallback))'
			],
			'TagOption'            => [
				'groups' => ['BaseDeclaration'],
				'regexp' => '\\$(\\w+)(?:=((?&LiteralOrUnquoted)))?'
			],
			'Token'                => [
				// PCRE1 complains about infinite loops if this isn't defined before MixedContent
				'order'  => -1,
				'regexp' => '\\{([A-Z]+[0-9]*)(\\?)?(?:=((?&LiteralOrUnquoted)))? (?:; ((?&TokenOptions))?)? \\}'
			],
			'TokenOptionFilter'    => [
				'groups' => ['TokenOption'],
				'order'  => -1,
				'regexp' => 'filterChain\\.(append|prepend)=((?&FilterCallback))'
			],
			'TokenOptionLegacyFilter' => [
				'groups' => ['TokenOption'],
				'order'  => -1,
				'regexp' => '(postFilter|preFilter)=((?&CommaSeparatedValues))'
			],
			'TokenOptionLiteral'   => [
				'groups' => ['TokenOption'],
				'regexp' => '(\\w+)(?:=((?&LiteralOrUnquoted)))?'
			],
			'TokenOptions'         => '((?&TokenOption)) ((?:; (?&TokenOption) )*);?',
			'UnquotedString'       => '[^\\s;\\]}]++',
		];
	}

	/**
	* @param  string $str
	* @return array
	*/
	public function parseBBCodeDefinition(string $start, string $content = ''): array
	{
		$definition            = $this->recurse($start, 'BBCodeStartTag');
		$definition['content'] = ($content === '') ? [] : $this->parseContent($content);

		return $definition;
	}

	/**
	* @param  string $str
	* @return array
	*/
	public function parseBBCodeStartTag(string $name, string $defaultAttribute = '', string $declarations = '', string $slash = ''): array
	{
		if ($defaultAttribute !== '')
		{
			$declarations = trim($name . $defaultAttribute . ' ' . $declarations);
		}

		$definition = [
			'bbcodeName' => BBCode::normalizeName($name),
			'content'    => []
		];
		if ($declarations !== '')
		{
			$definition += $this->recurse($declarations, 'BaseDeclarations');
		}

		return $definition;
	}

	/**
	* @param  string $declaration
	* @param  string $more
	* @return array
	*/
	public function parseBaseDeclarations(string $declaration, string $more = ''): array
	{
		$definition = $this->recurse($declaration, 'BaseDeclaration');
		if ($more !== '')
		{
			$definition = array_merge_recursive($definition, $this->recurse($more, 'BaseDeclarations'));
		}

		return $definition;
	}

	/**
	* @param  string   $str
	* @return string[]
	*/
	public function parseCommaSeparatedValues(string $str): array
	{
		return explode(',', $str);
	}

	/**
	* @param  string $str
	* @return mixed
	*/
	public function parseLiteralOrUnquoted(string $str)
	{
		try
		{
			return $this->recurse($str, 'Literal');
		}
		catch (RuntimeException $e)
		{
			return $str;
		}
	}

	/**
	* @param  string $name
	* @param  string $value
	* @return mixed
	*/
	public function parseRule(string $name, string $value = null)
	{
		if (!isset($value))
		{
			return ['rules' => [['name' => $name]]];
		}

		$rules = [];
		foreach ((array) $this->parseRuleValue($value) as $value)
		{
			$rules[] = ['name' => $name, 'value' => $value];
		}

		return ['rules' => $rules];
	}

	/**
	* @param  string $str
	* @return array|boolean
	*/
	public function parseRuleValue(string $str)
	{
		try
		{
			return $this->recurse($str, 'Literal');
		}
		catch (RuntimeException $e)
		{
			return explode(',', $str);
		}
	}

	/**
	* @param  string $name
	* @param  string $content
	* @return array
	*/
	public function parseTagAttribute(string $name, string $content): array
	{
		return [
			'attributes' => [
				[
					'name'    => $name,
					'content' => $this->recurse($content, 'TagAttributeValue')
				]
			]
		];
	}

	/**
	* @param  string $literal
	* @param  string $token
	* @param  string $remain
	* @return array
	*/
	public function parseMixedContent(string $literal, string $token = '', string $remain = ''): array
	{
		$content = [];
		if ($literal !== '')
		{
			$content[] = $literal;
		}
		if ($token !== '')
		{
			$content[] = $this->recurse($token, 'Token');
			if ($remain !== '')
			{
				$content = array_merge($content, $this->recurse($remain, 'MixedContent'));
			}
		}

		return $content;
	}

	/**
	* @param  string $content
	* @return array
	*/
	public function parseContent(string $content): array
	{
		return $this->recurse($content, 'MixedContent');
	}

	/**
	* @param  string $mode
	* @param  string $filter
	* @return array
	*/
	public function parseTagFilter(string $mode, string $filter): array
	{
		return ['filterChain' => [['mode' => $mode, 'filter' => $filter]]];
	}

	/**
	* @param  string $name
	* @param  string $value
	* @return array
	*/
	public function parseTagOption(string $name, string $value = null)
	{
		$option = ['name' => $name];
		if (isset($value))
		{
			$option['value'] = $this->parseLiteralOrUnquoted($value);
		}

		return ['options' => [$option]];
	}

	/**
	* @param  string $tokenId
	* @param  string $optional
	* @param  string $filterValue
	* @param  string $options
	* @return array
	*/
	public function parseToken(string $tokenId, string $optional = '', string $filterValue = '', string $options = ''): array
	{
		$token = ['id' => $tokenId];
		if ($optional !== '')
		{
			$options = 'required=false;' . $options;
		}
		if ($filterValue !== '')
		{
			$token['filterValue'] = $this->parseLiteralOrUnquoted($filterValue);
		}

		$options = trim($options, "; \r\n\t");
		if ($options !== '')
		{
			$token['options'] = $this->recurse($options, 'TokenOptions');
		}

		return $token;
	}

	/**
	* @param  string $mode   Either 'append' or 'prepend'
	* @param  string $filter Short-syntax filter, e.g. '#int' or 'strtolower($attrValue)'
	* @return array          Array of options
	*/
	public function parseTokenOptionFilter(string $mode, string $filter): array
	{
		return [['name' => 'filterChain.' . $mode, 'value' => $filter]];
	}

	/**
	* @param  string $name
	* @param  string $values
	* @return array
	*/
	public function parseTokenOptionLegacyFilter(string $name, string $values): array
	{
		$name    = ($name === 'preFilter') ? 'filterChain.prepend' : 'filterChain.append';
		$options = [];
		foreach (explode(',', $values) as $value)
		{
			$options[] = ['name' => $name, 'value' => $value];
		}

		return $options;
	}

	/**
	* @param  string $name
	* @param  string $value
	* @return array
	*/
	public function parseTokenOptionLiteral(string $name, string $value = ''): array
	{
		$option  = ['name' => $name];
		if ($value !== '')
		{
			$option['value'] = $this->parseLiteralOrUnquoted($value);
		}

		return [$option];
	}

	/**
	* @param  string $option
	* @param  string $more
	* @return array
	*/
	public function parseTokenOptions(string $option, string $more = ''): array
	{
		$more    = trim($more, "; \r\n\t");
		$options = $this->recurse($option, 'TokenOption');
		if ($more !== '')
		{
			$options = array_merge($options, $this->recurse($more, 'TokenOptions'));
		}

		return $options;
	}
}