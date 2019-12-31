<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;

class BlockElementsFosterFormattingElements implements TargetedRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateTargetedRules(TemplateInspector $src, TemplateInspector $trg)
	{
		return ($src->isBlock() && $src->isPassthrough() && $trg->isFormattingElement()) ? ['fosterParent'] : [];
	}
}