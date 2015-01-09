<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class IgnoreWhitespaceAroundBlockElements implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isBlock()) ? array('ignoreSurroundingWhitespace' => \true) : array();
	}
}