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
			'BBCodeDefinition'     => '((?&BBCodeStartTag)) (?:((?&Content)?) ((?&BBCodeEndTag)))?',
			'BBCodeEndTag'         => '\\[/((?&BBCodeName))\\]',
			'BBCodeName'           => '\\*|\\w[-\\w]*',
			'BBCodeStartTag'       => '\\[((?&BBCodeName)) ((?&BaseDeclarations)?) /?\\]',
			'BaseDeclaration'      => '((?&Rule)|(?&TagAttribute)|(?&TagOption))',
			'BaseDeclarations'     => '((?&BaseDeclaration)) ((?&BaseDeclaration)*)',
			'CommaSeparatedValues' => '(\\w+(?:,\\w+)*)',
			'Content'              => '((?&Tokens)?)',
			'FilterValue'          => '((?&ArrayValue)|(?&UnquotedString))',
			'Junk'                 => '(?:[^\\{]|(?=\\{)(?!(?&Token))\\{)*?',
			'Rule'                 => '#((?&RuleName))=((?&RuleValue))',
			'RuleName'             => '\\w+',
			'RuleValue'            => '((?&False)|(?&True)|(?&CommaSeparatedValues))',
			'TagAttribute'         => '((?&TagAttributeName))=((?&Tokens))',
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
			'Tokens'               => '((?&Junk))((?&Token))((?&Junk))((?&Tokens))?',
			'UnquotedString'       => '[^\\s;\\]{}]*'
		];
	}

	/**
	* @param  string $str
	* @return array
	*/
	public function parseBBCodeDefinition(string $start, string $content = ''): array
	{
		$definition = $this->recurse($start, 'BBCodeStartTag');

		return $definition;
	}

	/**
	* @param  string $str
	* @return array
	*/
	public function parseBBCodeStartTag(string $name, string $declarations = '', string $slash = ''): array
	{
		$definition = ['bbcodeName' => $name];
		if ($declarations !== '')
		{
			foreach ($this->recurse($declarations, 'BaseDeclarations') as $declaration)
			{
			}
		}
		var_dump(func_get_args());

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
}