<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

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
			'BBCodeName'           => '\\*|\\w[-\\w]*',
			'BBCodeStartTag'       => '\\[((?&BBCodeName)) ((?&BaseDeclarations)?) /?\\]',
			'BaseDeclaration'      => '((?&Rule)|(?&TagAttribute)|(?&TagOption))',
			'BaseDeclarations'     => '((?&BaseDeclaration)) ((?&BaseDeclaration)*)',
			'CommaSeparatedValues' => '(\\w+(?:,\\w+)*)',
			'FilterValue'          => '((?&ArrayValue)|(?&UnquotedString))',
			'Junk'                 => '.*?',
			'MixedContent'         => '((?&Junk))(?:((?&Token))((?&MixedContent)))?',
			'Rule'                 => '#((?&RuleName))=((?&RuleValue))',
			'RuleName'             => '\\w+',
			'RuleValue'            => '((?&False)|(?&True)|(?&CommaSeparatedValues))',
			'TagAttribute'         => '((?&TagAttributeName))=((?&MixedContent))',
			'TagAttributeName'     => '\\w[-\\w]*',
			'TagOption'            => '((?&TagOptionName))=((?&TagOptionValue))',
			'TagOptionName'        => '\\$[a-z]\\w*',
			'TagOptionValue'       => '((?&ArrayValue)|(?&UnquotedString))',
			'Token'                => '\\{((?&TokenId))(\\?)?(?:=((?&FilterValue)))?(?: ;((?&TokenOptions)))?\\}',
			'TokenId'              => '[A-Z]+[0-9]*',
			'TokenOption'          => '((?&TokenOptionName))(?:=((?&TokenOptionValue)))?',
			'TokenOptionName'      => '\\w+',
			'TokenOptionValue'     => '((?&ArrayValue)|(?&UnquotedString))',
			'TokenOptions'         => '((?&TokenOption))(?:; ((?&TokenOptions)))?',
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
			'bbcodeName' => $name,
			'content'    => []
		];
		if ($declarations !== '')
		{
			foreach ($this->recurse($declarations, 'BaseDeclarations') as $declaration)
			{
			}
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
		}
		if ($tokenOptions !== '')
		{
		}

		return $token;
	}
}