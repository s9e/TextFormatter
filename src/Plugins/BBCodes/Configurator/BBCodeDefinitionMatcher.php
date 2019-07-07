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
			'BBCodeDefinition'     => '((?&BBCodeStartTag)) (?:((?&MixedContent)?) ((?&BBCodeEndTag)))?',
			'BBCodeEndTag'         => '\\[/((?&BBCodeName))\\]',
			'BBCodeName'           => '\\*|\\w[-\\w]*+',
			'BBCodeStartTag'       => '\\[((?&BBCodeName)) ((?&BaseDeclarations))? /?\\]',
			'BaseDeclarations'     => '((?&BaseDeclaration)) ((?&BaseDeclarations))?',
			'CommaSeparatedValues' => '(\\w+(?:,\\w+)*)',
			'Junk'                 => '.*?',
			'MixedContent'         => '((?&Junk))(?:((?&Token))((?&MixedContent)))?',
			'Rule'                 => [
				'groups' => ['BaseDeclaration'],
				'regexp' => '#(\\w+)(?:=((?&RuleValue)))?'
			],
			'RuleValue'            => '((?&Literal)|(?&CommaSeparatedValues))',
			'TagAttribute'         => [
				'groups' => ['BaseDeclaration'],
				'regexp' => '([a-zA-Z][-\\w]*)=((?&MixedContent))'
			],
			'TagFilter'            => [
				'groups' => ['BaseDeclaration'],
				'order'  => -1,
				'regexp' => '\\$filterChain\\.(append|prepend)=((?&FilterCallback))'
			],
			'TagOption'            => [
				'groups' => ['BaseDeclaration'],
				'regexp' => '\\$(\\w+)(?:=((?&UnquotedLiteral)))?'
			],
			'Token'                => '\\{((?&TokenId))(\\?)?(?:=((?&UnquotedLiteral)))?(?: ; ((?&TokenOptions)))?\\}',
			'TokenId'              => '[A-Z]+[0-9]*',
			'TokenOptions'         => '(\\w+)(?:=((?&UnquotedLiteral)))?(?:; ((?&TokenOptions)))? ;?',
			'UnquotedLiteral'      => '((?&Literal)|(?&UnquotedString))',
			'UnquotedString'       => '[^\\s;\\]{}]*'
		];
	}

	/**
	* @param  string $str
	* @return array
	*/
	public function parseBBCodeDefinition(string $start, string $content = ''): array
	{
		$definition            = $this->recurse($start, 'BBCodeStartTag');
		$definition['content'] = ($content === '') ? [] : $this->recurse($content, 'MixedContent');

		return $definition;
	}

	/**
	* @param  string $str
	* @return array
	*/
	public function parseBBCodeStartTag(string $name, string $declarations = '', string $slash = ''): array
	{
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
	* @param  string $junk
	* @param  string $token
	* @param  string $more
	* @return array
	*/
	public function parseMixedContent(string $junk, string $token = null, string $more = null): array
	{
		$content = [];
		if ($junk !== '')
		{
			$content[] = $junk;
		}
		if (isset($token))
		{
			$content[] = $this->recurse($token, 'Token');
			if (isset($more))
			{
				$content = array_merge($content, $this->recurse($more, 'MixedContent'));
			}
		}

		return $content;
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
	public function parseTagAttribute(string $name, string $content)
	{
		// Remove quotes around the attribute's content
		if (preg_match('(^(?:"[^"]++"|\'[^\']++\')$)', $content))
		{
			$content = substr($content, 1, -1);
		}

		return [
			'attributes' => [
				[
					'name'    => $name,
					'content' => $this->recurse($content, 'MixedContent')
				]
			]
		];
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
			$option['value'] = $this->parseUnquotedLiteral($value);
		}

		return ['options' => [$option]];
	}

	/**
	* @param  string $tokenId
	* @param  string $optional
	* @param  string $filterValue
	* @param  string $tokenOptions
	* @return array
	*/
	public function parseToken(string $tokenId, string $optional = '', string $filterValue = '', string $tokenOptions = ''): array
	{
		$token = ['id' => $tokenId];
		if ($optional !== '')
		{
			$tokenOptions = 'required=false;' . $tokenOptions;
		}
		if ($filterValue !== '')
		{
			$token['filterValue'] = $this->parseUnquotedLiteral($filterValue);
		}
		if ($tokenOptions !== '')
		{
			$token['options'] = $this->recurse($tokenOptions, 'TokenOptions');
		}

		return $token;
	}

	/**
	* @param  string $name
	* @param  string $value
	* @param  string $more
	* @return array
	*/
	public function parseTokenOptions(string $name, string $value = '', string $more = ''): array
	{
		$option  = ['name' => $name];
		if ($value !== '')
		{
			$option['value'] = $this->parseUnquotedLiteral($value);
		}

		$options = [$option];
		if ($more !== '')
		{
			$options = array_merge($options, $this->recurse($more, 'TokenOptions'));
		}

		return $options;
	}

	/**
	* @param  string $str
	* @return mixed
	*/
	public function parseUnquotedLiteral(string $str)
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
}