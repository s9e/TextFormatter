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
			'AttributeDefinition'  => '((?&Junk))((?&Token))',
			'AttributeName'        => '(\\w[-\\w]*)',
			'AttributeOptionName'  => '[a-z]\\w*',
			'AttributeOptionValue' => '(?&ArrayValue)|[^\\s;]*',
			'BBCodeDefinition'     => '((?&StartTag))(?:((?&Content))((?&EndTag)))?',
			'BBCodeName'           => '(\\*|\\w[-\\w]*)',
			'EndTag'               => '\\[/((?&BBCodeName))\\]',
			'Junk'                 => '(?:[^\\s\\{]|(?=\\{)(?!(?&Token))\\{)*+',
			'StartTag'             => '\\[((?&BBCodeName)) (?&Attributes) (/?)\\]',
			'TagOptionName'        => '[$#]?[a-z]\\w*',
			'TagOptionValue'       => '(?&ArrayValue)|[^\\s;]*',
			'Token'                => '\\{((?&TokenName))(?:=((?&TokenOptions)))?\\}',
			'TokenName'            => '[A-Z]+[0-9]*',
			'TokenOption'          => '((?&OptionName))(?:=((?OptionValue)))?',
			'TokenOptions'         => '((?&TokenOption))(?:; ((?&TokenOptions)))?',
		];
	];
}