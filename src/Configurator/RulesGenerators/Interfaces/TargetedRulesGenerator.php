<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;

use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;

interface TargetedRulesGenerator
{
	/**
	* Generate targeted rules that apply to given template inspector
	*
	* @param  TemplateInspector $src Source template inspector
	* @param  TemplateInspector $trg Target template inspector
	* @return array                  List of rules that apply from the source template to the target
	*/
	public function generateTargetedRules(TemplateInspector $src, TemplateInspector $trg);
}