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
			'BaseDefinition'       => '((?&Rule)|(?&TagAttribute)|(?&TagOption))',
			'BaseDefinitions'      => '((?&BaseDefinition)) ((?&BaseDefinition)*)',
			'BBCodeDefinition'     => '((?&StartTag)) (?:((?&Content)) ((?&EndTag)))?',
			'BBCodeName'           => '(\\*|\\w[-\\w]*)',
			'CommaSeparatedValues' => '(\\w+(?:,\\w+)*)',
			'Content'              => '((?&Tokens)?)',
			'EndTag'               => '\\[/((?&BBCodeName))\\]',
			'FilterValue'          => '((?&ArrayValue)|(?&UnquotedString))',
			'Junk'                 => '(?:[^\\s\\{]|(?=\\{)(?!(?&Token))\\{)*+',
			'Rule'                 => '#((?&RuleName))=((?&RuleValue))',
			'RuleName'             => '\\w+',
			'RuleValue'            => '((?&False)|(?&True)|(?&CommaSeparatedValues))',
			'StartTag'             => '\\[((?&BBCodeName)) ((?&BaseDefinitions)?) (/?)\\]',
			'TagAttribute'         => '((?&TagAttributeName))=((?&Tokens))',
			'TagAttributeName'     => '\\w[-\\w]*',
			'TagOption'            => '((?&TagOptionName))=((?&TagOptionValue))',
			'TagOptionName'        => '\\$[a-z]\\w*',
			'TagOptionValue'       => '((?&ArrayValue)|(?&UnquotedString))',
			'Token'                => '\\{((?&TokenName))(\\?)?(?:=((?&FilterValue)))?(?: ;((?&TokenOptions)))?\\}',
			'TokenName'            => '[A-Z]+[0-9]*',
			'TokenOption'          => '((?&TokenOptionName))(?:=((?&TokenOptionValue)))?',
			'TokenOptionName'      => '\\w+',
			'TokenOptionValue'     => '((?&ArrayValue)|(?&UnquotedString))',
			'TokenOptions'         => '((?&TokenOption))(?:; ((?&TokenOptions)))?',
			'Tokens'               => '((?&Junk))((?&Token))((?&Junk))((?&Tokens))?',
			'UnquotedString'       => '[^\\s;\\]{}]*'
		];
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