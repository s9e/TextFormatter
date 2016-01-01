<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class AutoReopenFormattingElements implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isFormattingElement()) ? ['autoReopen' => true] : [];
	}
}