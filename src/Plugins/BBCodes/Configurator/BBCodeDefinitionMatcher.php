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
			'Attribute'            => '((?&AttributeName))=((?AttributeDefinition))',
			'AttributeDefinition'  => '((?&Junk))((?&Token))((?&Junk))((?AttributeDefinition)*)',
			'AttributeName'        => '(\\w[-\\w]*)',
			'BBCodeAttribute'      => '((?&Attribute)|(?&Rule)|(?&TagOption))',
			'BBCodeDefinition'     => '((?&StartTag))(?:((?&Content))((?&EndTag)))?',
			'BBCodeName'           => '(\\*|\\w[-\\w]*)',
			'CommaSeparatedValues' => '(\\w+(?:,\\w+)*)',
			'EndTag'               => '\\[/((?&BBCodeName))\\]',
			'Junk'                 => '(?:[^\\s\\{]|(?=\\{)(?!(?&Token))\\{)*+',
			'Rule'                 => '#((?&RuleName))=((?&RuleValue))',
			'RuleName'             => '(\\w+)',
			'RuleValue'            => '((?&False)|(?&True)|(?&CommaSeparatedValues))',
			'StartTag'             => '\\[((?&BBCodeName)) ((?&Attributes)) (/?)\\]',
			'TagOption'            => '((?&TagOptionName))=((?&TagOptionValue))',
			'TagOptionName'        => '$([a-z]\\w*)',
			'TagOptionValue'       => '(?&ArrayValue)|(?&UnquotedString)',
			'Token'                => '\\{((?&TokenName))(?:=((?&FilterOption)))?(?: ;((?&TokenOptions)))?\\}',
			'TokenName'            => '[A-Z]+[0-9]*',
			'TokenOption'          => '((?&OptionName))(?:=((?OptionValue)))?',
			'TokenOptions'         => '((?&TokenOption))(?:; ((?&TokenOptions)))?',
			'UnquotedString'       => '([^\\s;\\]{}]*)'
		];
	];

	/**
	* @param  string   $str
	* @return string[]
	*/
	public function parseCommaSeparatedValues(string $str): array
	{
		return explode(',', $str);
	}
}