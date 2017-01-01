<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class IgnoreTagsInCode implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateInspector $src)
	{
		$xpath = new DOMXPath($src->getDOM());

		if ($xpath->evaluate('count(//code//xsl:apply-templates)'))
		{
			return ['ignoreTags' => true];
		}

		return [];
	}
}